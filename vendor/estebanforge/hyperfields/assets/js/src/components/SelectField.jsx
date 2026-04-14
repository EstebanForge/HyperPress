/**
 * SelectField Component
 *
 * Select dropdown field with WordPress styling.
 *
 * @package HyperFields
 */

import { SelectControl } from '@wordpress/components';

/**
 * SelectField Component
 *
 * @param {Object} props - Component props
 * @param {string} props.name - Field name
 * @param {string} props.label - Field label
 * @param {string} props.value - Field value
 * @param {Function} props.onChange - Change callback
 * @param {boolean} props.required - Whether field is required
 * @param {string} props.help - Help text
 * @param {Array} props.options - Array of {value, label} options
 * @return {JSX.Element} Rendered component
 */
export default function SelectField({
    name,
    label,
    value = '',
    onChange,
    required = false,
    help = '',
    options = [],
}) {
    return (
        <div className="hyperpress-field-select">
            <SelectControl
                label={label}
                value={value}
                onChange={onChange}
                options={options}
                required={required}
                help={help}
                name={name}
            />
        </div>
    );
}
