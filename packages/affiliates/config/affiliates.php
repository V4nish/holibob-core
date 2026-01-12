<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Affiliate Providers
    |--------------------------------------------------------------------------
    |
    | Configuration for affiliate property providers. Each provider can have
    | different adapter classes and configuration requirements.
    |
    */

    'providers' => [
        'sykes' => [
            'name' => 'Sykes Cottages',
            'adapter_class' => \Holibob\Affiliates\Providers\SykesProvider::class,
            'enabled' => env('SYKES_ENABLED', false),
            'config' => [
                'affiliate_id' => env('SYKES_AFFILIATE_ID'),
                'feed_url' => env('SYKES_FEED_URL'),
                'feed_format' => env('SYKES_FEED_FORMAT', 'csv'), // csv or xml
                'affiliate_base_url' => env('SYKES_BASE_URL', 'https://www.sykescottages.co.uk'),
                'commission_rate' => env('SYKES_COMMISSION_RATE', 5.0),
            ],
        ],

        'hoseasons' => [
            'name' => 'Hoseasons',
            'adapter_class' => \Holibob\Affiliates\Providers\HoseasonsProvider::class,
            'enabled' => env('HOSEASONS_ENABLED', false),
            'config' => [
                'api_key' => env('HOSEASONS_API_KEY'),
                'affiliate_id' => env('HOSEASONS_AFFILIATE_ID'),
                'api_base_url' => env('HOSEASONS_API_URL', 'https://api.hoseasons.co.uk'),
                'commission_rate' => env('HOSEASONS_COMMISSION_RATE', 5.0),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    |
    | Global sync settings that apply to all providers.
    |
    */

    'sync' => [
        // Default sync frequency
        'default_frequency' => 'daily',

        // Sync timeout in seconds
        'timeout' => 3600,

        // Number of retries on failure
        'retries' => 3,

        // Should sync jobs be queued?
        'queue' => true,

        // Queue name for sync jobs
        'queue_name' => 'affiliates',
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Transformation
    |--------------------------------------------------------------------------
    |
    | Settings for data transformation and validation.
    |
    */

    'transformation' => [
        // Minimum required fields for a valid property
        'required_fields' => [
            'external_id',
            'name',
            'sleeps',
            'bedrooms',
        ],

        // Should invalid properties be skipped?
        'skip_invalid' => true,

        // Should images be downloaded and stored locally?
        'download_images' => false,
    ],
];
