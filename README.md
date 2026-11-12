# QR Code Generator

A simple PHP app that lets a user enter a website address, preview a QR code, and download it as a PNG.

## Features

- Website URL validation in the browser
- Optional discount code with a configurable URL parameter (defaults to `discount`)
- QR size selection
- Quiet zone control
- Foreground and background color controls
- Error correction selection for better scan reliability
- Optional image upload for center badges and poster-style QR artwork
- QR design choices including classic, rounded dots, soft stamp, image badge, and image poster
- PNG download with a custom filename
- Free downloads unlocked by a contact popup (name, business name, email, and prefilled website URL)
- Contact details validated on the server and saved in a private SQLite database
- Browser display-capture API disabled via Permissions-Policy
- Responsive interface for desktop and mobile

## Requirements

- PHP 8.0 or newer
- PHP PDO SQLite and session extensions
- Internet access in the browser so the QR image service can generate the QR code

## Run locally

From the project folder, start PHP's built-in server:

```bash
php -S localhost:8000
```

Then open `http://localhost:8000` in your browser.

## Notes

Discount codes are added to the QR destination URL, for example `https://example.com/?discount=SAVE10`. Set the parameter name to whatever the destination website supports. Existing query parameters and URL fragments are retained; a supplied code replaces an existing value for the selected parameter. Leaving the code empty keeps the website URL unchanged. The destination website is responsible for recognising and applying the discount.

The app generates the preview from a QR image service directly in the browser, so there is no PHP download proxy to maintain.

## Contact collection

Clicking Download PNG opens a required contact form. The website field is prefilled from the generated QR's original website input (before adding a discount code) and remains editable. Details are posted to PHP and saved successfully before the PNG download begins. Cancelling saves nothing; failures keep the form open for retry. Retrying the same submission does not create duplicate records.

By default, contacts are stored in `../qr-code-generator-private/contacts.sqlite`, outside this project's public directory. To choose another location, set the server environment variable `QR_CONTACTS_DIR` to an absolute private directory outside the server's document root. PHP needs write access to that directory. Storage inside the document root is rejected. Deploy over HTTPS and keep the database and its backups private. No contact data is committed to this repository or sent to the QR image service.

The SQLite `contacts` table contains `name`, `business`, `email`, `website`, and `created_at` (UTC), plus internal IDs. The site owner can open the database with a SQLite client to review or export contacts; there is intentionally no public contact-list endpoint. The popup explains that details are stored to identify users of the generator; it does not subscribe users to marketing.

`Permissions-Policy: display-capture=()` disables requests to the browser's screen-sharing API from this page where supported. Websites cannot disable operating-system screenshots or recording by other applications. Since the QR preview is visible in the browser, the download form is not a way to prevent determined visitors from copying it.

## Click Marketing Agent integration

On shared hosting without environment-variable configuration, create `storage-config.php` beside the app with `<?php return '/home/YOUR_ACCOUNT/qr-code-generator-private';`. This deployment-specific file is ignored by Git. It applies to both web requests and the CLI retry command; keep the selected directory outside the public document root.

Contacts are sent server-to-server when the download form is submitted, after local storage succeeds. Partial typing and cancelled forms are never sent. The receiver saves name, business name, email and website in the selected business workspace, with source `qr-code-generator`. It matches emails case-insensitively, fills missing details without overwriting existing details, and respects suppressed/deleted contacts. This does not send emails or enrol contacts in campaigns.

Deploy Click Marketing Agent's QR import route and website-column migration first (`npm run db:deploy`, then rebuild/restart its API and web app). In its project directory, run:

```text
node scripts/connect-qr-generator.mjs BUSINESS_UUID ABSOLUTE_PRIVATE_DIRECTORY https://your-marketing-agent.example
```

Use the same private directory as `QR_CONTACTS_DIR` (default: the sibling `qr-code-generator-private` directory). The setup script creates a revocable business-scoped integration token and writes `click-marketing.json` privately without printing the token. Do not place this file in the public project or commit it. Alternatively, configure `CLICK_MARKETING_URL`, `CLICK_MARKETING_BUSINESS_ID` and `CLICK_MARKETING_TOKEN` as server environment variables. HTTPS is required except on localhost. PHP needs `allow_url_fopen` and OpenSSL for HTTPS.

If the CRM is unavailable, the free download still works and the contact remains pending in SQLite. Configure your hosting scheduler to run `php /absolute/path/to/sync-contacts.php` every five minutes, with the same environment/private directory as the web server. Each run retries up to 20 pending contacts. Run it manually to drain a backlog. The private `contact_sync` table records acknowledgements; retrying is safe if a response was lost. Unconfigured integration reports `not-configured` from the CLI and retains contacts locally until configured. No scheduler is installed automatically.
