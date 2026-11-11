# Copy Embed Link

**Copy a direct embed link for any file in your Nextcloud** — a link that ends with the file's real name and extension, so the file plays or displays *inside* other apps instead of showing a download page.

[Versión en español](README.es.md)

## Why

Nextcloud's built-in sharing is great for sending files to people, but its links don't work as media embeds: note and page editors (AppFlowy, Notion, Obsidian, WordPress…) need a direct link to the file itself. Copy Embed Link adds the missing button to Nextcloud's share menu: **Copy embed link**.

## What it does

- **Type-aware links.** The button recognizes what you are sharing. Audio, video, images and PDFs get an **embed link** that plays or displays inside the other app. Files that cannot be embedded — ZIP archives, design and project files (Adobe and similar) — get a **share link** for viewing and downloading instead, and the panel tells you clearly which kind of link you got.
- **Original or converted.** When a file can be shared in more than one format, the button asks which one you want: the original file, or the converted format.
- **One file, one link.** Sharing a single file exposes only that file — never the folder around it.
- **Root link.** The link is tied to the file's internal identity, not its path, so it keeps working if you move the file to another folder or rename it. It only dies if the file is deleted.
- **Real file name.** The link ends with the actual name and extension (`…/DRILL.mp3`), which is what media players in other apps require.
- **Access control.** Links can be public or protected with a password, and can have an expiration date.
- **Permissions that don't clash.** Folder permissions and link permissions are independent — the most specific one wins. Share one file from a private folder and only that file becomes reachable (the app asks you first, in plain words, and you never need to copy the file elsewhere); lock a single file inside a public folder and it stays out of the share. Every link carries its own level: view-only, or download allowed.
- **Selective folder sharing.** Share a folder and choose exactly which files are visible through the link; the rest stay hidden.
- **One panel for everything.** A settings page lists every embed link you have created, with its access and expiration, ready to edit or revoke at any time.
- **Non-invasive.** If you remove the app, Nextcloud stays exactly as it was. Nothing in Nextcloud's core is modified.

## Status

🚧 **In development** — v0.1.0 is the project skeleton (app metadata and bootstrap). The embed-link engine comes next.

## Author

Created and maintained by **Pulgarin93** — a first open-source contribution, built in the open, process included.

## License

AGPL-3.0-or-later. Free to use, copy and improve; improvements shared back under the same license.
