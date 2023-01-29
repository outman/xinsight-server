<?php

declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            return new Settings([
                'displayErrorDetails' => true, // Should be set to false in production
                'logError'            => false,
                'logErrorDetails'     => false,
                'logger' => [
                    'name' => 'xinsight',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],
                'mongodb' => [
                    'xhprof' => [
                        'server' => $_ENV['MONGODB_SERVER'] ?? '',
                        'options' => str_split_to_options($_ENV['MONGODB_OPTIONS'] ?? ''),
                        'driverOptions' => str_split_to_options($_ENV['MONGODB_PASSWORD'] ?? ''),
                        'database' => $_ENV['MONGODB_NAME'] ?? '',
                        'collection' => $_ENV['MONGODB_COLLECTION'] ?? '',
                    ],
                ]
            ]);
        }
    ]);
};
