<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

class View
{
    /**************************************************************************
     * Protected Variables
     **************************************************************************/

    protected string $_basePath;

    /**************************************************************************
     * Public Variables
     **************************************************************************/

    /**************************************************************************
     * Magic Methods
     **************************************************************************/

    public function __construct(string $basePath)
    {
        $this->_basePath = rtrim($basePath, '/');
    }

    /**************************************************************************
     * Protected Methods
     **************************************************************************/

    protected function _resolve(string $view): string
    {
        $path = $this->_basePath . '/' . $view . '.php';

        if (!is_file($path)) {
            throw new RuntimeException("View not found: {$view}");
        }

        return $path;
    }

    /**************************************************************************
     * Public Methods
     **************************************************************************/

    public function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require $this->_resolve($view);
    }
}
