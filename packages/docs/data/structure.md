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

The default rakit structure is intended to provide a good starting point for large and small applications.
This structure is made similar to other existing frameworks so you won't feel unfamiliar.

<a id="folder-hierarchy"></a>

## Folder Hierarchy

By default, the rakit folder hierarchy will look like this:

```bash
├── /application
│   ├── /commands
│   ├── /config
│   ├── /controllers
│   ├── /jobs
│   ├── /language
│   ├── /libraries
│   ├── /migrations
│   ├── /models
│   ├── /tests
│   ├── /views
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
├── robots.txt
└── sample.htaccess
```

Now, let's discuss what these folders are for.

<a id="application-folder"></a>

### Application Folder

The `application/` folder contains controllers, views, configuration files and other default files.
Basically, this folder is a package (i.e. the default package) used to
bootstrap the rakit system and other packages you install into the `packages/` folder.

Default routing and other settings are also placed in this folder.

<a id="assets-folder"></a>

### Assets Folder

The `assets/` folder contains public assets such as CSS files, JavaScript, images
and other files that must be accessible by the web browser.

Inside this folder there is also a `packages/` subfolder used to place asset files
of packages you install.

<a id="packages-folder"></a>

### Packages Folder

The `packages/` folder contains the package folders you install.

<a id="storage-folder"></a>

### Storage Folder

The `storage/` folder contains built-in rakit subfolders for storing non-public files such as
cache files, sessions, database files (sqlite) and rendered files
from the [Blade Template Engine](/docs/views/templating#blade-template-engine).

<a id="system-folder"></a>

### System Folder

The `system/` folder is the core folder, inside it are the main files of rakit.
When upgrading the rakit framework, usually you just need to overwrite this folder with the new one.
