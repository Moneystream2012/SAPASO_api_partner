<?php
$appRootDir = realpath(__DIR__.'/..');

date_default_timezone_set('Europe/Berlin');

return [
    'settings' => [
        'mode' => getenv('ENV'),
        'log.enable' => (bool)getenv('ENABLE_LOG'), // set to false in production
        'debug' => (bool)getenv('ENABLE_DEBUG'), // set to false in production
        'displayErrorDetails' =>  (bool)getenv('DISPLAY_ERRORS'), // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        'appRootDir' => $appRootDir,

        // Company Data
        'sapaso' => [
            'companyName' => 'Sapaso GmbH',
            'iban' => 'DE89370400440532013000',
            'bic' => 'DUMYDEFFYYY',
            'bankId' => '0000UNKNOWN0000',
            'businessId' => '123abc456def789abc012de345fa678cbiz',
        ],
        
        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],
        // app internal logger - Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => $appRootDir.'/logs/'.getenv('ENV').'.log', // $_ENV['ENV'] = "production|development|test"
            'level' => \Monolog\Logger::DEBUG,
        ],
        // requests logger - Monolog settings
        'requests_logger' => [
            'name' => 'requests-logger',
            'path' => $appRootDir.'/logs/requests.'.getenv('ENV').'.log', // $_ENV['ENV'] = "production|development|test"
            'level' => \App\Middleware\RequestsLogger::LOG_LEVEL_ONLY_CALLER_AND_URI,
        ],
        'doctrine' => [
            'meta' => [
                'entity_path' => [
                    'src/Entity'
                ],
                'auto_generate_proxies' => true,
                'proxy_dir' =>  __DIR__.'/../cache/proxies',
                'cache' => null,
            ],
            'connection' => [
                'mysql' => [
                    'driver'   => 'pdo_mysql',
                    'host'     => getenv('DB_HOST') . (getenv('DB_PORT')? ':'.getenv('DB_PORT') :'') ,
                    'dbname'   => getenv('DB_NAME'),
                    'user'     => getenv('DB_USER'),
                    'password' => getenv('DB_PASSWORD'),
                    'charset'  => 'UTF8'
                ]
            ]
        ],
        // Solaris bank
        'solaris' => [
            'api' => (getenv('ENV') == 'production') ? 'https://api.solarisbank.de' : 'https://api.solaris-sandbox.de',
            'client_id_escrow' => getenv('SOLARIS_ID_ESCROW'),
            'client_secret_escrow' => getenv('SOLARIS_SECRET_ESCROW'),
            'client_id_ebank' => getenv('SOLARIS_ID_EBANK'),
            'client_secret_ebank' => getenv('SOLARIS_SECRET_EBANK')
        ],
        // Ikaros API
        'ikaros' => [
            'api' => (getenv('ENV') == 'production') ? 'https://213.182.10.92:443/api/rest' : 'https://213.182.10.92:443/api/rest',
            'username' => getenv('IKAROS_USERNAME'),
            'password' => getenv('IKAROS_PASSWORD')
        ],
        // Credit score
        'credit_score' => [
            'score_validity_days' => 365,
            'providers' => [
                'mexxon' => [
                    'username' => getenv('MEXXON_USERNAME'),
                    'password' => getenv('MEXXON_PASSWORD'),
                    'product_id' => (getenv('ENV') == 'production')?17062:17061
                ],
                'bisnode' => [
                    'username' => getenv('BISNODE_USERNAME'),
                    'password' => getenv('BISNODE_PASSWORD'),
                    'abo_id' => getenv('BISNODE_ABO_ID'),
                    'api' => getenv('BISNODE_URL'),
                    'aws_at_bisnode_bucket' => getenv('AWS_AT_BISNODE_BUCKET')
                ]
            ]
        ],
        // Pagination
        'pagination' => [
            'limit' => 10,
        ],
        // Basepath
        'baseUri'  => getenv('BASE_URI') ?? null,
        'basePath' => '/bank',
        'bankApiKey' => getenv('BANK_API_KEY'),

        'google' => [
            'api_key' => getenv('GOOGLE_API_KEY') ?: ''
        ],
        'aws' => [
            'connection' => [
                'credentials' => [
                    'key'    => getenv('AWS_ACCESS_KEY_ID'),
                    'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
                ],
                'region' => getenv('AWS_REGION'),
                'version' => 'latest'
            ],
            'cloudWatchLogGroup' => getenv('AWS_CLOUD_WATCH_LOG_GROUP') ?: 'api-bank',
            'cloudWatchGeneralLogPrefix' => getenv('AWS_CLOUD_WATCH_LOG_GENERAL_PREFIX') ?: 'general',
            'cloudWatchRequestsLogPrefix' => getenv('AWS_CLOUD_WATCH_LOG_REQUESTS_PREFIX') ?: 'requests',
            'cloudWatchSolarisLogPrefix' => getenv('AWS_CLOUD_WATCH_LOG_SOLARIS_PREFIX') ?: 'solaris',
            'cloudWatchCreditscoreLogPrefix' => getenv('AWS_CLOUD_WATCH_LOG_CREDITSCORE_PREFIX') ?: 'creditscore',
            'cloudWatchRetentionDays' => 90, // days
            'customerPayinQueueName' => getenv('AWS_CUSTOMER_PAYIN_QUEUE_NAME') ?: 'customerPayinQueue',
        ]
    ],
];
