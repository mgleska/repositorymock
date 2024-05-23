<?php

namespace App\RepositoryMock;

use PHPUnit\Framework\MockObject\MockObject;

/**
 * @method \void loadStore(mixed[][] $values)
 * @method \object[] getStoreContent()
 */
interface RepositoryMockObject extends MockObject
{
}
