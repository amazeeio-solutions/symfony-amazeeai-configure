<?php

declare(strict_types=1);

namespace AmazeeIO\AmazeeAIConfigure\Tests\Service;

use AmazeeIO\AmazeeAIConfigure\AmazeeAiClient;
use AmazeeIO\AmazeeAIConfigure\Exception\AmazeeAiApiException;
use AmazeeIO\AmazeeAIConfigure\Tests\Mock\AmazeeApiMockResponses;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(AmazeeAiClient::class)]
final class AmazeeAiClientTest extends TestCase
{
    /**
     * @param array<\Symfony\Component\HttpClient\Response\MockResponse> $responses
     */
    private function createClient(array $responses): AmazeeAiClient
    {
        $mockHttpClient = AmazeeApiMockResponses::createMockClient($responses);
        $logger = new NullLogger();

        return new AmazeeAiClient($mockHttpClient, $logger, 'api.amazee.ai');
    }

    #[Test]
    public function itCanSetAndGetToken(): void
    {
        $client = $this->createClient([]);

        $this->assertSame('', $client->getToken());

        $client->setToken('test-token');
        $this->assertSame('test-token', $client->getToken());
    }

    #[Test]
    public function itCanSetAndGetHost(): void
    {
        $client = $this->createClient([]);

        $this->assertSame('api.amazee.ai', $client->getHost());

        $client->setHost('custom.api.amazee.ai');
        $this->assertSame('custom.api.amazee.ai', $client->getHost());
    }

    #[Test]
    public function itCanLoginSuccessfully(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::loginSuccess(),
        ]);

        $token = $client->login('user@example.com', 'password123');

        $this->assertSame(AmazeeApiMockResponses::VALID_ACCESS_TOKEN, $token);
    }

    #[Test]
    public function itReturnsEmptyStringForInvalidLoginCredentials(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::loginInvalidCredentials(),
        ]);

        $token = $client->login('user@example.com', 'wrong_password');

        $this->assertSame('', $token);
    }

    #[Test]
    public function itCanLogoutSuccessfully(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::logoutSuccess(),
        ]);
        $client->setToken(AmazeeApiMockResponses::VALID_ACCESS_TOKEN);

        $result = $client->logout();

        $this->assertTrue($result);
    }

    #[Test]
    public function itReturnsFalseForFailedLogout(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::logoutFailed(),
        ]);

        $result = $client->logout();

        $this->assertFalse($result);
    }

    #[Test]
    public function itCanRegisterAndLoginSuccessfully(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::registerSuccess(),
            AmazeeApiMockResponses::loginSuccess(),
        ]);

        $token = $client->register('newuser@example.com', 'password123');

        $this->assertSame(AmazeeApiMockResponses::VALID_ACCESS_TOKEN, $token);
    }

    #[Test]
    public function itReturnsEmptyStringForFailedRegistration(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::registerEmailExists(),
        ]);

        $token = $client->register('existing@example.com', 'password123');

        $this->assertSame('', $token);
    }

    #[Test]
    public function itCanRequestValidationCode(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::validateEmailSuccess(),
        ]);

        // Should not throw an exception.
        $client->requestCode(AmazeeApiMockResponses::TEST_EMAIL);

        // Test passes if no exception was thrown.
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function itThrowsExceptionForInvalidEmailOnRequestCode(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::validateEmailInvalid(),
        ]);

        $this->expectException(AmazeeAiApiException::class);

        $client->requestCode('invalid-email');
    }

    #[Test]
    public function itCanValidateCodeSuccessfully(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::signInSuccess(),
        ]);

        $token = $client->validateCode(
            AmazeeApiMockResponses::TEST_EMAIL,
            AmazeeApiMockResponses::VALID_CODE,
        );

        $this->assertSame(AmazeeApiMockResponses::VALID_ACCESS_TOKEN, $token);
    }

    #[Test]
    public function itReturnsNullForInvalidCode(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::signInInvalidCode(),
        ]);

        $token = $client->validateCode(
            AmazeeApiMockResponses::TEST_EMAIL,
            AmazeeApiMockResponses::INVALID_CODE,
        );

        $this->assertNull($token);
    }

    #[Test]
    public function itCanCheckAuthorizationSuccessfully(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::authMeSuccess(),
        ]);
        $client->setToken(AmazeeApiMockResponses::VALID_ACCESS_TOKEN);

        $isAuthorized = $client->authorized();

        $this->assertTrue($isAuthorized);
        $this->assertSame(AmazeeApiMockResponses::TEST_TEAM_ID, $client->getTeamId());
    }

    #[Test]
    public function itReturnsFalseForUnauthorized(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::authMeUnauthorized(),
        ]);

        $isAuthorized = $client->authorized();

        $this->assertFalse($isAuthorized);
        $this->assertSame(0, $client->getTeamId());
    }

    #[Test]
    public function itCanGetRegions(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::regionsSuccess(),
        ]);
        $client->setToken(AmazeeApiMockResponses::VALID_ACCESS_TOKEN);

        $regions = $client->getRegions();

        // Only active regions should be returned.
        $this->assertCount(2, $regions);
        $this->assertArrayHasKey('eu-west-1', $regions);
        $this->assertArrayHasKey('us-east-1', $regions);
        $this->assertArrayNotHasKey('ap-southeast-1', $regions); // Inactive.
        $this->assertSame('Europe (Ireland)', $regions['eu-west-1']);
    }

    #[Test]
    public function itCanCreatePrivateAiKey(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::createPrivateAiKeySuccess(),
        ]);
        $client->setToken(AmazeeApiMockResponses::VALID_ACCESS_TOKEN);

        $result = $client->createPrivateAiKey(
            AmazeeApiMockResponses::TEST_REGION_ID,
            'test-key',
            AmazeeApiMockResponses::TEST_TEAM_ID,
        );

        $this->assertSame(AmazeeApiMockResponses::TEST_LITELLM_TOKEN, $result['litellm_token']);
        $this->assertSame(AmazeeApiMockResponses::TEST_LITELLM_API_URL, $result['litellm_api_url']);
        $this->assertArrayHasKey('database_host', $result);
        $this->assertSame('db.eu-west-1.amazee.ai', $result['database_host']);
        $this->assertArrayHasKey('database_port', $result);
        $this->assertSame(5_432, $result['database_port']);
        $this->assertArrayHasKey('database_name', $result);
        $this->assertSame('vectordb_test', $result['database_name']);
        $this->assertArrayHasKey('database_username', $result);
        $this->assertSame('ai_user', $result['database_username']);
        $this->assertArrayHasKey('database_password', $result);
        $this->assertSame('secure_password_123', $result['database_password']);
    }

    #[Test]
    public function itCanCreatePrivateAiKeyWithAutoTeamId(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::authMeSuccess(), // Called to get team_id.
            AmazeeApiMockResponses::createPrivateAiKeySuccess(),
        ]);
        $client->setToken(AmazeeApiMockResponses::VALID_ACCESS_TOKEN);

        $result = $client->createPrivateAiKey(
            AmazeeApiMockResponses::TEST_REGION_ID,
            'test-key',
            null, // No team_id provided, should fetch from /auth/me.
        );

        $this->assertSame(AmazeeApiMockResponses::TEST_LITELLM_TOKEN, $result['litellm_token']);
    }

    #[Test]
    public function itCanGetPrivateApiKeys(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::getPrivateAiKeysSuccess(),
        ]);
        $client->setToken(AmazeeApiMockResponses::VALID_ACCESS_TOKEN);

        $keys = $client->getPrivateApiKeys();

        // Demo keys should be filtered out.
        $this->assertCount(1, $keys);
        $this->assertSame('production-key', $keys[0]->name);
        $this->assertSame(AmazeeApiMockResponses::TEST_LITELLM_TOKEN, $keys[0]->litellm_token);
    }

    #[Test]
    public function itReturnsEmptyArrayWhenNoPrivateKeys(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::getPrivateAiKeysEmpty(),
        ]);
        $client->setToken(AmazeeApiMockResponses::VALID_ACCESS_TOKEN);

        $keys = $client->getPrivateApiKeys();

        $this->assertCount(0, $keys);
    }

    #[Test]
    public function itCanGetSpecificPrivateApiKey(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::getPrivateAiKeysSuccess(),
        ]);
        $client->setToken(AmazeeApiMockResponses::VALID_ACCESS_TOKEN);

        $key = $client->getPrivateApiKey(AmazeeApiMockResponses::TEST_LITELLM_TOKEN);

        $this->assertNotNull($key);
        $this->assertSame('production-key', $key->name);
    }

    #[Test]
    public function itReturnsNullForNonExistentApiKey(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::getPrivateAiKeysSuccess(),
        ]);
        $client->setToken(AmazeeApiMockResponses::VALID_ACCESS_TOKEN);

        $key = $client->getPrivateApiKey('non-existent-token');

        $this->assertNull($key);
    }

    #[Test]
    public function itCanGetModels(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::modelsSuccess(),
        ]);
        $client->setToken(AmazeeApiMockResponses::VALID_ACCESS_TOKEN);

        $models = $client->getModels();

        $this->assertCount(3, $models);
        $this->assertArrayHasKey('gpt-4', $models);
        $this->assertArrayHasKey('text-embedding-ada-002', $models);
        $this->assertArrayHasKey('video-generator', $models);

        // Verify chat model properties.
        $gpt4 = $models['gpt-4'];
        $this->assertSame('gpt-4', $gpt4->name);
        $this->assertTrue($gpt4->supportsChat);
        $this->assertFalse($gpt4->supportsEmbeddings);
        $this->assertTrue($gpt4->supportsImageInput);
        $this->assertFalse($gpt4->supportsImageAndAudioToVideo);

        // Verify embedding model properties.
        $embedding = $models['text-embedding-ada-002'];
        $this->assertTrue($embedding->supportsEmbeddings);
        $this->assertFalse($embedding->supportsChat);

        // Verify video generator with image+audio to video support.
        $videoGen = $models['video-generator'];
        $this->assertTrue($videoGen->supportsImageAndAudioToVideo);
        $this->assertTrue($videoGen->supportsImageInput);
        $this->assertTrue($videoGen->supportsAudioInput);
        $this->assertTrue($videoGen->supportsVideoOutput);
    }

    #[Test]
    public function itReturnsEmptyArrayWhenNoModels(): void
    {
        $client = $this->createClient([
            AmazeeApiMockResponses::modelsEmpty(),
        ]);
        $client->setToken(AmazeeApiMockResponses::VALID_ACCESS_TOKEN);

        $models = $client->getModels();

        $this->assertCount(0, $models);
    }

    #[Test]
    public function itThrowsExceptionWhenHostNotConfigured(): void
    {
        $mockHttpClient = AmazeeApiMockResponses::createMockClient([]);
        $logger = new NullLogger();
        $client = new AmazeeAiClient($mockHttpClient, $logger, '');

        $this->expectException(AmazeeAiApiException::class);

        $client->getRegions();
    }

    #[Test]
    public function fullAuthenticationWorkflow(): void
    {
        // Test the complete authentication flow as it would happen in the CLI command.
        $client = $this->createClient([
            AmazeeApiMockResponses::validateEmailSuccess(),    // Step 1: Request code.
            AmazeeApiMockResponses::signInSuccess(),           // Step 2: Validate code.
            AmazeeApiMockResponses::authMeSuccess(),           // Step 3: Check auth and get team_id.
            AmazeeApiMockResponses::regionsSuccess(),          // Step 4: Get regions.
            AmazeeApiMockResponses::getPrivateAiKeysEmpty(),   // Step 5: Check existing keys.
            AmazeeApiMockResponses::createPrivateAiKeySuccess(), // Step 6: Create new key.
        ]);

        // Step 1: Request verification code.
        $client->requestCode(AmazeeApiMockResponses::TEST_EMAIL);

        // Step 2: Validate the code.
        $token = $client->validateCode(
            AmazeeApiMockResponses::TEST_EMAIL,
            AmazeeApiMockResponses::VALID_CODE,
        );
        $this->assertNotNull($token);
        $client->setToken($token);

        // Step 3: Check authorization and get team_id.
        $this->assertTrue($client->authorized());
        $this->assertSame(AmazeeApiMockResponses::TEST_TEAM_ID, $client->getTeamId());

        // Step 4: Get available regions.
        $regions = $client->getRegions();
        $this->assertNotEmpty($regions);

        // Step 5: Check for existing keys.
        $existingKeys = $client->getPrivateApiKeys();
        $this->assertEmpty($existingKeys);

        // Step 6: Create a new private AI key.
        $regionId = array_key_first($regions);
        $this->assertNotNull($regionId);
        $newKey = $client->createPrivateAiKey(
            (string) $regionId,
            'my-app-key',
            $client->getTeamId(),
        );
        $this->assertNotEmpty($newKey['litellm_token']);
        $this->assertNotEmpty($newKey['litellm_api_url']);
    }
}
