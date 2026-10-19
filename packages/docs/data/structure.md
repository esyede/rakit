# Directory Structure

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Folder Hierarchy](#folder-hierarchy)
    -   [Application Folder](#application-folder)
    -   [Assets Folder](#assets-folder)
    -   [Packages Folder](#packages-folder)
    -   [Storage Folder](#storage-folder)
    -   [System Folder](#system-folder)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

The default layout suits small and large applications alike and follows the
conventions of other PHP frameworks.

<a id="folder-hierarchy"></a>

## Folder Hierarchy


```bash
├── /application
│   ├── /commands
│   ├── /components
│   ├── /config
│   ├── /controllers
│   ├── /jobs
│   ├── /language
│   ├── /libraries
│   ├── /migrations
│   ├── /models
│   ├── /observers
│   ├── /tests
│   ├── /transformers
│   ├── /views
│   │   ├── /components
│   │   └── /error
│   ├── boot.php
│   ├── composers.php
│   ├── hooks.php
│   ├── index.html
│   ├── middlewares.php
│   ├── packages.php
│   └── routes.php
├── /assets
├── /packages
│   └── /docs
├── /storage
│   ├── /cache
│   ├── /console
│   ├── /database
│   ├── /debugbar
│   ├── /jobs
│   ├── /logs
│   ├── /sessions
│   ├── /views
│   ├── .gitignore
│   └── index.html
├── /system
├── /tests
├── /vendor
├── .editorconfig
├── .gitattributes
├── .gitignore
├── composer.json
├── composer.lock
├── index.php
├── key.php    (auto-generated secret key)
├── LICENSE
├── paths.php
├── rakit
├── README.md
└── sample.htaccess
```

<a id="application-folder"></a>

### Application Folder

Your code: controllers, models, views, configuration, routes and so on. It is
itself a package, the default one, and its `boot.php` starts the system and the
packages registered in `packages.php`.

<a id="assets-folder"></a>

### Assets Folder

Public files the browser has to reach: CSS, JavaScript and images. Its
`packages/` subfolder holds the [published assets](/docs/packages#package-assets)
of installed packages.

<a id="packages-folder"></a>

### Packages Folder

The [packages](/docs/packages) you install.

<a id="storage-folder"></a>

### Storage Folder

Non-public files written at runtime: cache, sessions, logs, SQLite databases and
the views compiled by the
[Blade Template Engine](/docs/views/templating#blade-template-engine). It must be
writable by PHP.

<a id="system-folder"></a>

### System Folder

The framework core. Upgrading usually means replacing this folder.
