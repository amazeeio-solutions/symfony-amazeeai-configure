<?php

declare(strict_types=1);

namespace AmazeeIo\AmazeeAiConfigure\Tests\Command;

use AmazeeIo\AmazeeAiConfigure\AmazeeAiClient;
use AmazeeIo\AmazeeAiConfigure\AmazeeAiConfiguration;
use AmazeeIo\AmazeeAiConfigure\Command\AmazeeAiConfigureCommand;
use AmazeeIo\AmazeeAiConfigure\EnvFileWriter;
use AmazeeIo\AmazeeAiConfigure\EnvWriterInterface;
use AmazeeIo\AmazeeAiConfigure\Tests\Mock\AmazeeApiMockResponses;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(AmazeeAiConfigureCommand::class)]
final class AmazeeAiConfigureCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/amazee_test_'.uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Clean up temp directory.
        if (file_exists($this->tempDir.'/.env.local')) {
            unlink($this->tempDir.'/.env.local');
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    /**
     * @param array<\Symfony\Component\HttpClient\Response\MockResponse> $responses
     */
    private function createCommand(array $responses, bool $useSecrets = false, ?TestSecretsWriter $secretsWriter = null): CommandTester
    {
        $this->setAppEnv($useSecrets ? 'prod' : 'dev');
        $mockHttpClient = AmazeeApiMockResponses::createMockClient($responses);
        $logger = new NullLogger();
        $client = new AmazeeAiClient($mockHttpClient, $logger, 'https://api.amazee.ai');
        $envWriter = new EnvFileWriter($logger, $this->tempDir);
        $secretsWriter = $secretsWriter ?? new TestSecretsWriter();

        $configuration = new AmazeeAiConfiguration(
            $client,
            $envWriter,
            $secretsWriter,
        );

        $command = new AmazeeAiConfigureCommand(
            $configuration,
            $logger,
        );

        $application = new Application();
        $application->addCommand($command);

        return new CommandTester($application->find('ai:amazee:configure'));
    }

    #[Test]
    public function itRejectsInvalidEmail(): void
    {
        $commandTester = $this->createCommand([]);

        $commandTester->execute(['email' => 'not-an-email']);

        $this->assertSame(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('Invalid email address', $commandTester->getDisplay());
    }

    #[Test]
    public function itWritesEnvLocalWhenNotUsingSecrets(): void
    {
        $commandTester = $this->createCommand([
            AmazeeApiMockResponses::validateEmailSuccess(),
            AmazeeApiMockResponses::signInSuccess(),
            AmazeeApiMockResponses::authMeSuccess(),
            AmazeeApiMockResponses::getPrivateAiKeysEmpty(),
            AmazeeApiMockResponses::regionsSuccess(),
            AmazeeApiMockResponses::createPrivateAiKeySuccess(),
        ]);
        $commandTester->setInputs([
            AmazeeApiMockResponses::VALID_CODE,
            '0',
        ]);
        $commandTester->execute(['email' => AmazeeApiMockResponses::TEST_EMAIL]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Amazee.ai provider has been configured successfully', $commandTester->getDisplay());

        // Verify .env.local was created.
        $envLocalPath = $this->tempDir.'/.env.local';
        $this->assertFileExists($envLocalPath);

        $content = file_get_contents($envLocalPath);
        $this->assertIsString($content);
        $this->assertStringContainsString('AMAZEEAI_LLM_KEY=', $content);
        $this->assertStringContainsString('AMAZEEAI_LLM_API_URL=', $content);
    }

    #[Test]
    public function itWritesSecretsWhenUsingSecrets(): void
    {
        $secretsWriter = new TestSecretsWriter();
        $commandTester = $this->createCommand([
            AmazeeApiMockResponses::validateEmailSuccess(),
            AmazeeApiMockResponses::signInSuccess(),
            AmazeeApiMockResponses::authMeSuccess(),
            AmazeeApiMockResponses::getPrivateAiKeysEmpty(),
            AmazeeApiMockResponses::regionsSuccess(),
            AmazeeApiMockResponses::createPrivateAiKeySuccess(),
        ], true, $secretsWriter);

        $commandTester->setInputs([
            AmazeeApiMockResponses::VALID_CODE,
            '0',
        ]);
        $commandTester->execute([
            'email' => AmazeeApiMockResponses::TEST_EMAIL,
        ]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertCount(1, $secretsWriter->calls);

        // Only sensitive values should be in secrets.
        $secretEnvVars = $secretsWriter->calls[0]['variables'];
        $this->assertArrayHasKey('AMAZEEAI_LLM_KEY', $secretEnvVars);
        $this->assertArrayNotHasKey('AMAZEEAI_LLM_API_URL', $secretEnvVars);

        // Non-sensitive values should still be written to .env.local.
        $envLocalPath = $this->tempDir.'/.env.local';
        $this->assertFileExists($envLocalPath);
        $content = file_get_contents($envLocalPath);
        $this->assertIsString($content);
        $this->assertStringContainsString('AMAZEEAI_LLM_API_URL=', $content);
        $this->assertStringNotContainsString('AMAZEEAI_LLM_KEY=', $content);
    }

    #[Test]
    public function itHasCorrectCommandConfiguration(): void
    {
        $mockHttpClient = AmazeeApiMockResponses::createMockClient([]);
        $logger = new NullLogger();
        $client = new AmazeeAiClient($mockHttpClient, $logger, 'https://api.amazee.ai');
        $envWriter = new EnvFileWriter($logger, $this->tempDir);
        $secretsWriter = new TestSecretsWriter();

        $configuration = new AmazeeAiConfiguration(
            $client,
            $envWriter,
            $secretsWriter,
        );

        $command = new AmazeeAiConfigureCommand(
            $configuration,
            $logger,
        );

        $this->assertSame('ai:amazee:configure', $command->getName());
        $this->assertSame('Configures the amazee.ai provider via email-based authentication.', $command->getDescription());
        $this->assertTrue($command->getDefinition()->hasArgument('email'));
        $this->assertTrue($command->getDefinition()->hasOption('private-key-name'));
        $this->assertTrue($command->getDefinition()->hasOption('test-mode'));
        $this->assertTrue($command->getDefinition()->hasArgument('test-api-host'));
    }

    private function setAppEnv(string $value): void
    {
        $_SERVER['APP_ENV'] = $value;
        $_ENV['APP_ENV'] = $value;
        putenv('APP_ENV='.$value);
    }
}

final class TestSecretsWriter implements EnvWriterInterface
{
    /** @var array<int, array{variables: array<string, string>}> */
    public array $calls = [];

    public function write(array $variables): void
    {
        $this->calls[] = [
            'variables' => $variables,
        ];
    }
}
