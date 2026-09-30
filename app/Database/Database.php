<?php

declare(strict_types=1);

namespace App\Database;

use App\Config\AppConfig;
use PDO;

class Database
{
    /**************************************************************************
     * Protected Variables
     **************************************************************************/

    protected AppConfig $_config;
    protected ?PDO $_connection = null;

    /**************************************************************************
     * Public Variables
     **************************************************************************/

    /**************************************************************************
     * Magic Methods
     **************************************************************************/

    public function __construct(AppConfig $config)
    {
        $this->_config = $config;
    }

    /**************************************************************************
     * Protected Methods
     **************************************************************************/

    protected function _buildDsn(): string
    {
        return sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $this->_config->get('databaseHost'),
            $this->_config->get('databasePort'),
            $this->_config->get('databaseName')
        );
    }

    /**************************************************************************
     * Public Methods
     **************************************************************************/

    public function connection(): PDO
    {
        if ($this->_connection === null) {
            $this->_connection = new PDO(
                $this->_buildDsn(),
                $this->_config->get('databaseUser'),
                $this->_config->get('databasePassword'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        }

        return $this->_connection;
    }
}
