<?php

namespace LinkORB\Proxytect\Repository;

use Radvance\Repository\BaseRepository;
use Radvance\Repository\RepositoryInterface;
use LinkORB\Proxytect\Model\Proxy;

class PdoProxyRepository extends BaseRepository implements RepositoryInterface
{
    public function createEntity()
    {
        return Proxy::createNew();
    }
}
