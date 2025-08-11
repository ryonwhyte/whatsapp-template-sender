<?php
if (!defined('ABSPATH')) {
    exit;
}

$access_token = get_option('wts_access_token', '');
$phone_number_id = get_option('wts_phone_number_id', '');
$business_account_id = get_option('wts_business_account_id', '');
$allowed_users = get_option('wts_allowed_users', array());

$api_service = new WTS_API_Service();
$connection_status = null;

if (!empty($access_token) && !empty($phone_number_id) && !empty($business_account_id)) {
    $connection_status = $api_service->test_connection();
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (!current_user_can('manage_options')): ?>
        <div class="notice notice-info">
            <p><strong>Access Granted:</strong> You have been given access to the WhatsApp Template Sender plugin by an administrator. You can send messages and view history, but cannot modify settings.</p>
        </div>
        <p><a href="<?php echo admin_url('admin.php?page=whatsapp-templates'); ?>" class="button button-primary">Go to Send Message</a></p>
    <?php else: ?>
    
    <div class="wts-settings-container">
        <form method="post" action="">
            <?php wp_nonce_field('wts_save_settings', 'wts_settings_nonce'); ?>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="access_token">WhatsApp Access Token</label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="access_token" 
                                   name="access_token" 
                                   value="<?php echo esc_attr($access_token); ?>" 
                                   class="regular-text" 
                                   autocomplete="off" />
                            <p class="description">
                                Your WhatsApp Cloud API access token from Meta Business.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="phone_number_id">Phone Number ID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="phone_number_id" 
                                   name="phone_number_id" 
                                   value="<?php echo esc_attr($phone_number_id); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                Your WhatsApp Business phone number ID from Meta Business Manager.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="business_account_id">WhatsApp Business Account ID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="business_account_id" 
                                   name="business_account_id" 
                                   value="<?php echo esc_attr($business_account_id); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                Your WhatsApp Business Account ID (WABA ID) from Meta Business Manager.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="allowed_users">Allowed Non-Admin Users</label>
                        </th>
                        <td>
                            <?php
                            $users = get_users(array(
                                'role__not_in' => array('administrator'),
                                'fields' => array('ID', 'display_name', 'user_login')
                            ));
                            ?>
                            <?php if (empty($users)): ?>
                                <p>No non-admin users found.</p>
                            <?php else: ?>
                                <select name="allowed_users[]" multiple="multiple" class="wts-user-multiselect" size="8">
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo esc_attr($user->ID); ?>" 
                                                <?php selected(in_array($user->ID, $allowed_users)); ?>>
                                            <?php echo esc_html($user->display_name . ' (' . $user->user_login . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                            <p class="description">
                                Select which non-admin users should have access to the WhatsApp Template Sender plugin. Hold <strong>Ctrl</strong> (Windows) or <strong>Cmd</strong> (Mac) to select multiple users.
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <?php if ($connection_status): ?>
                <div class="wts-connection-status">
                    <h3>Connection Status</h3>
                    <div class="notice notice-<?php echo $connection_status['success'] ? 'success' : 'error'; ?> inline">
                        <p><?php echo esc_html($connection_status['message']); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php submit_button('Save Settings'); ?>
        </form>
        
        <div class="wts-help-section">
            <h3>Setup Instructions</h3>
            <ol>
                <li><strong>Create a Meta App:</strong> Go to <a href="https://developers.facebook.com/" target="_blank">Facebook Developers</a> and create a new app.</li>
                <li><strong>Add WhatsApp Product:</strong> Add WhatsApp Business API to your app.</li>
                <li><strong>Get Access Token:</strong> Generate a permanent access token in the WhatsApp API settings.</li>
                <li><strong>Get Phone Number ID:</strong> Find your phone number ID in the WhatsApp API configuration.</li>
                <li><strong>Get Business Account ID:</strong> Find your WhatsApp Business Account ID (WABA ID) in Meta Business Manager under WhatsApp Accounts.</li>
                <li><strong>Create Templates:</strong> Create and get approval for message templates in Meta Business Manager.</li>
            </ol>
            
            <h4>Required Permissions</h4>
            <p>Your access token needs these permissions:</p>
            <ul>
                <li><code>whatsapp_business_messaging</code></li>
                <li><code>whatsapp_business_management</code></li>
            </ul>
            
            <h4>Common Template Variables</h4>
            <p>Use these placeholders in your templates:</p>
            <ul>
                <li><code>{{1}}</code> - First parameter</li>
                <li><code>{{2}}</code> - Second parameter</li>
                <li><code>{{3}}</code> - Third parameter</li>
            </ul>
        </div>
    </div>
</div>

<style>
.wts-settings-container {
    max-width: 800px;
}

.wts-connection-status {
    margin: 20px 0;
}

.wts-help-section {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 4px;
    margin-top: 30px;
}

.wts-help-section h3 {
    margin-top: 0;
}

.wts-help-section ol, .wts-help-section ul {
    margin: 10px 0;
    padding-left: 30px;
}

.wts-help-section li {
    margin-bottom: 8px;
}

.wts-help-section a {
    color: #0073aa;
}

.wts-help-section code {
    background: #eee;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}

.wts-user-multiselect {
    width: 100%;
    max-width: 400px;
    height: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px;
    font-size: 14px;
    line-height: 1.4;
}

.wts-user-multiselect option {
    padding: 4px 8px;
    border-radius: 2px;
}

.wts-user-multiselect option:hover {
    background-color: #f0f0f0;
}
</style>

<?php endif; ?>