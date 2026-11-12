/**
 * Copy Embed Link - direct embed links for Nextcloud files.
 *
 * @copyright Copyright (c) 2026 Pulgarin93
 * @license AGPL-3.0-or-later
 *
 * Management panel: lists the current user's links from the app's
 * JSON API and wires the copy / revoke buttons.
 */
(function () {
	'use strict';

	var APP_ID = 'copy_embed_link';
	var t = (typeof window.t === 'function')
		? window.t
		: function (app, text) { return text; };

	function api(path, options) {
		var opts = options || {};
		opts.credentials = 'same-origin';
		opts.headers = Object.assign({}, opts.headers || {}, { 'requesttoken': OC.requestToken });
		return fetch(OC.generateUrl('/apps/' + APP_ID + path), opts)
			.then(function (response) { return response.json(); });
	}

	function copyToClipboard(text) {
		if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
			return navigator.clipboard.writeText(text);
		}
		return Promise.reject(new Error('clipboard unavailable'));
	}

	function notify(message) {
		if (window.OC && OC.Notification && typeof OC.Notification.showTemporary === 'function') {
			OC.Notification.showTemporary(message);
		}
	}

	function formatExpiry(link) {
		if (link.expiresAt === null || link.expiresAt === undefined) {
			return t(APP_ID, 'Never');
		}
		var date = new Date(link.expiresAt * 1000);
		var text = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
		return link.expired ? text + ' (' + t(APP_ID, 'expired') + ')' : text;
	}

	function buildRow(link) {
		var row = document.createElement('tr');

		var typeCell = document.createElement('td');
		typeCell.textContent = link.kind === 'embed' ? t(APP_ID, 'Embed') : t(APP_ID, 'Share');
		row.appendChild(typeCell);

		var fileCell = document.createElement('td');
		if (link.deleted) {
			fileCell.textContent = t(APP_ID, '(file deleted)');
		} else {
			fileCell.textContent = (link.isFolder ? '📁 ' : '') + (link.fileName || '');
		}
		row.appendChild(fileCell);

		var linkCell = document.createElement('td');
		var anchor = document.createElement('a');
		anchor.href = link.url;
		anchor.target = '_blank';
		anchor.rel = 'noopener noreferrer';
		anchor.textContent = link.url;
		linkCell.appendChild(anchor);
		row.appendChild(linkCell);

		var expiryCell = document.createElement('td');
		expiryCell.textContent = formatExpiry(link);
		row.appendChild(expiryCell);

		var downloadCell = document.createElement('td');
		downloadCell.textContent = link.allowDownload ? t(APP_ID, 'Allowed') : t(APP_ID, 'View only');
		row.appendChild(downloadCell);

		var actionsCell = document.createElement('td');

		var copyButton = document.createElement('button');
		copyButton.type = 'button';
		copyButton.textContent = t(APP_ID, 'Copy');
		copyButton.addEventListener('click', function () {
			copyToClipboard(link.url).then(function () {
				notify(t(APP_ID, 'Link copied.'));
			}, function () {
				notify(link.url);
			});
		});
		actionsCell.appendChild(copyButton);

		var revokeButton = document.createElement('button');
		revokeButton.type = 'button';
		revokeButton.textContent = t(APP_ID, 'Revoke');
		revokeButton.addEventListener('click', function () {
			var question = t(APP_ID, 'Revoke this link? It will stop working everywhere it was pasted.');
			var proceed = function () {
				api('/api/links/' + link.id, { method: 'DELETE' }).then(function () {
					loadLinks();
				});
			};
			if (window.OC && OC.dialogs && typeof OC.dialogs.confirm === 'function') {
				OC.dialogs.confirm(question, t(APP_ID, 'Embed links'), function (ok) { if (ok) { proceed(); } }, true);
			} else if (window.confirm(question)) {
				proceed();
			}
		});
		actionsCell.appendChild(revokeButton);

		row.appendChild(actionsCell);
		return row;
	}

	function loadLinks() {
		var body = document.getElementById('cel-links-body');
		var empty = document.getElementById('cel-empty');
		var loading = document.getElementById('cel-loading');
		var error = document.getElementById('cel-error');
		if (!body) {
			return;
		}
		api('/api/links').then(function (data) {
			if (loading) { loading.hidden = true; }
			body.innerHTML = '';
			var links = (data && data.links) || [];
			if (empty) { empty.hidden = links.length > 0; }
			links.forEach(function (link) {
				body.appendChild(buildRow(link));
			});
		}).catch(function () {
			if (loading) { loading.hidden = true; }
			if (error) {
				error.hidden = false;
				error.textContent = t(APP_ID, 'Your links could not be loaded.');
			}
		});
	}

	document.addEventListener('DOMContentLoaded', loadLinks);
})();
