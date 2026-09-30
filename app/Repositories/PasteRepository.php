<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

class PasteRepository
{
    /**************************************************************************
     * Protected Variables
     **************************************************************************/

    protected PDO $_database;

    /**************************************************************************
     * Public Variables
     **************************************************************************/

    /**************************************************************************
     * Magic Methods
     **************************************************************************/

    public function __construct(PDO $database)
    {
        $this->_database = $database;
    }

    /**************************************************************************
     * Protected Methods
     **************************************************************************/

    protected function _isUniqueViolation(PDOException $exception): bool
    {
        return $exception->getCode() === '23505';
    }

    /**************************************************************************
     * Public Methods
     **************************************************************************/

    public function insert(
        string $code,
        string $content,
        ?string $passwordSalt,
        ?string $passwordHash,
        int $lifetimeHours
    ): bool {
        $sql = <<<'SQL'
            INSERT INTO pastes (
                code,
                content,
                password_salt,
                password_hash,
                expires_at
            ) VALUES (
                :code,
                :content,
                :password_salt,
                :password_hash,
                NOW() + make_interval(hours => CAST(:lifetime_hours AS integer))
            )
        SQL;

        try {
            $statement = $this->_database->prepare($sql);
            $statement->execute([
                'code' => $code,
                'content' => $content,
                'password_salt' => $passwordSalt,
                'password_hash' => $passwordHash,
                'lifetime_hours' => $lifetimeHours,
            ]);

            return true;
        } catch (PDOException $exception) {
            if ($this->_isUniqueViolation($exception)) {
                return false;
            }

            throw $exception;
        }
    }

    public function findActiveByCode(string $code): ?array
    {
        $statement = $this->_database->prepare(
            <<<'SQL'
                SELECT code, content, password_salt, password_hash, created_at, expires_at
                FROM pastes
                WHERE code = :code
                  AND expires_at > NOW()
                LIMIT 1
            SQL
        );
        $statement->execute(['code' => $code]);

        $paste = $statement->fetch();

        return $paste === false ? null : $paste;
    }

    public function deleteExpired(): int
    {
        $statement = $this->_database->prepare('DELETE FROM pastes WHERE expires_at <= NOW()');
        $statement->execute();

        return $statement->rowCount();
    }
}
