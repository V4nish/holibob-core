<?php

namespace Holibob\Affiliates\Contracts;

use Illuminate\Support\Collection;

interface AffiliateProviderInterface
{
    /**
     * Fetch properties from affiliate source.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function fetchProperties(): Collection;

    /**
     * Transform raw affiliate data to internal schema.
     *
     * @param array<string, mixed> $rawData
     * @return array<string, mixed>
     */
    public function transform(array $rawData): array;

    /**
     * Generate tracked affiliate URL.
     *
     * @param string $externalId
     * @param array<string, mixed> $params
     * @return string
     */
    public function generateAffiliateUrl(string $externalId, array $params = []): string;

    /**
     * Check if provider is properly configured.
     *
     * @return bool
     */
    public function isConfigured(): bool;

    /**
     * Get the provider name.
     *
     * @return string
     */
    public function getName(): string;
}
