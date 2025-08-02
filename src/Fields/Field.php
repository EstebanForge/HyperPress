<?php

declare(strict_types=1);

namespace HMApi\Fields;

use HMApi\Log;

class Field
{
    private ?string $option_group = null;
    private string $type;
    private string $name;
    private string $label;
    private string|array|null $option_name = null;
    private mixed $default = null;
    private ?string $placeholder = null;
    private bool $required = false;
    private ?string $help = null;
    private array $options = [];
    private array $validation = [];
    private array $conditional_logic = [];
    private string $context = 'post';
    private string $storage_type = 'meta';
    private bool $multiple = false;
    private string $layout = 'grid';
    private ?int $min = null;
    private ?int $max = null;
    private string $post_type = 'post';
    private string $taxonomy = 'category';
    private bool $media_library = true;
    private array $map_options = [];
    private ?string $html_content = null;
    protected array $args = [];

    public function set_html(string $html): self
    {
        $this->html_content = $html;

        return $this;
    }

    public function set_html_content(string $html): self
    {
        return $this->set_html($html);
    }

    public const VALID_TYPES = [
        'text',
        'textarea',
        'number',
        'email',
        'url',
        'color',
        'date',
        'datetime',
        'time',
        'image',
        'file',
        'select',
        'multiselect',
        'checkbox',
        'radio',
        'radio_image',
        'rich_text',
        'hidden',
        'html',
        'map',
        'oembed',
        'separator',
        'header_scripts',
        'footer_scripts',
        'set',
        'sidebar',
        'association',
        'tabs',
        'custom',
    ];

    protected function __construct(string $type, string $name, string $label)
    {
        if (!in_array($type, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid field type: {$type}");
        }

        // Allow hyphens in field names for compatibility with extension filenames
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_-]*$/', $name)) {
            throw new \InvalidArgumentException("Invalid field name: {$name}");
        }

        $this->type = $type;
        $this->name = $name;
        $this->label = $label;
    }

    public static function make(string $type, string $name, string $label): self
    {
        return new self($type, $name, $label);
    }

    public function set_default(mixed $default): self
    {
        $this->default = $default;

        return $this;
    }

    public function set_placeholder(?string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function set_required(bool $required = true): self
    {
        $this->required = $required;

        return $this;
    }

    public function set_help(?string $help): self
    {
        $this->help = $help;

        return $this;
    }

    public function set_description(?string $description): self
    {
        $this->help = $description;

        return $this;
    }

    public function set_options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function set_validation(array $validation): self
    {
        $this->validation = $validation;

        return $this;
    }

    public function set_conditional_logic(array $conditional_logic): self
    {
        $this->conditional_logic = $conditional_logic;

        return $this;
    }

    public function set_context(string $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function set_storage_type(string $storage_type): self
    {
        $this->storage_type = $storage_type;

        return $this;
    }

    public function get_type(): string
    {
        return $this->type;
    }

    public function get_name(): string
    {
        return $this->name;
    }

    public function get_label(): string
    {
        return $this->label;
    }

    public function get_default(): mixed
    {
        return $this->default;
    }

    public function get_placeholder(): ?string
    {
        return $this->placeholder;
    }

    public function is_required(): bool
    {
        return $this->required;
    }

    public function get_help(): ?string
    {
        return $this->help;
    }

    public function get_description(): ?string
    {
        return $this->help;
    }

    public function get_options(): array
    {
        return $this->options;
    }

    public function get_html(): string
    {
        return $this->html_content ?? '';
    }

    public function get_validation(): array
    {
        return $this->validation;
    }

    public function get_conditional_logic(): array
    {
        return $this->conditional_logic;
    }

    public function get_context(): string
    {
        return $this->context;
    }

    public function get_storage_type(): string
    {
        return $this->storage_type;
    }

    public function add_arg(string $key, mixed $value): self
    {
        $this->args[$key] = $value;

        return $this;
    }

    public function set_option_values(array $values, ?string $option_group = null): self
    {
        Log::debug("Field {$this->name} setting option values: " . print_r($values, true), ['source' => 'hmapi-fields']);
        $this->option_name = $values; // Holds the values array for pre-loading
        if ($option_group !== null) {
            $this->option_group = $option_group;
        }
        return $this;
    }

    public function get_option_name(): string|array|null
    {
        return $this->option_name;
    }

    public function get_value()
    {
        // If option_name is an array, it means the OptionsPage has pre-loaded the values.
        if (is_array($this->option_name)) {
            Log::debug("Field {$this->name} using pre-loaded values. Value: " . print_r($this->option_name[$this->name] ?? 'NOT SET', true), ['source' => 'hmapi-fields']);

            return $this->option_name[$this->name] ?? $this->default;
        }

        // Fallback for standalone fields or fields rendered outside the OptionsPage context.
        if ($this->option_name) {
            $options = get_option($this->option_name);
            if (is_array($options)) {
                Log::debug("Field {$this->name} using get_option. Value: " . print_r($options[$this->name] ?? 'NOT SET', true), ['source' => 'hmapi-fields']);

                return $options[$this->name] ?? $this->default;
            }
        }

        Log::debug("Field {$this->name} using default value: " . print_r($this->default, true), ['source' => 'hmapi-fields']);

        return $this->default;
    }

    public function get_args(): array
    {
        return $this->args;
    }

    protected function set_args(array $args): void
    {
        $this->args = array_merge($this->args, $args);
    }

    public function render(array $args = []): void
    {
        $value = $this->get_value();
        $this->set_args($args);

        $field_data = $this->to_array();
        $field_data['value'] = $value;

        TemplateLoader::render_field($field_data, $value);
    }

    public function get_option_value(): mixed
    {
        return get_option($this->name, $this->default);
    }

    public function get_name_attr(): string
    {
        // Always use option_group[field_name] for options pages
        if ($this->option_group) {
            return sprintf('%s[%s]', $this->option_group, $this->name);
        }
        if (is_array($this->option_name)) {
            // Fallback for legacy/other usages
            return $this->name;
        }
        return $this->option_name ? sprintf('%s[%s]', $this->option_name, $this->name) : $this->name;
    }

    public function to_array(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'name_attr' => $this->get_name_attr(),
            'label' => $this->label,
            'default' => $this->default,
            'placeholder' => $this->placeholder,
            'required' => $this->required,
            'help' => $this->help,
            'options' => $this->options,
            'validation' => $this->validation,
            'conditional_logic' => $this->conditional_logic,
            'context' => $this->context,
            'storage_type' => $this->storage_type,
            'html_content' => $this->html_content,
            'post_type' => $this->post_type,
            'taxonomy' => $this->taxonomy,
            'media_library' => $this->media_library,
            'map_options' => $this->map_options,
        ];
    }

    public function sanitize_value(mixed $value): mixed
    {
        switch ($this->type) {
            case 'text':
            case 'hidden':
                return sanitize_text_field((string) $value);
            case 'textarea':
            case 'rich_text':
            case 'wysiwyg':
                return wp_kses_post((string) $value);
            case 'number':
                return is_numeric($value) ? (float) $value : 0;
            case 'email':
                return is_email($value) ? sanitize_email($value) : '';
            case 'url':
                return esc_url_raw($value) ?: '';
            case 'color':
                return sanitize_hex_color($value);
            case 'date':
                return sanitize_text_field($value);
            case 'datetime':
                return sanitize_text_field($value);
            case 'time':
                return sanitize_text_field($value);
            case 'image':
                return absint($value);
            case 'file':
                return esc_url_raw($value) ?: '';
            case 'select':
            case 'radio':
                return $this->sanitize_select_value($value);
            case 'multiselect':
            case 'checkbox_set':
                return $this->sanitize_array_value($value);
            case 'checkbox':
                return (bool) $value;
            case 'html':
                return wp_kses_post($value);
            case 'map':
                return $this->sanitize_map_value($value);
            case 'oembed':
                return esc_url_raw($value) ?: '';
            case 'separator':
            case 'header_scripts':
            case 'footer_scripts':
                return wp_kses_post($value);
            case 'complex':
            case 'repeater':
            case 'group':
                return $this->sanitize_complex_value($value);
            case 'association':
                return $this->sanitize_association_value($value);
            case 'sidebar':
                return sanitize_text_field($value);
            case 'gravity_form':
                return absint($value);
            default:
                return apply_filters("hmapi_field_sanitize_{$this->type}", $value, $this->type);
        }
    }

    private function sanitize_array_value(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_map('sanitize_text_field', $value);
    }

    private function sanitize_map_value(mixed $value): array
    {
        if (!is_array($value)) {
            return ['lat' => 0, 'lng' => 0];
        }

        return [
            'lat' => isset($value['lat']) ? (float) $value['lat'] : 0,
            'lng' => isset($value['lng']) ? (float) $value['lng'] : 0,
            'address' => isset($value['address']) ? sanitize_text_field($value['address']) : '',
        ];
    }

    private function sanitize_complex_value(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_map([$this, 'sanitize_nested_value'], $value);
    }

    private function sanitize_nested_value(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map('sanitize_text_field', $value);
        }

        return sanitize_text_field((string) $value);
    }

    private function sanitize_association_value(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_map('absint', $value);
    }

    private function sanitize_select_value(mixed $value): string
    {
        if (empty($this->options)) {
            return (string) $value;
        }

        $allowed_values = array_keys($this->options);

        return in_array($value, $allowed_values, true) ? (string) $value : (string) $allowed_values[0];
    }

    public function validate_value(mixed $value): bool
    {
        if ($this->required && empty($value)) {
            return false;
        }

        foreach ($this->validation as $rule => $param) {
            if (!$this->apply_validation_rule($value, $rule, $param)) {
                return false;
            }
        }

        return true;
    }

    private function apply_validation_rule(mixed $value, string $rule, mixed $param): bool
    {
        switch ($rule) {
            case 'min':
                return strlen((string) $value) >= $param;
            case 'max':
                return strlen((string) $value) <= $param;
            case 'pattern':
                return preg_match($param, (string) $value) === 1;
            case 'email':
                return is_email($value) !== false;
            case 'url':
                return esc_url_raw($value) === $value;
            case 'numeric':
                return is_numeric($value);
            case 'integer':
                return filter_var($value, FILTER_VALIDATE_INT) !== false;
            case 'float':
                return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
            default:
                return apply_filters("hmapi_field_validation_{$rule}", true, $value, $param, $this);
        }
    }
}
