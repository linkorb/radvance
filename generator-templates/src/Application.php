<?php

namespace $$NAMESPACE$$;

use Radvance\Framework\BaseWebApplication;
use Radvance\Framework\FrameworkApplicationInterface;
// use $$NAMESPACE$$\Repository\PdoExampleRepository;
use RuntimeException;

class Application extends BaseWebApplication implements FrameworkApplicationInterface
{
    public function getRootPath()
    {
        return realpath(__DIR__ . '/../');
    }

    protected function configureRepositories()
    {
        // Add repositories here
        // $this->addRepository(new PdoExampleRepository($this->pdo));
    }
}
