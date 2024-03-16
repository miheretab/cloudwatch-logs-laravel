<?php

return [
    //...
    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

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
