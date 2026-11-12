<?php

declare(strict_types=1);

/**
 * Copy Embed Link - direct embed links for Nextcloud files.
 *
 * @copyright Copyright (c) 2026 Pulgarin93
 * @license AGPL-3.0-or-later
 */

namespace OCA\CopyEmbedLink\Http;

/**
 * A stream wrapper that serves only a byte range of an underlying stream.
 *
 * Media players ask for ranges (HTTP Range header) so the listener can
 * seek inside an audio/video file; without range support the player in
 * the receiving app cannot jump within the track. This wrapper lets the
 * controller answer 206 Partial Content with a plain stream resource.
 */
class RangeStream {
	/** @var array<string, array{stream: mixed, start: int, length: int, size: int}> */
	private static array $registry = [];
	private static bool $registered = false;

	/** @var resource|null */
	public $context;

	/** @var resource|null */
	private $stream = null;
	private int $remaining = 0;
	private int $totalSize = 0;

	/**
	 * Wrap $stream so that reading from the returned URL yields exactly
	 * $length bytes starting at $start. $totalSize is the full file size
	 * (used for stream_stat).
	 *
	 * @param resource $stream an open, seekable stream
	 */
	public static function wrap($stream, int $start, int $length, int $totalSize): string {
		if (!self::$registered) {
			stream_wrapper_register('celrange', self::class);
			self::$registered = true;
		}
		$key = bin2hex(random_bytes(8));
		self::$registry[$key] = [
			'stream' => $stream,
			'start' => $start,
			'length' => $length,
			'size' => $totalSize,
		];
		return 'celrange://' . $key;
	}

	public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool {
		$key = substr($path, strlen('celrange://'));
		if (!isset(self::$registry[$key])) {
			return false;
		}
		$entry = self::$registry[$key];
		unset(self::$registry[$key]);
		$this->stream = $entry['stream'];
		$this->remaining = $entry['length'];
		$this->totalSize = $entry['size'];
		return fseek($this->stream, $entry['start']) === 0;
	}

	public function stream_read(int $count): string {
		if ($this->stream === null || $this->remaining <= 0) {
			return '';
		}
		$data = fread($this->stream, min($count, $this->remaining));
		if ($data === false) {
			return '';
		}
		$this->remaining -= strlen($data);
		return $data;
	}

	public function stream_eof(): bool {
		return $this->remaining <= 0 || $this->stream === null || feof($this->stream);
	}

	/** @return array|false */
	public function stream_stat() {
		$stat = [
			'size' => $this->totalSize,
			'mode' => 0100644,
		];
		return $stat;
	}

	public function stream_close(): void {
		if (is_resource($this->stream)) {
			fclose($this->stream);
		}
		$this->stream = null;
	}
}
