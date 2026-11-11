# Copy Embed Link

**Copy a direct embed link for any file in your Nextcloud** — a link that ends with the file's real name and extension, so the file plays or displays *inside* other apps instead of showing a download page.

[Versión en español](README.es.md)

## Why

Nextcloud's built-in sharing is great for sending files to people, but its links don't work as media embeds: note and page editors (AppFlowy, Notion, Obsidian, WordPress…) need a direct link to the file itself. Copy Embed Link adds the missing button to Nextcloud's share menu: **Copy embed link**.

## What it does

- **One file, one link.** Sharing a single file exposes only that file — never the folder around it.
- **Root link.** The link is tied to the file's internal identity, not its path, so it keeps working if you move the file to another folder or rename it. It only dies if the file is deleted.
- **Real file name.** The link ends with the actual name and extension (`…/DRILL.mp3`), which is what media players in other apps require.
- **Access control.** Links can be public or protected with a password, and can have an expiration date.
- **Selective folder sharing.** Share a folder and choose exactly which files are visible through the link; the rest stay hidden.
- **One panel for everything.** A settings page lists every embed link you have created, with its access and expiration, ready to edit or revoke at any time.
- **Non-invasive.** If you remove the app, Nextcloud stays exactly as it was. Nothing in Nextcloud's core is modified.

## Status

🚧 **In development** — v0.1.0 is the project skeleton (app metadata and bootstrap). The embed-link engine comes next.

## Author

Created and maintained by **Pulgarin93** — a first open-source contribution, built in the open, process included.

## License

AGPL-3.0-or-later. Free to use, copy and improve; improvements shared back under the same license.
