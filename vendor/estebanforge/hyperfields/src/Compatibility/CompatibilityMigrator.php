<?php

declare(strict_types=1);

namespace HyperFields\Compatibility;

use HyperFields\Compatibility\Store\StoreInterface;

final class CompatibilityMigrator
{
    /**
     * @param array<string, string> $key_map
     * @return array{changes: array<int, array{from: string, to: string, old: mixed, new: mixed}>, missing: array<int, string>}
     */
    public static function dryRun(StoreInterface $source, StoreInterface $target, array $key_map): array
    {
        $changes = [];
        $missing = [];

        foreach ($key_map as $from => $to) {
            $value = $source->get($from, null);
            if ($value === null) {
                $missing[] = $from;
                continue;
            }

            $changes[] = [
                'from' => $from,
                'to' => $to,
                'old' => $target->get($to, null),
                'new' => $value,
            ];
        }

        return [
            'changes' => $changes,
            'missing' => $missing,
        ];
    }

    /**
     * @param array<string, string> $key_map
     * @return array{success: bool, backup: array<string, mixed>, written: array<int, string>}
     */
    public static function migrate(StoreInterface $source, StoreInterface $target, array $key_map): array
    {
        $backup = [];
        $written = [];
        $success = true;

        foreach ($key_map as $from => $to) {
            $value = $source->get($from, null);
            if ($value === null) {
                continue;
            }

            $backup[$to] = $target->get($to, null);
            $ok = $target->set($to, $value);
            if ($ok) {
                $written[] = $to;
            } else {
                $success = false;
            }
        }

        return [
            'success' => $success,
            'backup' => $backup,
            'written' => $written,
        ];
    }

    /**
     * @param array<string, mixed> $backup
     */
    public static function restore(StoreInterface $target, array $backup): bool
    {
        $success = true;
        foreach ($backup as $key => $value) {
            $ok = $value === null
                ? $target->delete((string) $key)
                : $target->set((string) $key, $value);
            if (!$ok) {
                $success = false;
            }
        }

        return $success;
    }
}

