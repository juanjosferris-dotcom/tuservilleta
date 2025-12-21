<?php
/**
 * Clase para el panel de administración
 */

if (!defined('ABSPATH')) {
    exit;
}

class TuServilleta_Admin {

    /**
     * Añadir menú de administración
     */
    public function add_admin_menu() {
        add_menu_page(
            'TuServilleta',
            'TuServilleta',
            'manage_options',
            'tuservilleta',
            array($this, 'render_admin_page'),
            'dashicons-clipboard',
            56
        );

        add_submenu_page(
            'tuservilleta',
            'Configuración',
            'Configuración',
            'manage_options',
            'tuservilleta',
            array($this, 'render_admin_page')
        );

        add_submenu_page(
            'tuservilleta',
            'Pedidos',
            'Pedidos',
            'manage_options',
            'tuservilleta-orders',
            array($this, 'render_orders_page')
        );
    }

    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        // Configuración de PayPal
        register_setting('tuservilleta_payment', 'tuservilleta_paypal_enabled');
        register_setting('tuservilleta_payment', 'tuservilleta_paypal_mode');
        register_setting('tuservilleta_payment', 'tuservilleta_paypal_client_id');
        register_setting('tuservilleta_payment', 'tuservilleta_paypal_secret');

        // Configuración de Stripe
        register_setting('tuservilleta_payment', 'tuservilleta_stripe_enabled');
        register_setting('tuservilleta_payment', 'tuservilleta_stripe_mode');
        register_setting('tuservilleta_payment', 'tuservilleta_stripe_public_key');
        register_setting('tuservilleta_payment', 'tuservilleta_stripe_secret_key');

        // Configuración de HubSpot
        register_setting('tuservilleta_hubspot', 'tuservilleta_hubspot_portal_id');
        register_setting('tuservilleta_hubspot', 'tuservilleta_hubspot_form_id');

        // Configuración de imágenes
        for ($i = 1; $i <= 9; $i++) {
            register_setting('tuservilleta_images', 'tuservilleta_size_image_' . $i);
        }
        for ($i = 1; $i <= 4; $i++) {
            register_setting('tuservilleta_images', 'tuservilleta_type_image_' . $i);
        }
        for ($i = 1; $i <= 7; $i++) {
            register_setting('tuservilleta_images', 'tuservilleta_color_image_' . $i);
        }
        for ($i = 1; $i <= 6; $i++) {
            register_setting('tuservilleta_images', 'tuservilleta_printing_image_' . $i);
        }

        // Email de la empresa
        register_setting('tuservilleta_general', 'tuservilleta_company_email');
        register_setting('tuservilleta_general', 'tuservilleta_company_name');
    }

    /**
     * Cargar estilos del admin
     */
    public function enqueue_styles($hook) {
        if (strpos($hook, 'tuservilleta') === false) {
            return;
        }
        wp_enqueue_style('tuservilleta-admin', TUSERVILLETA_PLUGIN_URL . 'admin/css/admin.css', array(), TUSERVILLETA_VERSION);
        wp_enqueue_style('wp-color-picker');
    }

    /**
     * Cargar scripts del admin
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'tuservilleta') === false) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script('tuservilleta-admin', TUSERVILLETA_PLUGIN_URL . 'admin/js/admin.js', array('jquery', 'wp-color-picker'), TUSERVILLETA_VERSION, true);
        wp_localize_script('tuservilleta-admin', 'tuservilleta_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tuservilleta_admin_nonce'),
            'upload_nonce' => wp_create_nonce('tuservilleta_upload_nonce')
        ));
    }

    /**
     * Renderizar página de administración
     */
    public function render_admin_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap tuservilleta-admin">
            <h1><span class="dashicons dashicons-clipboard"></span> TuServilleta - Configuración</h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=tuservilleta&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-settings"></span> General
                </a>
                <a href="?page=tuservilleta&tab=payment" class="nav-tab <?php echo $active_tab === 'payment' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-money-alt"></span> Pagos
                </a>
                <a href="?page=tuservilleta&tab=images" class="nav-tab <?php echo $active_tab === 'images' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-format-gallery"></span> Imágenes
                </a>
                <a href="?page=tuservilleta&tab=products" class="nav-tab <?php echo $active_tab === 'products' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-upload"></span> Productos (CSV)
                </a>
                <a href="?page=tuservilleta&tab=hubspot" class="nav-tab <?php echo $active_tab === 'hubspot' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-links"></span> HubSpot
                </a>
            </nav>

            <div class="tab-content">
                <?php
                switch ($active_tab) {
                    case 'payment':
                        $this->render_payment_tab();
                        break;
                    case 'images':
                        $this->render_images_tab();
                        break;
                    case 'products':
                        $this->render_products_tab();
                        break;
                    case 'hubspot':
                        $this->render_hubspot_tab();
                        break;
                    default:
                        $this->render_general_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Tab General
     */
    private function render_general_tab() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('tuservilleta_general'); ?>
            
            <div class="tuservilleta-card">
                <h2>Configuración General</h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="tuservilleta_company_name">Nombre de la empresa</label></th>
                        <td>
                            <input type="text" id="tuservilleta_company_name" name="tuservilleta_company_name" 
                                   value="<?php echo esc_attr(get_option('tuservilleta_company_name', 'TuServilleta')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tuservilleta_company_email">Email de contacto</label></th>
                        <td>
                            <input type="email" id="tuservilleta_company_email" name="tuservilleta_company_email" 
                                   value="<?php echo esc_attr(get_option('tuservilleta_company_email')); ?>" class="regular-text">
                            <p class="description">Email donde se recibirán las solicitudes de presupuesto</p>
                        </td>
                    </tr>
                </table>

                <h3>Shortcode</h3>
                <p>Usa el siguiente shortcode para mostrar el configurador en cualquier página:</p>
                <code>[tuservilleta_configurador]</code>
            </div>

            <?php submit_button('Guardar Cambios'); ?>
        </form>
        <?php
    }

    /**
     * Tab de Pagos
     */
    private function render_payment_tab() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('tuservilleta_payment'); ?>
            
            <div class="tuservilleta-card">
                <h2><span class="dashicons dashicons-paypal"></span> Configuración de PayPal</h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Activar PayPal</th>
                        <td>
                            <label class="tuservilleta-switch">
                                <input type="checkbox" name="tuservilleta_paypal_enabled" value="1" 
                                       <?php checked(get_option('tuservilleta_paypal_enabled'), 1); ?>>
                                <span class="slider"></span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Modo</th>
                        <td>
                            <select name="tuservilleta_paypal_mode">
                                <option value="sandbox" <?php selected(get_option('tuservilleta_paypal_mode'), 'sandbox'); ?>>Sandbox (Pruebas)</option>
                                <option value="live" <?php selected(get_option('tuservilleta_paypal_mode'), 'live'); ?>>Live (Producción)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tuservilleta_paypal_client_id">Client ID</label></th>
                        <td>
                            <input type="text" id="tuservilleta_paypal_client_id" name="tuservilleta_paypal_client_id" 
                                   value="<?php echo esc_attr(get_option('tuservilleta_paypal_client_id')); ?>" class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tuservilleta_paypal_secret">Secret</label></th>
                        <td>
                            <input type="password" id="tuservilleta_paypal_secret" name="tuservilleta_paypal_secret" 
                                   value="<?php echo esc_attr(get_option('tuservilleta_paypal_secret')); ?>" class="large-text">
                        </td>
                    </tr>
                </table>
            </div>

            <div class="tuservilleta-card">
                <h2><span class="dashicons dashicons-credit-card"></span> Configuración de Stripe</h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Activar Stripe</th>
                        <td>
                            <label class="tuservilleta-switch">
                                <input type="checkbox" name="tuservilleta_stripe_enabled" value="1" 
                                       <?php checked(get_option('tuservilleta_stripe_enabled'), 1); ?>>
                                <span class="slider"></span>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Modo</th>
                        <td>
                            <select name="tuservilleta_stripe_mode">
                                <option value="test" <?php selected(get_option('tuservilleta_stripe_mode'), 'test'); ?>>Test (Pruebas)</option>
                                <option value="live" <?php selected(get_option('tuservilleta_stripe_mode'), 'live'); ?>>Live (Producción)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tuservilleta_stripe_public_key">Clave Pública</label></th>
                        <td>
                            <input type="text" id="tuservilleta_stripe_public_key" name="tuservilleta_stripe_public_key" 
                                   value="<?php echo esc_attr(get_option('tuservilleta_stripe_public_key')); ?>" class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tuservilleta_stripe_secret_key">Clave Secreta</label></th>
                        <td>
                            <input type="password" id="tuservilleta_stripe_secret_key" name="tuservilleta_stripe_secret_key" 
                                   value="<?php echo esc_attr(get_option('tuservilleta_stripe_secret_key')); ?>" class="large-text">
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button('Guardar Configuración de Pagos'); ?>
        </form>
        <?php
    }

    /**
     * Tab de Imágenes
     */
    private function render_images_tab() {
        $step_options = TuServilleta::$step_options;
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('tuservilleta_images'); ?>

            <div class="tuservilleta-card">
                <h2>Paso 1: Tamaño (9 imágenes)</h2>
                <p class="description">Sube las imágenes correspondientes a cada opción de tamaño</p>
                <div class="tuservilleta-image-grid">
                    <?php foreach ($step_options['size'] as $index => $option): 
                        $image_key = 'tuservilleta_size_image_' . ($index + 1);
                        $image_url = get_option($image_key);
                    ?>
                    <div class="tuservilleta-image-item">
                        <label><?php echo esc_html($option); ?></label>
                        <div class="image-preview" id="preview_<?php echo $image_key; ?>">
                            <?php if ($image_url): ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="">
                            <?php else: ?>
                                <span class="dashicons dashicons-format-image"></span>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="<?php echo $image_key; ?>" id="<?php echo $image_key; ?>" value="<?php echo esc_url($image_url); ?>">
                        <button type="button" class="button tuservilleta-upload-btn" data-target="<?php echo $image_key; ?>">Subir imagen</button>
                        <button type="button" class="button tuservilleta-remove-btn" data-target="<?php echo $image_key; ?>" <?php echo !$image_url ? 'style="display:none"' : ''; ?>>Eliminar</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tuservilleta-card">
                <h2>Paso 2: Calidad (4 imágenes)</h2>
                <p class="description">Sube las imágenes correspondientes a cada tipo de calidad</p>
                <div class="tuservilleta-image-grid">
                    <?php foreach ($step_options['type'] as $index => $option): 
                        $image_key = 'tuservilleta_type_image_' . ($index + 1);
                        $image_url = get_option($image_key);
                    ?>
                    <div class="tuservilleta-image-item">
                        <label><?php echo esc_html($option); ?></label>
                        <div class="image-preview" id="preview_<?php echo $image_key; ?>">
                            <?php if ($image_url): ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="">
                            <?php else: ?>
                                <span class="dashicons dashicons-format-image"></span>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="<?php echo $image_key; ?>" id="<?php echo $image_key; ?>" value="<?php echo esc_url($image_url); ?>">
                        <button type="button" class="button tuservilleta-upload-btn" data-target="<?php echo $image_key; ?>">Subir imagen</button>
                        <button type="button" class="button tuservilleta-remove-btn" data-target="<?php echo $image_key; ?>" <?php echo !$image_url ? 'style="display:none"' : ''; ?>>Eliminar</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tuservilleta-card">
                <h2>Paso 3: Color (7 imágenes)</h2>
                <p class="description">Sube las imágenes correspondientes a cada color</p>
                <div class="tuservilleta-image-grid">
                    <?php foreach ($step_options['color'] as $index => $option): 
                        $image_key = 'tuservilleta_color_image_' . ($index + 1);
                        $image_url = get_option($image_key);
                    ?>
                    <div class="tuservilleta-image-item">
                        <label><?php echo esc_html($option); ?></label>
                        <div class="image-preview" id="preview_<?php echo $image_key; ?>">
                            <?php if ($image_url): ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="">
                            <?php else: ?>
                                <span class="dashicons dashicons-format-image"></span>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="<?php echo $image_key; ?>" id="<?php echo $image_key; ?>" value="<?php echo esc_url($image_url); ?>">
                        <button type="button" class="button tuservilleta-upload-btn" data-target="<?php echo $image_key; ?>">Subir imagen</button>
                        <button type="button" class="button tuservilleta-remove-btn" data-target="<?php echo $image_key; ?>" <?php echo !$image_url ? 'style="display:none"' : ''; ?>>Eliminar</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tuservilleta-card">
                <h2>Paso 4: Impresión (6 imágenes)</h2>
                <p class="description">Sube las imágenes correspondientes a cada tipo de impresión</p>
                <div class="tuservilleta-image-grid">
                    <?php foreach ($step_options['printing'] as $index => $option): 
                        $image_key = 'tuservilleta_printing_image_' . ($index + 1);
                        $image_url = get_option($image_key);
                    ?>
                    <div class="tuservilleta-image-item">
                        <label><?php echo esc_html($option); ?></label>
                        <div class="image-preview" id="preview_<?php echo $image_key; ?>">
                            <?php if ($image_url): ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="">
                            <?php else: ?>
                                <span class="dashicons dashicons-format-image"></span>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="<?php echo $image_key; ?>" id="<?php echo $image_key; ?>" value="<?php echo esc_url($image_url); ?>">
                        <button type="button" class="button tuservilleta-upload-btn" data-target="<?php echo $image_key; ?>">Subir imagen</button>
                        <button type="button" class="button tuservilleta-remove-btn" data-target="<?php echo $image_key; ?>" <?php echo !$image_url ? 'style="display:none"' : ''; ?>>Eliminar</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tuservilleta-card">
                <h2>Paso 5: Cantidad</h2>
                <p class="description">El paso de cantidad se mostrará como un desplegable elegante con las 8 opciones disponibles</p>
                <ul class="tuservilleta-quantity-list">
                    <?php foreach ($step_options['quantity'] as $option): ?>
                    <li><span class="dashicons dashicons-yes"></span> <?php echo esc_html($option); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php submit_button('Guardar Imágenes'); ?>
        </form>
        <?php
    }

    /**
     * Tab de Productos (CSV)
     */
    private function render_products_tab() {
        $product_count = TuServilleta_Database::count_products();
        ?>
        <div class="tuservilleta-card">
            <h2>Importar Productos desde CSV</h2>
            
            <div class="tuservilleta-info-box">
                <h3>Formato del archivo CSV</h3>
                <p>El archivo debe estar separado por punto y coma (;) con las siguientes columnas:</p>
                <code>size;type;color;printing;quantity;price</code>
                <p><strong>Ejemplo:</strong></p>
                <pre>size;type;color;printing;quantity;price
Servilletas 20x20 (10x10 plegada);2 Capas;Blanco;Deluxe a 1 Tinta;250 uds.;45.50
Servilletas 20x20 (10x10 plegada);2 Capas;Blanco;Deluxe a 1 Tinta;500 uds.;75.00</pre>
            </div>

            <div class="tuservilleta-upload-section">
                <input type="file" id="tuservilleta_csv_file" accept=".csv">
                <button type="button" id="tuservilleta_upload_csv" class="button button-primary">
                    <span class="dashicons dashicons-upload"></span> Importar CSV
                </button>
            </div>

            <div id="tuservilleta_upload_result" class="tuservilleta-result"></div>

            <div class="tuservilleta-stats">
                <h3>Estadísticas</h3>
                <p><strong>Productos en la base de datos:</strong> <span id="product_count"><?php echo number_format($product_count); ?></span></p>
            </div>
        </div>
        <?php
    }

    /**
     * Tab de HubSpot
     */
    private function render_hubspot_tab() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('tuservilleta_hubspot'); ?>
            
            <div class="tuservilleta-card">
                <h2><span class="dashicons dashicons-admin-links"></span> Integración con HubSpot</h2>
                <p class="description">Configura la integración con HubSpot para enviar los leads automáticamente a tu CRM.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="tuservilleta_hubspot_portal_id">Portal ID</label></th>
                        <td>
                            <input type="text" id="tuservilleta_hubspot_portal_id" name="tuservilleta_hubspot_portal_id" 
                                   value="<?php echo esc_attr(get_option('tuservilleta_hubspot_portal_id')); ?>" class="regular-text">
                            <p class="description">Tu ID de portal de HubSpot</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tuservilleta_hubspot_form_id">Form ID</label></th>
                        <td>
                            <input type="text" id="tuservilleta_hubspot_form_id" name="tuservilleta_hubspot_form_id" 
                                   value="<?php echo esc_attr(get_option('tuservilleta_hubspot_form_id')); ?>" class="regular-text">
                            <p class="description">El ID del formulario de HubSpot donde se enviarán los leads</p>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button('Guardar Configuración de HubSpot'); ?>
        </form>
        <?php
    }

    /**
     * Renderizar página de pedidos
     */
    public function render_orders_page() {
        $orders = TuServilleta_Database::get_orders();
        ?>
        <div class="wrap tuservilleta-admin">
            <h1><span class="dashicons dashicons-list-view"></span> Pedidos</h1>
            
            <div class="tuservilleta-card">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8">No hay pedidos todavía.</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo esc_html($order->id); ?></td>
                            <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($order->created_at))); ?></td>
                            <td>
                                <strong><?php echo esc_html($order->customer_name); ?></strong><br>
                                <small><?php echo esc_html($order->customer_email); ?></small>
                            </td>
                            <td>
                                <?php echo esc_html($order->size); ?><br>
                                <small><?php echo esc_html($order->type . ' - ' . $order->color); ?></small>
                            </td>
                            <td><?php echo esc_html($order->quantity); ?></td>
                            <td><?php echo number_format($order->price_with_vat, 2, ',', '.'); ?> €</td>
                            <td>
                                <?php if ($order->order_type === 'payment'): ?>
                                    <span class="tuservilleta-badge badge-payment">Pago</span>
                                <?php else: ?>
                                    <span class="tuservilleta-badge badge-contact">Contacto</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $status_class = $order->payment_status === 'completed' ? 'status-completed' : 
                                               ($order->payment_status === 'pending' ? 'status-pending' : 'status-failed');
                                ?>
                                <span class="tuservilleta-status <?php echo $status_class; ?>">
                                    <?php echo esc_html(ucfirst($order->payment_status)); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Manejar subida de CSV
     */
    public function handle_csv_upload() {
        check_ajax_referer('tuservilleta_upload_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        if (!isset($_FILES['csv_file'])) {
            wp_send_json_error('No se ha subido ningún archivo');
        }

        $file = $_FILES['csv_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('Error al subir el archivo');
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'csv') {
            wp_send_json_error('El archivo debe ser CSV');
        }

        $result = TuServilleta_Database::import_csv($file['tmp_name']);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * Manejar guardado de imagen
     */
    public function handle_image_save() {
        check_ajax_referer('tuservilleta_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        $key = sanitize_text_field($_POST['key']);
        $url = esc_url_raw($_POST['url']);

        update_option($key, $url);
        wp_send_json_success();
    }
}
