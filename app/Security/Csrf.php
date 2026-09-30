<?php

declare(strict_types=1);

namespace App\Security;

class Csrf
{
    /**************************************************************************
     * Protected Variables
     **************************************************************************/

    protected string $_sessionKey = '_csrf_token';

    /**************************************************************************
     * Public Variables
     **************************************************************************/

    /**************************************************************************
     * Magic Methods
     **************************************************************************/

    /**************************************************************************
     * Protected Methods
     **************************************************************************/

    /**************************************************************************
     * Public Methods
     **************************************************************************/

    public function token(): string
    {
        if (empty($_SESSION[$this->_sessionKey])) {
            $_SESSION[$this->_sessionKey] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[$this->_sessionKey];
    }

    public function validate(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION[$this->_sessionKey])
            && hash_equals((string) $_SESSION[$this->_sessionKey], $token);
    }
}
