# Installation and Setup

## System Requirements

* PHP 5.3 or greater
* Apache or a web server which supports URL rewriting


## Installation

The framework can be installed through [Composer](https://getcomposer.org) by
running this command:

```bash
$ composer require "rawebone/wilson:1.0.*"
```

Or manually by adding the following to your `composer.json` file and and the
following line into your `require` block:

```json
{
    "require": {
        "rawebone/wilson": "1.0.*"
    }
}
```

And then run a `composer update`.


## Index.php

Wilson works on the Front Controller principle, in which all traffic is first
directed through an `index.php` file which interprets the request and passes
it to an appropriate handler in your application. The minimum amount of code
required to get this to work is:

```php
<?php

require_once "vendor/autoload.php";

$api = new Wilson\Api();
$api->dispatch();

```

We'll build on this later.


## Web Server Configuration

Please read the following appropriate to your web server to direct the traffic
to your `index.php`.

### Apache  

Ensure the `.htaccess` and `index.php` files are in the same public-accessible
directory. The `.htaccess` file should contain this code:

    RewriteEngine On
     
    # If you are using aliases you should setup your rewrite base;
    # For example if index.php is located in http://localhost/my/app/
    RewriteBase /my/app/
    
    # If you do not have any other files in the folder with your
    # index.php then skip the next line as it slows down request
    # processing markedly
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [QSA,L]

Additionally, make sure your virtual host is configured with the AllowOverride
option so that the `.htaccess` rewrite rules can be used:

   AllowOverride All



## Next: [Request Lifecycle](lifecycle.md)
