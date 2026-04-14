<?php

declare(strict_types=1);

namespace HyperFields\Compatibility\Store;

final class DualWriteStore implements StoreInterface
{
    /**
     *   construct.
     */
    public function __construct(
        private readonly StoreInterface $primary,
        private readonly StoreInterface $secondary
    ) {}

    /**
     * Get.
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->primary->get($key, $default);
    }

    /**
     * Set.
     *
     * @return bool
     */
    public function set(string $key, mixed $value): bool
    {
        $first = $this->primary->set($key, $value);
        $second = $this->secondary->set($key, $value);

        return $first || $second;
    }

    /**
     * Delete.
     *
     * @return bool
     */
    public function delete(string $key): bool
    {
        $first = $this->primary->delete($key);
        $second = $this->secondary->delete($key);

        return $first || $second;
    }

    /**
     * All.
     *
     * @return array
     */
    public function all(): array
    {
        return array_merge($this->secondary->all(), $this->primary->all());
    }
}
