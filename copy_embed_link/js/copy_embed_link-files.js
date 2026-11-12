/**
 * Copy Embed Link - direct embed links for Nextcloud files.
 *
 * @copyright Copyright (c) 2026 Pulgarin93
 * @license AGPL-3.0-or-later
 *
 * Files integration: a "Copy embed link" action in the file context
 * menu and a section in the Sharing sidebar tab. Both integrations
 * are feature-detected and degrade silently when the host API is not
 * there — the app keeps working through its own panel either way.
 */
(function () {
	'use strict';

	var APP_ID = 'copy_embed_link';
	var t = (typeof window.t === 'function')
		? window.t
		: function (app, text) { return text; };

	function notify(message) {
		if (window.OC && OC.Notification && typeof OC.Notification.showTemporary === 'function') {
			OC.Notification.showTemporary(message);
		} else if (typeof console !== 'undefined') {
			console.log('[Copy Embed Link] ' + message);
		}
	}

	function copyToClipboard(text) {
		if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
			return navigator.clipboard.writeText(text);
		}
		return new Promise(function (resolve, reject) {
			var area = document.createElement('textarea');
			area.value = text;
			area.style.position = 'fixed';
			area.style.opacity = '0';
			document.body.appendChild(area);
			area.select();
			try {
				document.execCommand('copy');
				resolve();
			} catch (e) {
				reject(e);
			} finally {
				document.body.removeChild(area);
			}
		});
	}

	function apiCreateLink(fileId, confirmed) {
		return fetch(OC.generateUrl('/apps/' + APP_ID + '/api/links'), {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'requesttoken': OC.requestToken
			},
			body: JSON.stringify({ fileId: fileId, confirmed: !!confirmed })
		}).then(function (response) { return response.json(); });
	}

	function successMessage(link) {
		if (link.kind === 'embed') {
			return t(APP_ID, 'Embed link copied. Paste it where you want the file to play or show.');
		}
		return t(APP_ID, 'Share link copied. This file type cannot be embedded, so the link opens it for viewing or download.');
	}

	function finishWithLink(data) {
		if (!data || !data.link || !data.link.url) {
			notify(t(APP_ID, 'The link could not be created.'));
			return;
		}
		copyToClipboard(data.link.url).then(function () {
			notify(successMessage(data.link));
		}, function () {
			notify(data.link.url);
		});
	}

	/**
	 * The privacy guard: the API answers needsConfirm when the file has
	 * no shares at all (it lives behind a private folder). Only after an
	 * explicit "yes" do we call again with confirmed=true.
	 */
	function createLink(fileId) {
		apiCreateLink(fileId, false).then(function (data) {
			if (data && data.needsConfirm) {
				askFirst(fileId);
				return;
			}
			finishWithLink(data);
		}).catch(function () {
			notify(t(APP_ID, 'The link could not be created.'));
		});
	}

	function askFirst(fileId) {
		// Exact wording agreed for the app (see the project spec).
		var question = 'This folder is private. Share just this file?';
		var retry = function () {
			apiCreateLink(fileId, true).then(finishWithLink).catch(function () {
				notify(t(APP_ID, 'The link could not be created.'));
			});
		};
		if (window.OC && OC.dialogs && typeof OC.dialogs.confirm === 'function') {
			OC.dialogs.confirm(
				question,
				t(APP_ID, 'Copy embed link'),
				function (confirmed) { if (confirmed) { retry(); } },
				true
			);
		} else if (typeof window.confirm === 'function') {
			if (window.confirm(question)) {
				retry();
			}
		}
	}

	// ------------------------------------------------------------------
	// Context-menu action (legacy Files fileActions API).
	//
	// TODO (verify on Nextcloud 33): the Vue-based Files app still ships
	// the legacy OCA.Files.fileActions bridge for the context menu, but
	// if it is ever removed this block simply never runs and the modern
	// registration (via @nextcloud/files file actions) has to be added
	// with a build step. The panel (app navigation) works regardless.
	// ------------------------------------------------------------------
	function fileIdFromContext(context, fileName) {
		try {
			if (context && context.fileInfoModel && typeof context.fileInfoModel.get === 'function') {
				var id = context.fileInfoModel.get('id');
				if (id) { return parseInt(id, 10); }
			}
			if (context && context.fileList && typeof context.fileList.findFile === 'function') {
				var found = context.fileList.findFile(fileName);
				if (found && found.id) { return parseInt(found.id, 10); }
			}
			if (context && context.$file && typeof context.$file.attr === 'function') {
				var attr = context.$file.attr('data-id');
				if (attr) { return parseInt(attr, 10); }
			}
		} catch (e) { /* fall through */ }
		return null;
	}

	if (window.OCA && OCA.Files && OCA.Files.fileActions
		&& typeof OCA.Files.fileActions.registerAction === 'function') {
		OCA.Files.fileActions.registerAction({
			name: 'copy-embed-link',
			displayName: t(APP_ID, 'Copy embed link'),
			mime: 'all',
			permissions: OC.PERMISSION_READ,
			iconClass: 'icon-link',
			actionHandler: function (fileName, context) {
				var fileId = fileIdFromContext(context, fileName);
				if (fileId) {
					createLink(fileId);
				} else {
					notify(t(APP_ID, 'Could not find that file.'));
				}
			}
		});
	}

	// ------------------------------------------------------------------
	// Sharing sidebar section (legacy ShareTabSections API).
	//
	// TODO (verify on Nextcloud 33): OCA.Sharing.ShareTabSections was
	// the legacy way to add a block to the Sharing tab; when present we
	// render a button that runs the same create flow for the file whose
	// sidebar is open.
	// ------------------------------------------------------------------
	if (window.OCA && OCA.Sharing && OCA.Sharing.ShareTabSections
		&& typeof OCA.Sharing.ShareTabSections.registerSection === 'function') {
		OCA.Sharing.ShareTabSections.registerSection(function (el, fileInfo) {
			if (!fileInfo || !fileInfo.id) {
				return;
			}
			var section = document.createElement('div');
			section.className = 'cel-sharing-section';

			var heading = document.createElement('h3');
			heading.textContent = t(APP_ID, 'Embed link');
			section.appendChild(heading);

			var hint = document.createElement('p');
			hint.textContent = t(APP_ID, 'A direct link that ends in the file name, ready to paste into other apps.');
			section.appendChild(hint);

			var button = document.createElement('button');
			button.type = 'button';
			button.textContent = t(APP_ID, 'Copy embed link');
			button.addEventListener('click', function () {
				createLink(parseInt(fileInfo.id, 10));
			});
			section.appendChild(button);

			el.appendChild(section);
		});
	}
})();
