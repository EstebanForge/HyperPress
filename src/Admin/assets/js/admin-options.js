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

    // Copy to clipboard functionality for API URL
    const copyButtons = document.querySelectorAll('.copy-api-url');
    copyButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const textToCopy = this.getAttribute('data-clipboard-text');
            const originalText = this.textContent;
            
            navigator.clipboard.writeText(textToCopy).then(() => {
                // Show success message
                this.textContent = 'Copied!';
                this.classList.add('button-primary');
                this.classList.remove('button-secondary');
                
                // Reset button after 2 seconds
                setTimeout(() => {
                    this.textContent = originalText;
                    this.classList.add('button-secondary');
                    this.classList.remove('button-primary');
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        });
    });
});
