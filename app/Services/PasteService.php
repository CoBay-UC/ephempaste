<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\AppConfig;
use App\Repositories\PasteRepository;
use App\Security\PasswordHasher;
use RuntimeException;

class PasteService
{
    /**************************************************************************
     * Protected Variables
     **************************************************************************/

    protected AppConfig $_config;
    protected PasteRepository $_repository;
    protected PasswordHasher $_passwordHasher;

    /**************************************************************************
     * Public Variables
     **************************************************************************/

    /**************************************************************************
     * Magic Methods
     **************************************************************************/

    public function __construct(
        AppConfig $config,
        PasteRepository $repository,
        PasswordHasher $passwordHasher
    ) {
        $this->_config = $config;
        $this->_repository = $repository;
        $this->_passwordHasher = $passwordHasher;
    }

    /**************************************************************************
     * Protected Methods
     **************************************************************************/

    protected function _generateCode(): string
    {
        $charset = $this->_config->get('pasteCodeCharset');
        $length = $this->_config->get('pasteCodeLength');
        $maxIndex = strlen($charset) - 1;
        $code = '';

        for ($index = 0; $index < $length; $index++) {
            $code .= $charset[random_int(0, $maxIndex)];
        }

        return $code;
    }

    /**************************************************************************
     * Public Methods
     **************************************************************************/

    public function createPaste(string $content, string $password): string
    {
        if (trim($content) === '') {
            throw new RuntimeException('Please enter some text to save.');
        }

        if (mb_strlen($content) > $this->_config->get('pasteMaxLength')) {
            throw new RuntimeException('The text is longer than the allowed maximum.');
        }

        $passwordSalt = null;
        $passwordHash = null;

        if ($password !== '') {
            $passwordData = $this->_passwordHasher->create($password);
            $passwordSalt = $passwordData['salt'];
            $passwordHash = $passwordData['hash'];
        }

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = $this->_generateCode();

            if ($this->_repository->insert(
                $code,
                $content,
                $passwordSalt,
                $passwordHash,
                $this->_config->get('pasteLifetimeHours')
            )) {
                return $code;
            }
        }

        throw new RuntimeException('Unable to generate a unique link. Please try again.');
    }

    public function getPaste(string $code): ?array
    {
        return $this->_repository->findActiveByCode($code);
    }

    public function isPasswordProtected(array $paste): bool
    {
        return !empty($paste['password_hash']) && !empty($paste['password_salt']);
    }

    public function verifyPassword(array $paste, string $password): bool
    {
        if (!$this->isPasswordProtected($paste)) {
            return true;
        }

        return $this->_passwordHasher->verify(
            $password,
            (string) $paste['password_salt'],
            (string) $paste['password_hash']
        );
    }
}
