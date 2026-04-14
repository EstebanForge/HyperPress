/**
 * TextareaField Component
 *
 * Textarea input field with WordPress styling.
 *
 * @package HyperFields
 */

import { TextareaControl } from '@wordpress/components';

/**
 * TextareaField Component
 *
 * @param {Object} props - Component props
 * @param {string} props.name - Field name
 * @param {string} props.label - Field label
 * @param {string} props.value - Field value
 * @param {Function} props.onChange - Change callback
 * @param {string} props.placeholder - Placeholder text
 * @param {boolean} props.required - Whether field is required
 * @param {string} props.help - Help text
 * @param {number} props.rows - Number of rows
 * @return {JSX.Element} Rendered component
 */
export default function TextareaField({
    name,
    label,
    value = '',
    onChange,
    placeholder = '',
    required = false,
    help = '',
    rows = 5,
}) {
    return (
        <div className="hyperpress-field-textarea">
            <TextareaControl
                label={label}
                value={value}
                onChange={onChange}
                placeholder={placeholder}
                required={required}
                help={help}
                rows={rows}
                name={name}
            />
        </div>
    );
}
