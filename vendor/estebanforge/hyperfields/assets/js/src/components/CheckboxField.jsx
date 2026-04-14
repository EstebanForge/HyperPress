/**
 * CheckboxField Component
 *
 * Checkbox input field with WordPress styling.
 *
 * @package HyperFields
 */

import { CheckboxControl } from '@wordpress/components';

/**
 * CheckboxField Component
 *
 * @param {Object} props - Component props
 * @param {string} props.name - Field name
 * @param {string} props.label - Field label
 * @param {boolean} props.value - Field value (checked state)
 * @param {Function} props.onChange - Change callback
 * @param {string} props.help - Help text
 * @param {string} props.checkedLabel - Label when checked
 * @param {string} props.uncheckedLabel - Label when unchecked
 * @return {JSX.Element} Rendered component
 */
export default function CheckboxField({
    name,
    label,
    value = false,
    onChange,
    help = '',
    checkedLabel = '',
    uncheckedLabel = '',
}) {
    return (
        <div className="hyperpress-field-checkbox">
            <CheckboxControl
                label={label}
                checked={!!value}
                onChange={onChange}
                help={help}
                name={name}
            />
        </div>
    );
}
