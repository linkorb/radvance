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
}
