<?php
/**
 * Clase para el frontend público
 */

if (!defined('ABSPATH')) {
    exit;
}

class TuServilleta_Public {

    /**
     * Cargar estilos públicos
     */
    public function enqueue_styles() {
        wp_enqueue_style('tuservilleta-public', TUSERVILLETA_PLUGIN_URL . 'public/css/public.css', array(), TUSERVILLETA_VERSION);
    }

    /**
     * Cargar scripts públicos
     */
    public function enqueue_scripts() {
        // PayPal SDK si está habilitado
        if (get_option('tuservilleta_paypal_enabled')) {
            $paypal_mode = get_option('tuservilleta_paypal_mode', 'sandbox');
            $paypal_client_id = get_option('tuservilleta_paypal_client_id');
            if ($paypal_client_id) {
                wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=' . $paypal_client_id . '&currency=EUR', array(), null, true);
            }
        }

        // Stripe JS si está habilitado
        if (get_option('tuservilleta_stripe_enabled')) {
            wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, true);
        }

        wp_enqueue_script('tuservilleta-public', TUSERVILLETA_PLUGIN_URL . 'public/js/public.js', array('jquery'), TUSERVILLETA_VERSION, true);
        
        // Obtener imágenes configuradas
        $images = array();
        $step_options = TuServilleta::$step_options;
        
        foreach ($step_options['size'] as $index => $option) {
            $images['size'][$option] = get_option('tuservilleta_size_image_' . ($index + 1), '');
        }
        foreach ($step_options['type'] as $index => $option) {
            $images['type'][$option] = get_option('tuservilleta_type_image_' . ($index + 1), '');
        }
        foreach ($step_options['color'] as $index => $option) {
            $images['color'][$option] = get_option('tuservilleta_color_image_' . ($index + 1), '');
        }
        foreach ($step_options['printing'] as $index => $option) {
            $images['printing'][$option] = get_option('tuservilleta_printing_image_' . ($index + 1), '');
        }

        wp_localize_script('tuservilleta-public', 'tuservilleta', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tuservilleta_public_nonce'),
            'step_options' => $step_options,
            'images' => $images,
            'stripe_public_key' => get_option('tuservilleta_stripe_public_key', ''),
            'paypal_enabled' => get_option('tuservilleta_paypal_enabled'),
            'stripe_enabled' => get_option('tuservilleta_stripe_enabled'),
            'hubspot_portal_id' => get_option('tuservilleta_hubspot_portal_id', ''),
            'hubspot_form_id' => get_option('tuservilleta_hubspot_form_id', ''),
            'company_name' => get_option('tuservilleta_company_name', 'TuServilleta'),
            'labels' => array(
                'size' => 'Tamaño',
                'type' => 'Calidad',
                'color' => 'Color',
                'printing' => 'Impresión',
                'quantity' => 'Cantidad',
                'price' => 'Precio'
            )
        ));
    }

    /**
     * Renderizar el configurador
     */
    public function render_configurador($atts) {
        $atts = shortcode_atts(array(), $atts);

        ob_start();
        ?>
        <div id="tuservilleta-configurador" class="tuservilleta-configurador">
            <!-- Progress Steps -->
            <div class="tuservilleta-progress">
                <div class="progress-step active" data-step="1">
                    <span class="step-number">1</span>
                    <span class="step-label">Tamaño</span>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="2">
                    <span class="step-number">2</span>
                    <span class="step-label">Calidad</span>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="3">
                    <span class="step-number">3</span>
                    <span class="step-label">Color</span>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="4">
                    <span class="step-number">4</span>
                    <span class="step-label">Impresión</span>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="5">
                    <span class="step-number">5</span>
                    <span class="step-label">Cantidad</span>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step" data-step="6">
                    <span class="step-number">6</span>
                    <span class="step-label">Presupuesto</span>
                </div>
            </div>

            <!-- Step Content Container -->
            <div class="tuservilleta-steps-container">
                
                <!-- Step 1: Size -->
                <div class="tuservilleta-step active" data-step="1">
                    <h2 class="step-title">Selecciona el tamaño</h2>
                    <div class="tuservilleta-options" id="size-options">
                        <!-- Options loaded via JS -->
                    </div>
                </div>

                <!-- Step 2: Type -->
                <div class="tuservilleta-step" data-step="2">
                    <h2 class="step-title">Selecciona la calidad</h2>
                    <div class="tuservilleta-options" id="type-options">
                        <!-- Options loaded via JS -->
                    </div>
                </div>

                <!-- Step 3: Color -->
                <div class="tuservilleta-step" data-step="3">
                    <h2 class="step-title">Selecciona el color</h2>
                    <div class="tuservilleta-options" id="color-options">
                        <!-- Options loaded via JS -->
                    </div>
                </div>

                <!-- Step 4: Printing -->
                <div class="tuservilleta-step" data-step="4">
                    <h2 class="step-title">Selecciona el tipo de impresión</h2>
                    <div class="tuservilleta-options" id="printing-options">
                        <!-- Options loaded via JS -->
                    </div>
                </div>

                <!-- Step 5: Quantity -->
                <div class="tuservilleta-step" data-step="5">
                    <h2 class="step-title">Selecciona la cantidad</h2>
                    <div class="tuservilleta-quantity-selector">
                        <select id="quantity-select" class="tuservilleta-select">
                            <option value="">Selecciona una cantidad</option>
                        </select>
                    </div>
                </div>

                <!-- Step 6: Quote/Price -->
                <div class="tuservilleta-step" data-step="6">
                    <h2 class="step-title">Tu presupuesto</h2>
                    
                    <div class="tuservilleta-quote">
                        <div class="quote-summary">
                            <h3>Resumen de tu selección</h3>
                            <div class="summary-items" id="summary-items">
                                <!-- Summary loaded via JS -->
                            </div>
                        </div>

                        <div class="quote-pricing">
                            <div class="price-row">
                                <span class="price-label">Precio (sin IVA)</span>
                                <span class="price-value" id="price-without-vat">0,00 €</span>
                            </div>
                            <div class="price-row shipping">
                                <span class="price-label">Gastos de envío</span>
                                <span class="price-value free">GRATIS</span>
                            </div>
                            <div class="price-row vat">
                                <span class="price-label">IVA (21%)</span>
                                <span class="price-value" id="price-vat">0,00 €</span>
                            </div>
                            <div class="price-row total">
                                <span class="price-label">Total (IVA incluido)</span>
                                <span class="price-value" id="price-total">0,00 €</span>
                            </div>
                        </div>

                        <div class="quote-actions">
                            <button type="button" class="tuservilleta-btn btn-contact" id="btn-request-contact">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                                Solicitar que nos contacten
                            </button>
                            <button type="button" class="tuservilleta-btn btn-pay" id="btn-pay-now">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                                Pagar ahora
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Back Button -->
            <div class="tuservilleta-navigation" style="display: none;">
                <button type="button" class="tuservilleta-btn btn-back" id="btn-back">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    Volver al paso anterior
                </button>
            </div>
        </div>

        <!-- Contact Modal -->
        <div id="tuservilleta-contact-modal" class="tuservilleta-modal">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <button type="button" class="modal-close">&times;</button>
                <h3>Solicitar contacto</h3>
                <p>Déjanos tus datos y nos pondremos en contacto contigo para finalizar tu pedido.</p>
                <form id="tuservilleta-contact-form">
                    <div class="form-group">
                        <label for="contact-name">Nombre completo *</label>
                        <input type="text" id="contact-name" name="customer_name" required>
                    </div>
                    <div class="form-group">
                        <label for="contact-email">Email *</label>
                        <input type="email" id="contact-email" name="customer_email" required>
                    </div>
                    <div class="form-group">
                        <label for="contact-phone">Teléfono</label>
                        <input type="tel" id="contact-phone" name="customer_phone">
                    </div>
                    <div class="form-group">
                        <label for="contact-company">Empresa</label>
                        <input type="text" id="contact-company" name="customer_company">
                    </div>
                    <button type="submit" class="tuservilleta-btn btn-submit">Enviar solicitud</button>
                </form>
            </div>
        </div>

        <!-- Payment Modal -->
        <div id="tuservilleta-payment-modal" class="tuservilleta-modal">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <button type="button" class="modal-close">&times;</button>
                <h3>Realizar pago</h3>
                <p>Completa tus datos para procesar el pago.</p>
                <form id="tuservilleta-payment-form">
                    <div class="form-group">
                        <label for="payment-name">Nombre completo *</label>
                        <input type="text" id="payment-name" name="customer_name" required>
                    </div>
                    <div class="form-group">
                        <label for="payment-email">Email *</label>
                        <input type="email" id="payment-email" name="customer_email" required>
                    </div>
                    <div class="form-group">
                        <label for="payment-phone">Teléfono</label>
                        <input type="tel" id="payment-phone" name="customer_phone">
                    </div>
                    <div class="form-group">
                        <label for="payment-company">Empresa</label>
                        <input type="text" id="payment-company" name="customer_company">
                    </div>
                    
                    <div class="payment-methods">
                        <h4>Método de pago</h4>
                        <div class="payment-options">
                            <div id="paypal-button-container"></div>
                            <div id="stripe-payment-container">
                                <div id="stripe-card-element"></div>
                                <button type="submit" class="tuservilleta-btn btn-stripe" id="stripe-submit">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                        <line x1="1" y1="10" x2="23" y2="10"></line>
                                    </svg>
                                    Pagar con tarjeta
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Success Modal -->
        <div id="tuservilleta-success-modal" class="tuservilleta-modal">
            <div class="modal-overlay"></div>
            <div class="modal-content modal-success">
                <div class="success-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h3 id="success-title">¡Gracias!</h3>
                <p id="success-message">Tu solicitud ha sido enviada correctamente. Nos pondremos en contacto contigo lo antes posible.</p>
                <button type="button" class="tuservilleta-btn btn-close-success">Cerrar</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtener opciones disponibles via AJAX
     */
    public function get_available_options() {
        check_ajax_referer('tuservilleta_public_nonce', 'nonce');

        $step = sanitize_text_field($_POST['step']);
        $selections = isset($_POST['selections']) ? array_map('sanitize_text_field', $_POST['selections']) : array();

        $valid_steps = array('size', 'type', 'color', 'printing', 'quantity');
        if (!in_array($step, $valid_steps)) {
            wp_send_json_error('Paso inválido');
        }

        $options = TuServilleta_Database::get_available_options($step, $selections);
        wp_send_json_success($options);
    }

    /**
     * Obtener precio via AJAX
     */
    public function get_price() {
        check_ajax_referer('tuservilleta_public_nonce', 'nonce');

        // Validar que todos los campos requeridos existan
        $required_fields = array('size', 'type', 'color', 'printing', 'quantity');
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                wp_send_json_error('Campo requerido faltante: ' . $field);
            }
        }

        $selections = array(
            'size' => sanitize_text_field($_POST['size']),
            'type' => sanitize_text_field($_POST['type']),
            'color' => sanitize_text_field($_POST['color']),
            'printing' => sanitize_text_field($_POST['printing']),
            'quantity' => sanitize_text_field($_POST['quantity'])
        );

        $price = TuServilleta_Database::get_price($selections);

        if ($price !== null) {
            $vat = floatval($price) * 0.21;
            $total = floatval($price) + $vat;

            wp_send_json_success(array(
                'price' => floatval($price),
                'vat' => round($vat, 2),
                'total' => round($total, 2)
            ));
        } else {
            wp_send_json_error('No se encontró precio para esta combinación');
        }
    }

    /**
     * Enviar pedido via AJAX
     */
    public function submit_order() {
        check_ajax_referer('tuservilleta_public_nonce', 'nonce');

        // Validar campos requeridos
        $required_fields = array('size', 'type', 'color', 'printing', 'quantity', 'customer_name', 'customer_email', 'order_type');
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                wp_send_json_error('Campo requerido faltante: ' . $field);
            }
        }

        // Obtener las selecciones del producto
        $selections = array(
            'size' => sanitize_text_field($_POST['size']),
            'type' => sanitize_text_field($_POST['type']),
            'color' => sanitize_text_field($_POST['color']),
            'printing' => sanitize_text_field($_POST['printing']),
            'quantity' => sanitize_text_field($_POST['quantity'])
        );

        // IMPORTANTE: Recalcular el precio server-side para prevenir manipulación
        $server_price = TuServilleta_Database::get_price($selections);
        if ($server_price === null) {
            wp_send_json_error('Combinación de producto no válida');
        }

        $data = array(
            'size' => $selections['size'],
            'type' => $selections['type'],
            'color' => $selections['color'],
            'printing' => $selections['printing'],
            'quantity' => $selections['quantity'],
            'price' => floatval($server_price), // Usar precio calculado server-side
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_phone' => isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '',
            'customer_company' => isset($_POST['customer_company']) ? sanitize_text_field($_POST['customer_company']) : '',
            'order_type' => sanitize_text_field($_POST['order_type'])
        );

        $order_id = TuServilleta_Database::save_order($data);

        if ($order_id) {
            // Enviar email de notificación
            $this->send_notification_email($data, $order_id);

            // Enviar a HubSpot si está configurado
            $this->send_to_hubspot($data);

            wp_send_json_success(array('order_id' => $order_id));
        } else {
            wp_send_json_error('Error al guardar el pedido');
        }
    }

    /**
     * Procesar pago via AJAX
     */
    public function process_payment() {
        check_ajax_referer('tuservilleta_public_nonce', 'nonce');

        $payment_method = sanitize_text_field($_POST['payment_method']);
        $order_id = intval($_POST['order_id']);

        if (!$order_id) {
            wp_send_json_error('ID de pedido no válido');
        }

        if ($payment_method === 'paypal') {
            $payment_id = sanitize_text_field($_POST['payment_id']);
            
            // Verificar el pago con PayPal API
            $paypal_verified = $this->verify_paypal_payment($payment_id);
            
            if ($paypal_verified) {
                TuServilleta_Database::update_payment_status($order_id, 'completed', $payment_id);
                wp_send_json_success(array('status' => 'completed'));
            } else {
                TuServilleta_Database::update_payment_status($order_id, 'failed', $payment_id);
                wp_send_json_error('No se pudo verificar el pago de PayPal');
            }
        } elseif ($payment_method === 'stripe') {
            // Procesar pago con Stripe Payment Intents API
            $payment_method_id = sanitize_text_field($_POST['stripe_token']);
            $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
            
            $stripe_secret = get_option('tuservilleta_stripe_secret_key');
            if (!$stripe_secret) {
                wp_send_json_error('Stripe no está configurado correctamente');
            }

            if ($amount <= 0) {
                wp_send_json_error('Importe no válido');
            }
            
            // Crear Payment Intent con Stripe
            $response = wp_remote_post('https://api.stripe.com/v1/payment_intents', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $stripe_secret,
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ),
                'body' => array(
                    'amount' => intval($amount * 100), // Stripe usa centavos
                    'currency' => 'eur',
                    'payment_method' => $payment_method_id,
                    'confirm' => 'true',
                    'description' => 'Pedido TuServilleta #' . $order_id,
                    'automatic_payment_methods[enabled]' => 'false'
                )
            ));

            if (is_wp_error($response)) {
                wp_send_json_error('Error al procesar el pago');
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['id']) && $body['status'] === 'succeeded') {
                TuServilleta_Database::update_payment_status($order_id, 'completed', $body['id']);
                wp_send_json_success(array('status' => 'completed'));
            } elseif (isset($body['status']) && $body['status'] === 'requires_action') {
                // Requiere autenticación adicional (3D Secure)
                wp_send_json_error('El pago requiere autenticación adicional. Por favor, contacta con nosotros.');
            } else {
                $error_message = isset($body['error']['message']) ? $body['error']['message'] : 'Error desconocido';
                TuServilleta_Database::update_payment_status($order_id, 'failed');
                wp_send_json_error($error_message);
            }
        } else {
            wp_send_json_error('Método de pago no válido');
        }
    }

    /**
     * Verificar pago de PayPal con la API
     */
    private function verify_paypal_payment($order_id) {
        $paypal_client_id = get_option('tuservilleta_paypal_client_id');
        $paypal_secret = get_option('tuservilleta_paypal_secret');
        $paypal_mode = get_option('tuservilleta_paypal_mode', 'sandbox');

        if (!$paypal_client_id || !$paypal_secret) {
            return false;
        }

        $base_url = $paypal_mode === 'live' 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';

        // Obtener access token
        $auth_response = wp_remote_post($base_url . '/v1/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($paypal_client_id . ':' . $paypal_secret),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => 'grant_type=client_credentials'
        ));

        if (is_wp_error($auth_response)) {
            return false;
        }

        $auth_body = json_decode(wp_remote_retrieve_body($auth_response), true);
        if (!isset($auth_body['access_token'])) {
            return false;
        }

        // Verificar el pedido
        $order_response = wp_remote_get($base_url . '/v2/checkout/orders/' . $order_id, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $auth_body['access_token'],
                'Content-Type' => 'application/json'
            )
        ));

        if (is_wp_error($order_response)) {
            return false;
        }

        $order_body = json_decode(wp_remote_retrieve_body($order_response), true);
        
        // Verificar que el estado sea COMPLETED
        return isset($order_body['status']) && $order_body['status'] === 'COMPLETED';
    }

    /**
     * Enviar email de notificación
     */
    private function send_notification_email($data, $order_id) {
        $company_email = get_option('tuservilleta_company_email');
        if (!$company_email) {
            return;
        }

        $company_name = get_option('tuservilleta_company_name', 'TuServilleta');
        $vat = $data['price'] * 0.21;
        $total = $data['price'] + $vat;

        $subject = ($data['order_type'] === 'payment') 
            ? "Nuevo pedido #$order_id - $company_name"
            : "Nueva solicitud de contacto #$order_id - $company_name";

        $message = "Se ha recibido una nueva solicitud:\n\n";
        $message .= "ID de pedido: #$order_id\n";
        $message .= "Tipo: " . ($data['order_type'] === 'payment' ? 'Pago' : 'Solicitud de contacto') . "\n\n";
        $message .= "CLIENTE:\n";
        $message .= "Nombre: {$data['customer_name']}\n";
        $message .= "Email: {$data['customer_email']}\n";
        $message .= "Teléfono: {$data['customer_phone']}\n";
        $message .= "Empresa: {$data['customer_company']}\n\n";
        $message .= "PRODUCTO:\n";
        $message .= "Tamaño: {$data['size']}\n";
        $message .= "Calidad: {$data['type']}\n";
        $message .= "Color: {$data['color']}\n";
        $message .= "Impresión: {$data['printing']}\n";
        $message .= "Cantidad: {$data['quantity']}\n\n";
        $message .= "PRECIO:\n";
        $message .= "Sin IVA: " . number_format($data['price'], 2, ',', '.') . " €\n";
        $message .= "IVA (21%): " . number_format($vat, 2, ',', '.') . " €\n";
        $message .= "Total: " . number_format($total, 2, ',', '.') . " €\n";

        wp_mail($company_email, $subject, $message);
    }

    /**
     * Enviar datos a HubSpot
     */
    private function send_to_hubspot($data) {
        $portal_id = get_option('tuservilleta_hubspot_portal_id');
        $form_id = get_option('tuservilleta_hubspot_form_id');

        if (!$portal_id || !$form_id) {
            return;
        }

        $url = "https://api.hsforms.com/submissions/v3/integration/submit/$portal_id/$form_id";

        $fields = array(
            array('name' => 'firstname', 'value' => $data['customer_name']),
            array('name' => 'email', 'value' => $data['customer_email']),
            array('name' => 'phone', 'value' => $data['customer_phone']),
            array('name' => 'company', 'value' => $data['customer_company']),
            array('name' => 'product_size', 'value' => $data['size']),
            array('name' => 'product_type', 'value' => $data['type']),
            array('name' => 'product_color', 'value' => $data['color']),
            array('name' => 'product_printing', 'value' => $data['printing']),
            array('name' => 'product_quantity', 'value' => $data['quantity']),
            array('name' => 'product_price', 'value' => strval($data['price']))
        );

        $body = array(
            'fields' => $fields,
            'context' => array(
                'pageUri' => home_url(),
                'pageName' => 'TuServilleta Configurador'
            )
        );

        wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($body),
            'timeout' => 15
        ));
    }
}
