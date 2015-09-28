<?php

namespace LinkORB\Framework;

interface FrameworkApplicationInterface
{
    public function getRootPath();

    /**
     * @param  string $name
     * @return RepositoryInterface
     */
    public function getRepository($name);
}
