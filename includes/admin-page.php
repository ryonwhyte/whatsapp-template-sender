<?php
if (!defined('ABSPATH')) {
    exit;
}

$api_service = new WTS_API_Service();
$templates = $api_service->get_available_templates();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="wts-send-message-container">
        <div class="wts-form-section">
            <h2>Send WhatsApp Template Message</h2>
            
            <form id="wts-send-form">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="phone_number">Recipient Phone Number</label>
                            </th>
                            <td>
                                <input type="tel" 
                                       id="phone_number" 
                                       name="phone_number" 
                                       class="regular-text" 
                                       placeholder="18761234567 or 876-123-4567"
                                       required />
                                <p class="description">
                                    Enter phone number with country code (e.g., 18761234567 for Jamaica)
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="template_name">Template</label>
                            </th>
                            <td>
                                <select id="template_name" name="template_name" class="regular-text" required>
                                    <option value="">Select a template...</option>
                                    <?php if (!empty($templates)): ?>
                                        <?php foreach ($templates as $template): ?>
                                            <?php if ($template['status'] === 'APPROVED'): ?>
                                                <option value="<?php echo esc_attr($template['name']); ?>">
                                                    <?php echo esc_html($template['name']); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option disabled>No templates found - check API settings</option>
                                    <?php endif; ?>
                                </select>
                                <p class="description">
                                    Select an approved WhatsApp message template
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="template_language">Template Language</label>
                            </th>
                            <td>
                                <select id="template_language" name="template_language" class="regular-text" required>
                                    <option value="en">English (en)</option>
                                    <option value="en_US">English (US) (en_US)</option>
                                </select>
                                <p class="description">
                                    Select the language of your template. Most templates use "English (en)".
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label>Template Parameters</label>
                            </th>
                            <td>
                                <div id="template-parameters">
                                    <p class="description">
                                        Select a template to see available parameters
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" 
                           name="submit" 
                           id="submit" 
                           class="button button-primary" 
                           value="Send Message" 
                           disabled />
                    <span id="wts-loading" style="display: none;">
                        <span class="spinner is-active"></span> Sending...
                    </span>
                </p>
            </form>
        </div>
        
        <div class="wts-status-section">
            <div id="wts-message-status" style="display: none;"></div>
        </div>
        
        <div class="wts-templates-info">
            <h3>Available Templates</h3>
            <?php if (!empty($templates)): ?>
                <div class="wts-templates-list">
                    <?php foreach ($templates as $template): ?>
                        <div class="wts-template-item">
                            <strong><?php echo esc_html($template['name']); ?></strong>
                            <span class="wts-template-status status-<?php echo strtolower($template['status']); ?>">
                                <?php echo esc_html($template['status']); ?>
                            </span>
                            <?php if (isset($template['components'])): ?>
                                <div class="wts-template-body">
                                    <?php foreach ($template['components'] as $component): ?>
                                        <?php if ($component['type'] === 'BODY'): ?>
                                            <code><?php echo esc_html($component['text']); ?></code>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No templates found. Please check your API settings and ensure you have approved templates in Meta Business Manager.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
var wtsTemplates = <?php echo json_encode($templates); ?>;
</script>

<style>
.wts-send-message-container {
    max-width: 800px;
}

.wts-form-section {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
}

.wts-status-section {
    margin: 20px 0;
}

.wts-templates-info {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 4px;
}

.wts-templates-list {
    display: grid;
    gap: 15px;
    margin-top: 15px;
}

.wts-template-item {
    background: #fff;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.wts-template-status {
    float: right;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-approved {
    background: #d4edda;
    color: #155724;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.wts-template-body {
    margin-top: 10px;
    clear: both;
}

.wts-template-body code {
    background: #f1f1f1;
    padding: 8px 12px;
    display: block;
    border-radius: 3px;
    font-family: monospace;
    font-size: 13px;
    white-space: pre-wrap;
}

#template-parameters .parameter-input {
    margin-bottom: 10px;
}

#template-parameters label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

#template-parameters input {
    width: 100%;
    max-width: 400px;
}

.notice.inline {
    margin: 5px 0 15px 0;
}

#wts-loading {
    margin-left: 10px;
    vertical-align: middle;
}
</style>