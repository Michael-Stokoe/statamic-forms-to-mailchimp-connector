# Forms To Mailchimp Connector

**Seamlessly connect your Statamic forms to Mailchimp** - Automatically add form submissions to your Mailchimp lists with advanced field mapping and configuration options.

## Features

- **Automatic subscriber addition** to any Mailchimp list
- **Custom field mapping** - Map form fields to Mailchimp merge fields
- **Double opt-in support** - Choose between immediate subscription or email confirmation
- **Comprehensive error handling** - Detailed logging for debugging
- **Production-ready** - Built with security and reliability in mind

## Requirements

- [Forms To Wherever](https://statamic.com/addons/stokoe/forms-to-wherever) base addon
- Mailchimp account with API access
- PHP 8.2+
- Statamic 5.0+

## Installation

1. Install the base Forms To Wherever addon:
```bash
composer require stokoe/forms-to-wherever
```

2. Install this Mailchimp connector:
```bash
composer require stokoe/forms-to-mailchimp-connector
```

## Configuration

### 1. Get Your Mailchimp API Key

1. Log into your Mailchimp account
2. Go to **Account → Extras → API keys**
3. Create a new API key or copy an existing one
4. Note the datacenter (e.g., `us1`, `us2`) from the end of your API key

### 2. Get Your List ID

1. In Mailchimp, go to **Audience → All contacts**
2. Click **Settings → Audience name and defaults**
3. Copy the **Audience ID** (this is your List ID)

### 3. Configure Your Form

Add the `form_connectors` field to your form blueprint:

```yaml
fields:
  # Your existing form fields...
  -
    handle: connectors
    field:
      type: form_connectors
      display: Form Connectors
```

### 4. Enable Mailchimp in Control Panel

1. Edit your form in the Statamic Control Panel
2. Navigate to the "Form Connectors" section
3. Enable the **Mailchimp** connector
4. Configure the settings:
   - **API Key**: Your Mailchimp API key
   - **List ID**: Your Mailchimp audience ID
   - **Email Field**: Form field containing email (default: `email`)
   - **Double Opt-in**: Enable for email confirmation requirement
   - **Field Mapping**: Map form fields to Mailchimp merge fields

## Field Mapping

Map your form fields to Mailchimp merge fields for rich subscriber data:

| Form Field | Mailchimp Merge Tag | Description |
|------------|-------------------|-------------|
| `first_name` | `FNAME` | First name |
| `last_name` | `LNAME` | Last name |
| `phone` | `PHONE` | Phone number |
| `company` | `COMPANY` | Company name |
| `website` | `WEBSITE` | Website URL |

### Custom Merge Fields

You can create custom merge fields in Mailchimp and map them:

1. In Mailchimp: **Audience → Settings → Audience fields and *|MERGE|* tags**
2. Add your custom field and note the merge tag
3. Map your form field to the custom merge tag in the connector settings

## Example Form

```yaml
# resources/forms/newsletter.yaml
title: Newsletter Signup
fields:
  -
    handle: email
    field:
      type: email
      display: Email Address
      validate: required|email
  -
    handle: first_name
    field:
      type: text
      display: First Name
      validate: required
  -
    handle: last_name
    field:
      type: text
      display: Last Name
  -
    handle: company
    field:
      type: text
      display: Company
  -
    handle: connectors
    field:
      type: form_connectors
      display: Form Connectors
```

## Error Handling

The connector includes comprehensive error handling:

- **Invalid API keys** - Logs warning and skips processing
- **Missing email addresses** - Logs warning with context
- **API failures** - Logs detailed error information
- **Network timeouts** - 10-second timeout with graceful failure

All errors are logged to your Laravel log files for debugging.

## Double Opt-in

When enabled, subscribers will receive a confirmation email before being added to your list:

- **Enabled**: Subscriber status set to `pending`, confirmation email sent
- **Disabled**: Subscriber immediately added with `subscribed` status

## Asynchronous Processing

By default, Mailchimp API calls are processed asynchronously using Laravel queues to prevent form submission delays. Ensure your queue worker is running:

```bash
php artisan queue:work
```

To process synchronously (not recommended for production), disable async processing in the form connector settings.

## Troubleshooting

### Common Issues

**"Invalid API key format"**
- Ensure your API key includes the datacenter (e.g., `abc123-us1`)
- Verify the API key is active in your Mailchimp account

**"Email not found"**
- Check the "Email Field" setting matches your form field handle
- Ensure the email field contains a valid email address

**"List not found"**
- Verify the List ID is correct
- Ensure the API key has access to the specified list

### Debug Logging

Enable debug logging to see detailed API interactions:

```php
// In your .env file
LOG_LEVEL=debug
```

Check `storage/logs/laravel.log` for detailed connector activity.

## Security

- API keys are never logged or exposed in error messages
- All API communications use HTTPS
- Email addresses are validated before sending to Mailchimp
- Comprehensive input sanitization and validation

## Support

- **Marketplace**: [Forms To Mailchimp Connector](https://statamic.com/addons/stokoe/forms-to-mailchimp-connector)
- **Base Addon**: [Forms To Wherever](https://statamic.com/addons/stokoe/forms-to-wherever)
- **Mailchimp API**: [Official Documentation](https://mailchimp.com/developer/marketing/api/)

## License

MIT License - Build amazing things with it!
