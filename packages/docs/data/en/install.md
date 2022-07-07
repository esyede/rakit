# Installation & Initial Setup

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [System Requirements](#kebutuhan-sistem)
- [Installation](#instalasi)
    - [Install via Composer](#instal-via-composer)
    - [Manual Installation](#install-manual)
- [Problems?](#ada-kesulitan)
- [Initial Configuration](#konfigurasi-awal)
- [Beautiful URL](#mempercantik-url)
    - [Apache](#apache)
    - [Nginx](#nginx)

<!-- /MarkdownTOC -->


<a id="kebutuhan-sistem"></a>
## System Requirements

- PHP 5.4.0 to PHP 8.0 (PHP 8.1+ not supported yet)
- [Mbstring](https://www.php.net/manual/en/book.mbstring.php) Extension
- [OpenSSL](https://www.php.net/manual/en/book.openssl.php) Extension
- [Fileinfo](https://www.php.net/manual/en/book.fileinfo.php) Extension


**Additional Extensions:**

Installing the following extensions will help you get the full benefit of rakit, but it is not mandatory:



- [PDO](https://www.php.net/manual/en/pdo.installation.php) driver for SQLite, MySQL, PostgreSQL, or SQL Server
- [cURL](https://www.php.net/manual/en/book.curl.php) extension to install packages via the rakit console
- [GD Image](https://www.php.net/manual/en/book.image.php) extension for image processing.


<a id="instalasi"></a>
## Installation

Rakit can be installed in 2 very easy ways, namely via [Composer](https://getcomposer.org) and manual installation.



<a id="instal-via-composer"></a>
### Install via Composer

If you have already installed **Composer** on your computer, installing rakit will be
very easy, just run the following command:


```bash
composer create-project esyede/rakit
```

Then rakit will be installed in the `/rakit` folder, all you need to do is go to that folder
and run the built-in webserver:


```bash
cd rakit && php rakit serve
```


<a id="install-manual"></a>
### Manual Installation

Manual installation is also very easy, as easy as counting one to three:


  - [Download](https://rakit.esyede.my.id/download) and extract the archive to your web server.
  - Make sure the `storage/views/` and `assets/` directories are writable.
  - Edit the `application/config/application.php` file and add your app key. Remember, it must be at least 32 characters long. You can also generate app key via this link: [App Key Generator](https://rakit.esyede.my.id/key)


  ```php
  /*
  |--------------------------------------------------------------------------
  | Application Key
  |--------------------------------------------------------------------------
  |
  | Key ini digunakan oleh kelas Crypter dan Cookie untuk menghasilkan
  | string dan hash terenkripsi yang aman. Sangat penting bahwa key ini
  | harus dirahasiakan dan tidak boleh dibagikan kepada siapa pun.
  |
  | Isilah dengan 32 karakter acak dan jangan diubah-ubah lagi. Anda juga
  | dapat mengisinya secara otomatis via rakit console.
  |
  */

  'key' => 'FillYourAppKeyHereAtLeast32CharactersLong'
',
  ```

View the results in your favorite web browser.
If all is going well, you should see the beautiful rakit's splash page.

Get ready, there's a lot more to learn!



<a id="ada-kesulitan"></a>
## Problems?

If you're having trouble installing, try some of these suggestions:


- If you use `mod_rewrite`, change `'index'` configuration options
  in `application/config/application.php` to an empty string.

- Make sure the `storage/` and `assets/` folders and all their child folders are writable.



<a id="konfigurasi-awal"></a>
## Initial Configuration

All configuration files are stored in the `config/` folder.
We recommend you to take a look at these files to get basic understandings
about the configuration options available to you.


Pay special attention to the `application/config/application.php` file since it
contains basic configuration options for your application.


> If you use `mod_rewrite`, change `'index'` configuration options
  in `application/config/application.php` to an empty string.


<a id="mempercantik-url"></a>
## Beautiful URL

When you are ready to install your application to a production server,
there are a few important things to keep in mind
you can do to make sure your application runs as efficiently as possible.


In this document, we'll cover some good starting points to make sure
your application is used properly.


Of course, you also don't want your application URL to contain `/index.php`.
You can remove it with URL Rewrite.


<a id="apache"></a>
### Apache

If your web server is Apache, make sure the `mod_rewrite` module is enabled,
then create a file named `.htaccess` in the root of your web server
(next to the `index.php` file) and copy the following code into it:


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

Is the above configuration not working? Try this one:


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
The following configuration file serves as a starting point for configuring your web server.


Most likely, this file will need to be adjusted according to your server configuration:


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


After you have finished setting up URL rewrite, you need to change the `'index'` option
in `application/config/application.php` to an empty string.


>  Each web server has a different method of handling HTTP rewrites,
   and may also require different configuration rules.

