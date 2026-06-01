<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */
    'task_files' => [
        'base_url' => env('TASK_FILES_BASE_URL', 'http://220.130.187.241:9680/task-files'),
    ],
    'python'     => [
        'url' => env('PYTHON_API_BASE_URL'),
    ],
    'ai'         => [
        'metrics_url' => env('AI_METRICS_URL'),
    ],
    'screenshot' => [
        'url' => env('SCREENSHOT_URL'),
    ],

    'postmark'   => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend'     => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses'        => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack'      => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'crawler'    => [
        'host'     => env('CRAWLER_SFTP_HOST', '45.77.241.149'),
        'username' => env('CRAWLER_SFTP_USERNAME', 'php_louis'),
        'password' => env('CRAWLER_SFTP_PASSWORD', 'Cr4wl3r_S3cur3_2026_!xK9'),
        'port'     => env('CRAWLER_SFTP_PORT', 22),
    ],

    'clean_file_server' => [
        'host'     => env('CLEAN_FILE_SERVER_HOST', '35.194.240.94'),
        'user'     => env('CLEAN_FILE_SERVER_USER', 'user3'),
        'username' => env('CLEAN_FILE_SERVER_USERNAME', 'user3'),
        'port'     => env('CLEAN_FILE_SERVER_PORT', 22),
        'ssh_key'  => env('CLEAN_FILE_SERVER_SSH_KEY', '/var/www/.ssh/id_rsa'),
    ],

    'unscanned_file_server' => [
        'host'        => env('UNSCANNED_FILE_SERVER_HOST', '34.81.79.232'),
        'username'    => env('UNSCANNED_FILE_SERVER_USERNAME', 'user3'),
        'port'        => env('UNSCANNED_FILE_SERVER_PORT', 22),
        'ssh_key'     => env('UNSCANNED_FILE_SERVER_SSH_KEY', '/home/user3/.ssh/id_rsa'), // SSH key path for authentication
        'target_host' => env('UNSCANNED_FILE_TARGET_HOST', '192.168.0.10'), // Private IP for second server
        'target_user' => env('UNSCANNED_FILE_TARGET_USERNAME', 'user3'),
        'target_port' => env('UNSCANNED_FILE_TARGET_PORT', 22),
    ],

    'crawler_api' => [
        'url'     => env('CRAWLER_API_URL', 'https://34.80.34.114/api/v1'),
        'key'     => env('CRAWLER_API_KEY'),
        'timeout' => env('CRAWLER_API_TIMEOUT', 30),
    ],

];
