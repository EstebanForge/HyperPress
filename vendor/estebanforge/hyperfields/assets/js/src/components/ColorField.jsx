/**
 * ColorField Component
 *
 * Color picker field with WordPress styling.
 *
 * @package HyperFields
 */

import { ColorPicker } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * ColorField Component
 *
 * @param {Object} props - Component props
 * @param {string} props.name - Field name
 * @param {string} props.label - Field label
 * @param {string} props.value - Field value (hex color)
 * @param {Function} props.onChange - Change callback
 * @param {boolean} props.required - Whether field is required
 * @param {string} props.help - Help text
 * @param {boolean} props.alpha - Whether to support alpha channel
 * @return {JSX.Element} Rendered component
 */
export default function ColorField({
    name,
    label,
    value = '#000000',
    onChange,
    required = false,
    help = '',
    alpha = false,
}) {
    const [color, setColor] = useState(value);

    const handleChange = (newColor) => {
        setColor(newColor);
        onChange(newColor);
    };

    return (
        <div className="hyperpress-field-color">
            <label className="hyperpress-field-label">
                {label}
                {required && <span className="required"> *</span>}
            </label>
            <ColorPicker
                color={color}
                onChange={handleChange}
                enableAlpha={alpha}
                copyFormat={alpha ? 'rgba' : 'hex'}
            />
            {help && <p className="description">{help}</p>}
        </div>
    );
}
