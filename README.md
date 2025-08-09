# WhatsApp Template Message Sender for WordPress

A WordPress plugin that allows administrators to send WhatsApp Business API template messages (HSM - Highly Structured Messages) directly from the WordPress admin dashboard, with full message logging and history tracking.

## 🎯 Purpose

This plugin is designed to **initiate WhatsApp conversations outside the 24-hour window** when your existing systems (like 3CX) cannot do so. Once a customer replies to the template message, your regular WhatsApp Business system can take over the conversation normally.

## ✨ Features

- **Send WhatsApp Template Messages** via Meta Cloud API
- **Auto-fetch approved templates** from your WhatsApp Business Account
- **Dynamic parameter filling** with real-time template preview
- **Language selection** support (English `en` and English US `en_US`)
- **Complete message logging** with timestamp, sender, and API response
- **Message history viewer** with search and pagination
- **Phone number validation** with international format support
- **Secure admin interface** with proper nonce verification
- **Easy setup** with connection testing

## 📋 Requirements

- **WordPress** 5.0 or higher
- **PHP** 7.4 or higher
- **Meta WhatsApp Business Account** with:
  - Verified phone number
  - Approved message templates
  - WhatsApp Cloud API access
  - Required permissions: `whatsapp_business_messaging` and `whatsapp_business_management`

## 🚀 Installation

1. **Download** the plugin files
2. **Upload** to `/wp-content/plugins/whatsapp-template-sender/`
3. **Activate** the plugin through WordPress admin
4. **Configure** API credentials in Settings

## ⚙️ Configuration

### Step 1: Get Your API Credentials

1. **Create a Meta App**: Go to [Facebook Developers](https://developers.facebook.com/) and create a new app
2. **Add WhatsApp Product**: Add WhatsApp Business API to your app
3. **Generate Access Token**: Create a permanent access token with required permissions
4. **Get Phone Number ID**: Find your phone number ID in WhatsApp API configuration
5. **Get Business Account ID**: Find your WhatsApp Business Account ID (WABA ID) in Meta Business Manager

### Step 2: Configure Plugin Settings

Navigate to **WhatsApp Templates > Settings** in your WordPress admin and enter:

- **Access Token**: Your WhatsApp Cloud API access token
- **Phone Number ID**: Your WhatsApp Business phone number ID  
- **Business Account ID**: Your WhatsApp Business Account ID (WABA ID)

Click **Save Settings** and verify you see "Connection successful".

### Step 3: Create Templates

Create and get approval for message templates in Meta Business Manager. Templates support parameters using `{{1}}`, `{{2}}`, etc.

## 📱 Usage

### Sending Messages

1. Go to **WhatsApp Templates > Send Message**
2. **Enter recipient phone number** (with country code, e.g., `18761234567`)
3. **Select a template** from the dropdown (shows only approved templates)
4. **Choose template language** (`en` or `en_US` based on your template)
5. **Fill in parameters** if required by the template
6. **Click Send Message**

### Viewing Message History

1. Go to **WhatsApp Templates > Message History**
2. View all sent messages with details:
   - Date and time sent
   - Recipient phone number
   - Template used
   - Parameters sent
   - Sending admin
   - Status (sent/failed)
3. **Filter by phone number** or use pagination

## 🛠️ Technical Details

### API Endpoints Used

- **Send Messages**: `POST https://graph.facebook.com/v19.0/{phone_number_id}/messages`
- **Fetch Templates**: `GET https://graph.facebook.com/v19.0/{business_account_id}/message_templates`
- **Connection Test**: `GET https://graph.facebook.com/v19.0/{phone_number_id}`

### Database Schema

The plugin creates a custom table `wp_whatsapp_templates` with:

```sql
CREATE TABLE wp_whatsapp_templates (
  id bigint(20) NOT NULL AUTO_INCREMENT,
  phone_number varchar(20) NOT NULL,
  template_name varchar(100) NOT NULL,
  parameters text,
  sent_by bigint(20) NOT NULL,
  sent_at datetime DEFAULT CURRENT_TIMESTAMP,
  status varchar(20) DEFAULT 'sent',
  api_response text,
  PRIMARY KEY (id)
);
```

### Security Features

- **Admin-only access**: Only users with `manage_options` capability can use the plugin
- **Nonce verification**: All forms include CSRF protection
- **Input sanitization**: All user inputs are properly sanitized
- **Secure credential storage**: API credentials stored as WordPress options

## 🔧 Troubleshooting

### Common Issues

**"No templates found"**
- Verify your Business Account ID is correct
- Ensure templates are approved in Meta Business Manager
- Check API permissions include `whatsapp_business_management`

**"Template name does not exist in the translation"**
- Try switching between "English (en)" and "English (US) (en_US)" language options
- Verify the template language in Meta Business Manager matches your selection

**"Connection failed"**
- Double-check your Access Token and Phone Number ID
- Ensure your access token has required permissions
- Verify your WhatsApp Business Account is active

**Phone number format errors**
- Use international format with country code (e.g., `18761234567` for Jamaica)
- Remove any spaces, dashes, or special characters

### Debug Information

Enable WordPress debug logging and check for API response details in failed message logs through the Message History page.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) for details.

## 🆘 Support

For issues and feature requests, please [open an issue](https://github.com/ryonwhyte/whatsapp-template-sender/issues) on GitHub.

---

**Note**: This plugin is designed to work alongside existing WhatsApp Business solutions like 3CX. It handles template message initiation, while your existing system manages ongoing conversations.