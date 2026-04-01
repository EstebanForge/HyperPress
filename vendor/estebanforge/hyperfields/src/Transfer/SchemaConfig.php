<?php

declare(strict_types=1);

namespace HyperFields\Transfer;

/**
 * Defines a custom envelope schema for Transfer\Manager export output.
 *
 * Consumers may pass a SchemaConfig to Manager::withSchema() to override the
 * default envelope fields (type, schema_version) and inject arbitrary top-level
 * keys (e.g. site metadata, environment info) into every export bundle.
 *
 * Example:
 * ```php
 * $schema = new SchemaConfig(
 *     type: 'my_plugin_manifest',
 *     schema_version: 2,
 *     extra: [
 *         'site' => ['url' => get_site_url(), 'environment' => 'staging'],
 *     ]
 * );
 * $manager->withSchema($schema)->export();
 * ```
 */
class SchemaConfig
{
    /**
     * @param string               $type           Value for the top-level "type" key.
     * @param int                  $schema_version Value for the top-level "schema_version" key.
     * @param array<string, mixed> $extra          Additional top-level keys merged into the envelope.
     *                                             Keys "schema_version", "type", "generated_at",
     *                                             "modules", and "errors" are reserved and will be
     *                                             ignored if present here.
     */
    public function __construct(
        public readonly string $type = 'hyperfields_transfer_bundle',
        public readonly int $schema_version = 1,
        public readonly array $extra = [],
    ) {}

    /**
     * Reserved envelope keys that callers may not override via $extra.
     *
     * @return string[]
     */
    public static function reservedKeys(): array
    {
        return ['schema_version', 'type', 'generated_at', 'modules', 'errors'];
    }

    /**
     * Returns only the safe subset of $extra with reserved keys removed.
     *
     * @return array<string, mixed>
     */
    public function safeExtra(): array
    {
        return array_diff_key($this->extra, array_flip(self::reservedKeys()));
    }
}
