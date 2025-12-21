<?php
/**
 * Plugin Name: Tu Servilleta Personalizador
 * Description: Personalizador paso a paso de servilletas y posavasos con cálculo de presupuesto, envío a la empresa, pago con PayPal/tarjeta y compatibilidad con HubSpot.
 * Version: 1.0.0
 * Author: tuservilleta
 * Text Domain: tuservilleta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Tuservilleta_Plugin {
	const OPTION_CATALOG  = 'tuservilleta_catalog';
	const OPTION_SETTINGS = 'tuservilleta_settings';
	const VERSION         = '1.0.0';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_tuservilleta_import_catalog', array( $this, 'handle_catalog_import' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_shortcode( 'tuservilleta_customizer', array( $this, 'render_shortcode' ) );

		add_action( 'wp_ajax_tuservilleta_send_quote', array( $this, 'handle_quote_request' ) );
		add_action( 'wp_ajax_nopriv_tuservilleta_send_quote', array( $this, 'handle_quote_request' ) );
	}

	public function register_menu() {
		add_menu_page(
			__( 'Tu Servilleta', 'tuservilleta' ),
			__( 'Tu Servilleta', 'tuservilleta' ),
			'manage_options',
			'tuservilleta',
			array( $this, 'render_admin_page' ),
			'dashicons-art',
			58
		);
	}

	public function register_settings() {
		register_setting(
			'tuservilleta_settings_group',
			self::OPTION_SETTINGS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(
					'contact_email'      => get_option( 'admin_email' ),
					'paypal_business'    => '',
					'currency'           => 'EUR',
					'hubspot_portal_id'  => '',
					'hubspot_form_id'    => '',
					'accent_color'       => '#0f172a',
				),
			)
		);
	}

	public function sanitize_settings( $input ) {
		$output                     = array();
		$output['contact_email']    = isset( $input['contact_email'] ) ? sanitize_email( $input['contact_email'] ) : '';
		$output['paypal_business']  = isset( $input['paypal_business'] ) ? sanitize_text_field( $input['paypal_business'] ) : '';
		$output['currency']         = isset( $input['currency'] ) ? sanitize_text_field( $input['currency'] ) : 'EUR';
		$output['hubspot_portal_id'] = isset( $input['hubspot_portal_id'] ) ? sanitize_text_field( $input['hubspot_portal_id'] ) : '';
		$output['hubspot_form_id']  = isset( $input['hubspot_form_id'] ) ? sanitize_text_field( $input['hubspot_form_id'] ) : '';
		$output['accent_color']     = isset( $input['accent_color'] ) ? sanitize_hex_color( $input['accent_color'] ) : '#0f172a';

		return $output;
	}

	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$catalog  = get_option( self::OPTION_CATALOG, array() );
		$settings = get_option( self::OPTION_SETTINGS, array() );
		?>
		<div class="wrap tuservilleta-admin">
			<h1><?php esc_html_e( 'Personalizador Tu Servilleta', 'tuservilleta' ); ?></h1>

			<div class="tuservilleta-cards">
				<div class="card">
					<h2><?php esc_html_e( 'Importar catálogo', 'tuservilleta' ); ?></h2>
					<p><?php esc_html_e( 'Sube un CSV separado por punto y coma (;) con las columnas: size;type;color;printing;quantity;price', 'tuservilleta' ); ?></p>
					<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="tuservilleta_import_catalog" />
						<?php wp_nonce_field( 'tuservilleta_import', 'tuservilleta_import_nonce' ); ?>
						<input type="file" name="catalog_csv" accept=".csv,text/csv" required />
						<p class="description"><?php esc_html_e( 'Un producto por línea. Los precios deben ser numéricos usando punto o coma.', 'tuservilleta' ); ?></p>
						<?php submit_button( __( 'Importar catálogo', 'tuservilleta' ), 'primary' ); ?>
					</form>
					<p>
						<strong><?php esc_html_e( 'Productos cargados:', 'tuservilleta' ); ?></strong>
						<?php echo esc_html( count( $catalog ) ); ?>
					</p>
				</div>

				<div class="card">
					<h2><?php esc_html_e( 'Integraciones y ajustes', 'tuservilleta' ); ?></h2>
					<form method="post" action="options.php">
						<?php
						settings_fields( 'tuservilleta_settings_group' );
						?>
						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Correo de contacto', 'tuservilleta' ); ?></th>
								<td>
									<input type="email" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[contact_email]" value="<?php echo isset( $settings['contact_email'] ) ? esc_attr( $settings['contact_email'] ) : ''; ?>" class="regular-text" />
									<p class="description"><?php esc_html_e( 'Recibirá las solicitudes de presupuesto y los archivos adjuntos.', 'tuservilleta' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'PayPal business', 'tuservilleta' ); ?></th>
								<td>
									<input type="text" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[paypal_business]" value="<?php echo isset( $settings['paypal_business'] ) ? esc_attr( $settings['paypal_business'] ) : ''; ?>" class="regular-text" />
									<p class="description"><?php esc_html_e( 'Cuenta para pagos directos (PayPal Standard). Permite tarjeta invitado.', 'tuservilleta' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Moneda', 'tuservilleta' ); ?></th>
								<td>
									<input type="text" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[currency]" value="<?php echo isset( $settings['currency'] ) ? esc_attr( $settings['currency'] ) : 'EUR'; ?>" class="small-text" />
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'HubSpot portal ID', 'tuservilleta' ); ?></th>
								<td><input type="text" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[hubspot_portal_id]" value="<?php echo isset( $settings['hubspot_portal_id'] ) ? esc_attr( $settings['hubspot_portal_id'] ) : ''; ?>" class="regular-text" /></td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'HubSpot form ID', 'tuservilleta' ); ?></th>
								<td><input type="text" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[hubspot_form_id]" value="<?php echo isset( $settings['hubspot_form_id'] ) ? esc_attr( $settings['hubspot_form_id'] ) : ''; ?>" class="regular-text" /></td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Color de acento', 'tuservilleta' ); ?></th>
								<td><input type="text" name="<?php echo esc_attr( self::OPTION_SETTINGS ); ?>[accent_color]" value="<?php echo isset( $settings['accent_color'] ) ? esc_attr( $settings['accent_color'] ) : '#0f172a'; ?>" class="regular-text" /></td>
							</tr>
						</table>
						<?php submit_button( __( 'Guardar ajustes', 'tuservilleta' ) ); ?>
					</form>
				</div>
			</div>
		</div>
		<style>
			.tuservilleta-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:24px;margin-top:20px;}
			.tuservilleta-cards .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;box-shadow:0 10px 30px rgba(0,0,0,0.04);}
		</style>
		<?php
	}

	public function handle_catalog_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No autorizado', 'tuservilleta' ) );
		}

		check_admin_referer( 'tuservilleta_import', 'tuservilleta_import_nonce' );

		if ( empty( $_FILES['catalog_csv']['tmp_name'] ) ) {
			wp_safe_redirect( add_query_arg( 'tuservilleta', 'missing', admin_url( 'admin.php?page=tuservilleta' ) ) );
			exit;
		}

		$file = wp_unslash( $_FILES['catalog_csv']['tmp_name'] );

		$rows = array();
		if ( ( $handle = fopen( $file, 'r' ) ) ) {
			while ( ( $data = fgetcsv( $handle, 1000, ';' ) ) !== false ) {
				if ( count( $data ) < 6 ) {
					continue;
				}

				$rows[] = array(
					'size'     => sanitize_text_field( $data[0] ),
					'type'     => sanitize_text_field( $data[1] ),
					'color'    => sanitize_text_field( $data[2] ),
					'printing' => sanitize_text_field( $data[3] ),
					'quantity' => (int) $data[4],
					'price'    => $this->normalize_price( $data[5] ),
				);
			}
			fclose( $handle );
		}

		update_option( self::OPTION_CATALOG, $rows );
		wp_safe_redirect( add_query_arg( 'tuservilleta', 'imported', admin_url( 'admin.php?page=tuservilleta' ) ) );
		exit;
	}

	private function normalize_price( $price ) {
		$normalized = str_replace( ',', '.', $price );
		return (float) $normalized;
	}

	public function enqueue_assets() {
		if ( ! is_singular() && ! is_page() ) {
			return;
		}

		wp_register_style(
			'tuservilleta-styles',
			plugins_url( 'assets/css/customizer.css', __FILE__ ),
			array(),
			self::VERSION
		);

		wp_register_script(
			'tuservilleta-script',
			plugins_url( 'assets/js/customizer.js', __FILE__ ),
			array(),
			self::VERSION,
			true
		);
	}

	public function render_shortcode() {
		wp_enqueue_style( 'tuservilleta-styles' );
		wp_enqueue_script( 'tuservilleta-script' );

		$catalog  = get_option( self::OPTION_CATALOG, array() );
		$settings = get_option( self::OPTION_SETTINGS, array() );

		wp_localize_script(
			'tuservilleta-script',
			'TuservilletaData',
			array(
				'catalog'   => $catalog,
				'settings'  => $settings,
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'tuservilleta_nonce' ),
				'strings'   => array(
					'steps'          => array( 'Tamaño', 'Calidad', 'Color', 'Cantidad', 'Impresión', 'Imagen' ),
					'emptyCatalog'   => __( 'Sube un catálogo para comenzar a cotizar.', 'tuservilleta' ),
					'quoteSent'      => __( 'Hemos recibido tu solicitud. Nos pondremos en contacto muy pronto.', 'tuservilleta' ),
					'quoteError'     => __( 'No se pudo enviar. Revisa los campos obligatorios.', 'tuservilleta' ),
					'priceNotFound'  => __( 'No hay coincidencias con el catálogo actual.', 'tuservilleta' ),
					'sendToCompany'  => __( 'Enviar a la empresa', 'tuservilleta' ),
					'payNow'         => __( 'Pagar ahora', 'tuservilleta' ),
				),
			)
		);

		ob_start();
		?>
		<div class="tuservilleta-wizard">
			<div class="tuservilleta-header">
				<div>
					<p class="eyebrow"><?php esc_html_e( 'Personalizador premium', 'tuservilleta' ); ?></p>
					<h2><?php esc_html_e( 'Diseña tu servilleta o posavasos', 'tuservilleta' ); ?></h2>
					<p class="sub"><?php esc_html_e( 'Seis pasos guiados al estilo Tesla / Ryanair. Selecciona la tarjeta y avanzamos al instante.', 'tuservilleta' ); ?></p>
				</div>
				<div class="pill"><?php esc_html_e( '6 pasos', 'tuservilleta' ); ?></div>
			</div>

			<div class="tuservilleta-progress" id="tuservilleta-progress"></div>
			<div id="tuservilleta-steps"></div>

			<div class="tuservilleta-summary" id="tuservilleta-summary">
				<h3><?php esc_html_e( 'Resumen & presupuesto', 'tuservilleta' ); ?></h3>
				<div class="summary-grid">
					<div>
						<ul id="tuservilleta-selection"></ul>
						<div class="summary-price" id="tuservilleta-price"></div>
					</div>
					<div class="summary-actions">
						<label><?php esc_html_e( 'Sube tu imagen (opcional)', 'tuservilleta' ); ?></label>
						<input type="file" id="tuservilleta-image" accept="image/*" />

						<div class="contact-fields">
							<input type="text" id="tuservilleta-name" placeholder="<?php esc_attr_e( 'Nombre completo*', 'tuservilleta' ); ?>" />
							<input type="email" id="tuservilleta-email" placeholder="<?php esc_attr_e( 'Email*', 'tuservilleta' ); ?>" />
							<input type="tel" id="tuservilleta-phone" placeholder="<?php esc_attr_e( 'Teléfono', 'tuservilleta' ); ?>" />
							<textarea id="tuservilleta-notes" rows="3" placeholder="<?php esc_attr_e( 'Notas o requisitos especiales', 'tuservilleta' ); ?>"></textarea>
						</div>

						<div class="action-buttons">
							<button class="btn ghost" id="tuservilleta-send"><?php esc_html_e( 'Enviar a la empresa', 'tuservilleta' ); ?></button>
							<button class="btn primary" id="tuservilleta-pay"><?php esc_html_e( 'Pagar ahora', 'tuservilleta' ); ?></button>
						</div>
						<form id="tuservilleta-paypal-form" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank" style="display:none;">
							<input type="hidden" name="cmd" value="_xclick" />
							<input type="hidden" name="business" value="<?php echo isset( $settings['paypal_business'] ) ? esc_attr( $settings['paypal_business'] ) : ''; ?>" />
							<input type="hidden" name="currency_code" value="<?php echo isset( $settings['currency'] ) ? esc_attr( $settings['currency'] ) : 'EUR'; ?>" />
							<input type="hidden" name="item_name" id="tuservilleta-item-name" value="Personalización servilletas/posavasos" />
							<input type="hidden" name="amount" id="tuservilleta-amount" value="" />
							<input type="hidden" name="return" value="<?php echo esc_url( home_url() ); ?>" />
						</form>
						<div id="tuservilleta-message" class="muted"></div>
					</div>
				</div>
			</div>

			<div id="tuservilleta-hubspot"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function handle_quote_request() {
		check_ajax_referer( 'tuservilleta_nonce', 'nonce' );

		$settings = get_option( self::OPTION_SETTINGS, array() );
		$to       = ! empty( $settings['contact_email'] ) ? $settings['contact_email'] : get_option( 'admin_email' );

		if ( empty( $to ) ) {
			wp_send_json_error( array( 'message' => __( 'No hay correo configurado.', 'tuservilleta' ) ), 400 );
		}

		$name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone  = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$notes  = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';
		$payload = isset( $_POST['payload'] ) ? json_decode( wp_unslash( $_POST['payload'] ), true ) : array();

		if ( empty( $name ) || empty( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Nombre y email son obligatorios.', 'tuservilleta' ) ), 400 );
		}

		$lines = array();
		if ( isset( $payload['selections'] ) && is_array( $payload['selections'] ) ) {
			foreach ( $payload['selections'] as $key => $value ) {
				$lines[] = sprintf( '<strong>%s</strong>: %s', esc_html( ucfirst( $key ) ), esc_html( $value ) );
			}
		}

		if ( isset( $payload['price'] ) ) {
			$lines[] = sprintf( '<strong>%s</strong>: %s', esc_html__( 'Precio', 'tuservilleta' ), esc_html( $payload['price'] ) );
		}

		$body  = '<h2>Nuevo presupuesto desde Tu Servilleta</h2>';
		$body .= '<p><strong>Cliente:</strong> ' . esc_html( $name ) . '</p>';
		$body .= '<p><strong>Email:</strong> ' . esc_html( $email ) . '</p>';
		if ( $phone ) {
			$body .= '<p><strong>Teléfono:</strong> ' . esc_html( $phone ) . '</p>';
		}
		if ( $notes ) {
			$body .= '<p><strong>Notas:</strong> ' . nl2br( esc_html( $notes ) ) . '</p>';
		}
		$body .= '<hr /><p>' . implode( '<br/>', $lines ) . '</p>';

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$attachments = array();

		if ( ! empty( $_FILES['image'] ) && ! empty( $_FILES['image']['tmp_name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$uploaded = wp_handle_upload(
				$_FILES['image'],
				array(
					'test_form' => false,
					'mimes'     => array(
						'jpg|jpeg' => 'image/jpeg',
						'png'      => 'image/png',
						'gif'      => 'image/gif',
						'webp'     => 'image/webp',
					),
				)
			);

			if ( ! isset( $uploaded['error'] ) && isset( $uploaded['file'] ) ) {
				$attachments[] = $uploaded['file'];
			}
		}

		wp_mail( $to, 'Nuevo pedido de servilletas/posavasos', $body, $headers, $attachments );

		if ( $email ) {
			wp_mail(
				$email,
				__( 'Resumen de tu solicitud', 'tuservilleta' ),
				__( 'Hemos recibido tu petición y nos pondremos en contacto enseguida.', 'tuservilleta' ),
				$headers
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Solicitud enviada con éxito', 'tuservilleta' ),
			)
		);
	}
}

new Tuservilleta_Plugin();
