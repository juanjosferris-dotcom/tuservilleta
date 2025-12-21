<?php
/**
 * Plugin Name: Tuservilleta Form
 * Plugin URI: https://tuservilleta.com
 * Description: Custom form plugin for Tuservilleta website with secure submission handling and email notifications
 * Version: 1.0.0
 * Author: Tuservilleta, SL
 * Author URI: https://tuservilleta.com
 * Text Domain: tuservilleta-form
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TUSERVILLETA_FORM_VERSION', '1.0.0');
define('TUSERVILLETA_FORM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TUSERVILLETA_FORM_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class Tuservilleta_Form {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Load text domain for translations
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Register shortcode
        add_shortcode('tuservilleta_form', array($this, 'render_form'));
        
        // Include form handler
        require_once TUSERVILLETA_FORM_PLUGIN_DIR . 'includes/form-handler.php';
    }
    
    /**
     * Load plugin text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain('tuservilleta-form', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Enqueue CSS and JavaScript assets
     */
    public function enqueue_assets() {
        // Enqueue CSS
        wp_enqueue_style(
            'tuservilleta-form-style',
            TUSERVILLETA_FORM_PLUGIN_URL . 'assets/css/style.css',
            array(),
            TUSERVILLETA_FORM_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'tuservilleta-form-validation',
            TUSERVILLETA_FORM_PLUGIN_URL . 'assets/js/validation.js',
            array('jquery'),
            TUSERVILLETA_FORM_VERSION,
            true
        );
        
        // Localize script for translations
        wp_localize_script('tuservilleta-form-validation', 'tuservilletaForm', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tuservilleta_form_nonce'),
            'messages' => array(
                'required' => __('This field is required.', 'tuservilleta-form'),
                'email' => __('Please enter a valid email address.', 'tuservilleta-form'),
                'phone' => __('Please enter a valid phone number.', 'tuservilleta-form'),
                'success' => __('Form submitted successfully!', 'tuservilleta-form'),
                'error' => __('An error occurred. Please try again.', 'tuservilleta-form')
            )
        ));
    }
    
    /**
     * Render the form shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Form HTML
     */
    public function render_form($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'title' => __('Contact Us', 'tuservilleta-form'),
            'submit_text' => __('Submit', 'tuservilleta-form')
        ), $atts, 'tuservilleta_form');
        
        // Start output buffering
        ob_start();
        
        // Display success/error messages
        if (isset($_GET['form_status'])) {
            if ($_GET['form_status'] === 'success') {
                echo '<div class="tuservilleta-form-message tuservilleta-form-success">';
                echo esc_html__('Thank you! Your message has been sent successfully.', 'tuservilleta-form');
                echo '</div>';
            } elseif ($_GET['form_status'] === 'error') {
                echo '<div class="tuservilleta-form-message tuservilleta-form-error">';
                echo esc_html__('There was an error submitting your form. Please try again.', 'tuservilleta-form');
                echo '</div>';
            }
        }
        ?>
        
        <div class="tuservilleta-form-container">
            <form id="tuservilleta-form" class="tuservilleta-form" method="post" action="" novalidate>
                
                <?php if (!empty($atts['title'])): ?>
                    <h2 class="tuservilleta-form-title"><?php echo esc_html($atts['title']); ?></h2>
                <?php endif; ?>
                
                <div class="tuservilleta-form-row">
                    <div class="tuservilleta-form-field">
                        <label for="tuservilleta_name">
                            <?php echo esc_html__('Name', 'tuservilleta-form'); ?> <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="tuservilleta_name" 
                            name="tuservilleta_name" 
                            class="tuservilleta-input" 
                            required
                            placeholder="<?php echo esc_attr__('Your name', 'tuservilleta-form'); ?>"
                        >
                        <span class="tuservilleta-error-message"></span>
                    </div>
                </div>
                
                <div class="tuservilleta-form-row">
                    <div class="tuservilleta-form-field">
                        <label for="tuservilleta_email">
                            <?php echo esc_html__('Email', 'tuservilleta-form'); ?> <span class="required">*</span>
                        </label>
                        <input 
                            type="email" 
                            id="tuservilleta_email" 
                            name="tuservilleta_email" 
                            class="tuservilleta-input" 
                            required
                            placeholder="<?php echo esc_attr__('your.email@example.com', 'tuservilleta-form'); ?>"
                        >
                        <span class="tuservilleta-error-message"></span>
                    </div>
                </div>
                
                <div class="tuservilleta-form-row">
                    <div class="tuservilleta-form-field">
                        <label for="tuservilleta_phone">
                            <?php echo esc_html__('Phone', 'tuservilleta-form'); ?>
                        </label>
                        <input 
                            type="tel" 
                            id="tuservilleta_phone" 
                            name="tuservilleta_phone" 
                            class="tuservilleta-input"
                            placeholder="<?php echo esc_attr__('Your phone number', 'tuservilleta-form'); ?>"
                        >
                        <span class="tuservilleta-error-message"></span>
                    </div>
                </div>
                
                <div class="tuservilleta-form-row">
                    <div class="tuservilleta-form-field">
                        <label for="tuservilleta_subject">
                            <?php echo esc_html__('Subject', 'tuservilleta-form'); ?> <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="tuservilleta_subject" 
                            name="tuservilleta_subject" 
                            class="tuservilleta-input" 
                            required
                            placeholder="<?php echo esc_attr__('Subject of your message', 'tuservilleta-form'); ?>"
                        >
                        <span class="tuservilleta-error-message"></span>
                    </div>
                </div>
                
                <div class="tuservilleta-form-row">
                    <div class="tuservilleta-form-field">
                        <label for="tuservilleta_message">
                            <?php echo esc_html__('Message', 'tuservilleta-form'); ?> <span class="required">*</span>
                        </label>
                        <textarea 
                            id="tuservilleta_message" 
                            name="tuservilleta_message" 
                            class="tuservilleta-textarea" 
                            rows="6" 
                            required
                            placeholder="<?php echo esc_attr__('Your message...', 'tuservilleta-form'); ?>"
                        ></textarea>
                        <span class="tuservilleta-error-message"></span>
                    </div>
                </div>
                
                <?php wp_nonce_field('tuservilleta_form_submit', 'tuservilleta_form_nonce'); ?>
                
                <div class="tuservilleta-form-row">
                    <button type="submit" class="tuservilleta-submit-btn">
                        <?php echo esc_html($atts['submit_text']); ?>
                    </button>
                </div>
                
            </form>
        </div>
        
        <?php
        return ob_get_clean();
    }
}

// Initialize the plugin
new Tuservilleta_Form();
