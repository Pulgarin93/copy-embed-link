<?php

declare(strict_types=1);

/**
 * Copy Embed Link - direct embed links for Nextcloud files.
 *
 * @copyright Copyright (c) 2026 Pulgarin93
 * @license AGPL-3.0-or-later
 */

namespace OCA\CopyEmbedLink\Listener;

use OCA\CopyEmbedLink\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Event\LoadAdditionalScriptsEvent;
use OCP\Util;

/**
 * Loads the Files integration script (context-menu action + sharing
 * sidebar section) whenever the Files app loads its own scripts.
 *
 * @implements IEventListener<LoadAdditionalScriptsEvent>
 */
class LoadFilesScriptsListener implements IEventListener {
	public function handle(Event $event): void {
		if (!($event instanceof LoadAdditionalScriptsEvent)) {
			return;
		}
		Util::addScript(Application::APP_ID, Application::APP_ID . '-files');
	}
}
