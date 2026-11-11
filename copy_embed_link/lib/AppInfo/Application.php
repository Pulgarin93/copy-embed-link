<?php

declare(strict_types=1);

/**
 * Copy Embed Link - direct embed links for Nextcloud files.
 *
 * @copyright Copyright (c) 2026 Pulgarin93
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 */

namespace OCA\CopyEmbedLink\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public const APP_ID = 'copy_embed_link';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		// TODO: register the share-menu action, the link controller
		// and the settings section.
	}

	public function boot(IBootContext $context): void {
		// TODO: load the Files app integration (button + sidebar section).
	}
}
