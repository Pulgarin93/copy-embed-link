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
use OCA\CopyEmbedLink\Http\RangeStream;
use OCA\CopyEmbedLink\Service\EmbedLinkService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StreamResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\IRequest;
use OCP\IURLGenerator;

/**
 * Serves the public side of a link: /f/{token}/...
 *
 * The token is the whole credential. The root route redirects to the
 * file URL ending in the file's real current name (what players and
 * embedders need to recognise the media type), children of a folder
 * link are only served when their id is on the link's allowed list,
 * and password-protected links ask for the password once and then
 * carry a signed cookie (see EmbedLinkService::authCookieValue()).
 */
class PublicController extends Controller {
	public function __construct(
		IRequest $request,
		private EmbedLinkService $service,
		private IURLGenerator $urlGenerator,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/** /f/{token} -> redirect to /f/{token}/{real-file-name} */
	#[PublicPage]
	#[NoCSRFRequired]
	public function root(string $token): Response {
		$link = $this->findLink($token);
		if ($link === null) {
			return $this->messageResponse('Link not found', 'This link does not exist or has been revoked.', Http::STATUS_NOT_FOUND);
		}
		$node = $this->service->resolveNode($link->getOwner(), $link->getFileId());
		if ($node === null) {
			return $this->messageResponse('File not found', 'The file behind this link no longer exists.', Http::STATUS_NOT_FOUND);
		}
		return new RedirectResponse($this->service->publicUrl($link, $node));
	}

	/** /f/{token}/{name} -> serve the file the link points at. */
	#[PublicPage]
	#[NoCSRFRequired]
	public function file(string $token, string $name): Response {
		$link = $this->findLink($token);
		if ($link === null) {
			return $this->messageResponse('Link not found', 'This link does not exist or has been revoked.', Http::STATUS_NOT_FOUND);
		}
		$target = $this->urlGenerator->linkToRouteAbsolute(
			'copy_embed_link.public.file',
			['token' => $token, 'name' => $name]
		);
		if (($gate = $this->checkAccess($link, $target)) !== null) {
			return $gate;
		}
		$node = $this->service->resolveNode($link->getOwner(), $link->getFileId());
		if ($node === null) {
			return $this->messageResponse('File not found', 'The file behind this link no longer exists.', Http::STATUS_NOT_FOUND);
		}
		// The {name} segment is cosmetic (it makes the URL end in a real
		// file name for players); the token alone decides what is served,
		// so the link keeps working after the file is renamed.
		return $this->serveNode($link, $node);
	}

	/** /f/{token}/d/{path} -> serve one file inside a folder link. */
	#[PublicPage]
	#[NoCSRFRequired]
	public function child(string $token, string $path): Response {
		$link = $this->findLink($token);
		if ($link === null) {
			return $this->messageResponse('Link not found', 'This link does not exist or has been revoked.', Http::STATUS_NOT_FOUND);
		}
		$target = $this->urlGenerator->linkToRouteAbsolute(
			'copy_embed_link.public.child',
			['token' => $token, 'path' => $path]
		);
		if (($gate = $this->checkAccess($link, $target)) !== null) {
			return $gate;
		}
		$root = $this->service->resolveNode($link->getOwner(), $link->getFileId());
		if (!($root instanceof Folder)) {
			return $this->messageResponse('Not found', 'This link does not point to a folder.', Http::STATUS_NOT_FOUND);
		}
		$child = $this->service->resolveChild($root, $path);
		if ($child === null) {
			return $this->messageResponse('Not found', 'That file is not available through this link.', Http::STATUS_NOT_FOUND);
		}
		// Selection guard: when the owner shared only some files of the
		// folder, the link carries their exact ids; anything else stays
		// invisible (answered as "not found", never as "forbidden").
		$allowed = $link->allowedIdList();
		if ($allowed !== [] && !in_array($child->getId(), $allowed, true)) {
			return $this->messageResponse('Not found', 'That file is not available through this link.', Http::STATUS_NOT_FOUND);
		}
		return $this->serveNode($link, $child);
	}

	/** POST /f/{token}/auth -> check the password and set the auth cookie. */
	#[PublicPage]
	#[NoCSRFRequired]
	public function auth(string $token, string $password = '', string $redirect = ''): Response {
		$link = $this->findLink($token);
		if ($link === null) {
			return $this->messageResponse('Link not found', 'This link does not exist or has been revoked.', Http::STATUS_NOT_FOUND);
		}
		if ($link->isExpired()) {
			return $this->messageResponse('Link expired', 'This link has expired and no longer works.', Http::STATUS_GONE);
		}
		if ($link->getPasswordHash() === null) {
			return new RedirectResponse($this->service->publicUrl($link));
		}
		if ($this->service->verifyPassword($link, $password)) {
			$response = new RedirectResponse($this->safeRedirect($link, $redirect));
			$response->addCookie(
				$this->service->cookieName($link),
				$this->service->authCookieValue($link),
				new \DateTimeImmutable('+30 days')
			);
			return $response;
		}
		return $this->passwordResponse($link, $redirect !== '' ? $redirect : $this->service->publicUrl($link), true);
	}

	private function findLink(string $token): ?EmbedLink {
		try {
			return $this->service->findByToken($token);
		} catch (DoesNotExistException) {
			return null;
		}
	}

	/**
	 * Expiry and password gate shared by the serving routes.
	 * Returns the response to send instead, or null when access is fine.
	 */
	private function checkAccess(EmbedLink $link, string $redirectTarget): ?Response {
		if ($link->isExpired()) {
			return $this->messageResponse('Link expired', 'This link has expired and no longer works.', Http::STATUS_GONE);
		}
		if ($link->getPasswordHash() !== null) {
			$cookie = $this->request->getCookie($this->service->cookieName($link));
			if ($cookie === null || !hash_equals($this->service->authCookieValue($link), $cookie)) {
				return $this->passwordResponse($link, $redirectTarget, false);
			}
		}
		return null;
	}

	private function serveNode(EmbedLink $link, Node $node): Response {
		if ($node instanceof Folder) {
			// Phase 1: a folder link's files are reached through their
			// own /d/ URLs; the folder root itself shows an explanation
			// instead of a listing.
			// TODO: render a simple file listing here for "share" folder
			// links opened in a browser.
			return $this->messageResponse(
				'Folder link',
				'This link points to a folder. Open one of its files with the direct link the owner shared.',
				Http::STATUS_OK
			);
		}
		if (!($node instanceof File)) {
			return $this->messageResponse('Not found', 'That file is not available through this link.', Http::STATUS_NOT_FOUND);
		}
		return $this->serveFile($link, $node);
	}

	private function serveFile(EmbedLink $link, File $file): Response {
		$size = (int)$file->getSize();
		$mimetype = $file->getMimetype();
		if ($mimetype === null || $mimetype === '') {
			$mimetype = 'application/octet-stream';
		}

		// View-only links (allowDownload = false) never honour a
		// download request: the file is always served inline.
		$downloadRequested = $this->request->getParam('download') !== null
			|| $this->request->getParam('dl') !== null;
		$asAttachment = $downloadRequested && $link->getAllowDownload();

		$rangeHeader = $this->request->getHeader('Range');
		$range = $this->parseRangeHeader($rangeHeader, $size);
		if ($range === null && $rangeHeader !== null && !str_contains($rangeHeader, ',')
			&& str_starts_with(strtolower(trim($rangeHeader)), 'bytes=')) {
			// A single range was asked for but cannot be satisfied.
			$response = new Response();
			$response->setStatus(Http::STATUS_REQUESTED_RANGE_NOT_SATISFIABLE);
			$response->addHeader('Content-Range', 'bytes */' . $size);
			return $response;
		}

		if ($range !== null) {
			[$start, $end] = $range;
			$length = $end - $start + 1;
			$stream = $file->fopen('rb');
			if ($stream === false) {
				return $this->messageResponse('Error', 'The file could not be read.', Http::STATUS_INTERNAL_SERVER_ERROR);
			}
			$wrapped = fopen(RangeStream::wrap($stream, $start, $length, $size), 'rb');
			if ($wrapped === false) {
				fclose($stream);
				return $this->messageResponse('Error', 'The file could not be read.', Http::STATUS_INTERNAL_SERVER_ERROR);
			}
			$response = new StreamResponse($wrapped);
			$response->setStatus(Http::STATUS_PARTIAL_CONTENT);
			$response->addHeader('Content-Type', $mimetype);
			$response->addHeader('Content-Length', (string)$length);
			$response->addHeader('Content-Range', 'bytes ' . $start . '-' . $end . '/' . $size);
			$response->addHeader('Accept-Ranges', 'bytes');
		} else {
			$stream = $file->fopen('rb');
			if ($stream === false) {
				return $this->messageResponse('Error', 'The file could not be read.', Http::STATUS_INTERNAL_SERVER_ERROR);
			}
			$response = new StreamResponse($stream);
			$response->addHeader('Content-Type', $mimetype);
			$response->addHeader('Content-Length', (string)$size);
			$response->addHeader('Accept-Ranges', 'bytes');
		}

		$name = $file->getName();
		$disposition = $asAttachment ? 'attachment' : 'inline';
		$response->addHeader(
			'Content-Disposition',
			$disposition . '; filename="' . $this->asciiFilename($name) . '"; filename*=UTF-8\'\'' . rawurlencode($name)
		);
		$response->addHeader('X-Content-Type-Options', 'nosniff');
		return $response;
	}

	/**
	 * Parse a single-range "Range: bytes=..." header.
	 *
	 * @return array{0: int, 1: int}|null [start, end] inclusive, or null
	 *         when there is no usable single range (multi-range headers
	 *         are ignored and the whole file is served instead).
	 */
	private function parseRangeHeader(?string $header, int $size): ?array {
		if ($header === null || $size <= 0) {
			return null;
		}
		$header = trim($header);
		if (str_contains($header, ',')) {
			return null;
		}
		if (!preg_match('/^bytes=(\d*)-(\d*)$/i', $header, $m)) {
			return null;
		}
		if ($m[1] === '' && $m[2] === '') {
			return null;
		}
		if ($m[1] === '') {
			// Suffix range: the last N bytes.
			$length = (int)$m[2];
			if ($length <= 0) {
				return null;
			}
			return [max(0, $size - $length), $size - 1];
		}
		$start = (int)$m[1];
		if ($start >= $size) {
			return null;
		}
		$end = $m[2] === '' ? $size - 1 : min((int)$m[2], $size - 1);
		if ($end < $start) {
			return null;
		}
		return [$start, $end];
	}

	/** Only ever redirect back to this link's own URLs (no open redirect). */
	private function safeRedirect(EmbedLink $link, string $target): string {
		if ($target !== '' && str_contains($target, '/f/' . $link->getToken())) {
			return $target;
		}
		return $this->service->publicUrl($link);
	}

	private function passwordResponse(EmbedLink $link, string $redirectTarget, bool $error): TemplateResponse {
		$response = new TemplateResponse(
			Application::APP_ID,
			'password',
			[
				'action' => $this->urlGenerator->linkToRoute('copy_embed_link.public.auth', ['token' => $link->getToken()]),
				'redirect' => $redirectTarget,
				'error' => $error,
			],
			TemplateResponse::RENDER_AS_GUEST
		);
		$response->setStatus(Http::STATUS_OK);
		return $response;
	}

	private function messageResponse(string $title, string $message, int $status): TemplateResponse {
		$response = new TemplateResponse(
			Application::APP_ID,
			'message',
			['title' => $title, 'message' => $message],
			TemplateResponse::RENDER_AS_GUEST
		);
		$response->setStatus($status);
		return $response;
	}

	/** Safe fallback file name for the legacy filename="..." parameter. */
	private function asciiFilename(string $name): string {
		$ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?? '';
		return $ascii === '' ? 'file' : $ascii;
	}
}
