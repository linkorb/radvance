<?php

namespace $$NAMESPACE$$\Repository;

use Radvance\Repository\BaseRepository;
use Radvance\Repository\RepositoryInterface;
use $$NAMESPACE$$\Model\$$CLASS_PREFIX$$;

class Pdo$$CLASS_PREFIX$$Repository extends BaseRepository implements RepositoryInterface
{
    public function createEntity()
    {
        return Proxy::createNew();
    }
}
