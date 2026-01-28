<?php

declare(strict_types=1);

namespace AmazeeIo\AmazeeAiConfigure;

/**
 * Interface for writing environment variables.
 */
interface EnvWriterInterface
{
    /**
     * Write or update environment variables in .env.local file.
     *
     * @param array<string, string> $variables key-value pairs to write
     *
     * @throws \RuntimeException when the file cannot be written
     */
    public function write(array $variables): void;
}
