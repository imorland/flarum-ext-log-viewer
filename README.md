# Log Viewer

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/ianm/log-viewer.svg)](https://packagist.org/packages/ianm/log-viewer) [![Total Downloads](https://img.shields.io/packagist/dt/ianm/log-viewer.svg)](https://packagist.org/packages/ianm/log-viewer)

Easily view your Flarum logfiles from within the admin interface.

This extension exposes the contents of files found in `{flarum_install_dir}/storage/logs` (including subdirectories) directly in the admin panel, without needing SSH or command-line access to your server.

> **Note:** Be careful when sharing log snippets — they may contain sensitive data such as user details, email addresses, or internal paths.

## Features

- **View** log files in the admin panel, including files in subdirectories (e.g. `composer/`)
- **Download** any log file directly from the admin panel
- **Delete** log files from the admin panel
- **Auto-split** large log files into smaller parts on a daily schedule (configurable, default 1 MB)
- **Auto-purge** old log files on a daily schedule (configurable, default 90 days)
- **API access** to list, retrieve, and delete log files from external systems

## Screenshots

![log viewer](https://user-images.githubusercontent.com/16573496/200803543-ff6237ac-e029-4563-aa3d-7922e8b47dce.png)
![log viewer mobile](https://user-images.githubusercontent.com/16573496/200811821-0712b10b-b3dd-4078-a6cf-43fb4380f5b0.png)

## Settings

Two settings are available on the extension's settings page:

| Setting | Default | Description |
|---|---|---|
| Maximum Log File Size (MB) | 1 | Files exceeding this size are split into numbered parts daily. Set to `0` to disable splitting. |
| Purge logfiles after days | 90 | Log files older than this are deleted daily. Set to `0` to disable purging. |

Both features rely on the [Flarum scheduler](https://docs.flarum.org/scheduler) being active.

## Permissions

By default, only admins can access the log viewer. A `View and manage logfiles` permission is provided — you can grant it to a custom group if you need log access without full admin rights.

**Never grant log access to regular users.**

The permission can be set on the extension page or the global Permissions tab.

![permission](https://user-images.githubusercontent.com/16573496/200804488-ede34025-3ce7-4b74-9bb1-91c0d9b27ee8.png)

## API Usage

Two API endpoints are provided to enable log retrieval from external systems.

All requests must be authenticated as a user with the `manageLogfiles` permission.

### List log files

```
GET /api/logs
```

Returns a list of all log files. Supports sorting via the `sort` query parameter:
- `-modified` (default) — newest first
- `modified` — oldest first
- `fileName`
- `size`

Each item in the response includes a `relativePath` attribute (e.g. `flarum.log` or `composer/output-2024-11-16.log`) and an `id` field which is the base64url-encoded relative path.

### Retrieve a log file

```
GET /api/logs/{id}
```

Returns the file's content. Use the `id` value from the list response.

### Download a log file

```
GET /api/logs/download/{id}
```

Returns the raw file as an attachment.

### Delete a log file

```
DELETE /api/logs/{id}
```

## Installation

Requires **Flarum 2.x**.

```sh
composer require ianm/log-viewer
```

## Updating

```sh
composer update ianm/log-viewer
php flarum cache:clear
```

## Links

- [Packagist](https://packagist.org/packages/ianm/log-viewer)
- [GitHub](https://github.com/imorland/flarum-ext-log-viewer)
- [Discuss](https://discuss.flarum.org/d/31932-flarum-log-viewer)
