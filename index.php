<?php
$defaultWebsite = 'https://example.com';
$defaultSize = '320';
$defaultMargin = '24';
$defaultForeground = '111111';
$defaultBackground = 'ffffff';
$defaultErrorLevel = 'M';
$defaultDesign = 'classic';
$defaultImageFit = 'badge';
$defaultCaption = 'Scan to visit';
$defaultFilename = 'qr-code';

$website = trim($_GET['website'] ?? $defaultWebsite);
$size = $_GET['size'] ?? $defaultSize;
$margin = $_GET['margin'] ?? $defaultMargin;
$foreground = preg_replace('/[^A-Fa-f0-9]/', '', $_GET['foreground'] ?? $defaultForeground);
$background = preg_replace('/[^A-Fa-f0-9]/', '', $_GET['background'] ?? $defaultBackground);
$errorLevel = strtoupper(trim($_GET['errorLevel'] ?? $defaultErrorLevel));
$design = trim($_GET['design'] ?? $defaultDesign);
$imageFit = trim($_GET['imageFit'] ?? $defaultImageFit);
$caption = trim($_GET['caption'] ?? $defaultCaption);
$filename = trim($_GET['filename'] ?? $defaultFilename);

$allowedSizes = ['240', '320', '400', '480'];
$allowedMargins = ['8', '16', '24', '32'];
$allowedErrorLevels = ['L', 'M', 'Q', 'H'];
$allowedDesigns = [
    'classic' => 'Classic square',
    'rounded' => 'Rounded dots',
    'soft' => 'Soft stamp',
    'badge' => 'Image badge',
    'poster' => 'Image poster',
];
$allowedImageFits = [
    'badge' => 'Safe square badge',
    'circle' => 'Safe circular badge',
    'none' => 'No image overlay',
];

if (!in_array($size, $allowedSizes, true)) {
    $size = $defaultSize;
}

if (!in_array($margin, $allowedMargins, true)) {
    $margin = $defaultMargin;
}

if (strlen($foreground) !== 6) {
    $foreground = $defaultForeground;
}

if (strlen($background) !== 6) {
    $background = $defaultBackground;
}

if (!in_array($errorLevel, $allowedErrorLevels, true)) {
    $errorLevel = $defaultErrorLevel;
}

if (!array_key_exists($design, $allowedDesigns)) {
    $design = $defaultDesign;
}

if (!array_key_exists($imageFit, $allowedImageFits)) {
    $imageFit = $defaultImageFit;
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function selected(string $value, string $expected): string
{
    return $value === $expected ? ' selected' : '';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Simple QR code generator built in PHP.">
    <title>QR Code Generator</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f7f3eb;
            --panel: rgba(255, 255, 255, 0.84);
            --panel-border: rgba(17, 24, 39, 0.09);
            --text: #12202f;
            --muted: #5d6775;
            --primary: #0f766e;
            --primary-dark: #115e59;
            --shadow: 0 18px 60px rgba(17, 24, 39, 0.12);
            --radius: 24px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(217, 239, 232, 0.95), transparent 33%),
                radial-gradient(circle at top right, rgba(250, 221, 200, 0.7), transparent 27%),
                linear-gradient(180deg, #fffaf1 0%, var(--bg) 100%);
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
        }

        .page {
            width: min(1140px, calc(100% - 32px));
            margin: 0 auto;
            padding: 32px 0 44px;
        }

        .hero {
            display: grid;
            gap: 18px;
            margin-bottom: 22px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(15, 118, 110, 0.1);
            color: var(--primary-dark);
            font: 700 0.8rem/1 Arial, sans-serif;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            max-width: 11ch;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(2.4rem, 5vw, 4.8rem);
            line-height: 0.95;
            letter-spacing: -0.05em;
        }

        .lead {
            margin: 0;
            max-width: 58ch;
            color: var(--muted);
            font-size: 1.05rem;
            line-height: 1.7;
        }

        .layout {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 24px;
            align-items: start;
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            backdrop-filter: blur(14px);
        }

        .form-card,
        .preview-card {
            padding: 24px;
        }

        .form-grid {
            display: grid;
            gap: 16px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font: 700 0.92rem/1.2 Arial, sans-serif;
            color: #243142;
        }

        input,
        select {
            width: 100%;
            border: 1px solid rgba(36, 49, 66, 0.16);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.92);
            color: var(--text);
            padding: 14px 16px;
            font: 400 1rem/1.4 Arial, sans-serif;
            outline: none;
        }

        input[type="file"]::file-selector-button {
            border: 0;
            background: rgba(15, 118, 110, 0.1);
            color: var(--primary-dark);
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font: 600 0.9rem Arial, sans-serif;
            margin-right: 12px;
            transition: background 160ms ease;
        }

        input[type="file"]::file-selector-button:hover {
            background: rgba(15, 118, 110, 0.2);
        }

        input[type="color"] {
            min-height: 50px;
            padding: 6px;
        }

        input:focus,
        select:focus {
            border-color: rgba(15, 118, 110, 0.5);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.12);
        }

        .two-up {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .three-up {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 2px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 999px;
            padding: 14px 18px;
            text-decoration: none;
            cursor: pointer;
            font: 700 0.96rem/1 Arial, sans-serif;
            transition: transform 160ms ease, box-shadow 160ms ease, background 160ms ease, opacity 160ms ease;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button.primary {
            background: linear-gradient(135deg, var(--primary), #0ea5a4);
            color: white;
            box-shadow: 0 10px 24px rgba(15, 118, 110, 0.24);
        }

        .button.secondary {
            background: rgba(17, 24, 39, 0.06);
            color: var(--text);
        }

        .button:disabled {
            cursor: not-allowed;
            opacity: 0.5;
            transform: none;
        }

        .hint,
        .message,
        .meta,
        .footnote {
            font: 400 0.95rem/1.6 Arial, sans-serif;
        }

        .message {
            margin: 0 0 14px;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(15, 118, 110, 0.08);
            color: #115e59;
            border: 1px solid rgba(15, 118, 110, 0.14);
        }

        .message.error {
            background: rgba(220, 38, 38, 0.08);
            color: #991b1b;
            border-color: rgba(220, 38, 38, 0.16);
        }

        .hint {
            margin: 0;
            color: var(--muted);
        }

        .preview-box {
            display: grid;
            place-items: center;
            min-height: 380px;
            border-radius: 20px;
            border: 1px dashed rgba(36, 49, 66, 0.18);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.78), rgba(245, 247, 250, 0.94));
            padding: 18px;
        }

        .preview-box canvas,
        .preview-box img {
            display: block;
            width: min(100%, 420px);
            height: auto;
            border-radius: 18px;
            background: white;
            padding: 14px;
            box-shadow: 0 10px 28px rgba(17, 24, 39, 0.08);
        }

        .preview-empty {
            max-width: 28ch;
            text-align: center;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.7;
        }

        .meta-grid {
            display: grid;
            gap: 8px;
            margin-top: 16px;
        }

        .meta {
            margin: 0;
            color: var(--muted);
        }

        .footnote {
            margin-top: 18px;
            color: var(--muted);
            font-size: 0.88rem;
        }

        .no-js {
            margin: 0 0 16px;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(249, 115, 22, 0.08);
            border: 1px solid rgba(249, 115, 22, 0.14);
            color: #9a3412;
            font: 600 0.95rem/1.5 Arial, sans-serif;
        }

        @media (max-width: 900px) {
            .layout {
                grid-template-columns: 1fr;
            }

            h1 {
                max-width: none;
            }
        }

        @media (max-width: 640px) {
            .page {
                width: min(100% - 20px, 1140px);
                padding-top: 20px;
            }

            .form-card,
            .preview-card {
                padding: 18px;
            }

            .two-up {
                grid-template-columns: 1fr;
            }

            .three-up {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
            }

            .button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="hero">
            <div class="eyebrow">PHP QR Code Generator</div>
            <h1>Turn a website into a QR code in seconds.</h1>
            <p class="lead">Enter a website address, choose the QR size and colors, then download a clean PNG that is ready to share, print, or place on a landing page.</p>
        </section>

        <section class="layout">
            <div class="card form-card">
                <p id="status" class="message" hidden></p>
                <noscript>
                    <p class="no-js">This app needs JavaScript enabled to generate and download QR codes.</p>
                </noscript>

                <form id="qr-form" method="get" action="">
                    <div class="form-grid">
                        <div>
                            <label for="website">Website address</label>
                            <input id="website" name="website" type="text" inputmode="url" autocomplete="url" placeholder="www.example.com" value="<?php echo esc($website); ?>" required>
                            <p class="hint">You can enter a full URL or a www address. The app will add https:// when needed.</p>
                        </div>

                        <div>
                            <label for="image">Overlay image (optional)</label>
                            <input id="image" name="image" type="file" accept="image/png,image/jpeg,image/jpg,image/webp" />
                            <p class="hint">Upload an image to use as a small center badge or poster artwork. Recommended: PNG, JPG, or WebP up to 4MB.</p>
                        </div>

                        <div class="two-up">
                            <div>
                                <label for="design">QR design</label>
                                <select id="design" name="design">
                                    <?php foreach ($allowedDesigns as $option => $label): ?>
                                        <option value="<?php echo esc($option); ?>"<?php echo selected($design, $option); ?>><?php echo esc($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="imageFit">Image treatment</label>
                                <select id="imageFit" name="imageFit">
                                    <?php foreach ($allowedImageFits as $option => $label): ?>
                                        <option value="<?php echo esc($option); ?>"<?php echo selected($imageFit, $option); ?>><?php echo esc($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="caption">Poster caption</label>
                            <input id="caption" name="caption" type="text" value="<?php echo esc($caption); ?>" maxlength="60" placeholder="Scan to visit">
                            <p class="hint">Used by the image poster design. Leave it as-is for normal QR downloads.</p>
                        </div>

                        <div class="two-up">
                            <div>
                                <label for="size">QR size</label>
                                <select id="size" name="size">
                                    <?php foreach ($allowedSizes as $option): ?>
                                        <option value="<?php echo esc($option); ?>"<?php echo selected($size, $option); ?>><?php echo esc($option); ?> px</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="margin">Quiet zone</label>
                                <select id="margin" name="margin">
                                    <?php foreach ($allowedMargins as $option): ?>
                                        <option value="<?php echo esc($option); ?>"<?php echo selected($margin, $option); ?>><?php echo esc($option); ?> px</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="two-up">
                            <div>
                                <label for="foreground">Foreground color</label>
                                <input id="foreground" name="foreground" type="text" value="<?php echo esc($foreground); ?>" maxlength="6" pattern="[A-Fa-f0-9]{6}" placeholder="111111">
                            </div>

                            <div>
                                <label for="background">Background color</label>
                                <input id="background" name="background" type="text" value="<?php echo esc($background); ?>" maxlength="6" pattern="[A-Fa-f0-9]{6}" placeholder="ffffff">
                            </div>
                        </div>

                        <div class="two-up">
                            <div>
                                <label for="errorLevel">Error correction</label>
                                <select id="errorLevel" name="errorLevel">
                                    <?php foreach ($allowedErrorLevels as $option): ?>
                                        <option value="<?php echo esc($option); ?>"<?php echo selected($errorLevel, $option); ?>><?php echo esc($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="filename">Download filename</label>
                                <input id="filename" name="filename" type="text" value="<?php echo esc($filename); ?>" placeholder="qr-code">
                            </div>
                        </div>

                        <p class="hint">Use six-digit hex values without a leading #, for example 0f766e or ffffff. Image overlays are rendered with high error correction automatically to protect scan reliability.</p>

                        <div class="actions">
                            <button class="button primary" type="submit">Generate QR code</button>
                            <a id="download-btn" class="button secondary" href="#" download="qr-code.png" aria-disabled="true">Download PNG</a>
                        </div>
                    </div>
                </form>
            </div>

            <aside class="card preview-card">
                <div class="preview-box">
                    <div id="qr-placeholder" class="preview-empty">Your QR preview will appear here after you generate it.</div>
                    <canvas id="qr-canvas" hidden width="380" height="380"></canvas>
                </div>

                <div class="meta-grid">
                    <p class="meta">Validated website input, custom colors, quiet zone control, selectable error correction, uploaded image badges, and poster-style QR artwork.</p>
                    <p class="meta">The PNG download is composed locally in the browser from your selected settings.</p>
                </div>
            </aside>
        </section>

        <p class="footnote">Tip: if you want the code to scan reliably, keep the foreground dark and the background light.</p>
    </main>

    <script>
        (() => {
            const form = document.getElementById('qr-form');
            const status = document.getElementById('status');
            const placeholder = document.getElementById('qr-placeholder');
            const canvas = document.getElementById('qr-canvas');
            const ctx = canvas.getContext('2d');
            const downloadButton = document.getElementById('download-btn');

            const websiteInput = document.getElementById('website');
            const imageInput = document.getElementById('image');
            const sizeInput = document.getElementById('size');
            const marginInput = document.getElementById('margin');
            const foregroundInput = document.getElementById('foreground');
            const backgroundInput = document.getElementById('background');
            const errorLevelInput = document.getElementById('errorLevel');
            const designInput = document.getElementById('design');
            const imageFitInput = document.getElementById('imageFit');
            const captionInput = document.getElementById('caption');
            const filenameInput = document.getElementById('filename');

            let overlayImage = null;

            function showMessage(message, isError = false) {
                status.textContent = message;
                status.hidden = false;
                status.classList.toggle('error', isError);
            }

            function clearMessage() {
                status.hidden = true;
                status.textContent = '';
                status.classList.remove('error');
            }

            function normalizeHex(value, fallback) {
                const cleaned = String(value || '').replace(/[^a-fA-F0-9]/g, '').slice(0, 6);
                return cleaned.length === 6 ? cleaned.toLowerCase() : fallback;
            }

            function sanitizeFilename(value) {
                const cleaned = String(value || 'qr-code').trim().replace(/[^A-Za-z0-9._-]+/g, '-').replace(/^-+|-+$/g, '');
                return cleaned || 'qr-code';
            }

            function parseUrl(value) {
                const trimmed = String(value || '').trim();
                if (!trimmed) {
                    throw new Error('Enter a website address before generating a QR code.');
                }

                if (/^[a-z][a-z0-9+.-]*:/i.test(trimmed) && !/^https?:\/\//i.test(trimmed)) {
                    throw new Error('Use an http or https website address.');
                }

                const normalized = /^[a-z][a-z0-9+.-]*:\/\//i.test(trimmed)
                    ? trimmed
                    : `https://${trimmed}`;

                const parsed = new URL(normalized);
                if (!['http:', 'https:'].includes(parsed.protocol)) {
                    throw new Error('Use an http or https website address.');
                }

                return parsed.toString();
            }

            function clearOutput() {
                canvas.hidden = true;
                placeholder.hidden = false;
                downloadButton.href = '#';
                downloadButton.download = 'qr-code.png';
                downloadButton.setAttribute('aria-disabled', 'true');
            }

            function buildServiceUrl(website, size, margin, foreground, background, errorLevel) {
                const params = new URLSearchParams({
                    data: website,
                    size: `${size}x${size}`,
                    margin: String(margin),
                    color: foreground,
                    bgcolor: background,
                    ecc: errorLevel,
                });

                return `https://api.qrserver.com/v1/create-qr-code/?${params.toString()}`;
            }

            function hexToRgb(hex) {
                const value = normalizeHex(hex, '111111');
                return {
                    r: parseInt(value.slice(0, 2), 16),
                    g: parseInt(value.slice(2, 4), 16),
                    b: parseInt(value.slice(4, 6), 16),
                };
            }

            function rgba(hex, alpha) {
                const color = hexToRgb(hex);
                return `rgba(${color.r}, ${color.g}, ${color.b}, ${alpha})`;
            }

            function roundedRect(targetCtx, x, y, width, height, radius) {
                const safeRadius = Math.min(radius, width / 2, height / 2);
                targetCtx.beginPath();
                targetCtx.moveTo(x + safeRadius, y);
                targetCtx.lineTo(x + width - safeRadius, y);
                targetCtx.quadraticCurveTo(x + width, y, x + width, y + safeRadius);
                targetCtx.lineTo(x + width, y + height - safeRadius);
                targetCtx.quadraticCurveTo(x + width, y + height, x + width - safeRadius, y + height);
                targetCtx.lineTo(x + safeRadius, y + height);
                targetCtx.quadraticCurveTo(x, y + height, x, y + height - safeRadius);
                targetCtx.lineTo(x, y + safeRadius);
                targetCtx.quadraticCurveTo(x, y, x + safeRadius, y);
                targetCtx.closePath();
            }

            function fillRoundedRect(targetCtx, x, y, width, height, radius, fillStyle) {
                targetCtx.fillStyle = fillStyle;
                roundedRect(targetCtx, x, y, width, height, radius);
                targetCtx.fill();
            }

            function drawCoverImage(targetCtx, image, x, y, width, height) {
                const sourceRatio = image.width / image.height;
                const targetRatio = width / height;
                let sourceWidth = image.width;
                let sourceHeight = image.height;
                let sourceX = 0;
                let sourceY = 0;

                if (sourceRatio > targetRatio) {
                    sourceWidth = image.height * targetRatio;
                    sourceX = (image.width - sourceWidth) / 2;
                } else {
                    sourceHeight = image.width / targetRatio;
                    sourceY = (image.height - sourceHeight) / 2;
                }

                targetCtx.drawImage(image, sourceX, sourceY, sourceWidth, sourceHeight, x, y, width, height);
            }

            function drawCenteredText(targetCtx, text, x, y, width, font, color) {
                targetCtx.font = font;
                targetCtx.fillStyle = color;
                targetCtx.textAlign = 'center';
                targetCtx.textBaseline = 'middle';

                let safeText = String(text || '').trim();
                while (safeText.length > 6 && targetCtx.measureText(safeText).width > width) {
                    safeText = safeText.slice(0, -1).trim();
                }

                if (safeText !== String(text || '').trim()) {
                    safeText = `${safeText.slice(0, -1)}...`;
                }

                targetCtx.fillText(safeText || 'Scan to visit', x + width / 2, y);
            }

            function sourceIsDark(data, width, height, x, y) {
                const sampleX = Math.max(0, Math.min(width - 1, Math.floor(x)));
                const sampleY = Math.max(0, Math.min(height - 1, Math.floor(y)));
                const index = (sampleY * width + sampleX) * 4;
                const red = data[index];
                const green = data[index + 1];
                const blue = data[index + 2];
                const alpha = data[index + 3];
                return alpha > 10 && ((red + green + blue) / 3) < 180;
            }

            function getQrData(qrImage, size) {
                const sourceCanvas = document.createElement('canvas');
                sourceCanvas.width = size;
                sourceCanvas.height = size;
                const sourceCtx = sourceCanvas.getContext('2d');
                sourceCtx.drawImage(qrImage, 0, 0, size, size);
                return sourceCtx.getImageData(0, 0, size, size).data;
            }

            function drawStylizedQr(targetCtx, qrImage, x, y, size, design, foreground, background) {
                fillRoundedRect(targetCtx, x, y, size, size, design === 'classic' ? 0 : 22, `#${background}`);

                if (design === 'classic') {
                    targetCtx.drawImage(qrImage, x, y, size, size);
                    return;
                }

                const sourceSize = 480;
                const data = getQrData(qrImage, sourceSize);
                const cell = design === 'rounded' ? 7 : 8;
                const scale = size / sourceSize;
                targetCtx.fillStyle = `#${foreground}`;

                for (let sourceY = 0; sourceY < sourceSize; sourceY += cell) {
                    for (let sourceX = 0; sourceX < sourceSize; sourceX += cell) {
                        const centerX = sourceX + cell / 2;
                        const centerY = sourceY + cell / 2;
                        const dark = sourceIsDark(data, sourceSize, sourceSize, centerX, centerY)
                            || sourceIsDark(data, sourceSize, sourceSize, sourceX + 1, sourceY + 1)
                            || sourceIsDark(data, sourceSize, sourceSize, sourceX + cell - 2, sourceY + cell - 2);

                        if (!dark) {
                            continue;
                        }

                        const drawX = x + sourceX * scale;
                        const drawY = y + sourceY * scale;
                        const drawSize = Math.ceil(cell * scale);

                        if (design === 'rounded' || design === 'badge') {
                            targetCtx.beginPath();
                            targetCtx.arc(drawX + drawSize / 2, drawY + drawSize / 2, drawSize * 0.43, 0, Math.PI * 2);
                            targetCtx.fill();
                        } else {
                            fillRoundedRect(targetCtx, drawX, drawY, drawSize * 0.96, drawSize * 0.96, drawSize * 0.28, `#${foreground}`);
                            targetCtx.fillStyle = rgba(foreground, 0.18);
                            targetCtx.beginPath();
                            targetCtx.arc(drawX + drawSize * 0.35, drawY + drawSize * 0.35, drawSize * 0.18, 0, Math.PI * 2);
                            targetCtx.fill();
                            targetCtx.fillStyle = `#${foreground}`;
                        }
                    }
                }
            }

            function drawImageBadge(targetCtx, image, centerX, centerY, size, mode, foreground) {
                if (!image || mode === 'none') {
                    return;
                }

                const padding = Math.max(8, size * 0.08);
                const x = centerX - size / 2;
                const y = centerY - size / 2;

                targetCtx.save();
                if (mode === 'circle') {
                    targetCtx.beginPath();
                    targetCtx.arc(centerX, centerY, size / 2 + padding, 0, Math.PI * 2);
                    targetCtx.fillStyle = 'white';
                    targetCtx.fill();
                    targetCtx.strokeStyle = rgba(foreground, 0.22);
                    targetCtx.lineWidth = Math.max(3, size * 0.04);
                    targetCtx.stroke();
                    targetCtx.beginPath();
                    targetCtx.arc(centerX, centerY, size / 2, 0, Math.PI * 2);
                    targetCtx.clip();
                } else {
                    fillRoundedRect(targetCtx, x - padding, y - padding, size + padding * 2, size + padding * 2, size * 0.18, 'white');
                    targetCtx.strokeStyle = rgba(foreground, 0.18);
                    targetCtx.lineWidth = Math.max(2, size * 0.025);
                    roundedRect(targetCtx, x - padding, y - padding, size + padding * 2, size + padding * 2, size * 0.18);
                    targetCtx.stroke();
                    roundedRect(targetCtx, x, y, size, size, size * 0.14);
                    targetCtx.clip();
                }

                drawCoverImage(targetCtx, image, x, y, size, size);
                targetCtx.restore();
            }

            function drawPoster(qrImage, options) {
                const posterSize = 900;
                canvas.width = posterSize;
                canvas.height = posterSize;

                const foreground = options.foreground;
                const background = options.background;
                const title = options.caption || 'Scan to visit';
                const host = new URL(options.url).host.replace(/^www\./, '');

                ctx.fillStyle = '#fffaf1';
                ctx.fillRect(0, 0, posterSize, posterSize);
                ctx.fillStyle = rgba(foreground, 0.08);
                ctx.beginPath();
                ctx.arc(150, 130, 160, 0, Math.PI * 2);
                ctx.arc(770, 780, 190, 0, Math.PI * 2);
                ctx.fill();

                if (overlayImage) {
                    ctx.save();
                    roundedRect(ctx, 245, 22, 410, 210, 60);
                    ctx.clip();
                    drawCoverImage(ctx, overlayImage, 245, 22, 410, 210);
                    ctx.restore();
                } else {
                    fillRoundedRect(ctx, 300, 56, 300, 126, 52, rgba(foreground, 0.14));
                    drawCenteredText(ctx, 'Upload image', 300, 119, 300, '700 30px Arial, sans-serif', `#${foreground}`);
                }

                fillRoundedRect(ctx, 92, 186, 716, 590, 38, '#8b5e3c');
                fillRoundedRect(ctx, 122, 216, 656, 530, 30, `#${background}`);
                ctx.strokeStyle = rgba(foreground, 0.2);
                ctx.lineWidth = 5;
                roundedRect(ctx, 122, 216, 656, 530, 30);
                ctx.stroke();

                drawStylizedQr(ctx, qrImage, 164, 246, 572, 'soft', foreground, background);
                drawImageBadge(ctx, overlayImage, 450, 532, 104, 'circle', foreground);

                fillRoundedRect(ctx, 114, 782, 672, 76, 28, '#f4a090');
                ctx.fillStyle = '#7a3e2f';
                ctx.font = '700 24px Arial, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(title.toUpperCase(), 450, 808);
                drawCenteredText(ctx, host, 148, 838, 604, '700 42px Georgia, "Times New Roman", serif', '#6b3327');
            }

            function composeArtwork(qrImage, options) {
                if (options.design === 'poster') {
                    drawPoster(qrImage, options);
                    return;
                }

                const size = options.size;
                canvas.width = size;
                canvas.height = size;

                const visualDesign = options.design === 'badge' ? 'rounded' : options.design;
                drawStylizedQr(ctx, qrImage, 0, 0, size, visualDesign, options.foreground, options.background);

                const shouldDrawBadge = overlayImage && options.imageFit !== 'none' && ['classic', 'rounded', 'soft', 'badge'].includes(options.design);
                if (shouldDrawBadge) {
                    const badgeSize = size * (options.design === 'badge' ? 0.18 : 0.16);
                    const badgeMode = options.design === 'badge' ? 'circle' : options.imageFit;
                    drawImageBadge(ctx, overlayImage, size / 2, size / 2, badgeSize, badgeMode, options.foreground);
                }
            }

            function shouldReserveQrDataForImage(design, imageFit) {
                return overlayImage && imageFit !== 'none' && ['classic', 'rounded', 'soft', 'badge', 'poster'].includes(design);
            }

            function renderQr() {
                clearMessage();

                let url;
                try {
                    url = parseUrl(websiteInput.value);
                } catch (error) {
                    clearOutput();
                    showMessage(error.message, true);
                    websiteInput.focus();
                    return;
                }

                const size = Number.parseInt(sizeInput.value, 10);
                const margin = Number.parseInt(marginInput.value, 10);
                const foreground = normalizeHex(foregroundInput.value, '111111');
                const background = normalizeHex(backgroundInput.value, 'ffffff');
                const filename = sanitizeFilename(filenameInput.value);
                const design = designInput.value;
                const imageFit = imageFitInput.value;
                const errorLevel = shouldReserveQrDataForImage(design, imageFit) ? 'H' : errorLevelInput.value;
                const caption = String(captionInput.value || '').trim();
                const qrSize = design === 'poster' ? 480 : size;
                const qrUrl = buildServiceUrl(url, qrSize, margin, foreground, background, errorLevel);

                const qrImage = new Image();
                qrImage.crossOrigin = 'anonymous';
                qrImage.onload = () => {
                    try {
                        composeArtwork(qrImage, {
                            url,
                            size,
                            margin,
                            foreground,
                            background,
                            errorLevel,
                            design,
                            imageFit,
                            caption,
                        });

                        placeholder.hidden = true;
                        canvas.hidden = false;

                        downloadButton.href = '#';
                        downloadButton.download = `${filename}.png`;
                        downloadButton.setAttribute('aria-disabled', 'false');

                        const message = design === 'poster'
                            ? 'Your poster-style QR artwork is ready. Download the PNG when you are happy with the settings.'
                            : overlayImage
                                ? 'Your QR code with uploaded image is ready. Download the PNG when you are happy with the settings.'
                                : 'Your QR code is ready. Download the PNG when you are happy with the settings.';
                        showMessage(message);
                    } catch (error) {
                        clearOutput();
                        showMessage('Error composing QR artwork: ' + error.message, true);
                    }
                };

                qrImage.onerror = () => {
                    clearOutput();
                    showMessage('The QR image service could not generate the code right now. Try again in a moment.', true);
                };

                qrImage.src = qrUrl;
            }

            function handleImageUpload(event) {
                const file = event.target.files[0];

                if (!file) {
                    overlayImage = null;
                    clearMessage();
                    return;
                }

                if (file.size > 4 * 1024 * 1024) {
                    showMessage('Image file is too large. Please use an image under 4MB.', true);
                    imageInput.value = '';
                    overlayImage = null;
                    return;
                }

                const reader = new FileReader();

                reader.onload = (e) => {
                    const img = new Image();
                    img.onload = () => {
                        overlayImage = img;
                        clearMessage();
                        renderQr();
                    };

                    img.onerror = () => {
                        showMessage('Could not load the image. Please check the file format.', true);
                        imageInput.value = '';
                        overlayImage = null;
                    };

                    img.src = e.target.result;
                };

                reader.onerror = () => {
                    showMessage('Could not read the file. Please try again.', true);
                    imageInput.value = '';
                    overlayImage = null;
                };

                reader.readAsDataURL(file);
            }

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                renderQr();
            });

            imageInput.addEventListener('change', handleImageUpload);

            [designInput, imageFitInput, sizeInput, marginInput, errorLevelInput].forEach((control) => {
                control.addEventListener('change', renderQr);
            });

            downloadButton.addEventListener('click', (event) => {
                if (downloadButton.getAttribute('aria-disabled') === 'true') {
                    event.preventDefault();
                    return;
                }

                // Prevent default link behavior and handle download manually
                event.preventDefault();

                const filename = sanitizeFilename(filenameInput.value) + '.png';
                canvas.toBlob((blob) => {
                    if (!blob) {
                        showMessage('The browser could not prepare the PNG download. Generate the QR code again and retry.', true);
                        return;
                    }

                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                });
            });

            websiteInput.value = websiteInput.value || 'https://example.com';
            renderQr();
        })();
    </script>
</body>
</html>
