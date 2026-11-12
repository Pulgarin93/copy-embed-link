<?php

declare(strict_types=1);

/**
 * Copy Embed Link - direct embed links for Nextcloud files.
 *
 * @copyright Copyright (c) 2026 Pulgarin93
 * @license AGPL-3.0-or-later
 */

namespace OCA\CopyEmbedLink\Controller;

use OCA\CopyEmbedLink\AppInfo\Application;
use OCA\CopyEmbedLink\Db\EmbedLink;
use OCA\CopyEmbedLink\Service\EmbedLinkService;
use OCA\CopyEmbedLink\Service\FileKind;
use OCP\AppFramework\ApiController as BaseApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * The owner's JSON API, used by the Files integration script and by
 * the app's own management panel. Every endpoint requires a session;
 * links are always scoped to the user who owns them.
 */
class ApiController extends BaseApiController {
	private ?string $userId;

	public function __construct(
		IRequest $request,
		IUserSession $userSession,
		private EmbedLinkService $service,
	) {
		parent::__construct(Application::APP_ID, $request);
		$user = $userSession->getUser();
		$this->userId = $user !== null ? $user->getUID() : null;
	}

	/**
	 * POST /api/links — create a link for a file or folder.
	 *
	 * The "ask first" guard: when the node has no shares of any kind
	 * (it is only reachable by its owner, typically inside a private
	 * folder), creating a public link crosses that privacy border, so
	 * the API answers {"needsConfirm": true} and creates NOTHING until
	 * the client repeats the call with confirmed=true.
	 */
	public function create(): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse(['error' => 'Not logged in'], Http::STATUS_UNAUTHORIZED);
		}
		$fileId = (int)$this->request->getParam('fileId', 0);
		$node = $fileId > 0 ? $this->service->resolveNode($this->userId, $fileId) : null;
		if ($node === null) {
			return new JSONResponse(['error' => 'File not found'], Http::STATUS_NOT_FOUND);
		}

		$confirmed = filter_var($this->request->getParam('confirmed', false), FILTER_VALIDATE_BOOLEAN);
		if (!$confirmed && $this->service->countExistingShares($this->userId, $node) === 0) {
			return new JSONResponse(['needsConfirm' => true]);
		}

		$kind = $this->request->getParam('kind');
		$password = $this->request->getParam('password');
		$expiresParam = $this->request->getParam('expiresAt');
		$expiresAt = ($expiresParam !== null && $expiresParam !== '' && (int)$expiresParam > 0)
			? (int)$expiresParam
			: null;
		$allowDownload = filter_var($this->request->getParam('allowDownload', true), FILTER_VALIDATE_BOOLEAN);
		$allowedParam = $this->request->getParam('allowedIds');
		$allowedIds = is_array($allowedParam) ? array_map('intval', $allowedParam) : null;

		$link = $this->service->create(
			$this->userId,
			$node,
			is_string($kind) ? $kind : null,
			is_string($password) ? $password : null,
			$expiresAt,
			$allowDownload,
			$allowedIds
		);

		return new JSONResponse(['link' => $this->linkToArray($link)], Http::STATUS_CREATED);
	}

	/** GET /api/links — every link the current user created. */
	public function listAll(): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse(['error' => 'Not logged in'], Http::STATUS_UNAUTHORIZED);
		}
		$links = array_map(
			fn (EmbedLink $link): array => $this->linkToArray($link),
			$this->service->listForOwner($this->userId)
		);
		return new JSONResponse(['links' => $links]);
	}

	/** DELETE /api/links/{id} — revoke one of my links. */
	public function revoke(int $id): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse(['error' => 'Not logged in'], Http::STATUS_UNAUTHORIZED);
		}
		$ok = $this->service->revoke($this->userId, $id);
		return new JSONResponse(
			['revoked' => $ok],
			$ok ? Http::STATUS_OK : Http::STATUS_NOT_FOUND
		);
	}

	/** GET /api/files/{fileId}/links — links I already made for one file. */
	public function linksForFile(int $fileId): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse(['error' => 'Not logged in'], Http::STATUS_UNAUTHORIZED);
		}
		$links = array_map(
			fn (EmbedLink $link): array => $this->linkToArray($link),
			$this->service->listForFile($this->userId, $fileId)
		);
		return new JSONResponse(['links' => $links]);
	}

	/**
	 * GET /api/folders/{fileId}/children — direct children of one of my
	 * folders, for the selection UI of a folder link. Call again with a
	 * subfolder's id to walk deeper.
	 */
	public function folderChildren(int $fileId): JSONResponse {
		if ($this->userId === null) {
			return new JSONResponse(['error' => 'Not logged in'], Http::STATUS_UNAUTHORIZED);
		}
		$node = $this->service->resolveNode($this->userId, $fileId);
		if (!($node instanceof Folder)) {
			return new JSONResponse(['error' => 'Folder not found'], Http::STATUS_NOT_FOUND);
		}
		$children = [];
		foreach ($node->getDirectoryListing() as $child) {
			$isFolder = $child instanceof Folder;
			$mimetype = $child instanceof File ? $child->getMimetype() : null;
			$children[] = [
				'id' => $child->getId(),
				'name' => $child->getName(),
				'isFolder' => $isFolder,
				'mimetype' => $mimetype,
				'size' => (int)$child->getSize(),
				'kind' => $isFolder ? FileKind::SHARE : FileKind::forMimetype($mimetype),
			];
		}
		return new JSONResponse(['children' => $children]);
	}

	/** @return array<string, mixed> */
	private function linkToArray(EmbedLink $link): array {
		$node = $this->service->resolveNode($link->getOwner(), $link->getFileId());
		$data = [
			'id' => $link->getId(),
			'fileId' => $link->getFileId(),
			'token' => $link->getToken(),
			'kind' => $link->getKind(),
			'url' => $this->service->publicUrl($link, $node),
			'hasPassword' => $link->getPasswordHash() !== null,
			'expiresAt' => $link->getExpiresAt(),
			'expired' => $link->isExpired(),
			'allowDownload' => $link->getAllowDownload(),
			'allowedIds' => $link->allowedIdList(),
			'createdAt' => $link->getCreatedAt(),
			'fileName' => $node !== null ? $node->getName() : null,
			'isFolder' => $node instanceof Folder,
			'deleted' => $node === null,
		];
		if ($node instanceof Folder) {
			// Per-child URLs so the panel can copy a file's own link
			// straight out of a folder link.
			$childUrls = [];
			foreach ($node->getDirectoryListing() as $child) {
				if ($child instanceof File) {
					$childUrls[$child->getId()] = $this->service->publicUrlForChild($link, $node, $child);
				}
			}
			$data['childUrls'] = $childUrls;
		}
		return $data;
	}
}
