<?php

namespace Holibob\Affiliates\Providers;

use Holibob\Affiliates\Contracts\AffiliateProviderInterface;
use Illuminate\Support\Str;

abstract class AbstractAffiliateProvider implements AffiliateProviderInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        protected array $config
    ) {
    }

    /**
     * Get configuration value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Check if a configuration key exists.
     *
     * @param string $key
     * @return bool
     */
    protected function hasConfig(string $key): bool
    {
        return array_key_exists($key, $this->config);
    }

    /**
     * Generate a slug from property name.
     *
     * @param string $name
     * @param string $externalId
     * @return string
     */
    protected function generateSlug(string $name, string $externalId): string
    {
        return Str::slug($name) . '-' . $externalId;
    }

    /**
     * Validate required config keys.
     *
     * @param array<int, string> $requiredKeys
     * @return bool
     */
    protected function validateConfig(array $requiredKeys): bool
    {
        foreach ($requiredKeys as $key) {
            if (! $this->hasConfig($key) || empty($this->config[$key])) {
                return false;
            }
        }

        return true;
    }
}
