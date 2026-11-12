<?php

declare(strict_types=1);

/**
 * Copy Embed Link - direct embed links for Nextcloud files.
 *
 * @copyright Copyright (c) 2026 Pulgarin93
 * @license AGPL-3.0-or-later
 */

namespace OCA\CopyEmbedLink\Service;

/**
 * Decides, from a file's real type, which kind of link it can get.
 *
 * Embeddable types (audio, video, images, PDF, plain text) can play or
 * display inside other apps, so they receive an "embed" link. Everything
 * else (zip archives, design/project files such as Adobe formats, office
 * suites' project files, executables...) cannot be played anywhere, so
 * it receives a "share" link for viewing/downloading instead.
 */
class FileKind {
	public const EMBED = 'embed';
	public const SHARE = 'share';

	/** @var string[] mimetypes that render inline even outside the media families */
	private const EMBEDDABLE_EXACT = [
		'application/pdf',
		'text/plain',
		'text/markdown',
		'image/svg+xml',
	];

	public static function forMimetype(?string $mimetype): string {
		if ($mimetype === null || $mimetype === '') {
			return self::SHARE;
		}
		$mimetype = strtolower($mimetype);
		if (str_starts_with($mimetype, 'audio/')
			|| str_starts_with($mimetype, 'video/')
			|| str_starts_with($mimetype, 'image/')) {
			return self::EMBED;
		}
		if (in_array($mimetype, self::EMBEDDABLE_EXACT, true)) {
			return self::EMBED;
		}
		return self::SHARE;
	}

	public static function isEmbeddable(?string $mimetype): bool {
		return self::forMimetype($mimetype) === self::EMBED;
	}
}
