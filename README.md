# Pomm2 Profiler for Silex micro-framework

![Pomm profiler in Silex 2.x](screenshot.png)

## Install

Add the following requirements in the project's `composer.json` file:

```
    "silex/web-profiler": "~1.0",
    "pomm-project/pomm-profiler-service-provider": "dev-silex-1"
```

## Setup

```php
<?php // bootstrap.php

    $app->register(new Silex\Provider\ServiceControllerServiceProvider());
    $app->register(new Provider\WebProfilerServiceProvider(), array(
        'profiler.cache_dir' => PROJECT_DIR.'/cache/profiler',
        'profiler.mount_prefix' => '/_profiler', // this is the default
    ));
    $app->register(new PommProject\Silex\ProfilerServiceProvider\PommProfilerServiceProvider());
```
