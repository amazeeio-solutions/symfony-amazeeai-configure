<?php

declare(strict_types=1);

namespace AmazeeIo\AmazeeAiConfigure;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Interface for Amazee AI Configuration.
 */
interface AmazeeAiConfigurationInterface
{
    /**
     * Postgres default port.
     */
    public const VDB_PORT_DEFAULT = 5_432;

    /**
     * Checks if there is an existing configuration.
     */
    public function isConfigured(): bool;

    /**
     * Switches between secrets and .env.
     *
     * Negotiated based on the APP_ENV value.
     */
    public function useSecrets(): bool;

    /**
     * Request a verification code for the given email.
     */
    public function requestVerificationCode(string $email): void;

    /**
     * Prompt the user for the verification code.
     */
    public function promptVerificationCode(
        QuestionHelper $helper,
        SymfonyStyle $io,
        InputInterface $input,
        OutputInterface $output,
    ): string;

    /**
     * Authenticate with a verification code and return the access token.
     */
    public function authenticate(string $email, string $code): string;

    /**
     * Fetch the current team id from the API.
     */
    public function fetchTeamId(): int;

    /**
     * Resolve an existing key or create a new one.
     *
     * @return array<string, mixed>
     */
    public function resolvePrivateKey(
        QuestionHelper $helper,
        SymfonyStyle $io,
        InputInterface $input,
        OutputInterface $output,
        string $privateKeyName,
        int $teamId,
    ): array;

    /**
     * Persist configuration values to .env.local or secrets.
     *
     * @param array<string, mixed> $apiKeyData
     */
    public function persistConfiguration(array $apiKeyData, bool $useSecrets): void;
}
