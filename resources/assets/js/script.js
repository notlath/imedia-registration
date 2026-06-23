/**
 * IMedia Forms — Frontend Script
 * Handles date interactions and form validation + submission.
 */
document.addEventListener('DOMContentLoaded', () => {

    /* =============================================
       CUSTOM MINIMALIST DATE PICKER (No Libraries)
       ============================================= */
    const createCustomPicker = (displayInput, hiddenInput) => {
        let currentDate = new Date();
        let selectedDate = hiddenInput.value ? new Date(hiddenInput.value) : null;
        if (selectedDate && !isNaN(selectedDate)) currentDate = new Date(selectedDate);

        const picker = document.createElement('div');
        picker.className = 'imf-custom-picker';
        picker.style.display = 'none';

        const render = () => {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

            picker.innerHTML = `
                <div class="imf-picker-header">
                    <button type="button" class="imf-picker-prev">&lsaquo;</button>
                    <div class="imf-picker-month-year">
                        <select class="imf-picker-select-month">
                            ${months.map((m, i) => `<option value="${i}" ${i === month ? 'selected' : ''}>${m}</option>`).join('')}
                        </select>
                        <select class="imf-picker-select-year">
                            ${(() => {
                                let years = '';
                                const curY = new Date().getFullYear();
                                for (let y = curY - 100; y <= curY + 100; y++) {
                                    years += `<option value="${y}" ${y === year ? 'selected' : ''}>${y}</option>`;
                                }
                                return years;
                            })()}
                        </select>
                    </div>
                    <button type="button" class="imf-picker-next">&rsaquo;</button>
                </div>
                <div class="imf-picker-weekdays">
                    <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                </div>
                <div class="imf-picker-days"></div>
            `;

            // Month/Year Select Listeners
            picker.querySelector('.imf-picker-select-month').addEventListener('change', (e) => {
                currentDate.setMonth(parseInt(e.target.value));
                render();
            });
            picker.querySelector('.imf-picker-select-year').addEventListener('change', (e) => {
                currentDate.setFullYear(parseInt(e.target.value));
                render();
            });

            const daysGrid = picker.querySelector('.imf-picker-days');
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            for (let i = 0; i < firstDay; i++) {
                daysGrid.appendChild(document.createElement('div'));
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const dayEl = document.createElement('div');
                dayEl.textContent = day;
                dayEl.className = 'imf-picker-day';
                if (selectedDate && selectedDate.getDate() === day && selectedDate.getMonth() === month && selectedDate.getFullYear() === year) {
                    dayEl.classList.add('selected');
                }
                dayEl.addEventListener('click', () => {
                    selectedDate = new Date(year, month, day);
                    const yyyy = selectedDate.getFullYear();
                    const mm = String(selectedDate.getMonth() + 1).padStart(2, '0');
                    const dd = String(selectedDate.getDate()).padStart(2, '0');
                    hiddenInput.value = `${yyyy}-${mm}-${dd}`;
                    displayInput.value = selectedDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                    hide();
                    hiddenInput.dispatchEvent(new Event('change'));
                });
                daysGrid.appendChild(dayEl);
            }

            picker.querySelector('.imf-picker-prev').addEventListener('click', (e) => {
                e.stopPropagation();
                currentDate.setMonth(currentDate.getMonth() - 1);
                render();
            });
            picker.querySelector('.imf-picker-next').addEventListener('click', (e) => {
                e.stopPropagation();
                currentDate.setMonth(currentDate.getMonth() + 1);
                render();
            });
        };

        const updatePosition = () => {
            const rect = displayInput.getBoundingClientRect();
            const pickerHeight = 320; // Estimated height
            const spaceBelow = window.innerHeight - rect.bottom;
            const spaceAbove = rect.top;

            // Smart flipping: Show on top if not enough space below
            if (spaceBelow < pickerHeight && spaceAbove > spaceBelow) {
                picker.style.top = (window.scrollY + rect.top - pickerHeight - 10) + 'px';
                picker.classList.add('imf-picker-top');
            } else {
                picker.style.top = (window.scrollY + rect.bottom + 5) + 'px';
                picker.classList.remove('imf-picker-top');
            }
            picker.style.left = (window.scrollX + rect.left) + 'px';
        };

        const show = () => {
            picker.style.display = 'block';
            updatePosition();
            document.addEventListener('mousedown', handleClickOutside);
            render();
        };

        const hide = () => {
            picker.style.display = 'none';
            document.removeEventListener('mousedown', handleClickOutside);
        };

        const handleClickOutside = (e) => {
            if (!picker.contains(e.target) && e.target !== displayInput) hide();
        };

        document.body.appendChild(picker);
        return { show, hide };
    };

    /* =============================================
       DATE PICKER — Connect display input to custom picker
       ============================================= */
    document.querySelectorAll('.imf-datepicker-wrap').forEach(wrap => {
        const display = wrap.querySelector('.imf-datepicker-display');
        const hidden  = wrap.querySelector('.imf-datepicker-hidden');
        const btn     = wrap.querySelector('.imf-datepicker-btn');
        if (!display || !hidden) return;

        const customPicker = createCustomPicker(display, hidden);

        const openPicker = (e) => {
            e.preventDefault();
            e.stopPropagation();
            customPicker.show();
        };

        display.addEventListener('click', openPicker);
        if (btn) btn.addEventListener('click', openPicker);

        // Ensure resizing window doesn't break positioning
        window.addEventListener('resize', () => customPicker.hide());
    });

    /* =============================================
       DATE FIELD — Auto-advance between MM / DD / YYYY inputs
       ============================================= */
    document.querySelectorAll('.imf-date-fields').forEach(wrap => {
        const inputs = wrap.querySelectorAll('.imf-date-part input[type="text"]');
        inputs.forEach((input, i) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '');
                if (input.value.length >= parseInt(input.maxLength) && i < inputs.length - 1) {
                    inputs[i + 1].focus();
                }
            });
        });
    });

    /* =============================================
       CUSTOM FILE UPLOAD DROP-ZONE
       ============================================= */
    document.querySelectorAll('.imf-field input[type="file"]').forEach(fileInput => {
        // Wrap the input so we can position it absolutely
        const fieldEl = fileInput.closest('.imf-field');
        if (!fieldEl) return;

        // Make field position:relative so absolute hidden input works
        fieldEl.style.position = 'relative';

        const accept = fileInput.accept ? `Accepted: ${fileInput.accept}` : 'Any file type accepted';

        const zone = document.createElement('div');
        zone.className = 'imf-file-dropzone';
        zone.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            <div class="imf-file-dropzone-text"><span>Click to upload</span> or drag & drop</div>
            <div class="imf-file-dropzone-hint">${accept}</div>
        `;

        // Insert zone right before the hidden input
        fileInput.parentNode.insertBefore(zone, fileInput);

        let fileNameEl = null;

        const showFileName = (name) => {
            if (fileNameEl) fileNameEl.remove();
            fileNameEl = document.createElement('div');
            fileNameEl.className = 'imf-file-name';
            fileNameEl.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="imf-file-remove" title="Remove file">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
                ${name}
            `;
            zone.after(fileNameEl);
            fileNameEl.querySelector('.imf-file-remove').addEventListener('click', () => {
                fileInput.value = '';
                fileNameEl.remove();
                fileNameEl = null;
                zone.style.display = '';
            });
            zone.style.display = 'none';
        };

        // Click to browse
        zone.addEventListener('click', () => fileInput.click());

        // File selected via dialog
        fileInput.addEventListener('change', () => {
            if (fileInput.files && fileInput.files[0]) {
                showFileName(fileInput.files[0].name);
            }
        });

        // Drag & drop
        zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('imf-drag-over'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('imf-drag-over'));
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('imf-drag-over');
            const dt = e.dataTransfer;
            if (dt && dt.files && dt.files[0]) {
                // Assign dropped file to the real input via DataTransfer
                const transfer = new DataTransfer();
                transfer.items.add(dt.files[0]);
                fileInput.files = transfer.files;
                showFileName(dt.files[0].name);
            }
        });
    });

    // Clear file drop-zones on form reset
    document.querySelectorAll('.imf-frontend-form').forEach(form => {
        form.addEventListener('reset', () => {
            form.querySelectorAll('.imf-file-name').forEach(el => el.remove());
            form.querySelectorAll('.imf-file-dropzone').forEach(z => z.style.display = '');
        });
    });

    /* =============================================
       INPUT LIMITS & DIGIT ENFORCEMENT
       ============================================= */
    document.querySelectorAll('.imf-field').forEach(field => {
        const maxLen = field.dataset.maxlength ? parseInt(field.dataset.maxlength) : null;
        const inputs = field.querySelectorAll('input:not([type="hidden"]), textarea');
        
        inputs.forEach(input => {
            if (maxLen) {
                // Apply maxlength attribute directly so native HTML enforces it
                input.setAttribute('maxlength', maxLen);
            }
            
            // If field type is number or phone (tel), restrict to digits
            if (input.type === 'tel' || input.type === 'number') {
                input.addEventListener('input', () => {
                    // Force digits only
                    input.value = input.value.replace(/\D/g, '');
                    // Force max length for type="number" where maxlength might fail natively
                    if (maxLen && input.value.length > maxLen) {
                        input.value = input.value.slice(0, maxLen);
                    }
                });
            }
        });
    });

    /* =============================================
       FORM SUBMISSION HANDLER
       ============================================= */
    document.querySelectorAll('.imf-form-wrap').forEach(wrap => {
        const form      = wrap.querySelector('.imf-frontend-form');
        const apiUrl    = (wrap.dataset.apiUrl || '').trim();
        const wpApiUrl  = (wrap.dataset.wpApiUrl || '').trim();
        const formId    = (wrap.dataset.formId || '').trim();
        const messageEl = wrap.querySelector('.imf-form-message');
        const submitBtn = form ? form.querySelector('.imf-submit-btn') : null;

        if (!form || !submitBtn || !messageEl) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            /* ---- Clear previous errors ---- */
            form.querySelectorAll('.imf-error').forEach(el => el.classList.remove('imf-error'));
            form.querySelectorAll('.imf-error-msg').forEach(el => {
                el.classList.remove('visible');
                el.textContent = '';
            });
            messageEl.style.display = 'none';
            messageEl.className = 'imf-form-message';

            /* ---- Honeypot Check ---- */
            const hp = form.querySelector('input[name="imf_hp_email"]');
            if (hp && hp.value) {
                // Silently abort, trick the bot
                messageEl.className = 'imf-form-message success';
                messageEl.textContent = 'Form submitted successfully! Thank you.';
                messageEl.style.display = 'block';
                form.reset();
                return;
            }

            /* ---- Validate ---- */
            let isValid = true;

            const showError = (fieldEl, inputEl, msg) => {
                isValid = false;
                if (inputEl) inputEl.classList.add('imf-error');
                let errEl = fieldEl.querySelector(':scope > .imf-error-msg');
                if (!errEl) {
                    errEl = document.createElement('div');
                    errEl.className = 'imf-error-msg';
                    fieldEl.appendChild(errEl);
                }
                errEl.textContent = msg;
                errEl.classList.add('visible');
            };

            form.querySelectorAll('.imf-field').forEach(fieldEl => {
                const customError = fieldEl.dataset.error || '';
                const minLen = fieldEl.dataset.minlength ? parseInt(fieldEl.dataset.minlength) : null;
                const maxLen = fieldEl.dataset.maxlength ? parseInt(fieldEl.dataset.maxlength) : null;
                const pattern = fieldEl.dataset.pattern || '';

                // ---- Detect field type by structure ----
                const isDatePicker  = !!fieldEl.querySelector('.imf-datepicker-wrap');
                const isDateFields  = !!fieldEl.querySelector('.imf-date-fields');
                const isDateDrops   = !!fieldEl.querySelector('.imf-date-dropdowns');
                const isNameField   = !!fieldEl.querySelector('input[name$="_first"]');
                const isAddress     = !!fieldEl.querySelector('.imf-address-grid');
                const isChoices     = !!fieldEl.querySelector('.imf-choices');

                // ---- Date Picker ----
                if (isDatePicker) {
                    const hidden  = fieldEl.querySelector('.imf-datepicker-hidden');
                    const display = fieldEl.querySelector('.imf-datepicker-display');
                    const isRequired = display && display.hasAttribute('required');
                    if (isRequired && (!hidden || !hidden.value.trim())) {
                        showError(fieldEl, display, customError || 'Please select a date.');
                    }
                    return;
                }

                // ---- Date Fields (MM/DD/YYYY text inputs) ----
                if (isDateFields) {
                    const inputs = fieldEl.querySelectorAll('.imf-date-part input[type="text"]');
                    const isRequired = inputs.length > 0 && inputs[0].hasAttribute('required');
                    if (isRequired) {
                        const allFilled = Array.from(inputs).every(i => i.value.trim() !== '');
                        if (!allFilled) {
                            showError(fieldEl, inputs[0], customError || 'Please complete the date.');
                        }
                    }
                    return;
                }

                // ---- Date Dropdowns ----
                if (isDateDrops) {
                    const selects = fieldEl.querySelectorAll('.imf-date-dd select');
                    const isRequired = selects.length > 0 && selects[0].hasAttribute('required');
                    if (isRequired) {
                        const allFilled = Array.from(selects).every(s => s.value !== '');
                        if (!allFilled) {
                            showError(fieldEl, selects[0], customError || 'Please complete the date.');
                        }
                    }
                    return;
                }

                // ---- Name field (first + last) ----
                if (isNameField) {
                    const halves = fieldEl.querySelectorAll('.imf-field-half input');
                    halves.forEach(inp => {
                        if (inp.hasAttribute('required') && !inp.value.trim()) {
                            showError(fieldEl, inp, customError || 'This field is required.');
                        }
                    });
                    return;
                }

                // ---- Address ----
                if (isAddress) {
                    const reqInputs = fieldEl.querySelectorAll('.imf-address-grid [required]');
                    reqInputs.forEach(inp => {
                        if (!inp.value.trim()) {
                            showError(fieldEl, inp, customError || 'This field is required.');
                        }
                    });
                    return;
                }

                // ---- Checkbox / Radio / Multi-choice ----
                if (isChoices) {
                    const inputs = fieldEl.querySelectorAll('.imf-choices input');
                    const isRequired = fieldEl.dataset.required === '1';
                    if (isRequired) {
                        const anyChecked = Array.from(inputs).some(i => i.checked);
                        if (!anyChecked) {
                            // Highlight the choices container
                            const choicesEl = fieldEl.querySelector('.imf-choices');
                            if (choicesEl) choicesEl.classList.add('imf-error');
                            showError(fieldEl, null, customError || 'Please select at least one option.');
                        }
                    }
                    return;
                }

                // ---- Standard single input / textarea / select ----
                const input = fieldEl.querySelector('input:not([type="hidden"]), textarea, select');
                if (!input) return;

                // Skip confirm email at this stage (handled separately below)
                if (input.name && input.name.endsWith('_confirm')) return;

                const value = input.value || '';

                if (input.hasAttribute('required') && !value.trim()) {
                    showError(fieldEl, input, customError || 'This field is required.');
                    return;
                }

                if (!value) return; // No further checks on empty optional fields

                if (minLen !== null && value.length < minLen) {
                    showError(fieldEl, input, customError || `Minimum ${minLen} characters required.`);
                    return;
                }
                if (maxLen !== null && value.length > maxLen) {
                    showError(fieldEl, input, customError || `Maximum ${maxLen} characters allowed.`);
                    return;
                }
                if (pattern) {
                    try {
                        if (!new RegExp(pattern).test(value)) {
                            showError(fieldEl, input, customError || 'Invalid format.');
                            return;
                        }
                    } catch (_) { /* ignore bad regex */ }
                }

                // Phone validation (digits only)
                if (input.type === 'tel') {
                    const cleaned = value.replace(/[\s\-\(\)\+]/g, '');
                    if (cleaned && !/^\d+$/.test(cleaned)) {
                        showError(fieldEl, input, customError || 'Please enter a valid phone number (digits only).');
                        return;
                    }
                }

                // Email confirmation check (STRICT MATCH)
                if (input.type === 'email') {
                    // Find if there is a _confirm field
                    const confirmInput = form.querySelector(`[name="${input.name}_confirm"]`);
                    if (confirmInput) {
                        // The user filled something in the original input, so we definitely need a match.
                        if (!confirmInput.value.trim()) {
                            const confirmField = confirmInput.closest('.imf-field-half') || confirmInput.closest('.imf-field');
                            if (confirmField) showError(confirmField, confirmInput, customError || 'Please confirm your email address.');
                        } else if (confirmInput.value !== value) {
                            const confirmField = confirmInput.closest('.imf-field-half') || confirmInput.closest('.imf-field');
                            if (confirmField) showError(confirmField, confirmInput, 'Email addresses do not match.');
                        }
                    }
                }
            });

            if (!isValid) {
                // Scroll to first error
                const firstError = form.querySelector('.imf-error-msg.visible');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            /* ---- Guard: No API URL configured ---- */
            if (!apiUrl) {
                messageEl.className = 'imf-form-message error';
                messageEl.textContent = 'This form has no API endpoint configured. Please contact the site administrator.';
                messageEl.style.display = 'block';
                return;
            }

            /* ---- reCAPTCHA Check ---- */
            const recaptcha = form.querySelector('.g-recaptcha');
            if (recaptcha) {
                if (typeof grecaptcha !== 'undefined') {
                    const resp = grecaptcha.getResponse();
                    if (!resp) {
                        isValid = false;
                        messageEl.className = 'imf-form-message error';
                        messageEl.textContent = 'Please complete the CAPTCHA.';
                        messageEl.style.display = 'block';
                        return;
                    }
                }
            }

            /* ---- Collect form data ---- */
            const formData = new FormData(form);
            const data = {};

            for (const [key, value] of formData.entries()) {
                if (key === 'imf_datepicker_hidden') continue; // skip internal helper
                if (key.endsWith('[]')) {
                    const cleanKey = key.slice(0, -2);
                    if (!data[cleanKey]) data[cleanKey] = [];
                    data[cleanKey].push(value);
                } else {
                    data[key] = value;
                }
            }

            // Remove confirmation fields from payload
            Object.keys(data).forEach(k => {
                if (k.endsWith('_confirm')) delete data[k];
            });
            // Remove honeypot
            if (data['imf_hp_email'] !== undefined) {
                delete data['imf_hp_email'];
            }
            // Add reCAPTCHA if present
            if (recaptcha && typeof grecaptcha !== 'undefined') {
                data['g-recaptcha-response'] = grecaptcha.getResponse();
            }

            /* ---- Submit ---- */
            submitBtn.disabled = true;
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Submitting…';

            try {
                if (!wpApiUrl || !formId) {
                    throw new Error('Form configuration is missing (wpApiUrl or formId).');
                }

                const wpFormData = new FormData(form);
                wpFormData.append('_imf_form_id', formId);
                
                // Cleanup confirmation and honeypot fields for WP backend
                wpFormData.delete('imf_hp_email');
                for (let k of wpFormData.keys()) {
                    if (k.endsWith('_confirm')) wpFormData.delete(k);
                }

                // Asynchronous submission (fire and forget)
                fetch(wpApiUrl, {
                    method: 'POST',
                    body: wpFormData, // Send as FormData to support $_FILES
                }).catch(err => console.error('IMedia Forms backend error:', err));

                // Wait 1 second before showing success message
                setTimeout(() => {
                    messageEl.className = 'imf-form-message success';
                    messageEl.textContent = 'Form submitted successfully! Thank you.';
                    messageEl.style.display = 'block';
                    form.reset();
                    
                    // Clear date picker displays after reset
                    form.querySelectorAll('.imf-datepicker-display').forEach(d => d.value = '');
                    if (recaptcha && typeof grecaptcha !== 'undefined') {
                        grecaptcha.reset();
                    }

                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }, 1000);

            } catch (err) {
                messageEl.className = 'imf-form-message error';
                messageEl.textContent = err.message || 'An unexpected error occurred. Please try again.';
                messageEl.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    });
});
