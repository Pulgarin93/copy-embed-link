/**
 * Copy Embed Link - direct embed links for Nextcloud files.
 *
 * @copyright Copyright (c) 2026 Pulgarin93
 * @license AGPL-3.0-or-later
 *
 * Files integration for Nextcloud 33: registers ONE file action,
 * "Copy embed link", with the modern @nextcloud/files API (v4).
 *
 * This file is the source. It is compiled with Vite (see
 * vite.config.mjs in the app root) into js/copy_embed_link-files.mjs,
 * which Nextcloud loads in the Files app through the
 * LoadAdditionalScriptsEvent listener on the PHP side.
 *
 * The flow is the same one the legacy script had:
 *   POST /apps/copy_embed_link/api/links {fileId, confirmed}
 *   -> {link: {url, kind}}        copy the URL, announce the kind
 *   -> {needsConfirm: true}       ask first (privacy guard), retry
 *                                 with confirmed=true
 */

import { getRequestToken } from '@nextcloud/auth'
import { showConfirmation, showError, showInfo, showSuccess } from '@nextcloud/dialogs'
import { Permission, registerFileAction } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

const APP_ID = 'copy_embed_link'

// The app icon (img/app.svg, a chain link), inlined for the menu.
const iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>'

async function copyToClipboard(text) {
	if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
		return navigator.clipboard.writeText(text)
	}
	const area = document.createElement('textarea')
	area.value = text
	area.style.position = 'fixed'
	area.style.opacity = '0'
	document.body.appendChild(area)
	area.select()
	try {
		if (!document.execCommand('copy')) {
			throw new Error('copy command rejected')
		}
	} finally {
		document.body.removeChild(area)
	}
}

async function apiCreateLink(fileId, confirmed) {
	const response = await fetch(generateUrl('/apps/' + APP_ID + '/api/links'), {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
			requesttoken: getRequestToken(),
		},
		body: JSON.stringify({ fileId, confirmed: !!confirmed }),
	})
	return response.json()
}

function successMessage(link) {
	if (link.kind === 'embed') {
		return t(APP_ID, 'Embed link copied. Paste it where you want the file to play or show.')
	}
	return t(APP_ID, 'Share link copied. This file type cannot be embedded, so the link opens it for viewing or download.')
}

/**
 * @return {Promise<boolean>} true when a link was delivered
 */
async function finishWithLink(data) {
	if (!data || !data.link || !data.link.url) {
		showError(t(APP_ID, 'The link could not be created.'))
		return false
	}
	try {
		await copyToClipboard(data.link.url)
		showSuccess(successMessage(data.link))
	} catch {
		// Clipboard unavailable: show the URL so it can be copied by hand.
		showInfo(data.link.url)
	}
	return true
}

/**
 * The privacy guard: the API answers needsConfirm when the file has
 * no shares at all (it lives behind a private folder). Only after an
 * explicit "yes" do we call again with confirmed=true.
 *
 * @return {Promise<boolean|null>} null when the user cancelled (silent)
 */
async function createLink(fileId) {
	try {
		const data = await apiCreateLink(fileId, false)
		if (data && data.needsConfirm) {
			return await askFirst(fileId)
		}
		return await finishWithLink(data)
	} catch {
		showError(t(APP_ID, 'The link could not be created.'))
		return false
	}
}

async function askFirst(fileId) {
	// Exact wording agreed for the app (see the project spec).
	const question = 'This folder is private. Share just this file?'
	let confirmed = false
	try {
		confirmed = await showConfirmation({
			name: t(APP_ID, 'Copy embed link'),
			text: question,
		})
	} catch {
		// Dialogs unavailable: fall back to the native confirm.
		confirmed = typeof window.confirm === 'function'
			? window.confirm(question)
			: false
	}
	if (!confirmed) {
		return null
	}
	try {
		const data = await apiCreateLink(fileId, true)
		return await finishWithLink(data)
	} catch {
		showError(t(APP_ID, 'The link could not be created.'))
		return false
	}
}

registerFileAction({
	id: 'copy-embed-link',
	displayName: () => t(APP_ID, 'Copy embed link'),
	iconSvgInline: () => iconSvg,
	// One node at a time, files and folders alike (the legacy action
	// used mime 'all'; the API decides per node kind), readable by
	// the current user.
	enabled: ({ nodes }) => nodes.length === 1
		&& (nodes[0].permissions & Permission.READ) !== 0,
	async exec({ nodes }) {
		const node = nodes[0]
		if (!node || !node.fileid) {
			showError(t(APP_ID, 'Could not find that file.'))
			return false
		}
		return createLink(node.fileid)
	},
})
