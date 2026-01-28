<?php

declare(strict_types=1);

namespace AmazeeIo\AmazeeAiConfigure\Tests\Mock;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Factory for creating mock HTTP responses for Amazee AI API testing.
 */
final class AmazeeApiMockResponses
{
    /**
     * Safely encode data as JSON, throwing on failure.
     */
    private static function jsonEncode(mixed $data): string
    {
        $json = json_encode($data);
        if (false === $json) {
            throw new \RuntimeException('Failed to encode JSON');
        }

        return $json;
    }

    /**
     * Valid test email address.
     */
    public const TEST_EMAIL = 'test@example.com';

    /**
     * Valid test verification code.
     */
    public const VALID_CODE = 'ABCD1234';

    /**
     * Invalid test verification code.
     */
    public const INVALID_CODE = '00000000';

    /**
     * Valid access token returned after successful sign-in.
     */
    public const VALID_ACCESS_TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.mock_token_payload';

    /**
     * Test team ID.
     */
    public const TEST_TEAM_ID = 42;

    /**
     * Test region ID.
     */
    public const TEST_REGION_ID = 'eu-west-1';

    /**
     * Test LiteLLM token.
     */
    public const TEST_LITELLM_TOKEN = 'sk-amazee-test-token-12345';

    /**
     * Test LiteLLM API URL.
     */
    public const TEST_LITELLM_API_URL = 'https://eu-west-1.litellm.amazee.ai';

    /**
     * Create a successful response for POST /auth/login.
     */
    public static function loginSuccess(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode([
                'access_token' => self::VALID_ACCESS_TOKEN,
                'token_type' => 'bearer',
            ]),
            ['http_code' => 200],
        );
    }

    /**
     * Create a failed response for POST /auth/login with invalid credentials.
     */
    public static function loginInvalidCredentials(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode(['detail' => 'Invalid credentials']),
            ['http_code' => 401],
        );
    }

    /**
     * Create a successful response for POST /auth/logout.
     */
    public static function logoutSuccess(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode(['message' => 'Successfully logged out']),
            ['http_code' => 200],
        );
    }

    /**
     * Create a failed response for POST /auth/logout.
     */
    public static function logoutFailed(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode(['detail' => 'Not authenticated']),
            ['http_code' => 401],
        );
    }

    /**
     * Create a successful response for POST /auth/register.
     */
    public static function registerSuccess(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode(['message' => 'User created successfully']),
            ['http_code' => 201],
        );
    }

    /**
     * Create a failed response for POST /auth/register (email already exists).
     */
    public static function registerEmailExists(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode(['detail' => 'Email already registered']),
            ['http_code' => 400],
        );
    }

    /**
     * Create a successful response for POST /auth/validate-email.
     */
    public static function validateEmailSuccess(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode(['message' => 'Verification code sent']),
            ['http_code' => 200],
        );
    }

    /**
     * Create an error response for POST /auth/validate-email with invalid email.
     */
    public static function validateEmailInvalid(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode(['detail' => 'Invalid email address']),
            ['http_code' => 400],
        );
    }

    /**
     * Create a successful response for POST /auth/sign-in.
     */
    public static function signInSuccess(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode([
                'access_token' => self::VALID_ACCESS_TOKEN,
                'token_type' => 'bearer',
            ]),
            ['http_code' => 200],
        );
    }

    /**
     * Create an error response for POST /auth/sign-in with invalid code.
     */
    public static function signInInvalidCode(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode(['detail' => 'Invalid or expired verification code']),
            ['http_code' => 401],
        );
    }

    /**
     * Create a successful response for GET /auth/me.
     */
    public static function authMeSuccess(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode([
                'id' => 1,
                'email' => self::TEST_EMAIL,
                'team_id' => self::TEST_TEAM_ID,
                'is_active' => true,
            ]),
            ['http_code' => 200],
        );
    }

    /**
     * Create an unauthorized response for GET /auth/me.
     */
    public static function authMeUnauthorized(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode(['detail' => 'Not authenticated']),
            ['http_code' => 401],
        );
    }

    /**
     * Create a successful response for GET /regions.
     */
    public static function regionsSuccess(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode([
                [
                    'id' => 'eu-west-1',
                    'name' => 'Europe (Ireland)',
                    'is_active' => true,
                ],
                [
                    'id' => 'us-east-1',
                    'name' => 'US East (N. Virginia)',
                    'is_active' => true,
                ],
                [
                    'id' => 'ap-southeast-1',
                    'name' => 'Asia Pacific (Singapore)',
                    'is_active' => false,
                ],
            ]),
            ['http_code' => 200],
        );
    }

    /**
     * Create a successful response for POST /private-ai-keys.
     */
    public static function createPrivateAiKeySuccess(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode([
                'id' => 1,
                'name' => 'test-key',
                'region' => self::TEST_REGION_ID,
                'litellm_token' => self::TEST_LITELLM_TOKEN,
                'litellm_api_url' => self::TEST_LITELLM_API_URL,
                'database_host' => 'db.eu-west-1.amazee.ai',
                'database_port' => 5_432,
                'database_name' => 'vectordb_test',
                'database_username' => 'ai_user',
                'database_password' => 'secure_password_123',
            ]),
            ['http_code' => 201],
        );
    }

    /**
     * Create a successful response for GET /private-ai-keys.
     */
    public static function getPrivateAiKeysSuccess(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode([
                [
                    'id' => 1,
                    'name' => 'production-key',
                    'region' => self::TEST_REGION_ID,
                    'litellm_token' => self::TEST_LITELLM_TOKEN,
                    'litellm_api_url' => self::TEST_LITELLM_API_URL,
                    'database_host' => 'db.eu-west-1.amazee.ai',
                    'database_port' => 5_432,
                    'database_name' => 'vectordb_prod',
                    'database_username' => 'ai_user',
                    'database_password' => 'secure_password_123',
                ],
                [
                    'id' => 2,
                    'name' => 'demo-key',
                    'region' => 'demo',
                    'litellm_token' => 'demo-token',
                    'litellm_api_url' => 'https://demo.litellm.ai',
                ],
            ]),
            ['http_code' => 200],
        );
    }

    /**
     * Create an empty response for GET /private-ai-keys.
     */
    public static function getPrivateAiKeysEmpty(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode([]),
            ['http_code' => 200],
        );
    }

    /**
     * Create a successful response for GET /model/info.
     */
    public static function modelsSuccess(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode([
                'data' => [
                    [
                        'model_name' => 'gpt-4',
                        'model_info' => [
                            'mode' => 'chat',
                            'supports_image_input' => true,
                            'supports_image_output' => false,
                            'supports_audio_input' => false,
                            'supports_audio_output' => false,
                            'supports_video_output' => false,
                            'supports_moderation' => false,
                            'supported_openai_params' => ['temperature', 'max_tokens'],
                        ],
                    ],
                    [
                        'model_name' => 'text-embedding-ada-002',
                        'model_info' => [
                            'mode' => 'embedding',
                            'supports_image_input' => false,
                            'supports_image_output' => false,
                            'supports_audio_input' => false,
                            'supports_audio_output' => false,
                            'supports_video_output' => false,
                            'supports_moderation' => false,
                            'supported_openai_params' => [],
                        ],
                    ],
                    [
                        'model_name' => 'video-generator',
                        'model_info' => [
                            'mode' => 'generation',
                            'supports_image_input' => true,
                            'supports_image_output' => false,
                            'supports_audio_input' => true,
                            'supports_audio_output' => false,
                            'supports_video_output' => true,
                            'supports_moderation' => false,
                            'supported_openai_params' => [],
                        ],
                    ],
                ],
            ]),
            ['http_code' => 200],
        );
    }

    /**
     * Create an empty response for GET /model/info.
     */
    public static function modelsEmpty(): MockResponse
    {
        return new MockResponse(
            self::jsonEncode(['data' => []]),
            ['http_code' => 200],
        );
    }

    /**
     * Create a network error response.
     */
    public static function networkError(): MockResponse
    {
        return new MockResponse(
            '',
            ['error' => 'Connection refused'],
        );
    }

    /**
     * Create a mock HTTP client with a sequence of responses.
     *
     * @param array<MockResponse> $responses responses in order they will be returned
     */
    public static function createMockClient(array $responses): MockHttpClient
    {
        return new MockHttpClient($responses);
    }

    /**
     * Create a mock HTTP client with a callback for dynamic responses.
     *
     * @param callable $callback function that receives method, url, options and returns MockResponse
     */
    public static function createMockClientWithCallback(callable $callback): MockHttpClient
    {
        return new MockHttpClient($callback);
    }
}
