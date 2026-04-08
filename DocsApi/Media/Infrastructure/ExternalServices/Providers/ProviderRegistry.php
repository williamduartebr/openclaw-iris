<?php

namespace Src\Media\Infrastructure\ExternalServices\Providers;

use Src\Media\Domain\Exceptions\ProviderUnavailableException;

class ProviderRegistry
{
    /** @var array<string, MediaGenerationProviderInterface> */
    private array $providers = [];

    public function register(MediaGenerationProviderInterface $provider): void
    {
        $this->providers[$provider->name()] = $provider;
    }

    public function resolve(string $name): MediaGenerationProviderInterface
    {
        if (! isset($this->providers[$name])) {
            throw new ProviderUnavailableException($name, 'Provider not registered');
        }

        $provider = $this->providers[$name];

        if (! $provider->isAvailable()) {
            throw new ProviderUnavailableException($name, 'Provider is not configured');
        }

        return $provider;
    }

    public function resolveDefault(): MediaGenerationProviderInterface
    {
        $defaultName = config('media.default_provider', 'openai');

        return $this->resolve($defaultName);
    }

    /**
     * @return array<string, MediaGenerationProviderInterface>
     */
    public function available(): array
    {
        return array_filter(
            $this->providers,
            fn (MediaGenerationProviderInterface $p) => $p->isAvailable()
        );
    }

    /**
     * @return array<string, MediaGenerationProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }
}
