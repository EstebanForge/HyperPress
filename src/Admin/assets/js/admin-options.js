document.addEventListener('DOMContentLoaded', function () {
    const activeLibrarySelect = document.querySelector('#hmapi_options_active_library');

    if (activeLibrarySelect) {
        activeLibrarySelect.addEventListener('change', function () {
            // Show a saving message
            const submitButton = document.querySelector('p.submit input[type="submit"]');
            if (submitButton) {
                const savingMessage = document.createElement('span');
                savingMessage.className = 'spinner is-active';
                savingMessage.style.float = 'none';
                savingMessage.style.marginTop = '5px';
                submitButton.parentNode.insertBefore(savingMessage, submitButton.nextSibling);
            }
            // Automatically submit the form to save the change and reload the page
            document.querySelector('#hmapi-options-form').submit();
        });
    }
});
