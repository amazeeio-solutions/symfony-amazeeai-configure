<?php

declare(strict_types=1);

namespace AmazeeIO\AmazeeAIConfigure;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Writes Symfony secrets via the console command.
 */
final class SecretsWriter implements EnvWriterInterface
{
    public function write(array $variables): void
    {
        foreach ($variables as $key => $value) {
            $process = new Process([
                'php',
                'bin/console',
                'secrets:set',
                $key,
                '--no-interaction',
            ]);

            $process->setInput($value."\n");
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        }
    }
}
