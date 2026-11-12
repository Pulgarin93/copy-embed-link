<?php

declare(strict_types=1);

/**
 * Copy Embed Link - direct embed links for Nextcloud files.
 *
 * @copyright Copyright (c) 2026 Pulgarin93
 * @license AGPL-3.0-or-later
 */

return [
	'routes' => [
		// Public serving (no session). The child route must stay above
		// the file route so the literal "d" segment is not swallowed by
		// the {name} placeholder.
		['name' => 'public.root', 'url' => '/f/{token}', 'verb' => 'GET',
			'requirements' => ['token' => '[A-Za-z0-9]+']],
		['name' => 'public.auth', 'url' => '/f/{token}/auth', 'verb' => 'POST',
			'requirements' => ['token' => '[A-Za-z0-9]+']],
		['name' => 'public.child', 'url' => '/f/{token}/d/{path}', 'verb' => 'GET',
			'requirements' => ['token' => '[A-Za-z0-9]+', 'path' => '.+']],
		['name' => 'public.file', 'url' => '/f/{token}/{name}', 'verb' => 'GET',
			'requirements' => ['token' => '[A-Za-z0-9]+']],

		// The management panel (app navigation).
		['name' => 'page.index', 'url' => '/', 'verb' => 'GET'],

		// The owner's JSON API (session required).
		['name' => 'api.create', 'url' => '/api/links', 'verb' => 'POST'],
		['name' => 'api.listAll', 'url' => '/api/links', 'verb' => 'GET'],
		['name' => 'api.revoke', 'url' => '/api/links/{id}', 'verb' => 'DELETE',
			'requirements' => ['id' => '\d+']],
		['name' => 'api.linksForFile', 'url' => '/api/files/{fileId}/links', 'verb' => 'GET',
			'requirements' => ['fileId' => '\d+']],
		['name' => 'api.folderChildren', 'url' => '/api/folders/{fileId}/children', 'verb' => 'GET',
			'requirements' => ['fileId' => '\d+']],
	],
];
