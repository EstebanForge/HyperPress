<?php

declare(strict_types=1);

namespace HyperFields\Compatibility\Store;

final class ArrayOptionStore implements StoreInterface
{
    public function __construct(private readonly string $option_name)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $data = get_option($this->option_name, []);
        if (!is_array($data)) {
            return $default;
        }

        return array_key_exists($key, $data) ? $data[$key] : $default;
    }

    public function set(string $key, mixed $value): bool
    {
        $data = get_option($this->option_name, []);
        if (!is_array($data)) {
            $data = [];
        }
        $data[$key] = $value;

        return (bool) update_option($this->option_name, $data);
    }

    public function delete(string $key): bool
    {
        $data = get_option($this->option_name, []);
        if (!is_array($data) || !array_key_exists($key, $data)) {
            return false;
        }

        unset($data[$key]);

        return (bool) update_option($this->option_name, $data);
    }

    public function all(): array
    {
        $data = get_option($this->option_name, []);

        return is_array($data) ? $data : [];
    }
}

