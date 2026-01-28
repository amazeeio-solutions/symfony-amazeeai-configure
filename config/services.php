<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AmazeeIO\AmazeeAIConfigure\AmazeeAiClient;
use AmazeeIO\AmazeeAIConfigure\AmazeeAiClientInterface;
use AmazeeIO\AmazeeAIConfigure\AmazeeAiConfiguration;
use AmazeeIO\AmazeeAIConfigure\AmazeeAiConfigurationInterface;
use AmazeeIO\AmazeeAIConfigure\Command\AmazeeAiConfigureCommand;
use AmazeeIO\AmazeeAIConfigure\EnvFileWriter;
use AmazeeIO\AmazeeAIConfigure\SecretsWriter;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // Register the HTTP client for Amazee AI.
    $services->set(AmazeeAiClientInterface::class, AmazeeAiClient::class)
        ->args([
            service('http_client'),
            service('logger'),
            null, // host will use default.
        ]);

    // Register EnvFileWriter.
    $services->set('amazee_ai.env_file_writer', EnvFileWriter::class)
        ->args([
            service('logger'),
            param('kernel.project_dir'),
        ]);

    // Register SecretsWriter.
    $services->set('amazee_ai.secrets_writer', SecretsWriter::class);

    // Register the configuration service.
    $services->set(AmazeeAiConfigurationInterface::class, AmazeeAiConfiguration::class)
        ->args([
            service(AmazeeAiClientInterface::class),
            service('amazee_ai.env_file_writer'),
            service('amazee_ai.secrets_writer'),
        ]);

    // Register the command.
    $services->set(AmazeeAiConfigureCommand::class)
        ->args([
            service(AmazeeAiConfigurationInterface::class),
            service('logger'),
        ])
        ->tag('console.command');
};
