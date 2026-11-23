import { createAppConfig } from '@nextcloud/vite-config'

// One entry point: src/files.js -> js/copy_embed_link-files.mjs
// (the app id prefix comes from appinfo/info.xml). That is the file
// the PHP listener loads in the Files app.
//
// emptyOutputDirectory is turned OFF on purpose: js/ also holds the
// management panel script (copy_embed_link-panel.js), which is plain
// JavaScript written by hand and must survive the build.
//
// inlineCSS is ON on purpose: this bundle is loaded with nothing but
// Util::addScript() (no addStyle call), and the confirmation dialog
// from @nextcloud/dialogs brings its own styles — inlining makes the
// bundle inject them by itself.
export default createAppConfig(
	{
		files: 'src/files.js',
	},
	{
		emptyOutputDirectory: false,
		inlineCSS: true,
	},
)
