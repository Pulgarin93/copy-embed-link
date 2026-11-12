<?php

declare(strict_types=1);

/**
 * Copy Embed Link - direct embed links for Nextcloud files.
 *
 * @copyright Copyright (c) 2026 Pulgarin93
 * @license AGPL-3.0-or-later
 */

namespace OCA\CopyEmbedLink\Service;

use OCA\CopyEmbedLink\Db\EmbedLink;
use OCA\CopyEmbedLink\Db\EmbedLinkMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IURLGenerator;
use OCP\Security\ISecureRandom;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;

class EmbedLinkService {
	public function __construct(
		private EmbedLinkMapper $mapper,
		private IRootFolder $rootFolder,
		private IShareManager $shareManager,
		private ISecureRandom $secureRandom,
		private IURLGenerator $urlGenerator,
	) {
	}

	/** Resolve a node inside the owner's own storage view, by its internal id. */
	public function resolveNode(string $owner, int $fileId): ?Node {
		try {
			$userFolder = $this->rootFolder->getUserFolder($owner);
			$nodes = $userFolder->getById($fileId);
			return $nodes[0] ?? null;
		} catch (\Throwable) {
			return null;
		}
	}

	/** Resolve a child of a folder by relative path ("sub/name.mp3"). */
	public function resolveChild(Folder $folder, string $relativePath): ?Node {
		try {
			$relativePath = trim(str_replace('\\', '/', $relativePath), '/');
			if ($relativePath === '') {
				return $folder;
			}
			return $folder->get($relativePath);
		} catch (NotFoundException) {
			return null;
		} catch (\Throwable) {
			return null;
		}
	}

	/** How many shares of any kind point at this node (0 = only the owner can reach it). */
	public function countExistingShares(string $owner, Node $node): int {
		$types = [
			IShare::TYPE_USER,
			IShare::TYPE_GROUP,
			IShare::TYPE_LINK,
			IShare::TYPE_EMAIL,
			IShare::TYPE_CIRCLE,
			IShare::TYPE_REMOTE,
			IShare::TYPE_GUEST,
		];
		$total = 0;
		foreach ($types as $type) {
			try {
				$shares = $this->shareManager->getSharesBy($owner, $type, $node, false, 1, 0);
				$total += count($shares);
			} catch (\Throwable) {
				// share type unavailable on this instance: ignore it
			}
		}
		return $total;
	}

	/**
	 * Create a link. The caller (API) is responsible for the "ask first"
	 * guard: when the node has no existing shares, creating a public link
	 * must be confirmed by the owner first.
	 */
	public function create(
		string $owner,
		Node $node,
		?string $kind,
		?string $password,
		?int $expiresAt,
		bool $allowDownload,
		?array $allowedIds,
	): EmbedLink {
		if ($kind === null || $kind === '') {
			$kind = ($node instanceof Folder)
				? FileKind::SHARE
				: FileKind::forMimetype($node->getMimetype());
		}

		$link = new EmbedLink();
		$link->setOwner($owner);
		$link->setFileId($node->getId());
		$link->setToken($this->secureRandom->generate(24, ISecureRandom::CHAR_ALPHANUMERIC));
		$link->setKind($kind);
		$link->setPasswordHash(
			($password !== null && $password !== '')
				? password_hash($password, PASSWORD_DEFAULT)
				: null
		);
		$link->setExpiresAt($expiresAt);
		$link->setAllowDownload($allowDownload);
		$link->setAllowedIds(
			($allowedIds !== null && $allowedIds !== [])
				? json_encode(array_values(array_map('intval', $allowedIds)))
				: null
		);
		$link->setCreatedAt(time());

		return $this->mapper->insert($link);
	}

	/** @throws DoesNotExistException */
	public function findByToken(string $token): EmbedLink {
		return $this->mapper->findByToken($token);
	}

	/** @return EmbedLink[] */
	public function listForOwner(string $owner): array {
		return $this->mapper->findAllByOwner($owner);
	}

	/** @return EmbedLink[] */
	public function listForFile(string $owner, int $fileId): array {
		return $this->mapper->findByOwnerAndFile($owner, $fileId);
	}

	public function revoke(string $owner, int $id): bool {
		foreach ($this->mapper->findAllByOwner($owner) as $link) {
			if ($link->getId() === $id) {
				$this->mapper->delete($link);
				return true;
			}
		}
		return false;
	}

	/** The public URL for a link, ending in the target's real current name. */
	public function publicUrl(EmbedLink $link, ?Node $node = null): string {
		$base = $this->urlGenerator->linkToRouteAbsolute(
			'copy_embed_link.public.root',
			['token' => $link->getToken()]
		);
		$node ??= $this->resolveNode($link->getOwner(), $link->getFileId());
		if ($node === null) {
			return $base;
		}
		return $base . '/' . rawurlencode($node->getName());
	}

	/** The public URL for one file inside a folder link. */
	public function publicUrlForChild(EmbedLink $link, Folder $folder, Node $child): string {
		$base = $this->urlGenerator->linkToRouteAbsolute(
			'copy_embed_link.public.root',
			['token' => $link->getToken()]
		);
		$folderPath = rtrim($folder->getPath(), '/') . '/';
		$childPath = $child->getPath();
		$relative = str_starts_with($childPath, $folderPath)
			? substr($childPath, strlen($folderPath))
			: $child->getName();
		$segments = array_map('rawurlencode', explode('/', $relative));
		return $base . '/d/' . implode('/', $segments);
	}

	public function verifyPassword(EmbedLink $link, string $password): bool {
		$hash = $link->getPasswordHash();
		return $hash !== null && password_verify($password, $hash);
	}

	/** Cookie value that proves the visitor already entered this link's password. */
	public function authCookieValue(EmbedLink $link): string {
		return hash_hmac('sha256', $link->getToken(), (string)$link->getPasswordHash());
	}

	public function cookieName(EmbedLink $link): string {
		return 'cel_auth_' . $link->getToken();
	}
}
