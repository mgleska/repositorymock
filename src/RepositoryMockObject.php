<?php

namespace Evolution\RepositoryMock;

use PHPUnit\Framework\MockObject\MockObject;

/**
 * @method \void loadStore(mixed[][] $values)
 * @method \object[] getStoreContent()
 * @method \object[] getStoreMappedById()
 */
interface RepositoryMockObject extends MockObject
{
}
