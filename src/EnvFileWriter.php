<?php

declare(strict_types=1);

namespace AmazeeIo\AmazeeAiConfigure;

use Psr\Log\LoggerInterface;

/**
 * Service for writing environment variables to .env.local file.
 */
final class EnvFileWriter implements EnvWriterInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $projectDir,
    ) {
    }

    public function write(array $variables): void
    {
        $envLocalPath = $this->projectDir.'/.env.local';
        $existingContent = '';

        if (file_exists($envLocalPath)) {
            $existingContent = file_get_contents($envLocalPath);
            if (false === $existingContent) {
                throw new \RuntimeException('Unable to read existing .env.local file');
            }
        }

        $lines = '' !== $existingContent ? explode("\n", $existingContent) : [];
        $existingKeys = [];

        $amazeeVars = [];
        $otherVars = [];
        foreach ($variables as $key => $value) {
            if (str_starts_with($key, 'AMAZEEAI_')) {
                $amazeeVars[$key] = $value;
            } else {
                $otherVars[$key] = $value;
            }
        }

        // Parse existing lines to find which keys already exist.
        foreach ($lines as $index => $line) {
            $line = trim($line);
            if ('' === $line || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                $parts = explode('=', $line, 2);
                $key = trim($parts[0]);
                $existingKeys[$key] = $index;
            }
        }

        // Update existing keys or prepare new ones.
        $newLines = [];
        foreach ($otherVars as $key => $value) {
            $formattedLine = $this->formatEnvLine($key, $value);
            if (isset($existingKeys[$key])) {
                // Update existing line.
                $lines[$existingKeys[$key]] = $formattedLine;
            } else {
                // Add to new lines.
                $newLines[] = $formattedLine;
            }
        }

        // Combine existing (updated) lines with new lines.
        $finalContent = implode("\n", $lines);
        if (!empty($newLines)) {
            if ('' !== $finalContent && !str_ends_with($finalContent, "\n")) {
                $finalContent .= "\n";
            }
            $finalContent .= implode("\n", $newLines);
        }

        $finalContent = $this->upsertAmazeeBlock($finalContent, $amazeeVars);

        // Ensure file ends with newline.
        if ('' !== $finalContent && !str_ends_with($finalContent, "\n")) {
            $finalContent .= "\n";
        }

        $result = file_put_contents($envLocalPath, $finalContent);
        if (false === $result) {
            throw new \RuntimeException('Unable to write to .env.local file');
        }

        $this->logger->info('Updated .env.local with Amazee AI configuration');
    }

    /**
     * Format a key-value pair as an environment variable line.
     */
    private function formatEnvLine(string $key, string $value): string
    {
        // Quote values that contain spaces or special characters.
        if (preg_match('/[\s#\$]/', $value) || str_contains($value, '=')) {
            $value = '"'.addslashes($value).'"';
        }

        return "{$key}={$value}";
    }

    /**
     * Insert or update the Amazee AI block in the env content.
     *
     * @param array<string, string> $variables
     */
    private function upsertAmazeeBlock(string $content, array $variables): string
    {
        if (empty($variables)) {
            return $content;
        }

        $blockLines = [
            '###> amazee.ai ###',
        ];

        foreach ($variables as $key => $value) {
            $blockLines[] = $this->formatEnvLine($key, $value);
        }

        $blockLines[] = '###< amazee.ai ###';

        $block = implode("\n", $blockLines);

        if ('' !== $content && !str_ends_with($content, "\n")) {
            $content .= "\n";
        }

        $pattern = '/###> amazee\.ai ###.*?###< amazee\.ai ###/s';
        if (preg_match($pattern, $content)) {
            return preg_replace($pattern, $block, $content) ?? $content;
        }

        if ('' !== $content) {
            return $content."\n".$block;
        }

        return $block;
    }
}
