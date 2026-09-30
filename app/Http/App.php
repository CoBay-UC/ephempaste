<?php

declare(strict_types=1);

namespace App\Http;

use App\Config\AppConfig;
use App\Security\Csrf;
use App\Services\PasteService;
use App\Support\View;

class App
{
    /**************************************************************************
     * Protected Variables
     **************************************************************************/

    protected AppConfig $_config;
    protected PasteService $_pasteService;
    protected Csrf $_csrf;
    protected View $_view;

    /**************************************************************************
     * Magic Methods
     **************************************************************************/

    public function __construct(
        AppConfig $config,
        PasteService $pasteService,
        Csrf $csrf,
        View $view
    ) {
        $this->_config = $config;
        $this->_pasteService = $pasteService;
        $this->_csrf = $csrf;
        $this->_view = $view;
    }

    /**************************************************************************
     * Protected Methods
     **************************************************************************/

    protected function _requestPath(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        return rawurldecode(trim($path, '/'));
    }

    protected function _isPost(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    protected function _redirect(string $location): never
    {
        header('Location: ' . $location, true, 303);
        exit;
    }

    protected function _jsonResponse(array $payload, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function _renderHome(
        ?string $error = null,
        ?string $createdUrl = null,
        string $content = ''
    ): void {
        $this->_view->render('home', [
            'appName' => $this->_config->get('appName'),
            'csrfToken' => $this->_csrf->token(),
            'maxLength' => $this->_config->get('pasteMaxLength'),
            'lifetimeHours' => $this->_config->get('pasteLifetimeHours'),
            'error' => $error,
            'createdUrl' => $createdUrl,
            'content' => $content,
        ]);
    }

    protected function _handleCreate(): void
    {
        if (!$this->_csrf->validate($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            $this->_renderHome('Your session token expired. Please try again.');
            return;
        }

        $content = (string) ($_POST['content'] ?? '');

        try {
            $code = $this->_pasteService->createPaste(
                $content,
                (string) ($_POST['password'] ?? '')
            );

            $_SESSION['created_url'] = $this->_config->get('appBaseUrl') . '/' . rawurlencode($code);
            $this->_redirect('/');
        } catch (\RuntimeException $exception) {
            // RuntimeException is used by the service for safe, user-facing
            // validation messages. Unexpected exceptions bubble to the global
            // handler where they are logged instead of exposed to the browser.
            $this->_renderHome($exception->getMessage(), null, $content);
        }
    }

    protected function _handleApiCreate(): never
    {
        if (!$this->_csrf->validate($_POST['_csrf'] ?? null)) {
            $this->_jsonResponse([
                'success' => false,
                'error' => 'Your session token expired. Please refresh the page and try again.',
            ], 419);
        }

        try {
            $code = $this->_pasteService->createPaste(
                (string) ($_POST['content'] ?? ''),
                (string) ($_POST['password'] ?? '')
            );

            $createdUrl = $this->_config->get('appBaseUrl') . '/' . rawurlencode($code);

            $this->_jsonResponse([
                'success' => true,
                'code' => $code,
                'url' => $createdUrl,
                'expiresInHours' => $this->_config->get('pasteLifetimeHours'),
            ]);
        } catch (\RuntimeException $exception) {
            $this->_jsonResponse([
                'success' => false,
                'error' => $exception->getMessage(),
            ], 422);
        }
    }

    protected function _handlePaste(string $code): void
    {
        $expectedLength = $this->_config->get('pasteCodeLength');

        if (strlen($code) !== $expectedLength) {
            $this->_renderNotFound();
            return;
        }

        $paste = $this->_pasteService->getPaste($code);

        if ($paste === null) {
            $this->_renderNotFound();
            return;
        }

        $protected = $this->_pasteService->isPasswordProtected($paste);
        $error = null;
        $authorized = !$protected;

        if ($protected && $this->_isPost()) {
            if (!$this->_csrf->validate($_POST['_csrf'] ?? null)) {
                $error = 'Your session token expired. Please try again.';
            } elseif ($this->_pasteService->verifyPassword($paste, (string) ($_POST['password'] ?? ''))) {
                $authorized = true;
            } else {
                $error = 'Incorrect password.';
            }
        }

        $this->_view->render('paste', [
            'appName' => $this->_config->get('appName'),
            'paste' => $paste,
            'protected' => $protected,
            'authorized' => $authorized,
            'error' => $error,
            'csrfToken' => $this->_csrf->token(),
        ]);
    }

    protected function _renderNotFound(): void
    {
        http_response_code(404);
        $this->_view->render('not-found', [
            'appName' => $this->_config->get('appName'),
        ]);
    }

    /**************************************************************************
     * Public Methods
     **************************************************************************/

    public function run(): void
    {
        $path = $this->_requestPath();

        if ($path === 'api/pastes') {
            if (!$this->_isPost()) {
                $this->_jsonResponse([
                    'success' => false,
                    'error' => 'Method not allowed.',
                ], 405);
            }

            $this->_handleApiCreate();
            return;
        }

        if ($path === '') {
            if ($this->_isPost()) {
                $this->_handleCreate();
                return;
            }

            $createdUrl = $_SESSION['created_url'] ?? null;
            unset($_SESSION['created_url']);
            $this->_renderHome(null, is_string($createdUrl) ? $createdUrl : null);
            return;
        }

        $this->_handlePaste($path);
    }
}
