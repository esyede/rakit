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
-   [mbstring](https://www.php.net/manual/en/book.mbstring.php), [OpenSSL](https://www.php.net/manual/en/book.openssl.php), and [fileinfo](https://www.php.net/manual/en/book.fileinfo.php) extensions

**Optional** (enable as needed)

-   [PDO](https://www.php.net/manual/en/pdo.installation.php) — database support (SQLite, MySQL, PostgreSQL, SQL Server)
-   [cURL](https://www.php.net/manual/en/book.curl.php) — required for installing packages via the `rakit` console
-   [GD](https://www.php.net/manual/en/book.image.php) — image processing
-   [sockets](https://www.php.net/manual/en/book.sockets.php) — WebSocket support

<a id="installation"></a>

## Installation

Pick whichever method matches your workflow.

<a id="install-via-composer"></a>

### Install via Composer

```bash
composer create-project esyede/rakit
cd rakit
php rakit serve
```

The framework will be created inside the `rakit/` folder, and `php rakit serve`
starts the built-in PHP web server so you can browse your app right away.

<a id="manual-install"></a>

### Manual Install

1.  [Download](https://rakit.esyede.my.id/download) the Rakit archive and extract it
    into your web server's document root.
2.  Make `storage/` and `assets/` writable by PHP.
3.  Open the site in a browser. You should see the Rakit splash page.

That's it — you're ready to start building.

<a id="having-trouble"></a>

## Having Trouble?

-   **404 errors / `/index.php` showing in URLs**: enable URL rewriting (see
    [Pretty URLs](#pretty-urls) below), then set the `'index'` option in
    `application/config/application.php` to an empty string `''`.
-   **Permission errors writing to `storage/` or `assets/`**: make sure those
    folders (and everything under them) are writable by the PHP process.

<a id="initial-configuration"></a>

## Initial Configuration

Configuration files live in `application/config/`. Skim them to see what is
available — most defaults are sensible and you only need to change a few values
to get started.

The most important file is `application/config/application.php`. It controls
the application URL, key, default timezone, and the URL `index` option used
for pretty URLs.

<a id="pretty-urls"></a>

## Pretty URLs

By default URLs look like `http://example.com/index.php/users`. To make them
look like `http://example.com/users`, configure URL rewriting on your web
server, then set `'index' => ''` in `application/config/application.php`.

<a id="apache"></a>

### Apache

Make sure `mod_rewrite` is enabled, then create a `.htaccess` file next to
`index.php` with the following contents:

```apacheconf
Options -MultiViews -Indexes
RewriteEngine on

RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} (.+)/$
RewriteRule ^ %1 [L,R=301]

RewriteRule ^(application|cgi-bin|packages|storage|system|vendor)/(.*)?$ / [F,L]
RewriteRule ^composer\.(lock|json)$ / [F,L]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . index.php [L]
```

If that does not work on your hosting, try the variant below — it wraps the
rules in `<IfModule>` guards so they are only applied when the relevant Apache
modules are loaded:

```apacheconf
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    RewriteRule ^(application|cgi-bin|packages|storage|system|vendor)/(.*)?$ / [F,L]
    RewriteRule ^composer\.(lock|json)$ / [F,L]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

<a id="nginx"></a>

### Nginx

Use the snippet below as a starting point. Adjust `server_name`, `root`, and
the PHP-FPM socket path to match your server:

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

    location /(application|cgi-bin|packages|storage|system|vendor) {
        return 403;
    }

    location /composer\.(lock|json) {
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

> Different web servers handle URL rewriting differently. The snippets above
> are starting points, not one-size-fits-all configs. Once rewriting works,
> remember to set `'index' => ''` in `application/config/application.php`.
