<?php
/** @var string $appName */
/** @var array $paste */
/** @var bool $protected */
/** @var bool $authorized */
/** @var ?string $error */
/** @var string $csrfToken */
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
            <?php if (!$authorized): ?>
                <div class="lock-card">
                    <p class="protected-note mb-3">This paste is password protected.</p>

                    <?php if ($error !== null): ?>
                        <div class="alert alert-danger mb-3" role="alert"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            class="form-control password-input mb-3"
                            placeholder="Enter Password"
                            aria-label="Password"
                            autofocus
                            required
                        >
                        <button class="btn btn-primary-action btn-lg w-100" type="submit">Unlock</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="paste-actions d-flex justify-content-end gap-2 mb-3">
                    <button class="btn btn-primary-action" type="button" data-copy-text="#paste-output">Copy Text</button>
                    <a class="btn btn-outline-action" href="/">Create Another</a>
                </div>

                <div class="paste-output" id="paste-output"><?= htmlspecialchars((string) $paste['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
    </section>
</main>

<script src="/assets/js/app.js?v=20260929-2"></script>
</body>
</html>
