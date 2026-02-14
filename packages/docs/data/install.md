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

-   PHP 5.4.0 or higher (tested up to PHP 8.x)
-   [Mbstring](https://www.php.net/manual/en/book.mbstring.php) extension
-   [OpenSSL](https://www.php.net/manual/en/book.openssl.php) extension
-   [Fileinfo](https://www.php.net/manual/en/book.fileinfo.php) extension

**Additional Extensions:**

Installing the following extensions will help you get the full benefits of rakit, but are not required:

-   [PDO](https://www.php.net/manual/en/pdo.installation.php) driver for SQLite,
    MySQL, PostgreSQL, or SQL Server to work with databases.
-   [cURL](https://www.php.net/manual/en/book.curl.php) extension to install packages via rakit console.
-   [GD Image](https://www.php.net/manual/en/book.image.php) extension for image processing.

<a id="installation"></a>

## Installation

Rakit can be installed in 2 very easy ways, namely installation via [Composer](https://getcomposer.org)
and manual installation.

<a id="install-via-composer"></a>

### Install via Composer

If you have installed Composer on your computer, installing rakit will be
very easy, just run the following command:

```bash
composer create-project esyede/rakit
```

Then rakit will be installed in the `/rakit` folder, all you need to do is go to that folder and
run the built-in webserver:

```bash
cd rakit && php rakit serve
```

<a id="manual-install"></a>

### Manual Install

This installation method is also very easy, as easy as counting from one to three:

-   [Download](https://rakit.esyede.my.id/download) and extract the Rakit archive to your web server.
-   Make sure the `storage/views/` and `assets/` directories can be written by PHP.
-   Ready to test!

See the results through your favorite browser. If everything is fine, you will see the beautiful Rakit splash page.

Get ready, there's a lot more to learn!

<a id="having-trouble"></a>

## Having Trouble?

If you have trouble installing, try some of the following suggestions:

-   If you are using `mod_rewrite`, change the configuration option `'index'`
    in `application/config/application.php` to an empty string.
-   Make sure the `storage/` and `assets/` folders and all folders inside them can be written by PHP.

<a id="initial-configuration"></a>

## Initial Configuration

All configuration files are stored in the `config/` folder.
We recommend you look at those files to get a basic understanding
of the configuration options available to you.

Pay special attention to the `application/config/application.php` file because it
contains the basic configuration options for your application.

> If you are using `mod_rewrite`, change the `'index'`
> option in `application/config/application.php` to an empty string.

<a id="pretty-urls"></a>

## Pretty URLs

When you are ready to deploy your application to production, there are some important things
you can do to ensure your application runs as efficiently as possible.

In this document, we will discuss some good starting points to ensure
your application is used correctly.

Of course, you also don't want your application's URLs to contain `/index.php`.
You can remove it using URL Rewrite.

<a id="apache"></a>

### Apache

If your web server uses Apache, make sure the `mod_rewrite` module is enabled,
then create a file named `.htaccess` in your web server root
(side by side with the `index.php` file) and copy the following code into it:

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

Doesn't the above configuration work? Try replacing it with this:

```apacheconf
<IfPackage mod_rewrite.c>
    <IfPackage mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfPackage>

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
</IfPackage>
```

<a id="nginx"></a>

### Nginx

If you deploy your application to a server running Nginx, you can use
the following configuration file as a starting point to configure your web server.

Most likely, this file needs to be adjusted according to your server configuration:

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
        fastcgi_pass unix:/var/run/php/php7.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

After finishing setting up URL rewrite, you need to change the `'index'`
option in `application/config/application.php` to an empty string.

> Every web server has a different method of handling HTTP rewrite,
> and may also require different configuration rules.
