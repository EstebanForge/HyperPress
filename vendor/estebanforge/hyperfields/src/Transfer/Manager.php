<?php

declare(strict_types=1);

namespace HyperFields\Transfer;

/**
 * Lightweight, extensible transfer module registry.
 *
 * Enables external consumers to register module-level export/import/diff
 * handlers and orchestrate them through one generic manager.
 *
 * To customise the export envelope shape use {@see SchemaConfig}:
 * ```php
 * $manager->withSchema(new SchemaConfig(
 *     type: 'my_plugin_manifest',
 *     schema_version: 2,
 *     extra: ['site' => ['url' => get_site_url(), 'environment' => 'staging']],
 * ))->export();
 * ```
 */
class Manager
{
    /**
     * @var array<string, array{
     *   exporter: callable,
     *   importer: callable,
     *   differ: callable|null
     * }>
     */
    private array $modules = [];

    private ?SchemaConfig $schemaConfig = null;

    /**
     * Set a custom schema configuration for export envelopes.
     *
     * Returns the same Manager instance for fluent chaining.
     */
    public function withSchema(SchemaConfig $config): static
    {
        $this->schemaConfig = $config;

        return $this;
    }

    /**
     * Registers an export/import module handler set.
     *
     * @param string        $key      Unique module key.
     * @param callable      $exporter Export callback: fn(array $context): mixed.
     * @param callable      $importer Import callback: fn(array $payload, array $context): mixed.
     * @param callable|null $differ   Optional diff callback: fn(array $payload, array $context): mixed.
     * @return void
     */
    public function registerModule(string $key, callable $exporter, callable $importer, ?callable $differ = null): void
    {
        $normalizedKey = sanitize_key($key);
        if ($normalizedKey === '') {
            return;
        }

        $this->modules[$normalizedKey] = [
            'exporter' => $exporter,
            'importer' => $importer,
            'differ' => $differ,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function listModules(): array
    {
        return array_keys($this->modules);
    }

    /**
     * Export selected modules.
     *
     * Exporters are called as: fn(array $context): array
     *
     * @param array<int, string> $moduleKeys Empty means all registered modules.
     * @param array<string, mixed> $context Shared caller context passed to every module.
     * @return array{
     *   schema_version: int,
     *   type: string,
     *   generated_at: string,
     *   modules: array<string, mixed>,
     *   errors: array<int, string>
     * }
     */
    public function export(array $moduleKeys = [], array $context = []): array
    {
        $selected = $this->resolveModuleKeys($moduleKeys);
        $modules = [];
        $errors = [];

        AuditContext::enterManager();
        try {
            foreach ($selected as $key) {
                $definition = $this->modules[$key] ?? null;
                if ($definition === null) {
                    $errors[] = "Module '{$key}' is not registered.";
                    continue;
                }

                try {
                    $modules[$key] = call_user_func($definition['exporter'], $context);
                } catch (\Throwable $throwable) {
                    $errors[] = "Module '{$key}' export failed: " . $throwable->getMessage();
                }
            }
        } finally {
            AuditContext::leaveManager();
        }

        $schema = $this->schemaConfig ?? new SchemaConfig();

        $result = array_merge(
            $schema->safeExtra(),
            [
                'schema_version' => $schema->schema_version,
                'type'           => $schema->type,
                'generated_at'   => gmdate('c'),
                'modules'        => $modules,
                'errors'         => $errors,
            ]
        );

        /**
         * Fires after Transfer Manager export completes.
         *
         * @param array $result
         * @param array $selected
         * @param array $context
         */
        do_action('hyperfields/transfer_manager/export/after', $result, $selected, $context);

        return $result;
    }

    /**
     * Diff selected modules from a transfer bundle.
     *
     * Differs are called as: fn(array $payload, array $context): array
     *
     * @param array<string, mixed> $bundle Bundle payload returned by export().
     * @param array<string, mixed> $context Shared context passed to each differ.
     * @return array{
     *   success: bool,
     *   modules: array<string, mixed>,
     *   errors: array<int, string>
     * }
     */
    public function diff(array $bundle, array $context = []): array
    {
        $errors = [];
        $results = [];
        $payloadModules = isset($bundle['modules']) && is_array($bundle['modules']) ? $bundle['modules'] : [];

        foreach ($payloadModules as $key => $payload) {
            $moduleKey = sanitize_key((string) $key);
            if ($moduleKey === '') {
                continue;
            }

            if (!isset($this->modules[$moduleKey])) {
                $errors[] = "Module '{$moduleKey}' payload found but module is not registered.";
                continue;
            }

            $differ = $this->modules[$moduleKey]['differ'];
            if (!is_callable($differ)) {
                $errors[] = "Module '{$moduleKey}' has no differ callback.";
                continue;
            }

            try {
                $results[$moduleKey] = call_user_func($differ, $payload, $context);
            } catch (\Throwable $throwable) {
                $errors[] = "Module '{$moduleKey}' diff failed: " . $throwable->getMessage();
            }
        }

        return [
            'success' => empty($errors),
            'modules' => $results,
            'errors' => $errors,
        ];
    }

    /**
     * Import selected modules from a transfer bundle.
     *
     * Importers are called as: fn(array $payload, array $context): array
     *
     * @param array<string, mixed> $bundle Bundle payload returned by export().
     * @param array<string, mixed> $context Shared context passed to each importer.
     * @return array{
     *   success: bool,
     *   modules: array<string, mixed>,
     *   errors: array<int, string>
     * }
     */
    public function import(array $bundle, array $context = []): array
    {
        $errors = [];
        $results = [];
        $payloadModules = isset($bundle['modules']) && is_array($bundle['modules']) ? $bundle['modules'] : [];
        $attemptedModuleKeys = [];

        AuditContext::enterManager();
        try {
            foreach ($payloadModules as $key => $payload) {
                $moduleKey = sanitize_key((string) $key);
                if ($moduleKey === '') {
                    continue;
                }
                $attemptedModuleKeys[] = $moduleKey;

                if (!isset($this->modules[$moduleKey])) {
                    $errors[] = "Module '{$moduleKey}' payload found but module is not registered.";
                    continue;
                }

                $modulePayload = apply_filters(
                    'hyperfields/transfer_manager/import/module_payload',
                    $payload,
                    $moduleKey,
                    $context,
                    $bundle
                );

                $moduleStrategy = self::resolveModuleStrategy($modulePayload);
                $decision = apply_filters(
                    'hyperfields/transfer_manager/import/module_decision',
                    self::defaultModuleDecision($moduleStrategy),
                    $moduleKey,
                    $modulePayload,
                    $context,
                    $bundle
                );

                $action = is_array($decision) && isset($decision['action'])
                    ? sanitize_key((string) $decision['action'])
                    : 'import';
                if ($action === 'skip') {
                    $results[$moduleKey] = [
                        'success' => true,
                        'skipped' => true,
                        'reason' => is_array($decision) && isset($decision['reason'])
                            ? sanitize_text_field((string) $decision['reason'])
                            : 'custom_rule',
                    ];
                    continue;
                }

                $moduleContext = apply_filters(
                    'hyperfields/transfer_manager/import/module_context',
                    array_merge($context, ['strategy' => $moduleStrategy !== '' ? $moduleStrategy : 'replace']),
                    $moduleKey,
                    $modulePayload,
                    $bundle
                );
                $moduleContext = is_array($moduleContext) ? $moduleContext : $context;

                try {
                    $results[$moduleKey] = call_user_func($this->modules[$moduleKey]['importer'], $modulePayload, $moduleContext);
                } catch (\Throwable $throwable) {
                    $errors[] = "Module '{$moduleKey}' import failed: " . $throwable->getMessage();
                }
            }
        } finally {
            AuditContext::leaveManager();
        }

        $result = [
            'success' => empty($errors),
            'modules' => $results,
            'errors' => $errors,
        ];

        /**
         * Fires after Transfer Manager import completes.
         *
         * @param array $result
         * @param array $attemptedModuleKeys
         * @param array $context
         */
        do_action('hyperfields/transfer_manager/import/after', $result, $attemptedModuleKeys, $context);

        return $result;
    }

    /**
     * @param array<int, string> $moduleKeys
     * @return array<int, string>
     */
    private function resolveModuleKeys(array $moduleKeys): array
    {
        if (empty($moduleKeys)) {
            return array_keys($this->modules);
        }

        $keys = [];
        foreach ($moduleKeys as $moduleKey) {
            $normalized = sanitize_key((string) $moduleKey);
            if ($normalized === '') {
                continue;
            }
            $keys[] = $normalized;
        }

        return array_values(array_unique($keys));
    }

    /**
     * Resolves module strategy from module payload.
     *
     * @param mixed $modulePayload
     * @return string
     */
    private static function resolveModuleStrategy(mixed $modulePayload): string
    {
        if (!is_array($modulePayload) || !isset($modulePayload['__strategy'])) {
            return '';
        }

        return sanitize_key((string) $modulePayload['__strategy']);
    }

    /**
     * Builds default import decision from module strategy.
     *
     * @param string $strategy
     * @return array{action: string, reason: string}
     */
    private static function defaultModuleDecision(string $strategy): array
    {
        if ($strategy === 'skip') {
            return [
                'action' => 'skip',
                'reason' => 'strategy_skip',
            ];
        }

        return [
            'action' => 'import',
            'reason' => '',
        ];
    }
}
