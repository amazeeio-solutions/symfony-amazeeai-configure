<?php

declare(strict_types=1);

namespace AmazeeIo\AmazeeAiConfigure;

use AmazeeIo\AmazeeAiConfigure\Exception\AmazeeAiApiException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Configuration helpers for Amazee AI configure command.
 */
final class AmazeeAiConfiguration implements AmazeeAiConfigurationInterface
{
    public function __construct(
        private readonly AmazeeAiClientInterface $client,
        // @todo refactor
        private readonly EnvWriterInterface $envFileWriter,
        private readonly EnvWriterInterface $secretsWriter,
    ) {
    }

    public function isConfigured(): bool
    {
        // Checks if the environment variables are already set.
        // We just need to check env vars for AMAZEEAI_LLM_API_URL and AMAZEEAI_VDB_HOST.
        // If both are set, we can consider the configuration complete.
        $llmApiUrl = $this->getEnvValue('AMAZEEAI_LLM_API_URL');
        $vdbHost = $this->getEnvValue('AMAZEEAI_VDB_HOST');

        return !empty($llmApiUrl) && !empty($vdbHost);
    }

    public function useSecrets(): bool
    {
        // Enforce using secrets for production based on APP_ENV.
        $environment = $this->getEnvValue('APP_ENV') ?? 'dev';

        return \in_array($environment, ['prod', 'production'], true);
    }

    public function requestVerificationCode(string $email): void
    {
        $this->client->requestCode($email);
    }

    public function promptVerificationCode(
        QuestionHelper $helper,
        SymfonyStyle $io,
        InputInterface $input,
        OutputInterface $output,
    ): string {
        $question = new Question('Enter the verification code from your email: ');
        $question->setValidator(static function ($answer) {
            if (empty($answer) || !preg_match('/^[A-Z0-9]{8}$/', trim($answer))) {
                throw new \RuntimeException('Please enter a valid verification code (8 characters).');
            }

            return trim($answer);
        });
        $question->setMaxAttempts(3);

        try {
            return $helper->ask($input, $output, $question);
        } catch (\RuntimeException $e) {
            $io->error('Too many invalid attempts.');
            throw $e;
        }
    }

    public function authenticate(string $email, string $code): string
    {
        $token = $this->client->validateCode($email, $code);
        if (null === $token) {
            throw new AmazeeAiApiException('Invalid or expired verification code.');
        }

        $this->client->setToken($token);

        return $token;
    }

    public function fetchTeamId(): int
    {
        if (!$this->client->authorized()) {
            throw new AmazeeAiApiException('Failed to fetch team ID.');
        }

        return $this->client->getTeamId();
    }

    public function resolvePrivateKey(
        QuestionHelper $helper,
        SymfonyStyle $io,
        InputInterface $input,
        OutputInterface $output,
        string $privateKeyName,
        int $teamId,
    ): array {
        $existingKeys = $this->client->getPrivateApiKeys();
        $existingKey = null;

        foreach ($existingKeys as $key) {
            if (($key->name ?? '') === $privateKeyName) {
                $existingKey = $key;
                break;
            }
        }

        if (null !== $existingKey) {
            $io->note("Found existing API key for '{$privateKeyName}'");

            return [
                'litellm_token' => $existingKey->litellm_token ?? '',
                'litellm_api_url' => $existingKey->litellm_api_url ?? '',
                'database_host' => $existingKey->database_host ?? null,
                'database_port' => $existingKey->database_port ?? null,
                'database_name' => $existingKey->database_name ?? null,
                'database_username' => $existingKey->database_username ?? null,
                'database_password' => $existingKey->database_password ?? null,
            ];
        }

        $io->text('No existing key found. Creating a new one...');

        $regions = $this->client->getRegions();
        if (empty($regions)) {
            throw new AmazeeAiApiException('No regions available. Please contact amazee.ai support.');
        }

        $regionChoices = array_values($regions);
        $regionQuestion = new ChoiceQuestion(
            'Select a region for your AI infrastructure:',
            $regionChoices,
            0,
        );
        $selectedRegionName = $helper->ask($input, $output, $regionQuestion);
        $selectedRegionId = array_search($selectedRegionName, $regions, true);

        if (false === $selectedRegionId) {
            throw new AmazeeAiApiException('Invalid region selected.');
        }

        $io->text("Creating API key in region: <info>{$selectedRegionName}</info>...");

        $apiKeyData = $this->client->createPrivateAiKey((string) $selectedRegionId, $privateKeyName, $teamId);
        $io->success('API key created successfully!');

        return $apiKeyData;
    }

    public function persistConfiguration(array $apiKeyData, bool $useSecrets): void
    {
        $envVars = [
            'AMAZEEAI_LLM_KEY' => $apiKeyData['litellm_token'],
            'AMAZEEAI_LLM_API_URL' => $apiKeyData['litellm_api_url'],
        ];

        // Add VectorDB credentials if available.
        if (!empty($apiKeyData['database_host'])) {
            $envVars['AMAZEEAI_VDB_HOST'] = $apiKeyData['database_host'];
            $envVars['AMAZEEAI_VDB_PORT'] = (string) $apiKeyData['database_port'];
            $envVars['AMAZEEAI_VDB_NAME'] = $apiKeyData['database_name'] ?? '';
            $envVars['AMAZEEAI_VDB_USER'] = $apiKeyData['database_username'] ?? '';
            $envVars['AMAZEEAI_VDB_PASSWORD'] = $apiKeyData['database_password'] ?? '';
        }

        if ($useSecrets) {
            $secretVars = [
                'AMAZEEAI_LLM_KEY' => $envVars['AMAZEEAI_LLM_KEY'],
            ];

            if (!empty($envVars['AMAZEEAI_VDB_PASSWORD'])) {
                $secretVars['AMAZEEAI_VDB_PASSWORD'] = $envVars['AMAZEEAI_VDB_PASSWORD'];
            }

            // Store only sensitive values in secrets.
            $this->secretsWriter->write($secretVars);

            unset($envVars['AMAZEEAI_LLM_KEY'], $envVars['AMAZEEAI_VDB_PASSWORD']);
        }

        $this->envFileWriter->write($envVars);
    }

    private function getEnvValue(string $key): ?string
    {
        $value = $_SERVER[$key] ?? $_ENV[$key] ?? getenv($key);

        if (empty($value)) {
            return null;
        }

        return trim((string) $value);
    }
}
