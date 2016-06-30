<?php

namespace Radvance\Repository;

/**
 * Classes implementing GlobalRepositoryInterface are indicated
 * to NOT store their data in a space-specific datastore.
 *
 * This is used for Permission and Space repositories.
 * All other repositories should be non-global, meaning space-specific
 */
interface GlobalRepositoryInterface extends RepositoryInterface
{
}
