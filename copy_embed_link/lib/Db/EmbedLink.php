<?php

declare(strict_types=1);

/**
 * Copy Embed Link - direct embed links for Nextcloud files.
 *
 * @copyright Copyright (c) 2026 Pulgarin93
 * @license AGPL-3.0-or-later
 */

namespace OCA\CopyEmbedLink\Db;

use OCP\AppFramework\Db\Entity;

/**
 * One embed/share link created by a user for one file or folder.
 *
 * The link points at the file's internal id ("root link"): it survives
 * moves and renames, and dies only when the file itself is deleted.
 *
 * @method string getOwner()
 * @method void setOwner(string $owner)
 * @method int getFileId()
 * @method void setFileId(int $fileId)
 * @method string getToken()
 * @method void setToken(string $token)
 * @method string getKind()
 * @method void setKind(string $kind)
 * @method ?string getPasswordHash()
 * @method void setPasswordHash(?string $passwordHash)
 * @method ?int getExpiresAt()
 * @method void setExpiresAt(?int $expiresAt)
 * @method bool getAllowDownload()
 * @method void setAllowDownload(bool $allowDownload)
 * @method ?string getAllowedIds()
 * @method void setAllowedIds(?string $allowedIds)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 */
class EmbedLink extends Entity {
	protected string $owner = '';
	protected int $fileId = 0;
	protected string $token = '';
	protected string $kind = 'embed';
	protected ?string $passwordHash = null;
	protected ?int $expiresAt = null;
	protected bool $allowDownload = true;
	protected ?string $allowedIds = null;
	protected int $createdAt = 0;

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('fileId', 'integer');
		$this->addType('expiresAt', 'integer');
		$this->addType('allowDownload', 'boolean');
		$this->addType('createdAt', 'integer');
	}

	/** @return int[] file ids allowed through this link (folder links), empty = all */
	public function allowedIdList(): array {
		if ($this->allowedIds === null || $this->allowedIds === '') {
			return [];
		}
		$decoded = json_decode($this->allowedIds, true);
		if (!is_array($decoded)) {
			return [];
		}
		return array_map('intval', $decoded);
	}

	public function isExpired(): bool {
		return $this->expiresAt !== null && $this->expiresAt > 0 && $this->expiresAt < time();
	}
}
