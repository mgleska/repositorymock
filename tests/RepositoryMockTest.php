<?php

/**
 * @noinspection PhpUnhandledExceptionInspection
 */

declare(strict_types=1);

namespace Tests;

use BadMethodCallException;
use DateTime;
// use App\RepositoryMock\RepositoryMockObject;
use App\RepositoryMock\RepositoryMockTrait;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionMethod;
use Tests\Entity\Entity;
use Tests\Entity\EntityMulti;
use Tests\Entity\EntityUser;
use Tests\Repository\Repository;
use Tests\Repository\RepositoryMulti;
use Tests\Repository\RepositoryNoSave;
use Tests\Repository\RepositorySaveWithoutType;
use Tests\Repository\RepositorySimple;
use Doctrine\Common\Collections\Collection;


class RepositoryMockTest extends TestCase
{
    use RepositoryMockTrait;

    #[Test]
    public function createFakeObjectTest1(): void
    {
        $obj = $this->createFakeObject(
            Entity::class,
            [
                'referenceId' => 10,
                'name' => null,
                'updatedBy' => [
                    'id' => 2,
                    'fullName' => 'Test Name',
                ]
            ]);

        $this->assertInstanceOf(Entity::class, $obj);
        $this->assertSame(10, $obj->getReferenceId());
        $this->assertInstanceOf(EntityUser::class, $obj->getUpdatedBy());
        $this->assertSame(2, $obj->getUpdatedBy()->getId());
        $this->assertSame('Test Name', $obj->getUpdatedBy()->getFullName());
    }

    #[Test]
    public function createFakeObjectTest2(): void
    {
        $obj = $this->createFakeObject(
            EntityMulti::class,
            [
                'owner' => [
                    'fullName' => 'Owner Name',
                ],
                'users' => [
                    [
                        'fullName' => 'User Name 1',
                    ],
                    [
                        'fullName' => 'User Name 2',
                    ],
                ],
                'subMulti' => [
                    'owner' => [
                        'fullName' => 'Owner SubMulti',
                    ],
                ],
            ]);

        $this->assertInstanceOf(EntityMulti::class, $obj);
        $this->assertInstanceOf(EntityUser::class, $obj->getOwner());
        $this->assertSame('Owner Name', $obj->getOwner()->getFullName());
        $this->assertInstanceOf(Collection::class, $obj->getUsers());
        $this->assertSame('User Name 1', $obj->getUsers()[0]->getFullName());
        $this->assertSame('User Name 2', $obj->getUsers()[1]->getFullName());
        $this->assertInstanceOf(EntityMulti::class, $obj->getSubMulti());
        $this->assertInstanceOf(EntityUser::class, $obj->getSubMulti()->getOwner());
        $this->assertSame('Owner SubMulti', $obj->getSubMulti()->getOwner()->getFullName());
    }

    #[Test]
    public function createFakeObjectTest3(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessageMatches('/^Automatic mocking of mapping type used for property/');

        $this->createFakeObject(
            EntityMulti::class,
            [
                'owner' => [
                    'fullName' => 'Owner Name',
                ],
                'groups' => [
                    [
                        'fullName' => 'User Name 1',
                    ],
                ],
            ]
        );
    }

    #[Test]
    public function createFakeObjectTest4(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessageMatches('/^Not defined targetEntity for property/');

        $this->createFakeObject(
            EntityMulti::class,
            [
                'owner' => [
                    'fullName' => 'Owner Name',
                ],
                'badUsers' => [
                    [
                        'fullName' => 'User Name 1',
                    ],
                ],
            ]
        );
    }
}
