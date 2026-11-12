# Changelog

All notable changes to Copy Embed Link are documented here.

## 0.2.0 — 2026-11-11

Phase 1 engine — the app now works end to end:

- **Public link serving** under `/f/{token}/…`: the root URL redirects to the
  file URL ending in the file's real current name, so players and embedders
  recognise the media. The link is tied to the file's internal id, so it
  survives moves and renames.
- **Folder links**: files inside a folder are served through `/f/{token}/d/…`
  URLs, and only when their id is on the link's allowed list — sharing a
  folder can expose an exact selection of its files, nothing more.
- **Passwords**: a protected link shows a password gate; after one correct
  entry a signed cookie (`cel_auth_{token}`) keeps the visitor authorised.
- **Expiry**: expired links stop working and say so.
- **View-only links**: when downloads are off, the file is always served
  inline and download requests are ignored.
- **Range streaming**: HTTP byte ranges are honoured (206 Partial Content),
  so listeners and viewers can seek inside audio and video.
- **Owner API**: create (with the privacy guard — a file with no shares asks
  "This folder is private. Share just this file?" before anything is
  created), list, revoke, list a folder's children, and list the links of a
  file.
- **Files integration**: "Copy embed link" in the file context menu and a
  section in the Sharing sidebar; both announce whether the link delivered
  is an embed link or a share link, based on the file type.
- **Management panel** ("Embed links" in the app menu): every link with its
  type, file, expiry and download setting, with copy and revoke actions.

## 0.1.0 — 2026-11-11

- Project foundation: app skeleton, the link entity and database table,
  file-type recognition (embed vs share), and the link service (root links
  bound to the file id, tokens, password hashing, per-child URLs).
