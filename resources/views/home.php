<?php
/** @var string $appName */
/** @var string $csrfToken */
/** @var int $maxLength */
/** @var int $lifetimeHours */
/** @var ?string $error */
/** @var ?string $createdUrl */
/** @var string $content */
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1f2041">
    <title><?= htmlspecialchars($appName, ENT_QUOTES) ?></title>
    <link rel="icon" href="/images/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/app.css?v=20260929-2" rel="stylesheet">
</head>
<body>
<main class="page-shell d-flex align-items-center justify-content-center p-3 p-md-4">
    <section class="paste-panel">
        <div class="panel-media">
            <img src="/images/header.png" alt="EphemPaste" class="panel-media-image">
        </div>

        <div class="panel-body">
            <div id="form-state" class="panel-state<?= $createdUrl !== null ? ' d-none' : '' ?>">
                <div id="form-error" class="<?= $error === null ? 'd-none' : '' ?> mb-3">
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars((string) $error, ENT_QUOTES) ?></div>
                </div>

                <form id="paste-form" method="post" action="/" data-create-endpoint="/api/pastes" novalidate>
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">

                    <div class="mb-3">
                        <textarea
                            id="content"
                            name="content"
                            class="form-control paste-textarea"
                            maxlength="<?= (int) $maxLength ?>"
                            placeholder="Paste max <?= (int) $maxLength ?> characters"
                            aria-label="Paste content"
                            required
                        ><?= htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                        <div class="textarea-meta d-flex justify-content-end mt-2">
                            <span id="character-count" class="character-count">0 / <?= (int) $maxLength ?></span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            class="form-control password-input"
                            autocomplete="new-password"
                            placeholder="Enter Password to Secure"
                            aria-label="Optional password"
                        >
                        <div class="form-text mt-2">Optional — leave blank for a link without password protection.</div>
                    </div>

                    <button id="save-button" type="submit" class="btn btn-primary-action btn-lg w-100">Save &amp; Generate Link</button>
                </form>
            </div>

            <div id="success-state" class="panel-state success-state<?= $createdUrl === null ? ' d-none' : '' ?>">
                <div class="success-card">
                    <div class="input-group success-link-group">
                        <input
                            id="generated-url"
                            type="text"
                            class="form-control"
                            value="<?= htmlspecialchars((string) $createdUrl, ENT_QUOTES) ?>"
                            aria-label="Shareable link"
                            readonly
                        >
                        <button class="btn btn-copy" type="button" data-copy-target="#generated-url">Copy</button>
                    </div>
                    <p class="retention-note mb-0 mt-3">Expires automatically after <?= (int) $lifetimeHours ?> hours.</p>
                </div>

                <div class="d-grid mt-4">
                    <a id="create-another-button" href="/" class="btn btn-outline-action btn-lg">Create Another</a>
                </div>
            </div>
        </div>
    </section>
</main>

<script src="/assets/js/app.js?v=20260929-2"></script>
</body>
</html>
