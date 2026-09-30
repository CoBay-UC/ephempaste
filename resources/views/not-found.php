<?php /** @var string $appName */ ?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1f2041">
    <title>Not Found · <?= htmlspecialchars($appName, ENT_QUOTES) ?></title>
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
    <section class="paste-panel text-center">
        <div class="panel-media">
            <img src="/images/header.png" alt="EphemPaste" class="panel-media-image">
        </div>
        <div class="panel-body py-5">
            <div class="error-code mb-2">404</div>
            <p class="not-found-text mb-4">This paste is invalid, unavailable, or has already expired.</p>
            <a href="/" class="btn btn-primary-action btn-lg">Create a New Paste</a>
        </div>
    </section>
</main>
</body>
</html>
