console.log('Conditional Logic Test Loaded');

// Test if the HyperFieldsConditional class is available
if (typeof HyperFieldsConditional !== 'undefined') {
    console.log('HyperFieldsConditional class is available');
    
    // Wait a bit for DOM to be ready
    setTimeout(() => {
        const conditionalFields = document.querySelectorAll('[data-hm-conditional-logic]');
        console.log(`Found ${conditionalFields.length} conditional fields`);
        
        conditionalFields.forEach((field, index) => {
            try {
                const logicData = JSON.parse(field.getAttribute('data-hm-conditional-logic'));
                console.log(`Field ${index}:`, logicData);
            } catch (e) {
                console.warn(`Field ${index} has invalid conditional logic data:`, e);
            }
        });
    }, 1000);
} else {
    console.log('HyperFieldsConditional class is NOT available');
}