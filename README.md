# Log Viewer

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/ianm/log-viewer.svg)](https://packagist.org/packages/ianm/log-viewer) [![Total Downloads](https://img.shields.io/packagist/dt/ianm/log-viewer.svg)](https://packagist.org/packages/ianm/log-viewer)

A [Flarum](http://flarum.org) extension. Easily view your Flarum logfiles from within the admin interface

This utility extension offers access to Flarum's logfiles without the need for command line access to your server. It exposes the contents of files found in the `{flarum_install_dir}/storage/logs` directly to the admin interface, or optionally via the API.

This is especially useful if you have either limited knowledge of `SSH` access/commands, or you are using a host where this is simply not permitted. So long as the Flarum `logger` has not been modified to store logs elsewhere (usually only on multi-instance, scalable hosting solutions), then this extension will work for you!

Need to access the logs in order to troubleshoot a problem you're having with your forum? Simply login as an admin account and look for any trouble signs in the log viewer. Simple, just be sure to review any log snippets you share with others, as they _may_ contain sensitive data.

## Screenshots

![log viewer](https://user-images.githubusercontent.com/16573496/200803543-ff6237ac-e029-4563-aa3d-7922e8b47dce.png)

## API Usage

Two API endpoints are provided to enable the logs to be easily extracted from Flarum into another system. Permissions to access these endpoints are provided, and restriced to `admin` users only by default, although you may create a dedicated permission group and apply log access to that group as well for log retrieval without full admin permissions.  **NEVER GRANT LOG ACCESS TO REGULAR USERS**.

Permission can be set within the extension page, or the global `Permissions` tab
![permission](https://user-images.githubusercontent.com/16573496/200804488-ede34025-3ce7-4b74-9bb1-91c0d9b27ee8.png)

Once authenticated, a `GET` request can be made to `/api/logs` to list the available log files.

To retrieve a particular file, another `GET` request should be made to `/api/logs/{filename}`

## Future changes/features

- Add option to download a file from the admin interface
- Add option to delete a file from the admin interface
- Add option automatically purge logfiles after X days/months
- Add feature to tail new logfile content and stream this into the viewer

## Installation

Install with composer:

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
- [GitHub](https://github.com/ianm/log-viewer)
- [Discuss](https://discuss.flarum.org/d/PUT_DISCUSS_SLUG_HERE)
