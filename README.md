# Mailpot

A local mail inbox for Laravel. No external tools or services required.
Mailpot lets you intercept, store, and inspect emails sent during development.

Think of it like [Mailtrap](https://mailtrap.io/) or [Mailhog](https://github.com/mailhog/MailHog), but fully embedded in your Laravel app. No Docker, no SMTP config, no fuss.

## Features

- Stores sent mails as `.json` files
- Clean inbox UI built with Tailwind and Alpine.js
- Read/unread tracking (client-side, no database required)
- Resizable viewport preview (mobile, tablet, desktop)
- Stats: message count, storage size, largest/smallest message
- Artisan commands for cleaning inbox and viewing stats
- Configurable storage path
- No third-party tools or services
- Compatible with Laravel 10, 11, and 12

## Installation

```bash
composer require --dev rulr/laravel-mailpot
```

This package is intended for local development only.

## Mail Configuration

Update your `config/mail.php` file to add the mailpot mailer:

```php
'mailers' => [
    'mailpot' => [
        'transport' => 'mailpot',
    ],
    // ...
],
```

Set the mailer in your `.env` file:

```env
MAIL_MAILER=mailpot
```

## Publishing Configuration

To customize the storage path, publish the configuration file:

```bash
php artisan vendor:publish --tag=mailpot-config
```

This creates `config/mailpot.php` with the following options:

```php
return [
    'storage_path' => env('MAILPOT_STORAGE_PATH', storage_path('framework/mailpot')),
];
```

You can set a custom path in your `.env` file:

```env
MAILPOT_STORAGE_PATH=/path/to/custom/mailpot
```

If not configured, messages are stored in `storage/framework/mailpot`.

## Web UI

Visit the inbox in your browser:

```
http://localhost:8000/mailpot
```

The interface allows you to:

- Browse and read emails with subject, from, to, date, and content
- Switch between mobile, tablet, and desktop viewport sizes
- Resize the viewport manually for custom widths
- Track read/unread status (stored in browser localStorage)
- View inbox statistics when no email is selected

The web UI is only available when `APP_ENV=local`.

## Artisan Commands

### Show Stats

```bash
php artisan mailpot:stats
```

Displays a summary of:

- Total message count
- Total inbox size
- Largest and smallest message
- Latest message details

### Clean Inbox

```bash
php artisan mailpot:clean
```

Deletes all stored messages. You will be prompted to optionally delete the stats file as well.

## Testing

This package uses [Orchestra Testbench](https://github.com/orchestral/testbench).

Run tests with:

```bash
composer test
```

Or directly:

```bash
./vendor/bin/phpunit
```

## Code Quality

Run static analysis:

```bash
./vendor/bin/phpstan analyse
```

Run code style fixes:

```bash
./vendor/bin/pint
```

## Files and Storage

Messages are saved to the configured storage path (default: `storage/framework/mailpot`).

- Each email is stored as a `.json` file
- `stats.json` contains cached statistics
- `.gitignore` is auto-generated to exclude messages from Git

## Recommended .gitignore

```gitignore
/vendor
/.phpunit.result.cache
/storage/framework/mailpot/*
!/storage/framework/mailpot/.gitignore
```

## License

MIT. See [LICENSE.md](LICENSE.md)

## Contributing

Feel free to submit PRs or open issues. Bug fixes, ideas, and improvements are welcome.

## Credits

Created by [Rulr](https://rulr.dev) for Laravel developers who want email to work locally.
