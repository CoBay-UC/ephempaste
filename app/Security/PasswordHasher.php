<?php

declare(strict_types=1);

namespace App\Security;

class PasswordHasher
{
    /**************************************************************************
     * Protected Variables
     **************************************************************************/

    /**************************************************************************
     * Public Variables
     **************************************************************************/

    /**************************************************************************
     * Magic Methods
     **************************************************************************/

    /**************************************************************************
     * Protected Methods
     **************************************************************************/

    protected function _hash(string $password, string $salt): string
    {
        return hash('sha256', $salt . $password);
    }

    /**************************************************************************
     * Public Methods
     **************************************************************************/

    public function create(string $password): array
    {
        $salt = bin2hex(random_bytes(16));

        return [
            'salt' => $salt,
            'hash' => $this->_hash($password, $salt),
        ];
    }

    public function verify(string $password, string $salt, string $expectedHash): bool
    {
        return hash_equals($expectedHash, $this->_hash($password, $salt));
    }
}
