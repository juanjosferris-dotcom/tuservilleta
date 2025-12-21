# Tuservilleta Form Plugin - Testing Guide

## Overview
This document provides instructions for testing the Tuservilleta Form plugin to ensure all features work correctly.

## Prerequisites
- WordPress 5.0 or higher installed
- PHP 7.2 or higher
- Access to WordPress admin dashboard
- Email delivery configured (SMTP or default wp_mail)

## Installation Testing

### 1. Install the Plugin
```bash
# Copy plugin to WordPress plugins directory
cp -r tuservilleta-form /path/to/wordpress/wp-content/plugins/

# Or upload via WordPress admin:
# Dashboard → Plugins → Add New → Upload Plugin
```

### 2. Activate the Plugin
- Navigate to **Plugins** in WordPress admin
- Find "Tuservilleta Form" in the plugin list
- Click **Activate**
- ✅ Verify: No errors appear during activation

## Feature Testing

### Test 1: Form Rendering
**Objective**: Verify the form displays correctly on a page

**Steps**:
1. Create or edit a WordPress page
2. Add the shortcode: `[tuservilleta_form]`
3. Publish/Update the page
4. View the page on the frontend

**Expected Result**:
- ✅ Form displays with all fields (Name, Email, Phone, Subject, Message)
- ✅ Form is responsive on mobile, tablet, and desktop
- ✅ CSS styles are loaded correctly
- ✅ Form has proper spacing and alignment

### Test 2: Shortcode Customization
**Objective**: Verify shortcode attributes work

**Steps**:
1. Use shortcode with custom attributes:
   ```
   [tuservilleta_form title="Get in Touch" submit_text="Send Message"]
   ```
2. View the page

**Expected Result**:
- ✅ Form title displays "Get in Touch"
- ✅ Submit button shows "Send Message"

### Test 3: Client-Side Validation
**Objective**: Verify JavaScript validation works

**Steps**:
1. Navigate to the form page
2. Click Submit without filling any fields

**Expected Result**:
- ✅ Form does NOT submit
- ✅ Error messages appear under required fields
- ✅ Fields are highlighted with red border
- ✅ Page scrolls to first error

**Steps**:
1. Enter invalid email (e.g., "notanemail")
2. Tab to next field

**Expected Result**:
- ✅ Email field shows error: "Please enter a valid email address"

**Steps**:
1. Type in the Message field
2. Observe character counter

**Expected Result**:
- ✅ Character counter appears (e.g., "150 / 2000 characters")
- ✅ Counter updates in real-time

### Test 4: Server-Side Validation
**Objective**: Verify PHP validation and sanitization

**Test Case 4.1: Empty Form Submission**
1. Disable JavaScript in browser
2. Try to submit empty form

**Expected Result**:
- ✅ Form redirects with error message
- ✅ No email is sent

**Test Case 4.2: Invalid Email**
1. Enter invalid email with JS disabled
2. Submit form

**Expected Result**:
- ✅ Form validation fails
- ✅ Error message appears
- ✅ No email is sent

### Test 5: CSRF Protection
**Objective**: Verify nonce security works

**Steps**:
1. Inspect form HTML
2. Locate hidden nonce field: `tuservilleta_form_nonce`
3. Copy form HTML to external file
4. Try submitting from external page

**Expected Result**:
- ✅ Submission fails with "Security check failed" message

**Alternative Test**:
1. Submit valid form
2. Use browser back button
3. Submit again (nonce expired)

**Expected Result**:
- ✅ Second submission may fail if nonce has expired

### Test 6: Email Notification
**Objective**: Verify admin receives email

**Steps**:
1. Fill form with valid data:
   - Name: Test User
   - Email: test@example.com
   - Phone: 123-456-7890
   - Subject: Test Submission
   - Message: This is a test message
2. Submit form

**Expected Result**:
- ✅ Success message appears: "Thank you! Your message has been sent successfully."
- ✅ Admin email receives notification
- ✅ Email contains all form data
- ✅ Email Reply-To is set to submitter's email
- ✅ Email subject includes site name and subject

**Email Content Check**:
```
Subject: [Site Name] New Form Submission: Test Submission
From: WordPress <wordpress@yourdomain.com>
Reply-To: Test User <test@example.com>

New form submission from Site Name

Submission Details:
--------------------------------------------------

Name: Test User
Email: test@example.com
Phone: 123-456-7890
Subject: Test Submission

Message:
This is a test message

--------------------------------------------------
Submitted at: 2025-12-21 08:35:00
```

### Test 7: Database Storage
**Objective**: Verify submissions are saved in database

**Steps**:
1. Submit a form
2. In WordPress admin, navigate to **Form Submissions** menu

**Expected Result**:
- ✅ New custom post type menu "Form Submissions" exists
- ✅ Submission appears in the list
- ✅ Submission shows correct title
- ✅ Opening submission shows message content
- ✅ Meta fields are saved (check with custom code or meta box)

**Database Check (Advanced)**:
```sql
-- Check post type exists
SELECT * FROM wp_posts WHERE post_type = 'tuservilleta_submission';

-- Check meta data
SELECT * FROM wp_postmeta WHERE post_id = [submission_id];
```

### Test 8: Data Sanitization
**Objective**: Verify XSS protection

**Steps**:
1. Enter malicious code in form fields:
   - Name: `<script>alert('XSS')</script>`
   - Subject: `<img src=x onerror=alert('XSS')>`
   - Message: `<b>Bold</b> and <script>alert('XSS')</script>`
2. Submit form

**Expected Result**:
- ✅ Script tags are stripped
- ✅ HTML is escaped
- ✅ No JavaScript executes
- ✅ Email shows sanitized content
- ✅ Database contains sanitized content

### Test 9: Responsive Design
**Objective**: Verify mobile compatibility

**Steps**:
1. Open form on different devices:
   - Desktop (1920x1080)
   - Tablet (768x1024)
   - Mobile (375x667)
2. Test form interaction on each

**Expected Result**:
- ✅ Form adapts to screen size
- ✅ All fields are accessible
- ✅ Touch targets are adequate (min 44x44px)
- ✅ No horizontal scrolling
- ✅ Font sizes are readable

### Test 10: Accessibility Testing
**Objective**: Verify WCAG compliance

**Steps**:
1. Navigate form using keyboard only (Tab key)
2. Test with screen reader (NVDA/JAWS)
3. Check color contrast

**Expected Result**:
- ✅ All fields are keyboard accessible
- ✅ Focus indicators are visible
- ✅ Labels are properly associated with inputs
- ✅ Error messages are announced
- ✅ Required fields are indicated
- ✅ Color contrast meets WCAG AA (4.5:1)

### Test 11: Localization
**Objective**: Verify translation support

**Steps**:
1. Install translation plugin (e.g., Loco Translate)
2. Create translation for text domain `tuservilleta-form`
3. Translate strings
4. View form in translated language

**Expected Result**:
- ✅ All form labels are translatable
- ✅ Error messages are translatable
- ✅ Success messages are translatable
- ✅ Translated strings appear correctly

### Test 12: Multiple Forms on Same Page
**Objective**: Verify multiple instances work

**Steps**:
1. Add multiple shortcodes to same page:
   ```
   [tuservilleta_form title="Contact Sales"]
   
   [tuservilleta_form title="Support Request"]
   ```
2. Submit each form separately

**Expected Result**:
- ✅ Both forms render correctly
- ✅ Each form submits independently
- ✅ No JavaScript conflicts
- ✅ No CSS conflicts

### Test 13: Plugin Deactivation/Deletion
**Objective**: Verify clean deactivation

**Steps**:
1. Deactivate plugin
2. Check frontend page with shortcode

**Expected Result**:
- ✅ No errors on deactivation
- ✅ Shortcode content doesn't display (or shows shortcode text)

**Steps**:
1. Reactivate plugin
2. Deactivate and delete plugin

**Expected Result**:
- ✅ Plugin files are removed
- ✅ Submissions remain in database (optional: check if you want to preserve data)

## Performance Testing

### Test 14: Load Time
**Objective**: Verify plugin doesn't slow down site

**Steps**:
1. Measure page load time without plugin
2. Activate plugin and measure again
3. Use tools like GTmetrix or WebPageTest

**Expected Result**:
- ✅ Minimal impact on page load time (<100ms)
- ✅ CSS file size is reasonable (~5KB)
- ✅ JS file size is reasonable (~10KB)

## Security Testing

### Test 15: SQL Injection
**Objective**: Verify database queries are safe

**Steps**:
1. Try SQL injection in form fields:
   - Name: `'; DROP TABLE wp_posts; --`
   - Email: `admin@site.com' OR '1'='1`
2. Submit form

**Expected Result**:
- ✅ No SQL errors
- ✅ Data is escaped/sanitized
- ✅ Database remains intact

### Test 16: File Upload Prevention
**Objective**: Verify no unauthorized uploads

**Steps**:
1. Inspect form for file upload fields
2. Try to modify HTML to add file input
3. Attempt submission

**Expected Result**:
- ✅ No file upload fields exist
- ✅ Modified submissions are rejected

## Error Handling

### Test 17: Email Delivery Failure
**Objective**: Verify graceful error handling

**Steps**:
1. Temporarily disable email (misconfigure SMTP)
2. Submit form

**Expected Result**:
- ✅ Error message appears: "There was an error submitting your form"
- ✅ Submission is still saved to database
- ✅ No PHP errors displayed

### Test 18: Database Write Failure
**Objective**: Verify error handling for DB issues

**Steps**:
1. Simulate database error (requires technical setup)
2. Submit form

**Expected Result**:
- ✅ Email is still sent (if possible)
- ✅ User sees appropriate error/success message
- ✅ No sensitive error details exposed

## Browser Compatibility

### Test 19: Cross-Browser Testing
**Objective**: Verify compatibility

**Browsers to Test**:
- ✅ Chrome (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Edge (latest)
- ✅ Mobile Safari (iOS)
- ✅ Chrome Mobile (Android)

**Test in Each**:
- Form rendering
- Validation
- Submission
- Responsive design

## Test Results Template

```
Test Date: _______________
Tester: __________________
WordPress Version: _______
PHP Version: _____________
Theme: ___________________

Test 1: Form Rendering              [ ] Pass [ ] Fail
Test 2: Shortcode Customization     [ ] Pass [ ] Fail
Test 3: Client-Side Validation      [ ] Pass [ ] Fail
Test 4: Server-Side Validation      [ ] Pass [ ] Fail
Test 5: CSRF Protection             [ ] Pass [ ] Fail
Test 6: Email Notification          [ ] Pass [ ] Fail
Test 7: Database Storage            [ ] Pass [ ] Fail
Test 8: Data Sanitization           [ ] Pass [ ] Fail
Test 9: Responsive Design           [ ] Pass [ ] Fail
Test 10: Accessibility              [ ] Pass [ ] Fail
Test 11: Localization               [ ] Pass [ ] Fail
Test 12: Multiple Forms             [ ] Pass [ ] Fail
Test 13: Deactivation/Deletion      [ ] Pass [ ] Fail
Test 14: Load Time                  [ ] Pass [ ] Fail
Test 15: SQL Injection              [ ] Pass [ ] Fail
Test 16: File Upload Prevention     [ ] Pass [ ] Fail
Test 17: Email Failure              [ ] Pass [ ] Fail
Test 18: Database Failure           [ ] Pass [ ] Fail
Test 19: Cross-Browser              [ ] Pass [ ] Fail

Notes:
_________________________________________________
_________________________________________________
_________________________________________________
```

## Automated Testing (Future Enhancement)

For future development, consider adding:

- PHPUnit tests for form handler class
- Integration tests for WordPress hooks
- JavaScript unit tests for validation logic
- Selenium/Playwright tests for E2E testing

## Support

If any test fails, check:
1. WordPress and PHP version compatibility
2. Plugin conflicts (disable other plugins)
3. Theme compatibility (switch to default theme)
4. Server configuration (error logs)
5. Email configuration (test wp_mail function)

For support, contact: support@tuservilleta.com
