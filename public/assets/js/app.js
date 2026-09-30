(() => {
    'use strict';

    const textarea = document.querySelector('#content');
    const counter = document.querySelector('#character-count');
    const form = document.querySelector('#paste-form');
    const saveButton = document.querySelector('#save-button');
    const formState = document.querySelector('#form-state');
    const successState = document.querySelector('#success-state');
    const createAnotherButton = document.querySelector('#create-another-button');
    const generatedUrl = document.querySelector('#generated-url');
    const errorContainer = document.querySelector('#form-error');

    const updateCount = () => {
        if (!textarea || !counter) {
            return;
        }

        const maxLength = Number(textarea.getAttribute('maxlength') || 0);
        counter.textContent = `${textarea.value.length} / ${maxLength}`;
    };

    const showError = (message) => {
        if (!errorContainer) {
            return;
        }

        errorContainer.classList.remove('d-none');
        errorContainer.innerHTML = '<div class="alert alert-danger" role="alert"></div>';
        const alert = errorContainer.querySelector('.alert');

        if (alert) {
            alert.textContent = message;
        }
    };

    const clearError = () => {
        if (!errorContainer) {
            return;
        }

        errorContainer.classList.add('d-none');
        errorContainer.innerHTML = '';
    };

    const swapToSuccess = (url) => {
        if (generatedUrl) {
            generatedUrl.value = url;
            generatedUrl.focus();
            generatedUrl.select();
        }

        formState?.classList.add('d-none');
        successState?.classList.remove('d-none');
    };

    const swapToForm = () => {
        successState?.classList.add('d-none');
        formState?.classList.remove('d-none');
        form?.reset();
        clearError();
        updateCount();
        textarea?.focus();
    };

    const flashButtonState = (button, text) => {
        const originalText = button.textContent;
        button.textContent = text;
        window.setTimeout(() => {
            button.textContent = originalText;
        }, 1400);
    };

    const copyToClipboard = async (text) => {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return;
        }

        const fallback = document.createElement('textarea');
        fallback.value = text;
        fallback.setAttribute('readonly', '');
        fallback.style.position = 'fixed';
        fallback.style.opacity = '0';
        fallback.style.pointerEvents = 'none';
        fallback.style.left = '-9999px';

        document.body.appendChild(fallback);
        fallback.focus();
        fallback.select();
        fallback.setSelectionRange(0, fallback.value.length);

        const copied = document.execCommand('copy');
        document.body.removeChild(fallback);

        if (!copied) {
            throw new Error('Clipboard copy failed.');
        }
    };

    if (textarea && counter) {
        textarea.addEventListener('input', updateCount);
        updateCount();
    }

    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearError();

            const originalButtonText = saveButton?.textContent || 'Save';

            if (saveButton) {
                saveButton.disabled = true;
                saveButton.textContent = 'Saving…';
            }

            try {
                const createEndpoint = form.dataset.createEndpoint || form.action;
                const response = await fetch(createEndpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'fetch'
                    },
                    body: new FormData(form)
                });

                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    showError(payload.error || 'Unable to save the paste. Please try again.');
                    return;
                }

                swapToSuccess(payload.url || '');
            } catch (error) {
                showError('Unable to save the paste right now. Please try again.');
            } finally {
                if (saveButton) {
                    saveButton.disabled = false;
                    saveButton.textContent = originalButtonText;
                }
            }
        });
    }

    if (createAnotherButton) {
        createAnotherButton.addEventListener('click', (event) => {
            event.preventDefault();
            swapToForm();
            history.replaceState({}, document.title, '/');
        });
    }

    document.querySelectorAll('[data-copy-target]').forEach((button) => {
        button.addEventListener('click', async () => {
            const target = document.querySelector(button.dataset.copyTarget);

            if (!target) {
                return;
            }

            try {
                await copyToClipboard(target.value);
                flashButtonState(button, 'Copied');
            } catch (error) {
                target.focus();
                target.select();
                flashButtonState(button, 'Select & Copy');
            }
        });
    });

    document.querySelectorAll('[data-copy-text]').forEach((button) => {
        button.addEventListener('click', async () => {
            const target = document.querySelector(button.dataset.copyText);

            if (!target) {
                return;
            }

            try {
                await copyToClipboard(target.textContent || '');
                flashButtonState(button, 'Copied');
            } catch (error) {
                flashButtonState(button, 'Copy Failed');
            }
        });
    });
})();
