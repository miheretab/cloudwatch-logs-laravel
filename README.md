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
Install the latest version with [Composer](https://getcomposer.org/) by running

```bash
$ composer require dhs/cloudwatch-laravel-log:dev-master
```

## Basic Laravel Usage
 - Create folder Logging and create a file CloudWatchLoggerFactory.php
 - Guide Link: [Guid](https://s2sontech.com/php-and-laravel/laravel-10-logging-aws-cloudwatch)
```php
<?php

namespace App\Logging;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Dhs\CloudWatchLogs\Handler\CloudWatch;
use Monolog\Logger;
use Monolog\Formatter\JsonFormatter;
use Monolog\Level;

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
            Level::Info
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
```php
<?php
return [
    //...
    'channels' => [
        //....
        'cloudwatch' => [
            'driver' => 'custom',
            'via' => \App\Logging\CloudWatchLoggerFactory::class,
            'sdk' => [
                'region' => env('AWS_DEFAULT_REGION', 'eu-west-1'),
                'version' => 'latest',
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID', ''),
                    'secret' => env('AWS_SECRET_ACCESS_KEY', ''),
                    // 'token' => '', // token is optional
                ]
            ],
            'retention' => 30,
            'level' => 'info',
            'group_name' => env('CLOUDWATCH_LOG_GROUP', 'group-log'),
            'stream_name' => env('CLOUDWATCH_LOG_STREAM', 'error-log'),
        ],
    ],
];

```
## Basic PHP Usage
```php
<?php

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Monolog\Logger;
use Monolog\Level;
use Monolog\Formatter\JsonFormatter;
use Dhs\CloudWatchLogs\Handler\CloudWatch;

$sdkParams = [
    'region' => 'eu-west-1',
    'version' => 'latest',
    'credentials' => [
        'key' => 'your AWS key',
        'secret' => 'your AWS secret',
        'token' => 'your AWS session token', // token is optional
    ]
];

// Instantiate AWS SDK CloudWatch Logs Client
$client = new CloudWatchLogsClient($sdkParams);

// Log group name, will be created if none
$groupName = 'php-logtest';

// Log stream name, will be created if none
$streamName = 'ec2-instance-1';

// Days to keep logs, 14 by default. Set to `null` to allow indefinite retention.
$retentionDays = 30;

// Instantiate handler (tags are optional)
$handler = new CloudWatch($client, $groupName, $streamName, $retentionDays, 10000, ['my-awesome-tag' => 'tag-value'], Level::Info);

// Optionally set the JsonFormatter to be able to access your log messages in a structured way
$handler->setFormatter(new JsonFormatter());

// Create a log channel
$log = new Logger('name');

// Set handler
$log->pushHandler($handler);

// Add records to the log
$log->debug('Foo');
$log->warning('Bar');
$log->error('Baz');
```

## Frameworks integration
 - [Silex](http://silex.sensiolabs.org/doc/master/providers/monolog.html#customization)
 - [Symfony](http://symfony.com/doc/current/logging.html) ([Example](https://github.com/maxbanton/cwh/issues/10#issuecomment-296173601))
 - [Lumen](https://lumen.laravel.com/docs/5.2/errors)
 - [Laravel](https://laravel.com/docs/5.4/errors) ([Example](https://stackoverflow.com/a/51790656/1856778))

 [And many others](https://github.com/Seldaek/monolog#framework-integrations)

# AWS IAM needed permissions
If you prefer to use a separate programmatic IAM user (recommended) or want to define a policy, make sure following permissions are included:
1. `CreateLogGroup` [aws docs](https://docs.aws.amazon.com/AmazonCloudWatchLogs/latest/APIReference/API_CreateLogGroup.html)
1. `CreateLogStream` [aws docs](https://docs.aws.amazon.com/AmazonCloudWatchLogs/latest/APIReference/API_CreateLogStream.html)
1. `PutLogEvents` [aws docs](https://docs.aws.amazon.com/AmazonCloudWatchLogs/latest/APIReference/API_PutLogEvents.html)
1. `PutRetentionPolicy` [aws docs](https://docs.aws.amazon.com/AmazonCloudWatchLogs/latest/APIReference/API_PutRetentionPolicy.html)
1. `DescribeLogStreams` [aws docs](https://docs.aws.amazon.com/AmazonCloudWatchLogs/latest/APIReference/API_DescribeLogStreams.html)
1. `DescribeLogGroups` [aws docs](https://docs.aws.amazon.com/AmazonCloudWatchLogs/latest/APIReference/API_DescribeLogGroups.html)

When setting the `$createGroup` argument to `false`, permissions `DescribeLogGroups` and `CreateLogGroup` can be omitted

## Sample 1: Write to any log stream in a log group
This policy example allows writing to any log stream in a log group (named `my-app`). The log streams will be created automatically.

*Note: The first statement allows creation of log groups, and is not required when setting the `$createGroup` argument to `false`.*

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "logs:CreateLogGroup",
                "logs:DescribeLogGroups"
            ],
            "Resource": "arn:aws:logs:*:*:log-group:*"
        },
        {
            "Effect": "Allow",
            "Action": [
                "logs:CreateLogStream",
                "logs:DescribeLogStreams",
                "logs:PutRetentionPolicy",
                "logs:PutLogEvents"
            ],
            "Resource": "arn:aws:logs:*:*:log-group:my-app:*"
        }
    ]
}
```

## Sample 2: Write to specific log streams in a log group
This policy example allows writing to specific log streams (named `my-stream-1` and `my-stream-2`) in a log group (named `my-app`). The log streams will be created automatically.

*Note: The first statement allows creation of log groups, and is not required when setting the `$createGroup` argument to `false`.*

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "logs:CreateLogGroup",
                "logs:DescribeLogGroups"
            ],
            "Resource": "arn:aws:logs:*:*:log-group:*"
        },
        {
            "Effect": "Allow",
            "Action": [
                "logs:CreateLogStream",
                "logs:DescribeLogStreams",
                "logs:PutRetentionPolicy"
            ],
            "Resource": "arn:aws:logs:*:*:log-group:my-app:*"
        },
        {
            "Effect": "Allow",
            "Action": [
                "logs:PutLogEvents"
            ],
            "Resource": [
                "arn:aws:logs:*:*:log-group:my-app:log-stream:my-stream-1",
                "arn:aws:logs:*:*:log-group:my-app:log-stream:my-stream-2",
            ]
        }
    ]
}
```
