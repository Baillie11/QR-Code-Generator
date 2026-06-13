# QR Code Generator

A simple PHP app that lets a user enter a website address, preview a QR code, and download it as a PNG.

## Features

- Website URL validation in the browser
- QR size selection
- Quiet zone control
- Foreground and background color controls
- Error correction selection for better scan reliability
- Optional image upload for center badges and poster-style QR artwork
- QR design choices including classic, rounded dots, soft stamp, image badge, and image poster
- PNG download with a custom filename
- Responsive interface for desktop and mobile

## Requirements

- PHP 8.0 or newer
- Internet access in the browser so the QR image service can generate the QR code

## Run locally

From the project folder, start PHP's built-in server:

```bash
php -S localhost:8000
```

Then open `http://localhost:8000` in your browser.

## Notes

The app generates the preview from a QR image service directly in the browser, so there is no PHP download proxy to maintain.
