# Tuservilleta Form Plugin - Quick Start Guide

## 🚀 Installation

### Method 1: Upload via WordPress Admin
1. Download the `tuservilleta-form` folder
2. In WordPress admin, go to **Plugins → Add New → Upload Plugin**
3. Upload the plugin as a ZIP file or FTP the folder to `/wp-content/plugins/`
4. Click **Activate Plugin**

### Method 2: Manual Installation
```bash
# Navigate to your WordPress installation
cd /path/to/wordpress/wp-content/plugins/

# Copy the plugin folder (assuming it's in the repo root)
cp -r /path/to/repo/wp-content/plugins/tuservilleta-form .

# Set proper permissions
chmod -R 755 tuservilleta-form
```

## 📝 Basic Usage

### Add Form to a Page

1. **Edit any WordPress page or post**
2. **Add the shortcode:**
   ```
   [tuservilleta_form]
   ```
3. **Publish/Update the page**
4. **View the page** - the form will appear!

### Customize the Form

Use shortcode attributes to customize:

```
[tuservilleta_form title="Contact Sales" submit_text="Send Now"]
```

**Available Attributes:**
- `title` - Form heading (default: "Contact Us")
- `submit_text` - Button text (default: "Submit")

## 🎨 Form Fields

The form includes:
- ✅ **Name** (required) - Text input
- ✅ **Email** (required) - Email input with validation
- ✅ **Phone** (optional) - Phone number input
- ✅ **Subject** (required) - Subject line
- ✅ **Message** (required) - Textarea with character counter

## 📧 Email Configuration

By default, form submissions send an email to the WordPress admin email address.

### Check Email Settings
1. Go to **Settings → General**
2. Verify **Administration Email Address** is correct

### Test Email Delivery
```php
// Add this to your theme's functions.php temporarily to test
add_action('init', function() {
    if (isset($_GET['test_email'])) {
        $result = wp_mail(
            get_option('admin_email'),
            'Test Email',
            'If you receive this, email is working!'
        );
        die($result ? 'Email sent!' : 'Email failed!');
    }
});
```

Then visit: `yoursite.com/?test_email`

### Configure SMTP (Recommended)

For reliable email delivery, use an SMTP plugin:
1. Install **WP Mail SMTP** or **Easy WP SMTP**
2. Configure with your email provider (Gmail, SendGrid, etc.)

## 🗂️ View Form Submissions

1. In WordPress admin, look for **Form Submissions** menu item
2. Click to view all submissions
3. Submissions are stored as private posts
4. Only administrators can view them

## 🎨 Customize Styling

### Override Default Styles

Add to your theme's CSS (Appearance → Customize → Additional CSS):

```css
/* Change form background color */
.tuservilleta-form-container {
    background: #f8f9fa;
}

/* Change submit button color */
.tuservilleta-submit-btn {
    background-color: #28a745;
}

.tuservilleta-submit-btn:hover {
    background-color: #218838;
}

/* Change form width */
.tuservilleta-form-container {
    max-width: 800px;
}
```

## 🌍 Translation

### Using Loco Translate (Recommended)

1. Install **Loco Translate** plugin
2. Go to **Loco Translate → Plugins → Tuservilleta Form**
3. Click **New language**
4. Select your language (e.g., Spanish)
5. Translate all strings
6. Save

### Manual Translation

1. Create a `.po` file in `/wp-content/languages/plugins/`
2. Name it: `tuservilleta-form-{locale}.po` (e.g., `tuservilleta-form-es_ES.po`)
3. Translate strings
4. Compile to `.mo` file

## 🔧 Troubleshooting

### Form Doesn't Appear
- ✅ Verify plugin is activated
- ✅ Check shortcode spelling: `[tuservilleta_form]`
- ✅ Clear cache if using caching plugin
- ✅ Check for JavaScript errors in browser console

### Email Not Received
- ✅ Check spam/junk folder
- ✅ Verify admin email in Settings → General
- ✅ Test with simple test email (see above)
- ✅ Install SMTP plugin for reliable delivery
- ✅ Check server mail logs

### Form Shows "Security Check Failed"
- ✅ Clear browser cache
- ✅ Clear WordPress cache
- ✅ Disable caching on form page
- ✅ Check that cookies are enabled

### Validation Not Working
- ✅ Check browser console for JavaScript errors
- ✅ Verify jQuery is loaded
- ✅ Check for plugin conflicts (disable other plugins temporarily)

### Styling Issues
- ✅ Clear browser cache
- ✅ Check for CSS conflicts with theme
- ✅ Verify CSS file is loading (check Network tab in browser DevTools)
- ✅ Try adding `!important` to custom CSS rules

## 🔒 Security Notes

- ✅ All inputs are sanitized server-side
- ✅ CSRF protection with WordPress nonces
- ✅ XSS prevention through proper escaping
- ✅ Email addresses validated with WordPress `is_email()`
- ✅ No file upload functionality (prevents malicious uploads)

## 📊 Monitor Submissions

### Export Submissions

Use a plugin like **WP All Export** to export form submissions to CSV/Excel.

### Set Up Email Alerts

The plugin automatically emails the admin on each submission. To add more recipients:

```php
// Add to theme's functions.php
add_filter('tuservilleta_form_email_to', function($email) {
    return array(
        get_option('admin_email'),
        'sales@yourcompany.com',
        'support@yourcompany.com'
    );
});
```

### Integration with Other Services

Send submissions to external services:

```php
// Add to theme's functions.php
add_action('tuservilleta_form_after_submit', function($form_data) {
    // Send to Slack
    wp_remote_post('https://hooks.slack.com/services/YOUR/WEBHOOK/URL', array(
        'body' => json_encode(array(
            'text' => 'New form submission from: ' . $form_data['name']
        ))
    ));
    
    // Send to Google Sheets, CRM, etc.
});
```

## 🎯 Best Practices

1. **Test the form** after installation
2. **Configure SMTP** for reliable email delivery
3. **Check submissions regularly** in WordPress admin
4. **Backup submissions** periodically
5. **Keep plugin updated** (if updates are released)
6. **Monitor spam submissions** and add CAPTCHA if needed

## 📱 Responsive Design

The form is fully responsive and works on:
- 📱 Mobile phones (portrait & landscape)
- 📱 Tablets (portrait & landscape)
- 💻 Desktop computers
- 🖥️ Large screens

No additional configuration needed!

## ♿ Accessibility

The form follows WCAG 2.1 guidelines:
- Keyboard navigation support
- Screen reader friendly
- Proper label associations
- Focus indicators
- Error message announcements
- Sufficient color contrast

## 🆘 Support

For issues or questions:
1. Check the [TESTING.md](TESTING.md) file for comprehensive testing procedures
2. Review the [README.md](README.md) for detailed documentation
3. Contact: support@tuservilleta.com

## 📈 Next Steps

After installation:
1. ✅ Test the form with a real submission
2. ✅ Verify you receive the email
3. ✅ Customize the styling to match your site
4. ✅ Translate if needed
5. ✅ Add to multiple pages if desired

---

**Version:** 1.0.0  
**Author:** Tuservilleta, SL  
**License:** GPL v2 or later
