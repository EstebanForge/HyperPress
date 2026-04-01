<?php

declare(strict_types=1);

namespace HyperFields\Compatibility\Store;

interface StoreInterface
{
    /**
     * Get.
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set.
     *
     * @return bool
     */
    public function set(string $key, mixed $value): bool;

    /**
     * Delete.
     *
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * All.
     *
     * @return array
     */
    public function all(): array;
}

