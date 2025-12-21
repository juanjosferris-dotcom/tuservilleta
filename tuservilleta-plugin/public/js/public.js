/**
 * TuServilleta Public JavaScript
 * Elegant step-by-step product configurator
 */
(function($) {
    'use strict';

    // Configuration state
    var TuServilletaApp = {
        currentStep: 1,
        totalSteps: 6,
        selections: {},
        price: 0,
        vat: 0,
        total: 0,
        availableOptions: {},

        init: function() {
            this.bindEvents();
            this.loadStep(1);
        },

        // Helper function to normalize text for comparison (remove punctuation, extra spaces, lowercase)
        normalizeText: function(text) {
            if (!text) return '';
            return text.toLowerCase()
                .trim()
                .replace(/[.,;:()]/g, '') // Remove punctuation
                .replace(/\s+/g, ' ')      // Normalize spaces
                .trim();
        },

        // Helper function to sort options based on predefined order
        sortOptions: function(options, stepKey) {
            var self = this;
            var predefinedOrder = tuservilleta.step_options[stepKey] || [];
            
            if (predefinedOrder.length === 0) {
                return options;
            }

            // Create normalized versions for matching
            var predefinedNormalized = predefinedOrder.map(function(opt) {
                return self.normalizeText(opt);
            });

            // Sort options based on predefined order
            return options.slice().sort(function(a, b) {
                var aNorm = self.normalizeText(a);
                var bNorm = self.normalizeText(b);

                // Find position in predefined order (use partial matching)
                var aIndex = -1;
                var bIndex = -1;

                for (var i = 0; i < predefinedNormalized.length; i++) {
                    var predNorm = predefinedNormalized[i];
                    if (aIndex === -1 && (aNorm === predNorm || aNorm.indexOf(predNorm) !== -1 || predNorm.indexOf(aNorm) !== -1)) {
                        aIndex = i;
                    }
                    if (bIndex === -1 && (bNorm === predNorm || bNorm.indexOf(predNorm) !== -1 || predNorm.indexOf(bNorm) !== -1)) {
                        bIndex = i;
                    }
                }

                // If both found in predefined, use that order
                if (aIndex !== -1 && bIndex !== -1) {
                    return aIndex - bIndex;
                }
                // If only one found, prioritize it
                if (aIndex !== -1) return -1;
                if (bIndex !== -1) return 1;
                // Otherwise alphabetical
                return a.localeCompare(b);
            });
        },

        // Helper function to sort quantity options numerically
        sortQuantities: function(options) {
            return options.slice().sort(function(a, b) {
                // Extract numeric value from strings like "250 uds.", "1.000 uds."
                var aNum = parseInt(a.replace(/\./g, '').replace(/[^\d]/g, ''), 10) || 0;
                var bNum = parseInt(b.replace(/\./g, '').replace(/[^\d]/g, ''), 10) || 0;
                return aNum - bNum;
            });
        },

        bindEvents: function() {
            var self = this;

            // Option selection (click on image or name to proceed)
            $(document).on('click', '.tuservilleta-option', function() {
                self.selectOption($(this));
            });

            // Quantity dropdown change
            $('#quantity-select').on('change', function() {
                var value = $(this).val();
                if (value) {
                    self.selections.quantity = value;
                    self.goToStep(6);
                }
            });

            // Back button
            $('#btn-back').on('click', function() {
                self.goBack();
            });

            // Contact button
            $('#btn-request-contact').on('click', function() {
                $('#tuservilleta-contact-modal').addClass('active');
            });

            // Pay button
            $('#btn-pay-now').on('click', function() {
                $('#tuservilleta-payment-modal').addClass('active');
                self.initPaymentMethods();
            });

            // Modal close buttons
            $('.modal-close, .modal-overlay').on('click', function() {
                $(this).closest('.tuservilleta-modal').removeClass('active');
            });

            // Prevent modal content click from closing
            $('.modal-content').on('click', function(e) {
                e.stopPropagation();
            });

            // Contact form submit
            $('#tuservilleta-contact-form').on('submit', function(e) {
                e.preventDefault();
                self.submitContactForm();
            });

            // Success modal close
            $('.btn-close-success').on('click', function() {
                $('#tuservilleta-success-modal').removeClass('active');
                location.reload();
            });
        },

        selectOption: function($option) {
            var step = this.currentStep;
            var stepMap = {1: 'size', 2: 'type', 3: 'color', 4: 'printing'};
            var stepKey = stepMap[step];

            if (!stepKey) return;

            // Get selected value
            var value = $option.data('value');
            this.selections[stepKey] = value;

            // Visual feedback
            $option.siblings().removeClass('selected');
            $option.addClass('selected');

            // Short delay for visual feedback, then proceed
            setTimeout(function() {
                this.goToStep(step + 1);
            }.bind(this), 200);
        },

        loadStep: function(step) {
            var self = this;
            var $container;
            var stepKey;

            switch(step) {
                case 1:
                    stepKey = 'size';
                    $container = $('#size-options');
                    break;
                case 2:
                    stepKey = 'type';
                    $container = $('#type-options');
                    break;
                case 3:
                    stepKey = 'color';
                    $container = $('#color-options');
                    break;
                case 4:
                    stepKey = 'printing';
                    $container = $('#printing-options');
                    break;
                case 5:
                    stepKey = 'quantity';
                    this.loadQuantityOptions();
                    return;
                case 6:
                    this.loadQuote();
                    return;
            }

            // Show loading
            $container.html('<div class="tuservilleta-loading"><div class="loading-spinner"></div></div>');

            // Get available options from server
            $.ajax({
                url: tuservilleta.ajax_url,
                type: 'POST',
                data: {
                    action: 'tuservilleta_get_options',
                    nonce: tuservilleta.nonce,
                    step: stepKey,
                    selections: this.selections
                },
                success: function(response) {
                    if (response.success) {
                        self.availableOptions[stepKey] = response.data;
                        self.renderOptions(stepKey, $container, response.data);
                    } else {
                        $container.html('<p style="text-align:center;color:#666;">No hay opciones disponibles para esta selección.</p>');
                    }
                },
                error: function() {
                    $container.html('<p style="text-align:center;color:#c00;">Error al cargar las opciones.</p>');
                }
            });
        },

        // Helper function to find matching image for an option
        findImageForOption: function(option, images, predefinedOptions) {
            var self = this;
            if (!option) return '';
            
            var normalizedOption = self.normalizeText(option);
            var MIN_PARTIAL_MATCH_LENGTH = 3; // Minimum length for partial matches
            
            // First try exact match
            if (images[option]) {
                return images[option];
            }
            
            // Try normalized exact match in images
            for (var key in images) {
                if (images.hasOwnProperty(key) && images[key]) {
                    var normalizedKey = self.normalizeText(key);
                    if (normalizedKey === normalizedOption) {
                        return images[key];
                    }
                }
            }
            
            // Try partial match with images (contains match)
            for (var key in images) {
                if (images.hasOwnProperty(key) && images[key]) {
                    var normalizedKey = self.normalizeText(key);
                    if (normalizedKey.length >= MIN_PARTIAL_MATCH_LENGTH && normalizedOption.length >= MIN_PARTIAL_MATCH_LENGTH) {
                        if (normalizedOption.indexOf(normalizedKey) !== -1 ||
                            normalizedKey.indexOf(normalizedOption) !== -1) {
                            return images[key];
                        }
                    }
                }
            }
            
            // Try matching with predefined options to find the corresponding image
            for (var i = 0; i < predefinedOptions.length; i++) {
                var predefined = predefinedOptions[i];
                var normalizedPredefined = self.normalizeText(predefined);
                
                // Check if option matches this predefined option
                if (normalizedOption === normalizedPredefined ||
                    normalizedOption.indexOf(normalizedPredefined) !== -1 ||
                    normalizedPredefined.indexOf(normalizedOption) !== -1) {
                    // Found a match, now get the image for this predefined option
                    if (images[predefined]) {
                        return images[predefined];
                    }
                    // Also try with index-based image key
                    var imageIndex = i + 1;
                    for (var imgKey in images) {
                        if (images.hasOwnProperty(imgKey) && images[imgKey]) {
                            if (self.normalizeText(imgKey) === normalizedPredefined) {
                                return images[imgKey];
                            }
                        }
                    }
                }
            }
            
            return '';
        },

        renderOptions: function(stepKey, $container, availableOptions) {
            var self = this;
            var html = '';
            var images = tuservilleta.images[stepKey] || {};
            var predefinedOptions = tuservilleta.step_options[stepKey] || [];
            
            // Sort options based on predefined order
            var sortedOptions = self.sortOptions(availableOptions, stepKey);

            // Show all available options from the database directly (sorted)
            sortedOptions.forEach(function(option) {
                if (!option || option.trim() === '') {
                    return; // Skip empty options
                }

                // Find matching image using flexible matching
                var imageUrl = self.findImageForOption(option, images, predefinedOptions);

                var imageHtml = imageUrl 
                    ? '<img src="' + imageUrl + '" alt="' + option + '">' 
                    : '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>';

                html += '<div class="tuservilleta-option" data-value="' + option + '">';
                html += '<div class="option-image">' + imageHtml + '</div>';
                html += '<div class="option-name">' + option + '</div>';
                html += '</div>';
            });

            if (html === '') {
                html = '<p style="text-align:center;color:#666;grid-column:1/-1;">No hay opciones disponibles para esta combinación.</p>';
            }

            $container.html(html);
        },

        loadQuantityOptions: function() {
            var self = this;
            var $select = $('#quantity-select');

            // Clear and add loading option
            $select.html('<option value="">Cargando...</option>');

            // Get available quantities from server
            $.ajax({
                url: tuservilleta.ajax_url,
                type: 'POST',
                data: {
                    action: 'tuservilleta_get_options',
                    nonce: tuservilleta.nonce,
                    step: 'quantity',
                    selections: this.selections
                },
                success: function(response) {
                    if (response.success) {
                        self.renderQuantityOptions(response.data);
                    } else {
                        $select.html('<option value="">No hay cantidades disponibles</option>');
                    }
                }
            });
        },

        renderQuantityOptions: function(availableOptions) {
            var self = this;
            var $select = $('#quantity-select');
            var html = '<option value="">Selecciona una cantidad</option>';

            // Sort quantities numerically (from lowest to highest)
            var sortedQuantities = self.sortQuantities(availableOptions);

            // Show all available options from the database directly (sorted)
            sortedQuantities.forEach(function(qty) {
                if (qty && qty.trim() !== '') {
                    html += '<option value="' + qty + '">' + qty + '</option>';
                }
            });

            $select.html(html);
        },

        loadQuote: function() {
            var self = this;

            // Get price from server
            $.ajax({
                url: tuservilleta.ajax_url,
                type: 'POST',
                data: {
                    action: 'tuservilleta_get_price',
                    nonce: tuservilleta.nonce,
                    size: this.selections.size,
                    type: this.selections.type,
                    color: this.selections.color,
                    printing: this.selections.printing,
                    quantity: this.selections.quantity
                },
                success: function(response) {
                    if (response.success) {
                        self.price = response.data.price;
                        self.vat = response.data.vat;
                        self.total = response.data.total;
                        self.renderQuote();
                    } else {
                        alert('Error al obtener el precio. Por favor, inténtalo de nuevo.');
                    }
                }
            });
        },

        renderQuote: function() {
            var labels = tuservilleta.labels;

            // Render summary
            var summaryHtml = '';
            summaryHtml += '<div class="summary-item"><span class="item-label">' + labels.size + '</span><span class="item-value">' + this.selections.size + '</span></div>';
            summaryHtml += '<div class="summary-item"><span class="item-label">' + labels.type + '</span><span class="item-value">' + this.selections.type + '</span></div>';
            summaryHtml += '<div class="summary-item"><span class="item-label">' + labels.color + '</span><span class="item-value">' + this.selections.color + '</span></div>';
            summaryHtml += '<div class="summary-item"><span class="item-label">' + labels.printing + '</span><span class="item-value">' + this.selections.printing + '</span></div>';
            summaryHtml += '<div class="summary-item"><span class="item-label">' + labels.quantity + '</span><span class="item-value">' + this.selections.quantity + '</span></div>';

            $('#summary-items').html(summaryHtml);

            // Render prices
            $('#price-without-vat').text(this.formatPrice(this.price));
            $('#price-vat').text(this.formatPrice(this.vat));
            $('#price-total').text(this.formatPrice(this.total));
        },

        formatPrice: function(price) {
            return parseFloat(price).toLocaleString('es-ES', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' €';
        },

        goToStep: function(step) {
            if (step < 1 || step > this.totalSteps) return;

            this.currentStep = step;

            // Update progress
            $('.progress-step').each(function() {
                var stepNum = $(this).data('step');
                $(this).removeClass('active completed');
                if (stepNum < step) {
                    $(this).addClass('completed');
                } else if (stepNum === step) {
                    $(this).addClass('active');
                }
            });

            // Show/hide steps
            $('.tuservilleta-step').removeClass('active');
            $('.tuservilleta-step[data-step="' + step + '"]').addClass('active');

            // Show/hide back button
            if (step > 1) {
                $('.tuservilleta-navigation').show();
            } else {
                $('.tuservilleta-navigation').hide();
            }

            // Load step content
            this.loadStep(step);

            // Scroll to top of configurator
            $('html, body').animate({
                scrollTop: $('#tuservilleta-configurador').offset().top - 50
            }, 300);
        },

        goBack: function() {
            var prevStep = this.currentStep - 1;
            if (prevStep >= 1) {
                // Clear selection for current step
                var stepMap = {2: 'size', 3: 'type', 4: 'color', 5: 'printing', 6: 'quantity'};
                var keyToClear = stepMap[this.currentStep];
                if (keyToClear) {
                    delete this.selections[keyToClear];
                }
                this.goToStep(prevStep);
            }
        },

        submitContactForm: function() {
            var self = this;
            var $form = $('#tuservilleta-contact-form');
            var $btn = $form.find('.btn-submit');

            $btn.prop('disabled', true).text('Enviando...');

            $.ajax({
                url: tuservilleta.ajax_url,
                type: 'POST',
                data: {
                    action: 'tuservilleta_submit_order',
                    nonce: tuservilleta.nonce,
                    size: this.selections.size,
                    type: this.selections.type,
                    color: this.selections.color,
                    printing: this.selections.printing,
                    quantity: this.selections.quantity,
                    price: this.price,
                    customer_name: $('#contact-name').val(),
                    customer_email: $('#contact-email').val(),
                    customer_phone: $('#contact-phone').val(),
                    customer_company: $('#contact-company').val(),
                    order_type: 'contact'
                },
                success: function(response) {
                    if (response.success) {
                        $('#tuservilleta-contact-modal').removeClass('active');
                        $('#success-title').text('¡Solicitud enviada!');
                        $('#success-message').text('Hemos recibido tu solicitud. Nos pondremos en contacto contigo lo antes posible.');
                        $('#tuservilleta-success-modal').addClass('active');
                    } else {
                        alert('Error al enviar la solicitud. Por favor, inténtalo de nuevo.');
                    }
                },
                error: function() {
                    alert('Error de conexión. Por favor, inténtalo de nuevo.');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Enviar solicitud');
                }
            });
        },

        initPaymentMethods: function() {
            var self = this;

            // Initialize PayPal if enabled
            if (tuservilleta.paypal_enabled && typeof paypal !== 'undefined') {
                this.initPayPal();
            }

            // Initialize Stripe if enabled
            if (tuservilleta.stripe_enabled && tuservilleta.stripe_public_key && typeof Stripe !== 'undefined') {
                this.initStripe();
            }
        },

        initPayPal: function() {
            var self = this;
            var $container = $('#paypal-button-container');

            if ($container.children().length > 0) return; // Already initialized

            paypal.Buttons({
                style: {
                    layout: 'horizontal',
                    color: 'gold',
                    shape: 'rect',
                    label: 'paypal'
                },
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: [{
                            description: 'Pedido TuServilleta - ' + self.selections.size,
                            amount: {
                                currency_code: 'EUR',
                                value: self.total.toFixed(2)
                            }
                        }]
                    });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        // First save order
                        self.saveOrderAndPayment('paypal', data.orderID);
                    });
                },
                onError: function(err) {
                    alert('Error en el pago. Por favor, inténtalo de nuevo.');
                    console.error(err);
                }
            }).render('#paypal-button-container');
        },

        initStripe: function() {
            var self = this;
            
            if (this.stripeInitialized) return;
            this.stripeInitialized = true;

            var stripe = Stripe(tuservilleta.stripe_public_key);
            var elements = stripe.elements();
            var cardElement = elements.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#1a1a2e',
                        '::placeholder': {
                            color: '#666'
                        }
                    }
                }
            });

            cardElement.mount('#stripe-card-element');

            // Store references for later use
            self.stripe = stripe;
            self.cardElement = cardElement;

            $('#stripe-submit').on('click', function(e) {
                e.preventDefault();

                var $btn = $(this);
                $btn.prop('disabled', true).text('Procesando...');

                // First save the order to get order_id, then create payment intent
                self.saveOrderForStripe($btn);
            });
        },

        saveOrderForStripe: function($btn) {
            var self = this;

            $.ajax({
                url: tuservilleta.ajax_url,
                type: 'POST',
                data: {
                    action: 'tuservilleta_submit_order',
                    nonce: tuservilleta.nonce,
                    size: this.selections.size,
                    type: this.selections.type,
                    color: this.selections.color,
                    printing: this.selections.printing,
                    quantity: this.selections.quantity,
                    price: this.price,
                    customer_name: $('#payment-name').val(),
                    customer_email: $('#payment-email').val(),
                    customer_phone: $('#payment-phone').val(),
                    customer_company: $('#payment-company').val(),
                    order_type: 'payment',
                    payment_method: 'stripe'
                },
                success: function(response) {
                    if (response.success) {
                        // Now confirm card payment
                        self.confirmStripePayment(response.data.order_id, $btn);
                    } else {
                        alert('Error al guardar el pedido.');
                        $btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg> Pagar con tarjeta');
                    }
                },
                error: function() {
                    alert('Error de conexión.');
                    $btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg> Pagar con tarjeta');
                }
            });
        },

        confirmStripePayment: function(orderId, $btn) {
            var self = this;

            // Create PaymentMethod and confirm
            self.stripe.createPaymentMethod({
                type: 'card',
                card: self.cardElement,
                billing_details: {
                    name: $('#payment-name').val(),
                    email: $('#payment-email').val()
                }
            }).then(function(result) {
                if (result.error) {
                    alert(result.error.message);
                    $btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg> Pagar con tarjeta');
                } else {
                    // Send payment method to server for processing
                    self.processStripePayment(orderId, result.paymentMethod.id, $btn);
                }
            });
        },

        processStripePayment: function(orderId, paymentMethodId, $btn) {
            var self = this;

            $.ajax({
                url: tuservilleta.ajax_url,
                type: 'POST',
                data: {
                    action: 'tuservilleta_process_payment',
                    nonce: tuservilleta.nonce,
                    order_id: orderId,
                    payment_method: 'stripe',
                    stripe_token: paymentMethodId,
                    amount: self.total
                },
                success: function(response) {
                    if (response.success) {
                        $('#tuservilleta-payment-modal').removeClass('active');
                        $('#success-title').text('¡Pago completado!');
                        $('#success-message').text('Tu pedido ha sido procesado correctamente. Recibirás un email de confirmación en breve.');
                        $('#tuservilleta-success-modal').addClass('active');
                    } else {
                        alert('Error al procesar el pago: ' + (response.data || 'Error desconocido'));
                        $btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg> Pagar con tarjeta');
                    }
                },
                error: function() {
                    alert('Error de conexión al procesar el pago.');
                    $btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg> Pagar con tarjeta');
                }
            });
        },

        saveOrderAndPayment: function(paymentMethod, paymentId) {
            var self = this;

            // First save order
            $.ajax({
                url: tuservilleta.ajax_url,
                type: 'POST',
                data: {
                    action: 'tuservilleta_submit_order',
                    nonce: tuservilleta.nonce,
                    size: this.selections.size,
                    type: this.selections.type,
                    color: this.selections.color,
                    printing: this.selections.printing,
                    quantity: this.selections.quantity,
                    price: this.price,
                    customer_name: $('#payment-name').val(),
                    customer_email: $('#payment-email').val(),
                    customer_phone: $('#payment-phone').val(),
                    customer_company: $('#payment-company').val(),
                    order_type: 'payment',
                    payment_method: paymentMethod
                },
                success: function(response) {
                    if (response.success) {
                        // Now process payment
                        self.processPayPalPayment(response.data.order_id, paymentId);
                    } else {
                        alert('Error al guardar el pedido.');
                    }
                }
            });
        },

        processPayPalPayment: function(orderId, paymentId) {
            var self = this;

            $.ajax({
                url: tuservilleta.ajax_url,
                type: 'POST',
                data: {
                    action: 'tuservilleta_process_payment',
                    nonce: tuservilleta.nonce,
                    order_id: orderId,
                    payment_method: 'paypal',
                    payment_id: paymentId
                },
                success: function(response) {
                    if (response.success) {
                        $('#tuservilleta-payment-modal').removeClass('active');
                        $('#success-title').text('¡Pago completado!');
                        $('#success-message').text('Tu pedido ha sido procesado correctamente. Recibirás un email de confirmación en breve.');
                        $('#tuservilleta-success-modal').addClass('active');
                    } else {
                        alert('Error al procesar el pago: ' + (response.data || 'Error desconocido'));
                    }
                },
                error: function() {
                    alert('Error de conexión al procesar el pago.');
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('#tuservilleta-configurador').length) {
            TuServilletaApp.init();
        }
    });

})(jQuery);
