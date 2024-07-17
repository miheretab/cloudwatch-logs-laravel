## This is forked version to fix the pluging to work for Laravel 9

## Disclaimer
This library uses AWS API through AWS PHP SDK, which has limits on concurrent requests. It means that on high concurrent or high load applications it may not work on it's best way. Please consider using another solution such as logging to the stdout and redirecting logs with fluentd.

## Requirements
* PHP >=8.1
* AWS account with proper permissions (see list of permissions below)

## Features
* Up to 10000 batch logs sending in order to avoid _Rate exceeded_ errors
* Log Groups creating with tags
* AWS CloudWatch Logs staff lazy loading
* Suitable for web applications and for long-living CLI daemons and workers

## Installation
Add the following to the composer.json
```bash
    "repositories": [
        ...
        {
            "type": "vcs",
            "url": "https://github.com/miheretab/cloudwatch-logs-laravel"
        }
    ],
```
Install the latest version with [Composer](https://getcomposer.org/) by running
```bash
$ composer require dhs/cloudwatch-laravel-log:dev-laravel-9-2
```

## Basic Laravel Usage
 - Create a folder Logging and create a file CloudWatchLoggerFactory.php
 - Guide Link: [Guid](https://s2sontech.com/php-and-laravel/laravel-10-logging-aws-cloudwatch)
```php
<?php

namespace App\Logging;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Dhs\CloudWatchLogs\Handler\CloudWatch;
use Monolog\Logger;
use Monolog\Formatter\JsonFormatter;

const LEVEL_DEBUG = 100;
const LEVEL_INFO = 200;
const LEVEL_NOTICE = 250;
const LEVEL_WARNING = 300;
const LEVEL_ERROR = 400;
const LEVEL_CRITICAL = 500;
const LEVEL_ALERT = 550;
const LEVEL_EMERGENCY = 600;

class CloudWatchLoggerFactory
{
    /**
     * Create a custom Monolog instance.
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {        
        // Instantiate AWS SDK CloudWatch Logs Client
        $client = new CloudWatchLogsClient($config['sdk']);

        // Instantiate handler (tags are optional)
        $handler = new CloudWatch(
            $client, 
            $config['group_name'],
            $config['stream_name'],
            $config['retention'], 
            10000,
            ['my-awesome-tag' => 'tag-value'],
            LEVEL_DEBUG
        );

        // Optionally set the JsonFormatter to be able to access your log messages in a structured way
        $handler->setFormatter(new JsonFormatter());

        $name = $config['name'] ?? 'cloudwatch';
        
        // Create a log channel
        $logger = new Logger($name);
        
        // Set handler
        $logger->pushHandler($handler);
        
        return $logger;
    }
}
```

## Config Usage
 - open file logging.php in folder config in laravel app and add the code config below

```php
<?PHP
use Aws\Credentials\CredentialProvider;
...
return [
    //...
    'channels' => [
        //....
        'cloudwatch' => [
            'driver' => 'custom',
            'via' => \App\Logging\CloudWatchLoggerFactory::class,
            'sdk' => [
                'region' => env('AWS_DEFAULT_REGION', 'us-west-2'),
                'version' => 'latest',
                'credentials' => CredentialProvider::memoize(CredentialProvider::instanceProfile())
            ],
            'retention' => 7,
            'level' => 'info',
            'group_name' => env('CLOUDWATCH_GROUP', 'confirmed-dev'),
            'stream_name' => env('CLOUDWATCH_STREAM', 'confirmed'),
        ],
    ],
];
```



check the main source readme for more information.
