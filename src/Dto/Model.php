<?php

declare(strict_types=1);

namespace AmazeeIo\AmazeeAiConfigure\Dto;

/**
 * An Amazee AI Model with information about which features are supported.
 */
final class Model
{
    /**
     * Whether the model supports image and audio to video.
     */
    public readonly bool $supportsImageAndAudioToVideo;

    /**
     * Construct a Model object.
     *
     * @param string   $name                  the name of the model
     * @param bool     $supportsImageInput    whether the model supports image input
     * @param bool     $supportsImageOutput   whether the model supports image output
     * @param bool     $supportsAudioInput    whether the model supports audio input
     * @param bool     $supportsAudioOutput   whether the model supports audio output
     * @param bool     $supportsVideoOutput   whether the model supports video output
     * @param bool     $supportsEmbeddings    whether the model supports embeddings
     * @param bool     $supportsChat          whether the model supports chat
     * @param bool     $supportsModeration    whether the model supports moderation
     * @param string[] $supportedOpenAiParams the OpenAI compatible params supported by this model
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $supportsImageInput,
        public readonly bool $supportsImageOutput,
        public readonly bool $supportsAudioInput,
        public readonly bool $supportsAudioOutput,
        public readonly bool $supportsVideoOutput,
        public readonly bool $supportsEmbeddings,
        public readonly bool $supportsChat,
        public readonly bool $supportsModeration,
        public readonly array $supportedOpenAiParams,
    ) {
        $this->supportsImageAndAudioToVideo = $supportsImageInput && $supportsAudioInput && $supportsVideoOutput;
    }

    /**
     * Create a Model from an API response object.
     *
     * @param \stdClass $response the object returned by the API from model info
     *
     * @return self a model constructed from the API response
     */
    public static function createFromResponse(\stdClass $response): self
    {
        $modelInfo = $response->model_info ?? new \stdClass();

        return new self(
            name: $response->model_name,
            supportsImageInput: $modelInfo->supports_image_input ?? false,
            supportsImageOutput: $modelInfo->supports_image_output ?? false,
            supportsAudioInput: $modelInfo->supports_audio_input ?? false,
            supportsAudioOutput: $modelInfo->supports_audio_output ?? false,
            supportsVideoOutput: $modelInfo->supports_video_output ?? false,
            supportsEmbeddings: ($modelInfo->mode ?? null) === 'embedding',
            supportsChat: ($modelInfo->mode ?? null) === 'chat',
            supportsModeration: $modelInfo->supports_moderation ?? false,
            supportedOpenAiParams: $modelInfo->supported_openai_params ?? [],
        );
    }
}
