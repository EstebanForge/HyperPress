<?php

declare(strict_types=1);

namespace HyperFields\Transfer;

/**
 * Lightweight, extensible transfer module registry.
 *
 * Enables external consumers to register module-level export/import/diff
 * handlers and orchestrate them through one generic manager.
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

        return [
            'schema_version' => 1,
            'type' => 'hyperfields_transfer_bundle',
            'generated_at' => gmdate('c'),
            'modules' => $modules,
            'errors' => $errors,
        ];
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

        foreach ($payloadModules as $key => $payload) {
            $moduleKey = sanitize_key((string) $key);
            if ($moduleKey === '') {
                continue;
            }

            if (!isset($this->modules[$moduleKey])) {
                $errors[] = "Module '{$moduleKey}' payload found but module is not registered.";
                continue;
            }

            try {
                $results[$moduleKey] = call_user_func($this->modules[$moduleKey]['importer'], $payload, $context);
            } catch (\Throwable $throwable) {
                $errors[] = "Module '{$moduleKey}' import failed: " . $throwable->getMessage();
            }
        }

        return [
            'success' => empty($errors),
            'modules' => $results,
            'errors' => $errors,
        ];
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
}

