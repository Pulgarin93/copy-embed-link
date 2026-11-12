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
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

/**
 * The app's own management panel ("Embed links" in the app menu):
 * every link the user created, with copy and revoke actions.
 * The panel's data comes from the JSON API (ApiController).
 */
class PageController extends Controller {
	public function __construct(IRequest $request) {
		parent::__construct(Application::APP_ID, $request);
	}

	public function index(): TemplateResponse {
		Util::addScript(Application::APP_ID, Application::APP_ID . '-panel');
		return new TemplateResponse(Application::APP_ID, 'main');
	}
}
