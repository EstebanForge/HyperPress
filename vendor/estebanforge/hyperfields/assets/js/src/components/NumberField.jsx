/**
 * NumberField Component
 *
 * Number input field with WordPress styling.
 *
 * @package HyperFields
 */

import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';

/**
 * NumberField Component
 *
 * @param {Object} props - Component props
 * @param {string} props.name - Field name
 * @param {string} props.label - Field label
 * @param {number|string} props.value - Field value
 * @param {Function} props.onChange - Change callback
 * @param {string} props.placeholder - Placeholder text
 * @param {boolean} props.required - Whether field is required
 * @param {string} props.help - Help text
 * @param {number|null} props.min - Minimum value
 * @param {number|null} props.max - Maximum value
 * @param {number} props.step - Step increment
 * @return {JSX.Element} Rendered component
 */
export default function NumberField({
    name,
    label,
    value = '',
    onChange,
    placeholder = '',
    required = false,
    help = '',
    min = null,
    max = null,
    step = 1,
}) {
    const handleChange = (newValue) => {
        const numValue = newValue === '' ? '' : parseFloat(newValue);
        onChange(numValue);
    };

    return (
        <div className="hyperpress-field-number">
            <TextControl
                label={label}
                type="number"
                value={value}
                onChange={handleChange}
                placeholder={placeholder}
                required={required}
                help={help}
                min={min}
                max={max}
                step={step}
                name={name}
            />
        </div>
    );
}
