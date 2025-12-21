<?php
/**
 * Plugin Name: TuServilleta - Configurador de Productos
 * Plugin URI: https://tuservilleta.com
 * Description: Plugin para la personalización elegante de servilletas, posavasos y manteles. Diseñado para clientes de alto standing.
 * Version: 1.0.0
 * Author: TuServilleta
 * Author URI: https://tuservilleta.com
 * License: GPL v2 or later
 * Text Domain: tuservilleta
 * Domain Path: /languages
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('TUSERVILLETA_VERSION', '1.0.0');
define('TUSERVILLETA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TUSERVILLETA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TUSERVILLETA_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin
 */
class TuServilleta {

    /**
     * Instancia única del plugin
     */
    private static $instance = null;

    /**
     * Opciones de cada paso
     */
    public static $step_options = array(
        'size' => array(
            'Servilletas 20x20 (10x10 plegada)',
            'Servilletas 24x24 (12x12 plegada)',
            'Servilletas 30x30 (15x15 plegada)',
            'Servilletas 33x33 (16,5x16,5 plegada)',
            'Servilletas 40x40 (20x20 plegada)',
            'Servilletas 40x40 (10x20 plegada)',
            'Posavasos 9 cm., redondo',
            'Posavasos 9 cm. cuadrado',
            'Mantelín 30x40 cm.'
        ),
        'type' => array(
            '2 Capas',
            '3 Capas',
            'Doble Punto',
            'Tisú Seco'
        ),
        'color' => array(
            'Blanco',
            'Negro',
            'Beige',
            'Burdeos',
            'Verde Pino',
            'Azul Marino',
            'Reciclado'
        ),
        'printing' => array(
            'Deluxe a 1 Tinta',
            'Deluxe a 2 Tintas',
            'Digital a Todo Color',
            'Sin Personalizar',
            'Estándar a 1 Tinta',
            'Estándar a 2 Tintas'
        ),
        'quantity' => array(
            '250 uds.',
            '500 uds.',
            '1.000 uds.',
            '2.000 uds.',
            '3.000 uds.',
            '5.000 uds.',
            '10.000 uds.',
            '20.000 uds.'
        )
    );

    /**
     * Obtener instancia única
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Cargar dependencias
     */
    private function load_dependencies() {
        require_once TUSERVILLETA_PLUGIN_DIR . 'includes/class-tuservilleta-database.php';
        require_once TUSERVILLETA_PLUGIN_DIR . 'admin/class-tuservilleta-admin.php';
        require_once TUSERVILLETA_PLUGIN_DIR . 'public/class-tuservilleta-public.php';
    }

    /**
     * Definir hooks del admin
     */
    private function define_admin_hooks() {
        $admin = new TuServilleta_Admin();
        add_action('admin_menu', array($admin, 'add_admin_menu'));
        add_action('admin_init', array($admin, 'register_settings'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_scripts'));
        add_action('wp_ajax_tuservilleta_upload_csv', array($admin, 'handle_csv_upload'));
        add_action('wp_ajax_tuservilleta_save_image', array($admin, 'handle_image_save'));
    }

    /**
     * Definir hooks públicos
     */
    private function define_public_hooks() {
        $public = new TuServilleta_Public();
        add_action('wp_enqueue_scripts', array($public, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($public, 'enqueue_scripts'));
        add_shortcode('tuservilleta_configurador', array($public, 'render_configurador'));
        add_action('wp_ajax_tuservilleta_get_options', array($public, 'get_available_options'));
        add_action('wp_ajax_nopriv_tuservilleta_get_options', array($public, 'get_available_options'));
        add_action('wp_ajax_tuservilleta_get_price', array($public, 'get_price'));
        add_action('wp_ajax_nopriv_tuservilleta_get_price', array($public, 'get_price'));
        add_action('wp_ajax_tuservilleta_submit_order', array($public, 'submit_order'));
        add_action('wp_ajax_nopriv_tuservilleta_submit_order', array($public, 'submit_order'));
        add_action('wp_ajax_tuservilleta_process_payment', array($public, 'process_payment'));
        add_action('wp_ajax_nopriv_tuservilleta_process_payment', array($public, 'process_payment'));
    }

    /**
     * Activación del plugin
     */
    public static function activate() {
        require_once TUSERVILLETA_PLUGIN_DIR . 'includes/class-tuservilleta-database.php';
        TuServilleta_Database::create_tables();
        
        // Crear directorio para uploads
        $upload_dir = wp_upload_dir();
        $tuservilleta_dir = $upload_dir['basedir'] . '/tuservilleta';
        if (!file_exists($tuservilleta_dir)) {
            wp_mkdir_p($tuservilleta_dir);
        }
        
        flush_rewrite_rules();
    }

    /**
     * Desactivación del plugin
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
}

// Hooks de activación y desactivación
register_activation_hook(__FILE__, array('TuServilleta', 'activate'));
register_deactivation_hook(__FILE__, array('TuServilleta', 'deactivate'));

// Iniciar el plugin
function tuservilleta_init() {
    return TuServilleta::get_instance();
}
add_action('plugins_loaded', 'tuservilleta_init');
