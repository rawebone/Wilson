# Performance

As noted in the first part of the documentation, Wilson is designed to be
as efficient as possible out of the box to create a very small footprint.
However to get the best performance possible, there are a couple of steps
that additionally need to be taken.

This document outlines the methodologies that can be applied to improve
the performance of the framework and bring it's overhead as low possible.


## Caching

For every request the framework processes it creates a Routing Table from
the resources that are passed through to it. This requires reflection and
parsing of the doc comments and so, while the cost is almost negligible 
for small applications, the larger the application gets the slower
performance becomes. As such the framework provides a mechanism for
creating the Routing Table ahead of time to reduce this cost.

> Caching can be applied regardless of the size of the application, but the
> performance gained from this will depend upon the size of the Routing table
> versus the amount of instructions required to load the routing graph. As such
> mileage may vary. 

The suggested implementation is:

```php
<?php

require_once "vendor/autoload.php";

$api = new Wilson\Api();
$api->resources = array("Users");
$api->services  = new Services();

if ($production) { // @todo define the flag somewhere
    $api->cacheFile = "/path/to/cache/framework.php";
    
    if (!is_file($api->cacheFile)) {
        $api->createCache();
    }
}

if (isset($testing)) {
    return $api;
}

$api->dispatch();

```


## Include Optimisation

Autoloading can add in a significant amount of time to a request, even when
optimised. This is because PHP firstly has to make a call about whether the
class/interface/trait exists, then has to pass a request through a stack of
autoload handlers and those handlers need to check whether files exist and
then call include.

Overall I found in one application that 12% of the total request time was
taken by Composer. Now, I am not a naysayer to autoloading- it is a
brilliant thing for the best part - and this has been recognised by a
number of other projects who start to use a "preloader": a compiled form
of the application objects that are needed on every request. Wilson provides
the same basic thing, but without needing to load lots of dependencies.

```bash

$ php vendor/rawebone/wilson/bin/compile.php

```

```php
<?php

@include "/path/to/wilson_compiled.php";
require_once "vendor/autoload.php";

$api = new Wilson\Api();
$api->resources = array("Users");
$api->services  = new Services();

if ($production) { // @todo define the flag somewhere
    $api->cacheFile = "/path/to/cache/framework.php";
    
    if (!is_file($api->cacheFile)) {
        $api->createCache();
    }
}

if (isset($testing)) {
    return $api;
}

$api->dispatch();

```


## Next: [Internals](internals.md)
