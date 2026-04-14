/**
 * ReactFieldsApp - Main application component
 *
 * Renders all React-enhanced fields for the current options page.
 *
 * @package HyperFields
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import TextField from './components/TextField';
import TextareaField from './components/TextareaField';
import NumberField from './components/NumberField';
import EmailField from './components/EmailField';
import UrlField from './components/UrlField';
import ColorField from './components/ColorField';
import ImageField from './components/ImageField';
import CheckboxField from './components/CheckboxField';
import SelectField from './components/SelectField';

// Map field types to their React components
const fieldComponents = {
    text: TextField,
    textarea: TextareaField,
    number: NumberField,
    email: EmailField,
    url: UrlField,
    color: ColorField,
    image: ImageField,
    checkbox: CheckboxField,
    select: SelectField,
};

/**
 * ReactFieldsApp Component
 *
 * @param {Object} props - Component props
 * @param {Array} props.fields - Array of field configurations
 * @param {string} props.optionName - WordPress option name
 * @param {Object} props.values - Current field values
 * @param {Object} props.strings - Translated strings
 * @return {JSX.Element} Rendered component
 */
export default function ReactFieldsApp({ fields, optionName, values, strings }) {
    const [fieldValues, setFieldValues] = useState({ ...values });

    // Update hidden inputs when field values change
    useEffect(() => {
        fields.forEach((field) => {
            const input = document.querySelector(
                `input[name="${optionName}[${field.name}]"], textarea[name="${optionName}[${field.name}]"], select[name="${optionName}[${field.name}]"]`
            );

            if (input) {
                input.value = fieldValues[field.name] ?? field.value ?? '';
            }
        });
    }, [fieldValues, fields, optionName]);

    // Handle field value changes
    const handleFieldChange = (fieldName, newValue) => {
        setFieldValues((prev) => ({
            ...prev,
            [fieldName]: newValue,
        }));
    };

    if (!fields || fields.length === 0) {
        return null;
    }

    return (
        <div className="hyperpress-react-fields">
            {fields.map((field) => {
                const FieldComponent = fieldComponents[field.type];

                if (!FieldComponent) {
                    console.warn(`HyperFields: No component for field type "${field.type}"`);
                    return null;
                }

                return (
                    <div key={field.name} className="hyperpress-react-field-wrapper">
                        <FieldComponent
                            {...field.props}
                            name={field.name}
                            label={field.label}
                            value={fieldValues[field.name] ?? field.value ?? field.props?.default ?? ''}
                            onChange={(newValue) => handleFieldChange(field.name, newValue)}
                        />
                    </div>
                );
            })}
        </div>
    );
}
