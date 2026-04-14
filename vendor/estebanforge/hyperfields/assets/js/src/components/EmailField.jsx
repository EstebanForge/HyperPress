/**
 * EmailField Component
 *
 * Email input field with WordPress styling.
 *
 * @package HyperFields
 */

import { TextControl } from '@wordpress/components';

/**
 * EmailField Component
 *
 * @param {Object} props - Component props
 * @param {string} props.name - Field name
 * @param {string} props.label - Field label
 * @param {string} props.value - Field value
 * @param {Function} props.onChange - Change callback
 * @param {string} props.placeholder - Placeholder text
 * @param {boolean} props.required - Whether field is required
 * @param {string} props.help - Help text
 * @return {JSX.Element} Rendered component
 */
export default function EmailField({
    name,
    label,
    value = '',
    onChange,
    placeholder = '',
    required = false,
    help = '',
}) {
    return (
        <div className="hyperpress-field-email">
            <TextControl
                label={label}
                type="email"
                value={value}
                onChange={onChange}
                placeholder={placeholder}
                required={required}
                help={help}
                name={name}
            />
        </div>
    );
}
