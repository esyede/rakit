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

The default structure suits both small and large applications, and follows the
conventions of other PHP frameworks.

<a id="folder-hierarchy"></a>

## Folder Hierarchy

By default, the rakit folder hierarchy will look like this:

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

What each folder is for:

<a id="application-folder"></a>

### Application Folder

Holds controllers, views and configuration. It is itself a package, the default
one, which boots the system and the packages installed into `packages/`.
Routing and the rest of the application settings live here too.

<a id="assets-folder"></a>

### Assets Folder

Public files the browser has to reach: CSS, JavaScript and images. Its `packages/`
subfolder holds the assets of installed packages.

<a id="packages-folder"></a>

### Packages Folder

Holds the packages you install.

<a id="storage-folder"></a>

### Storage Folder

Non-public files: cache, sessions, sqlite databases, and the views compiled by the
[Blade Template Engine](/docs/views/templating#blade-template-engine).

<a id="system-folder"></a>

### System Folder

The framework core. Upgrading usually means replacing this folder.
