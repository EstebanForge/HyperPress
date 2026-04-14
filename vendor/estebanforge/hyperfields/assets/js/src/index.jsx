/**
 * HyperFields React App - Main Entry Point
 *
 * This React app provides modern UI components for HyperFields options pages.
 * It integrates with WordPress' wp-element and wp-components libraries.
 *
 * @package HyperFields
 */

import { createRoot } from '@wordpress/element';
import { dispatch } from '@wordpress/data';
import ReactFieldsApp from './ReactFieldsApp';

/**
 * Initialize the HyperFields React app when DOM is ready
 */
function initHyperFieldsReact() {
    const reactRoot = document.getElementById('hyperpress-react-root');

    if (!reactRoot) {
        return;
    }

    // Check if we have data passed from PHP
    if (!window.hyperfieldsReactData) {
        console.error('HyperFields: No data provided from PHP');
        return;
    }

    const { fields, optionName, values, strings } = window.hyperfieldsReactData;

    // Create React root and render the app
    const root = createRoot(reactRoot);
    root.render(
        <ReactFieldsApp
            fields={fields}
            optionName={optionName}
            values={values}
            strings={strings}
        />
    );
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHyperFieldsReact);
} else {
    initHyperFieldsReact();
}

// Export for testing
export default initHyperFieldsReact;
