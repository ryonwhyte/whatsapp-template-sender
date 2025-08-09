<?php
/**
 * Plugin Name: WhatsApp Template Message Sender
 * Plugin URI: https://github.com/ryonwhyte/whatsapp-template-sender
 * Description: Send WhatsApp Business API template messages from WordPress admin dashboard and log message history.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: whatsapp-template-sender
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WTS_VERSION', '1.0.0');
define('WTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WTS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WTS_TABLE_NAME', 'whatsapp_templates');

class WhatsAppTemplateSender
{
    public function __construct()
    {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init()
    {
        if (!is_admin()) {
            return;
        }

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_wts_send_message', array($this, 'ajax_send_message'));
        add_action('wp_ajax_wts_get_history', array($this, 'ajax_get_history'));
    }

    public function activate()
    {
        $this->create_database_table();
        add_option('wts_access_token', '');
        add_option('wts_phone_number_id', '');
        add_option('wts_business_account_id', '');
    }

    public function deactivate()
    {
        // Keep data on deactivation
    }

    public function create_database_table()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . WTS_TABLE_NAME;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            phone_number varchar(20) NOT NULL,
            template_name varchar(100) NOT NULL,
            parameters text,
            sent_by bigint(20) NOT NULL,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'sent',
            api_response text,
            PRIMARY KEY (id),
            KEY sent_by (sent_by),
            KEY sent_at (sent_at),
            KEY phone_number (phone_number)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'WhatsApp Templates',
            'WhatsApp Templates',
            'manage_options',
            'whatsapp-templates',
            array($this, 'admin_page'),
            'dashicons-format-chat',
            30
        );

        add_submenu_page(
            'whatsapp-templates',
            'Send Message',
            'Send Message',
            'manage_options',
            'whatsapp-templates',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'whatsapp-templates',
            'Message History',
            'Message History',
            'manage_options',
            'whatsapp-templates-history',
            array($this, 'history_page')
        );

        add_submenu_page(
            'whatsapp-templates',
            'Settings',
            'Settings',
            'manage_options',
            'whatsapp-templates-settings',
            array($this, 'settings_page')
        );
    }

    public function enqueue_admin_scripts($hook)
    {
        if (strpos($hook, 'whatsapp-templates') === false) {
            return;
        }

        wp_enqueue_script(
            'wts-admin-js',
            WTS_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            WTS_VERSION,
            true
        );

        wp_enqueue_style(
            'wts-admin-css',
            WTS_PLUGIN_URL . 'assets/admin.css',
            array(),
            WTS_VERSION
        );

        wp_localize_script('wts-admin-js', 'wts_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wts_nonce')
        ));
    }

    public function admin_page(){
        include WTS_PLUGIN_DIR . 'includes/admin-page.php';
    }

    public function history_page()
    {
        include WTS_PLUGIN_DIR . 'includes/history-page.php';
    }

    public function settings_page()
    {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        include WTS_PLUGIN_DIR . 'includes/settings-page.php';
    }

    public function save_settings()
    {
        if (!wp_verify_nonce($_POST['wts_settings_nonce'], 'wts_save_settings')) {
            wp_die('Security check failed');
        }

        update_option('wts_access_token', sanitize_text_field($_POST['access_token']));
        update_option('wts_phone_number_id', sanitize_text_field($_POST['phone_number_id']));
        update_option('wts_business_account_id', sanitize_text_field($_POST['business_account_id']));
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        });
    }

    public function ajax_send_message()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'wts_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $phone_number = sanitize_text_field($_POST['phone_number']);
        $template_name = sanitize_text_field($_POST['template_name']);
        $template_language = sanitize_text_field($_POST['template_language']);
        $parameters = isset($_POST['parameters']) ? array_map('sanitize_text_field', $_POST['parameters']) : array();

        $api_service = new WTS_API_Service();
        $result = $api_service->send_template_message($phone_number, $template_name, $parameters, $template_language);

        if ($result['success']) {
            $this->log_message($phone_number, $template_name, $parameters, 'sent', $result['response'], $template_language);
        } else {
            $this->log_message($phone_number, $template_name, $parameters, 'failed', $result['response'], $template_language);
        }

        wp_send_json($result);
    }

    public function ajax_get_history()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'wts_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . WTS_TABLE_NAME;

        $page = intval($_POST['page']) ?: 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $where = '';
        $params = array();

        if (!empty($_POST['phone_filter'])) {
            $where .= ' WHERE phone_number LIKE %s';
            $params[] = '%' . $wpdb->esc_like($_POST['phone_filter']) . '%';
        }

        $total_query = "SELECT COUNT(*) FROM $table_name $where";
        $total = $wpdb->get_var($wpdb->prepare($total_query, $params));

        $query = "SELECT m.*, u.display_name as sender_name 
                  FROM $table_name m 
                  LEFT JOIN {$wpdb->users} u ON m.sent_by = u.ID 
                  $where 
                  ORDER BY m.sent_at DESC 
                  LIMIT $per_page OFFSET $offset";

        $messages = $wpdb->get_results($wpdb->prepare($query, $params));

        wp_send_json(array(
            'success' => true,
            'messages' => $messages,
            'total' => $total,
            'pages' => ceil($total / $per_page)
        ));
    }

    private function log_message($phone_number, $template_name, $parameters, $status, $api_response, $template_language = 'en')
    {
        global $wpdb;
        $table_name = $wpdb->prefix . WTS_TABLE_NAME;

        $wpdb->insert(
            $table_name,
            array(
                'phone_number' => $phone_number,
                'template_name' => $template_name,
                'parameters' => json_encode($parameters),
                'sent_by' => get_current_user_id(),
                'status' => $status,
                'api_response' => json_encode($api_response),
                'sent_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );
    }
}

require_once WTS_PLUGIN_DIR . 'includes/api-service.php';

new WhatsAppTemplateSender();