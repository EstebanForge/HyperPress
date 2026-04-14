<?php

declare(strict_types=1);

namespace HyperFields\Validation;

/**
 * Standalone schema validator for arbitrary option values.
 *
 * Validates raw PHP values against declarative schema rules without requiring
 * a bound Field instance.  Designed for import/export validation and for use
 * by external consumers that need server-side data validation.
 *
 * Schema rule format:
 * ```php
 * [
 *     'type'    => 'string',          // Required. PHP type: string|integer|double|boolean|array|null
 *     'max'     => 255,               // Optional. Max string length (mb_strlen).
 *     'min'     => 1,                 // Optional. Min string length (mb_strlen).
 *     'pattern' => '/^[a-z]+$/',      // Optional. PCRE regex the value must match.
 *     'enum'    => ['yes', 'no'],     // Optional. Allowed values (strict comparison).
 *     'format'  => 'email',           // Optional. Semantic format — see SUPPORTED_FORMATS.
 *     'fields'  => [...],             // Optional. Sub-schema for array values (keyed field rules).
 * ]
 * ```
 *
 * Usage:
 * ```php
 * // Single value
 * $error = SchemaValidator::validate('my_field', $value, ['type' => 'string', 'max' => 255]);
 *
 * // Batch — validate a keyed map against a schema map
 * $errors = SchemaValidator::validateMap($values, $schemaMap);
 *
 * // Check if a value matches a schema (boolean)
 * $valid = SchemaValidator::isValid($value, ['type' => 'string', 'format' => 'email']);
 * ```
 */
class SchemaValidator
{
    /**
     * Supported format identifiers for the 'format' rule.
     *
     * @var array<int, string>
     */
    private const SUPPORTED_FORMATS = [
        'email',
        'email_or_empty',
        'email_csv',
        'email_csv_or_empty',
        'hex_color',
        'url',
        'url_or_empty',
    ];

    /**
     * Allowed type identifiers.
     *
     * @var array<int, string>
     */
    private const ALLOWED_TYPES = [
        'string',
        'integer',
        'double',
        'boolean',
        'array',
        'null',
    ];

    /**
     * Validates a single value against a schema rule.
     *
     * @param string $fieldName Human-readable field name (for error messages).
     * @param mixed  $value     The value to validate.
     * @param array  $rule      Schema rule array (type, max, min, pattern, enum, format, fields).
     * @return string|null      Error message on failure, null on success.
     */
    public static function validate(string $fieldName, mixed $value, array $rule): ?string
    {
        // Null values are always accepted — the field may not exist on source.
        if ($value === null) {
            return null;
        }

        // ── Type check ──────────────────────────────────────────────
        $expectedType = $rule['type'] ?? null;

        if ($expectedType === null) {
            return sprintf('"%s": schema rule is missing required "type" key.', $fieldName);
        }

        if (!in_array($expectedType, self::ALLOWED_TYPES, true)) {
            return sprintf('"%s": unknown schema type "%s".', $fieldName, $expectedType);
        }

        $actualType = self::detectType($value);

        if ($actualType !== $expectedType) {
            return sprintf(
                '"%s" must be %s, got %s.',
                $fieldName,
                $expectedType,
                $actualType
            );
        }

        // ── String constraints ──────────────────────────────────────
        if ($expectedType === 'string' && is_string($value)) {
            return self::validateStringConstraints($fieldName, $value, $rule);
        }

        // ── Array sub-schema (fields) ───────────────────────────────
        if ($expectedType === 'array' && is_array($value) && isset($rule['fields']) && is_array($rule['fields'])) {
            return self::validateArrayFields($fieldName, $value, $rule['fields']);
        }

        return null;
    }

    /**
     * Validates a keyed map of values against a keyed map of schema rules.
     *
     * Keys in $values that have no corresponding rule in $schemaMap are skipped.
     * Keys in $schemaMap that are absent from $values are skipped.
     *
     * @param array<string, mixed>          $values    Keyed values to validate.
     * @param array<string, array>          $schemaMap Keyed schema rules.
     * @param string                        $prefix    Optional prefix for error field names.
     * @return array<int, string>                      Array of error messages (empty = all valid).
     */
    public static function validateMap(array $values, array $schemaMap, string $prefix = ''): array
    {
        $errors = [];

        foreach ($values as $key => $value) {
            $key = (string) $key;
            $rule = $schemaMap[$key] ?? null;

            if ($rule === null) {
                continue;
            }

            $qualifiedName = $prefix !== '' ? sprintf('%s.%s', $prefix, $key) : $key;
            $error = self::validate($qualifiedName, $value, $rule);

            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * Returns true when a value passes validation against a schema rule.
     *
     * @param mixed $value The value to check.
     * @param array $rule  Schema rule array.
     * @return bool
     */
    public static function isValid(mixed $value, array $rule): bool
    {
        return self::validate('_', $value, $rule) === null;
    }

    /**
     * Returns the list of supported format identifiers.
     *
     * @return array<int, string>
     */
    public static function supportedFormats(): array
    {
        return self::SUPPORTED_FORMATS;
    }

    /**
     * Returns the list of allowed type identifiers.
     *
     * @return array<int, string>
     */
    public static function allowedTypes(): array
    {
        return self::ALLOWED_TYPES;
    }

    /**
     * Detects the canonical type name of a PHP value.
     *
     * @param mixed $value
     * @return string
     */
    public static function detectType(mixed $value): string
    {
        return match (true) {
            is_null($value)   => 'null',
            is_array($value)  => 'array',
            is_bool($value)   => 'boolean',
            is_int($value)    => 'integer',
            is_float($value)  => 'double',
            is_string($value) => 'string',
            default           => 'string',
        };
    }

    // ──────────────────────────────────────────────────────────────────
    //  String constraint validation
    // ──────────────────────────────────────────────────────────────────

    /**
     * ValidateStringConstraints.
     *
     * @return ?string
     */
    private static function validateStringConstraints(string $fieldName, string $value, array $rule): ?string
    {
        // max length
        if (isset($rule['max'])) {
            $max = (int) $rule['max'];
            $len = mb_strlen($value);

            if ($len > $max) {
                return sprintf('"%s" exceeds max length %d (got %d).', $fieldName, $max, $len);
            }
        }

        // min length
        if (isset($rule['min'])) {
            $min = (int) $rule['min'];
            $len = mb_strlen($value);

            if ($len < $min) {
                return sprintf('"%s" is shorter than min length %d (got %d).', $fieldName, $min, $len);
            }
        }

        // enum
        if (isset($rule['enum']) && is_array($rule['enum'])) {
            if (!in_array($value, $rule['enum'], true)) {
                return sprintf(
                    '"%s" must be one of [%s], got "%s".',
                    $fieldName,
                    implode(', ', $rule['enum']),
                    $value
                );
            }
        }

        // pattern
        if (isset($rule['pattern']) && is_string($rule['pattern']) && $value !== '') {
            if (!preg_match($rule['pattern'], $value)) {
                return sprintf('"%s" does not match expected pattern.', $fieldName);
            }
        }

        // format
        if (isset($rule['format']) && is_string($rule['format'])) {
            return self::validateFormat($fieldName, $value, $rule['format']);
        }

        return null;
    }

    // ──────────────────────────────────────────────────────────────────
    //  Format validation
    // ──────────────────────────────────────────────────────────────────

    /**
     * ValidateFormat.
     *
     * @return ?string
     */
    private static function validateFormat(string $fieldName, string $value, string $format): ?string
    {
        // Empty strings pass all format checks — emptiness is a separate concern
        // handled by 'min' or 'required' rules.
        if ($value === '') {
            // Except for strict formats that don't allow empty.
            if (in_array($format, ['email', 'url', 'hex_color', 'email_csv'], true)) {
                return sprintf('"%s" cannot be empty for format "%s".', $fieldName, $format);
            }

            return null;
        }

        switch ($format) {
            case 'email':
                if (!self::isValidEmail($value)) {
                    return sprintf('"%s" is not a valid email address.', $fieldName);
                }
                break;

            case 'email_or_empty':
                if (!self::isValidEmail($value)) {
                    return sprintf('"%s" is not a valid email address.', $fieldName);
                }
                break;

            case 'email_csv':
            case 'email_csv_or_empty':
                foreach (explode(',', $value) as $addr) {
                    $addr = trim($addr);
                    if ($addr !== '' && !self::isValidEmail($addr)) {
                        return sprintf('"%s" contains invalid email "%s".', $fieldName, $addr);
                    }
                }
                break;

            case 'hex_color':
                if (!preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                    return sprintf('"%s" must be a hex colour (#rrggbb), got "%s".', $fieldName, $value);
                }
                break;

            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    return sprintf('"%s" is not a valid URL.', $fieldName);
                }
                break;

            case 'url_or_empty':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    return sprintf('"%s" is not a valid URL.', $fieldName);
                }
                break;

            default:
                /**
                 * Allows external code to validate custom format identifiers.
                 *
                 * Return a non-null string to signal a validation error.
                 * Return null to indicate the value is valid.
                 *
                 * @param string|null $error     Default null (valid).
                 * @param string      $fieldName The field being validated.
                 * @param string      $value     The value to validate.
                 * @param string      $format    The format identifier.
                 */
                $custom = apply_filters('hyperfields/validation/format', null, $fieldName, $value, $format);
                if (is_string($custom)) {
                    return $custom;
                }
                break;
        }

        return null;
    }

    // ──────────────────────────────────────────────────────────────────
    //  Array sub-field validation
    // ──────────────────────────────────────────────────────────────────

    /**
     * Validates fields inside an array value against a keyed sub-schema.
     *
     * Returns the first error found, or null if all valid.
     */
    private static function validateArrayFields(string $parentName, array $value, array $fieldRules): ?string
    {
        foreach ($value as $fieldName => $fieldValue) {
            $fieldName = (string) $fieldName;
            $rule = $fieldRules[$fieldName] ?? null;

            if ($rule === null) {
                continue;
            }

            $qualifiedName = sprintf('%s.%s', $parentName, $fieldName);
            $error = self::validate($qualifiedName, $fieldValue, $rule);

            if ($error !== null) {
                return $error;
            }
        }

        return null;
    }

    // ──────────────────────────────────────────────────────────────────
    //  Email helper
    // ──────────────────────────────────────────────────────────────────

    /**
     * Validates an email address using WordPress is_email() when available,
     * falling back to filter_var().
     */
    private static function isValidEmail(string $email): bool
    {
        if (function_exists('is_email')) {
            return (bool) is_email($email);
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
