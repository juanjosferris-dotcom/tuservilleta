# Tuservilleta Form Plugin

A custom WordPress plugin for the Tuservilleta website that provides a secure, responsive contact form with validation, email notifications, and database storage.

## Features

- **Responsive Design**: Mobile-friendly form that adapts to all screen sizes
- **Security**: Built-in CSRF protection with WordPress nonces
- **Validation**: Both client-side (JavaScript) and server-side (PHP) validation
- **Email Notifications**: Automatic email notifications to admin on form submission
- **Database Storage**: Optional storage of submissions in WordPress database
- **Localization**: Full translation support using WordPress gettext
- **Customizable**: Shortcode attributes for easy customization
- **Accessibility**: WCAG compliant with proper labels and focus management

## Installation

1. Upload the `tuservilleta-form` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin is now ready to use!

## Usage

### Basic Shortcode

Add the form to any post or page using the shortcode:

```
[tuservilleta_form]
```

### Customized Shortcode

You can customize the form title and submit button text:

```
[tuservilleta_form title="Get in Touch" submit_text="Send Message"]
```

### Shortcode Attributes

- `title` - Form heading text (default: "Contact Us")
- `submit_text` - Submit button text (default: "Submit")

## Form Fields

The form includes the following fields:

1. **Name** (required) - Text input for user's name
2. **Email** (required) - Email input with validation
3. **Phone** (optional) - Telephone number input
4. **Subject** (required) - Subject line for the message
5. **Message** (required) - Textarea for the main message

## Security Features

- **Nonce Verification**: All form submissions are verified using WordPress nonces
- **Data Sanitization**: All input is sanitized before processing
- **Email Validation**: Server-side email validation using WordPress `is_email()`
- **CSRF Protection**: Built-in protection against cross-site request forgery attacks

## Email Notifications

When a form is submitted:

- An email is sent to the WordPress admin email address
- The email includes all form data
- Reply-To header is set to the submitter's email for easy responses

## Database Storage

Form submissions are stored as a custom post type `tuservilleta_submission`:

- Accessible from WordPress admin menu
- Stored as private posts
- Includes all form metadata
- Can be managed like regular posts

## Client-Side Validation

The plugin includes real-time JavaScript validation:

- Required field checking
- Email format validation
- Phone number format validation
- Character length validation
- Visual feedback with error messages
- Character counter for message field

## Styling

The form comes with default styling that includes:

- Clean, modern design
- Smooth transitions and animations
- Focus states for accessibility
- Error and success states
- Loading spinner on submit button
- Dark mode support (optional)

### Custom CSS

To override default styles, add custom CSS to your theme:

```css
.tuservilleta-form-container {
    /* Your custom styles */
}
```

## Localization

The plugin is translation-ready. To translate:

1. Create a `.po` file for your language
2. Place it in the `/languages/` directory
3. Name it `tuservilleta-form-{locale}.po` (e.g., `tuservilleta-form-es_ES.po`)
4. Compile to `.mo` file

## Developer Hooks

### Filters

The plugin provides several filters for customization:

```php
// Modify email recipient
add_filter('tuservilleta_form_email_to', function($email) {
    return 'custom@email.com';
});

// Modify email subject
add_filter('tuservilleta_form_email_subject', function($subject, $form_data) {
    return 'Custom Subject: ' . $form_data['subject'];
}, 10, 2);
```

### Actions

```php
// Action after successful submission
add_action('tuservilleta_form_after_submit', function($form_data) {
    // Your custom code
});
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- jQuery (included with WordPress)

## File Structure

```
tuservilleta-form/
├── tuservilleta-form.php       # Main plugin file
├── README.md                    # This file
├── includes/
│   └── form-handler.php        # Form processing logic
└── assets/
    ├── css/
    │   └── style.css           # Form styles
    └── js/
        └── validation.js       # Client-side validation
```

## Support

For issues, questions, or feature requests, please contact Tuservilleta, SL.

## License

GPL v2 or later

## Changelog

### Version 1.0.0
- Initial release
- Contact form with validation
- Email notifications
- Database storage
- Responsive design
- Security features
