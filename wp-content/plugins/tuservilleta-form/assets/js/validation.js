/**
 * Tuservilleta Form Plugin - Client-side Validation
 * Provides real-time form validation and enhanced user experience
 */

(function($) {
    'use strict';
    
    // Wait for DOM to be ready
    $(document).ready(function() {
        
        const form = $('#tuservilleta-form');
        
        if (form.length === 0) {
            return;
        }
        
        /**
         * Validation Rules
         */
        const validationRules = {
            tuservilleta_name: {
                required: true,
                minLength: 2,
                maxLength: 100
            },
            tuservilleta_email: {
                required: true,
                email: true
            },
            tuservilleta_phone: {
                required: false,
                phone: true
            },
            tuservilleta_subject: {
                required: true,
                minLength: 3,
                maxLength: 200
            },
            tuservilleta_message: {
                required: true,
                minLength: 10,
                maxLength: 2000
            }
        };
        
        /**
         * Validate individual field
         */
        function validateField(field) {
            const fieldName = field.attr('name');
            const fieldValue = field.val().trim();
            const rules = validationRules[fieldName];
            
            if (!rules) {
                return true;
            }
            
            // Clear previous error
            clearFieldError(field);
            
            // Required validation
            if (rules.required && fieldValue === '') {
                showFieldError(field, tuservilletaForm.messages.required);
                return false;
            }
            
            // Skip other validations if field is empty and not required
            if (!rules.required && fieldValue === '') {
                return true;
            }
            
            // Email validation
            if (rules.email && !isValidEmail(fieldValue)) {
                showFieldError(field, tuservilletaForm.messages.email);
                return false;
            }
            
            // Phone validation
            if (rules.phone && fieldValue !== '' && !isValidPhone(fieldValue)) {
                showFieldError(field, tuservilletaForm.messages.phone);
                return false;
            }
            
            // Min length validation
            if (rules.minLength && fieldValue.length < rules.minLength) {
                showFieldError(field, 'Minimum ' + rules.minLength + ' characters required.');
                return false;
            }
            
            // Max length validation
            if (rules.maxLength && fieldValue.length > rules.maxLength) {
                showFieldError(field, 'Maximum ' + rules.maxLength + ' characters allowed.');
                return false;
            }
            
            // Field is valid
            field.addClass('success').removeClass('error');
            return true;
        }
        
        /**
         * Validate entire form
         */
        function validateForm() {
            let isValid = true;
            
            form.find('input[required], textarea[required]').each(function() {
                if (!validateField($(this))) {
                    isValid = false;
                }
            });
            
            // Also validate optional fields if they have values
            form.find('input:not([required]), textarea:not([required])').each(function() {
                const field = $(this);
                if (field.val().trim() !== '') {
                    if (!validateField(field)) {
                        isValid = false;
                    }
                }
            });
            
            return isValid;
        }
        
        /**
         * Show field error
         */
        function showFieldError(field, message) {
            field.addClass('error').removeClass('success');
            field.siblings('.tuservilleta-error-message')
                .text(message)
                .addClass('show');
        }
        
        /**
         * Clear field error
         */
        function clearFieldError(field) {
            field.removeClass('error success');
            field.siblings('.tuservilleta-error-message')
                .text('')
                .removeClass('show');
        }
        
        /**
         * Email validation helper
         */
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        /**
         * Phone validation helper (basic international format)
         */
        function isValidPhone(phone) {
            const phoneRegex = /^[\d\s\-\+\(\)]{7,20}$/;
            return phoneRegex.test(phone);
        }
        
        /**
         * Real-time validation on blur
         */
        form.find('input, textarea').on('blur', function() {
            validateField($(this));
        });
        
        /**
         * Clear error on focus
         */
        form.find('input, textarea').on('focus', function() {
            clearFieldError($(this));
        });
        
        /**
         * Real-time character counter for message field
         */
        const messageField = $('#tuservilleta_message');
        const maxChars = 2000;
        
        if (messageField.length > 0) {
            // Add character counter if not exists
            if (messageField.siblings('.char-counter').length === 0) {
                messageField.after('<span class="char-counter" style="font-size: 12px; color: #999; display: block; margin-top: 5px;"></span>');
            }
            
            const charCounter = messageField.siblings('.char-counter');
            
            messageField.on('input', function() {
                const currentLength = $(this).val().length;
                charCounter.text(currentLength + ' / ' + maxChars + ' characters');
                
                if (currentLength > maxChars) {
                    charCounter.css('color', '#e74c3c');
                } else if (currentLength > maxChars * 0.9) {
                    charCounter.css('color', '#f39c12');
                } else {
                    charCounter.css('color', '#999');
                }
            });
            
            // Initialize counter
            messageField.trigger('input');
        }
        
        /**
         * Form submission handler
         */
        form.on('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateForm()) {
                // Scroll to first error
                const firstError = form.find('.error').first();
                if (firstError.length > 0) {
                    $('html, body').animate({
                        scrollTop: firstError.offset().top - 100
                    }, 300);
                    firstError.focus();
                }
                return false;
            }
            
            // Disable submit button and show loading state
            const submitBtn = form.find('.tuservilleta-submit-btn');
            submitBtn.prop('disabled', true).addClass('loading');
            
            // Submit form normally (non-AJAX for compatibility and simplicity)
            // Note: This is secure because the server-side validates the nonce
            // and sanitizes all inputs regardless of client-side submission method
            this.submit();
            
            // Alternative AJAX submission (for enhanced UX without page reload):
            // Uncomment this block and comment out "this.submit()" above to enable
            /*
            $.ajax({
                url: tuservilletaForm.ajaxurl,
                type: 'POST',
                data: form.serialize() + '&action=tuservilleta_submit_form&nonce=' + tuservilletaForm.nonce,
                success: function(response) {
                    submitBtn.prop('disabled', false).removeClass('loading');
                    
                    if (response.success) {
                        // Show success message
                        form.before('<div class="tuservilleta-form-message tuservilleta-form-success">' + response.data.message + '</div>');
                        form[0].reset();
                        
                        // Remove success message after 5 seconds
                        setTimeout(function() {
                            $('.tuservilleta-form-success').fadeOut(function() {
                                $(this).remove();
                            });
                        }, 5000);
                    } else {
                        // Show error message
                        form.before('<div class="tuservilleta-form-message tuservilleta-form-error">' + response.data.message + '</div>');
                        
                        // Remove error message after 5 seconds
                        setTimeout(function() {
                            $('.tuservilleta-form-error').fadeOut(function() {
                                $(this).remove();
                            });
                        }, 5000);
                    }
                },
                error: function() {
                    submitBtn.prop('disabled', false).removeClass('loading');
                    form.before('<div class="tuservilleta-form-message tuservilleta-form-error">' + tuservilletaForm.messages.error + '</div>');
                }
            });
            */
        });
        
        /**
         * Auto-hide success/error messages
         */
        setTimeout(function() {
            $('.tuservilleta-form-message').fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
    });
    
})(jQuery);
