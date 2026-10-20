# Installation & Initial Configuration

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [System Requirements](#system-requirements)
-   [Installation](#installation)
    -   [Install via Composer](#install-via-composer)
    -   [Manual Install](#manual-install)
-   [Having Trouble?](#having-trouble)
-   [Initial Configuration](#initial-configuration)
-   [Pretty URLs](#pretty-urls)
    -   [Apache](#apache)
    -   [Nginx](#nginx)

<!-- /MarkdownTOC -->

<a id="system-requirements"></a>

## System Requirements

**Required**

-   PHP 5.4 — 8.5
-   [JSON](https://www.php.net/manual/en/book.json.php), [mbstring](https://www.php.net/manual/en/book.mbstring.php), [OpenSSL](https://www.php.net/manual/en/book.openssl.php) and [fileinfo](https://www.php.net/manual/en/book.fileinfo.php) extensions

**Optional**

-   [PDO](https://www.php.net/manual/en/pdo.installation.php) — databases (SQLite, MySQL, PostgreSQL, SQL Server)
-   [cURL](https://www.php.net/manual/en/book.curl.php) — installing packages with the `rakit` console
-   [GD](https://www.php.net/manual/en/book.image.php) — image processing
-   [sockets](https://www.php.net/manual/en/book.sockets.php) — WebSocket support

<a id="installation"></a>

## Installation

<a id="install-via-composer"></a>

### Install via Composer

```bash
composer create-project esyede/rakit
cd rakit
php rakit serve
```

This creates the app in `rakit/`; `php rakit serve` starts PHP's built-in web
server so you can browse it right away.

<a id="manual-install"></a>

### Manual Install

1.  [Download](https://rakit.esyede.my.id/download) the archive and extract it into
    your web server's document root.
2.  Make `storage/` and `assets/` writable by PHP. On the first run the root folder
    must be writable too, so Rakit can generate `key.php`.
3.  Open the site in a browser. You should see the Rakit splash page.

<a id="having-trouble"></a>

## Having Trouble?

-   **404 errors, or `/index.php` in URLs**: set up [Pretty URLs](#pretty-urls).
-   **Permission errors in `storage/` or `assets/`**: make those folders (and
    everything under them) writable by the PHP process.

<a id="initial-configuration"></a>

## Initial Configuration

Configuration files live in `application/config/` and the defaults are sensible.
The one to check first is `application/config/application.php`: the application
`url`, the `timezone`, and the `index` option used for pretty URLs. The
application key lives in `key.php` at the root and is generated on the first run.
See [Configuration](/docs/config) for reading and changing values at runtime.

<a id="pretty-urls"></a>

## Pretty URLs

By default URLs look like `http://example.com/index.php/users`. To get
`http://example.com/users`, enable URL rewriting on your web server, then set
`'index' => ''` in `application/config/application.php`.

<a id="apache"></a>

### Apache

Enable `mod_rewrite`, then put a `.htaccess` file next to `index.php` (a minimal
`sample.htaccess` ships in the root folder):

```apacheconf
Options -MultiViews -Indexes
RewriteEngine on

RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} (.+)/$
RewriteRule ^ %1 [L,R=301]

RewriteRule (^|/)\.(?!well-known/) - [F,L]
RewriteRule ^(application|cgi-bin|packages|storage|system|vendor|tests)/(.*)?$ / [F,L]
RewriteRule ^(key\.php|paths\.php|_ide_helper\.php|rakit|doctum\.phar|repositories\.json|composer\.(lock|json))$ / [F,L]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . index.php [L]
```

If your host rejects it, wrap the rules in `<IfModule mod_rewrite.c>` (and the
`Options` line in `<IfModule mod_negotiation.c>`) so they only apply when those
modules are loaded.

<a id="nginx"></a>

### Nginx

Adjust `server_name`, `root` and the PHP-FPM socket path to your server:

```nginx
server {
    listen 80;
    server_name example.com;
    root /srv/example.com;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;
    autoindex off;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/(application|cgi-bin|packages|storage|system|vendor|tests)/ {
        return 403;
    }

    location ~ ^/(key\.php|paths\.php|_ide_helper\.php|rakit|doctum\.phar|repositories\.json|composer\.(lock|json))$ {
        return 403;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        # Adjust this path to your installed PHP-FPM version.
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

> These snippets are starting points, not drop-in configs. Once rewriting works,
> remember to set `'index' => ''` in `application/config/application.php`.
