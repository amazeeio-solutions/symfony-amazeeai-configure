<?php

declare(strict_types=1);

namespace AmazeeIO\AmazeeAIConfigure\Command;

use AmazeeIO\AmazeeAIConfigure\AmazeeAiConfigurationInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ai:amazee:configure',
    description: 'Configures the amazee.ai provider via email-based authentication.',
)]
final class AmazeeAiConfigureCommand extends Command
{
    public function __construct(
        private readonly AmazeeAiConfigurationInterface $configuration,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'email',
                InputArgument::REQUIRED,
                'The email address to use for authentication',
            )
            ->addOption(
                'private-key-name',
                'pk',
                InputOption::VALUE_OPTIONAL,
                'Private key name for (defaults to hostname)',
                gethostname() ?: 'amazee_ai',
            )
            ->addOption(
                'test-mode',
                't',
                InputOption::VALUE_NONE,
                'Enable test mode.',
            )
            ->addArgument(
                'test-api-host',
                InputArgument::OPTIONAL,
                'Test API hostname. To be used in test mode, for development purpose only.',
            )
            ->setHelp(<<<'HELP'
                The <info>%command.name%</info> command configures the amazee.ai provider.

                <info>php %command.full_name% user@example.com</info>
                HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');
        $email = $input->getArgument('email');
        $useSecrets = $this->configuration->useSecrets();
        $isTestMode = $input->getOption('test-mode');
        $testApiHostname = $input->getArgument('test-api-host');
        $privateKeyName = $input->getOption('private-key-name');

        $io->title('amazee.ai Provider Configuration');

        // Check if amazee.ai is already configured.
        if ($this->configuration->isConfigured()) {
            $io->warning('An existing amazee.ai configuration was detected.');
            if (!$io->confirm('Do you want to overwrite the existing configuration?', false)) {
                $io->text('No changes were made.');

                return Command::SUCCESS;
            }
        }

        // Validate email format.
        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $io->error('Invalid email address provided.');

            return Command::FAILURE;
        }

        if ($isTestMode && empty($testApiHostname)) {
            $io->error('Test API hostname cannot be empty when using the test mode.');
        }

        // Step 1: Request verification code.
        $io->section('1. Email Verification');
        $io->text("Sending verification code to <info>{$email}</info>...");

        try {
            $this->configuration->requestVerificationCode($email);
            $io->success('Verification code sent! Check your inbox.');
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to request verification code', ['error' => $e->getMessage()]);
            $io->error('Failed to send verification code: '.$e->getMessage());

            return Command::FAILURE;
        }

        // Step 2: Prompt for verification code.
        $io->section('2. Enter Verification Code');
        try {
            $code = $this->configuration->promptVerificationCode(
                $questionHelper,
                $io,
                $input,
                $output,
            );
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to validate code', ['error' => $e->getMessage()]);
            $io->error('Failed to validate code: '.$e->getMessage());

            return Command::FAILURE;
        }

        // Validate code and get the access token.
        $io->text('Validating code...');

        try {
            $this->configuration->authenticate($email, $code);
            $io->success('Authentication successful!');
        } catch (\RuntimeException $e) {
            $this->logger->error('Authentication failed', ['error' => $e->getMessage()]);
            $io->error('Authentication failed: '.$e->getMessage());

            return Command::FAILURE;
        }

        // Step 3: Get user info and team_id.
        $io->section('3. Fetching Account Info');

        try {
            $teamId = $this->configuration->fetchTeamId();
            $io->text("Team ID: <info>{$teamId}</info>");
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to fetch team ID', ['error' => $e->getMessage()]);
            $io->error('Failed to fetch team ID');

            return Command::FAILURE;
        }

        // Step 4: Check for existing keys.
        $io->section('4. API Key Configuration');

        try {
            $apiKeyData = $this->configuration->resolvePrivateKey(
                $questionHelper,
                $io,
                $input,
                $output,
                $privateKeyName,
                $teamId,
            );
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to configure API key', ['error' => $e->getMessage()]);
            $io->error('Failed to configure API key: '.$e->getMessage());

            return Command::FAILURE;
        }

        $io->section('Step 5: Saving Configuration');

        try {
            $this->configuration->persistConfiguration($apiKeyData, $useSecrets);
            $io->success($useSecrets ? 'Configuration saved to Symfony secrets.' : 'Configuration saved to .env.local');
        } catch (\RuntimeException $e) {
            $this->logger->error('Failed to save configuration', ['error' => $e->getMessage()]);
            $io->error('Failed to save configuration: '.$e->getMessage());

            return Command::FAILURE;
        }

        // Summary.
        $io->section('Configuration Complete');
        $io->definitionList(
            ['LLM API URL' => $apiKeyData['litellm_api_url']],
            ['LLM Key' => substr($apiKeyData['litellm_token'], 0, 20).'...'],
            ['VectorDB Host' => $apiKeyData['database_host'] ?? 'N/A'],
        );

        $io->success('Amazee.ai provider has been configured successfully!');
        $io->text([
            'You can now use the amazee.ai provider with Symfony AI.',
            $useSecrets
                ? 'The credentials are stored as Symfony secrets.'
                : 'The credentials are stored in <info>.env.local</info>.',
            '',
            'For production deployments, consider using Symfony secrets for these values:',
            '  <info>php bin/console secrets:set AMAZEEAI_LLM_KEY</info>',
            '  <info>php bin/console secrets:set AMAZEEAI_VDB_PASSWORD</info>',
        ]);

        return Command::SUCCESS;
    }
}
