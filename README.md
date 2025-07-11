# Mailpot

A local mail inbox for Laravel — no external tools or services required.  
Mailpot lets you intercept, store, and inspect emails sent during development.

Think of it like [Mailtrap](https://mailtrap.io/) or [Mailhog](https://github.com/mailhog/MailHog), but **fully embedded** in your Laravel app. No Docker, no SMTP config, no fuss.

---

## Features

- Stores sent mails as `.json` files in `storage/framework/mailpot`
- Clean inbox UI built with Tailwind + Alpine.js
- Stats: count, size, latest email
- Artisan commands for cleaning inbox and viewing stats
- No third-party tools or services
- Compatible with Laravel 10–12

---

## Installation

```bash
composer require --dev rulr/laravel-mailpot
```

> This package is intended for **local development only**.  
> You may want to wrap it with `app()->environment('local')` when registering routes manually.

---

## Configuration

Update your `config/mail.php` file to add a custom mailer:

```php
'mailers' => [
    'mailpot' => [
        'transport' => 'mailpot',
    ],
    // ...
],
```

And in your `.env` file:

```env
MAIL_MAILER=mailpot
```

---

## Web UI

Visit:

```
http://localhost:8000/mailpot
```

You’ll see a simple, responsive interface to browse and read emails.  
Emails are displayed with subject, from, to, date, and content preview.

If no email is selected, you'll see general inbox statistics.

---

## Artisan Commands

### Clean inbox

```bash
php artisan mailpot:clean
```

Deletes all stored messages.  
Prompts to also delete `stats.json`.

---

### Show stats

```bash
php artisan mailpot:stats
```

Displays a summary of:

- total message count
- total inbox size
- largest/smallest message
- latest message details

---

## Testing

This package uses [Orchestra Testbench](https://github.com/orchestral/testbench).

Run tests with:

```bash
composer test
```

Or directly via:

```bash
./vendor/bin/phpunit
```

---

## Files & Storage

Messages are saved to:

```
storage/framework/mailpot/*.json
```

- `stats.json`: stored stats for quick access
- `.gitignore`: auto-generated to exclude messages from Git

---

## Recommended `.gitignore`

```gitignore
/vendor
/.phpunit.result.cache
/storage/framework/mailpot/*
!/storage/framework/mailpot/.gitignore
```

---

## License

MIT — see [LICENSE.md](LICENSE.md)

---

## Contributing

Feel free to submit PRs or open issues.  
Bug fixes, ideas, and improvements are all welcome.

---

## Credits

Created with ❤️ by [Rulr](https://rulr.dev)  
For Laravel developers who just want email to work — locally.
