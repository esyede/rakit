# Installation & Initial Configuration

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [System Requirements](#kebutuhan-sistem)
- [Instalation](#instalasi)
    - [Instal via Composer](#instal-via-composer)
    - [Manual Install](#install-manual)
- [Any problem?](#ada-kesulitan)
- [Initial Configuration](#konfigurasi-awal)
- [Pretty URL](#mempercantik-url)
    - [Apache](#apache)
    - [Nginx](#nginx)

<!-- /MarkdownTOC -->


<a id="kebutuhan-sistem"></a>
## System Requirements

- PHP 5.4.0+ (up to 8.0)
- Ekstensi [Mbstring](https://www.php.net/manual/en/book.mbstring.php)
- Ekstensi [OpenSSL](https://www.php.net/manual/en/book.openssl.php)
- Ekstensi [Fileinfo](https://www.php.net/manual/en/book.fileinfo.php)


**Additional Extensions:**

Installing these extensions will help you get full benefits from rakit, but it's not mandatory:


- Driver [PDO](https://www.php.net/manual/en/pdo.installation.php) for SQLite,
  MySQL, PostgreSQL, or SQL Server to work with these databases.
- Extension [cURL](https://www.php.net/manual/en/book.curl.php) to install package usig rakit console.
- Extension [GD Image](https://www.php.net/manual/en/book.image.php) to manipulate images.


<a id="instalasi"></a>
## Instalation

Rakit can be installed with 2 easy ways, using [Composer](https://getcomposer.org)
and manual installation.


<a id="instal-via-composer"></a>
### Install using Composer

If you have installed Composer in your computer, installing rakit will be very easy, just run
this command:

```bash
composer create-project esyede/rakit
```

Then rakit will be installed in `/rakit` folder, you just only need to go to this folder and run rakit's  
built-in webserver:

```bash
cd rakit && php rakit serve
```


<a id="install-manual"></a>
### Manual Install

Installing manually also as easy as 123:

  - [Download](https://rakit.esyede.my.id/download) and extract Rakit archive to your web server.
  - Make sure `storage/views/` folder and `assets/` folder can be written by PHP.
  - Edit  `application/config/application.php` file and add your app key, remember, minimum length 
  is 32 character.
  You can also generate app key through this link: [App Key Generator](https://rakit.esyede.my.id/key)

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

  'key' => 'isiAppKeyAndaDisiniMinimal32karakter',
  ```

View the result using your favorite browser. If everything goes fine, you will see Rakit's beautiful 
splash page.

Get ready, there are a lot more things to learn!


<a id="ada-kesulitan"></a>
## Any problem?

If you have problem in installing Rakit, try some of these suggestions:

- If you use `mod_rewrite`, change `'index'` configuration option
  in `application/config/application.php` to empty string.
- Make sure `storage/` folder and `assets/` folder and all folders inside can be written by PHP.


<a id="konfigurasi-awal"></a>
## Initial Configuration

All configuration file stored in `config/` folder.
We suggest you to see these files in order to get basic understanding 
about configuration options available to you.

Pay special attention to `application/config/application.php` file because this file 
contains basic configuration options for your application.

>  If you use `mod_rewrite`, change `'index'` option 
   in `application/config/application.php` file to empty string.


<a id="mempercantik-url"></a>
## Pretty URL

When you are reay to install application to production server, there are some important things you can do to ensure you application run as efficient as possible.

In this document, we will discuss some good starting points to make sure your application were
used in the right way.

Of course, you also don't want you application URL contains `/index.php`.
You can remove it using URL Rewrite.

<a id="apache"></a>
### Apache

If you use Apache as your web server, make sure `mod_rewrite` module has been activated,
then create a file called `.htaccess` in your web server's root
(in the same folder with `index.php`) and copy the following code into the file:

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

Did the configuration above work? If not, try to use this one:

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

If your application hosted on server running Nginx, you can use the following configuration file
as a starting point to configure your web server.

Probably, this file will need to be customized to fit your server configuration:

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



After finished managing URL rewrite, you need to change `'index'` option
in `application/config/application.php` file to empty string.

>  Every web server has different method in handling HTTP rewrite,
   and probably will need differen configuration rule as well.
