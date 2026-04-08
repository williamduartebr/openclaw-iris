# 03 — Provider Architecture

## Design Principles

1. **Contract-driven** — All providers implement a single interface
2. **Config-driven** — Provider availability, API keys, and defaults are in `config/media.php`
3. **Registry pattern** — A `ProviderRegistry` resolves provider instances by name
4. **Domain isolation** — Provider-specific logic never leaks into controllers or domain models
5. **Extensible** — Adding a new provider requires one class and one config entry

## Provider Interface

```php
namespace Src\Media\Infrastructure\ExternalServices\Providers;

interface MediaGenerationProviderInterface
{
    /**
     * Unique provider identifier (e.g., 'openai', 'google_gemini').
     */
    public function name(): string;

    /**
     * List of supported model identifiers.
     */
    public function supportedModels(): array;

    /**
     * Default model if none specified.
     */
    public function defaultModel(): string;

    /**
     * Check if the provider is configured and available.
     */
    public function isAvailable(): bool;

    /**
     * Generate an image from a prompt.
     *
     * Returns raw image binary data and metadata.
     *
     * @param  array{
     *     model?: string,
     *     prompt: string,
     *     negative_prompt?: string,
     *     width?: int,
     *     height?: int,
     *     style?: string,
     *     quality?: string,
     * } $params
     * @return array{
     *     image_data: string,
     *     mime_type: string,
     *     width: int,
     *     height: int,
     *     provider_metadata: array,
     * }
     * @throws \Src\Media\Domain\Exceptions\GenerationFailedException
     * @throws \Src\Media\Domain\Exceptions\ProviderUnavailableException
     */
    public function generate(array $params): array;
}
```

## Provider Registry

The `ProviderRegistry` acts as a factory/resolver:

```php
class ProviderRegistry
{
    /** @var array<string, MediaGenerationProviderInterface> */
    private array $providers = [];

    public function register(MediaGenerationProviderInterface $provider): void;
    public function resolve(string $name): MediaGenerationProviderInterface;
    public function resolveDefault(): MediaGenerationProviderInterface;
    public function available(): array; // Returns only providers where isAvailable() === true
    public function all(): array;
}
```

Registration happens in `MediaServiceProvider::register()`.

## Initial Providers

### OpenAI Image Provider

**Provider name**: `openai`
**Supported models**: `gpt-image-1`, `dall-e-3`, `dall-e-2`
**Default model**: `gpt-image-1`

**API**: OpenAI Images API (`/v1/images/generations`)
**Authentication**: API key from `config('media.providers.openai.api_key')`

**Capabilities**:

| Feature | gpt-image-1 | dall-e-3 | dall-e-2 |
|---------|-------------|----------|----------|
| Max resolution | 1536×1024 | 1792×1024 | 1024×1024 |
| Quality options | low, medium, high | standard, hd | — |
| Style options | natural, vivid | natural, vivid | — |
| Negative prompt | No | No | No |
| Output format | png, webp, jpeg | png | png |

**Implementation notes**:
- Uses `guzzlehttp/guzzle` or `openai-php/client` if available
- Returns raw image bytes (base64 decoded from API response)
- Maps `quality` and `style` params to provider-specific values
- Falls back to defaults for unsupported params per model

### Google Gemini Image Provider

**Provider name**: `google_gemini`
**Supported models**: `gemini-2.0-flash-exp-image-generation`, `gemini-2.5-flash-image`, `gemini-2.0-flash-exp`, `imagen-3.0-generate-002`
**Default model**: `gemini-2.0-flash-exp-image-generation`

**API**: Google Generative AI API
**Authentication**: API key from `config('media.providers.google_gemini.api_key')`

**Capabilities**:

| Feature | gemini-2.5-flash-image | imagen-3.0 |
|---------|----------------------|------------|
| Max resolution | Multiple aspect ratios | 1024×1024 |
| Negative prompt | Via prompt engineering | Yes (native) |
| Style options | Via prompt engineering | — |
| Output format | png | png |

**Implementation notes**:
- Uses Gemini REST API (`/v1beta/models/{model}:generateContent`)
- For Gemini models, uses `responseModalities: ["TEXT", "IMAGE"]`
- Image data returned as base64 in `inlineData` parts
- Maps generation params to provider-specific API shape

## Configuration

```php
// config/media.php
return [
    'default_provider' => env('MEDIA_DEFAULT_PROVIDER', 'openai'),

    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'default_model' => env('MEDIA_OPENAI_MODEL', 'gpt-image-1'),
            'base_url' => env('OPENAI_API_BASE_URL', 'https://api.openai.com/v1'),
            'timeout' => 120,
        ],
        'google_gemini' => [
            'api_key' => env('GOOGLE_GEMINI_API_KEY'),
            'default_model' => env('MEDIA_GEMINI_MODEL', 'gemini-2.0-flash-exp-image-generation'),
            'base_url' => env('GOOGLE_GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com'),
            'timeout' => 120,
        ],
    ],

    'processing' => [
        'quality' => 82,
        'max_width' => 1920,
        'max_height' => 1080,
        'format' => 'webp',
        'strip_metadata' => true,
    ],

    'storage' => [
        'disk' => 's3',
        'temp_prefix' => 'temp/Media',
        'final_prefix' => 'Media',
    ],
];
```

## Adding a New Provider

1. Create class implementing `MediaGenerationProviderInterface`:
   ```
   src/Media/Infrastructure/ExternalServices/Providers/NewProvider.php
   ```

2. Add config entry:
   ```php
   // config/media.php
   'providers' => [
       'new_provider' => [
           'api_key' => env('NEW_PROVIDER_API_KEY'),
           'default_model' => 'model-v1',
       ],
   ],
   ```

3. Register in `MediaServiceProvider`:
   ```php
   $registry->register(new NewProvider(config('media.providers.new_provider')));
   ```

No changes required in controllers, services, or routes.

## Error Handling

Each provider wraps API errors into domain exceptions:

- **`ProviderUnavailableException`** — API key not configured, provider down
- **`GenerationFailedException`** — API returned error, content policy violation, rate limit

Providers must never throw raw HTTP or SDK exceptions.
