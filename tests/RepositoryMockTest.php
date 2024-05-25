<?php

/**
 * @noinspection PhpUnhandledExceptionInspection
 */

declare(strict_types=1);

namespace Tests;

use RepositoryMock\RepositoryMockObject;
use RepositoryMock\RepositoryMockTrait;
use BadMethodCallException;
use DateTime;
use Doctrine\Common\Collections\Collection;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception as PHPUnitException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Tests\Entity\Entity;
use Tests\Entity\EntityMulti;
use Tests\Entity\EntityUser;
use Tests\Repository\Repository;
use Tests\Repository\RepositoryMulti;
use Tests\Repository\RepositoryNoSave;
use Tests\Repository\RepositorySaveWithoutType;

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
                ],
            ]
        );

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
            ]
        );

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

    #[Test]
    public function repositoryClassExists(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessageMatches('/^Repository class does not exist[.]$/');

        /** @var class-string $class */
        $class = 'bad-class-name';
        $this->createRepositoryMock($class);
    }

    #[Test]
    public function repositoryClassIsServiceEntityRepository(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessageMatches('/^Repository class does not extend class EntityRepository[.]$/');

        $this->createRepositoryMock(Entity::class);
    }

    #[Test]
    public function repositoryMockIsMockObjectAndClass(): void
    {
        $repository = $this->createRepositoryMock(
            Repository::class
        );

        $this->assertInstanceOf(MockObject::class, $repository);
        $this->assertInstanceOf(Repository::class, $repository);
    }

    #[Test]
    public function exceptionWhenSaveMethodNotDefined(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessageMatches("/^Can't detect class of entity[.] Please specify class as second parameter[.]$/");

        $this->createRepositoryMock(
            RepositoryNoSave::class
        );
    }

    #[Test]
    public function exceptionWhenSaveMethodWithoutEntityType(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessageMatches("/^Can't detect class of entity[.] Please specify class as second parameter[.]$/");

        $this->createRepositoryMock(
            RepositorySaveWithoutType::class
        );
    }

    #[Test]
    public function dbLoadDataAfterCreateMock(): void
    {
        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(Repository::class);

        $repository->loadStore(
            [
                [
                    'id' => 2,
                    'referenceId' => 7,
                    'name' => 'Name 2',
                ],
                [
                    'id' => 3,
                    'referenceId' => 12,
                    'name' => 'Name 3',
                ],
            ]
        );

        /** @var Entity[] $entities */
        $entities = $repository->getStoreContent();
        $this->assertSame(2, count($entities));

        $this->assertArrayHasKey(2, $entities);
        $this->assertArrayHasKey(3, $entities);

        $entity = $entities[2];
        $this->assertInstanceOf(Entity::class, $entity);
        $this->assertSame(2, $entity->getId());
        $this->assertSame(7, $entity->getReferenceId());
        $this->assertSame('Name 2', $entity->getName());

        $entity = $entities[3];
        $this->assertInstanceOf(Entity::class, $entity);
        $this->assertSame(3, $entity->getId());
        $this->assertSame(12, $entity->getReferenceId());
        $this->assertSame('Name 3', $entity->getName());
    }

    #[Test]
    public function entityTypeAsSecondParameter(): void
    {
        /** @var RepositoryNoSave|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(
            RepositoryNoSave::class,
            EntityMulti::class
        );

        $repository->loadStore(
            [
                [
                    'id' => 1,
                ],
            ]
        );

        $entities = $repository->getStoreContent();
        $entity = $entities[1];
        $this->assertInstanceOf(EntityMulti::class, $entity);
    }

    #[Test]
    public function createTwoLevelEntityManyToOneMapping(): void
    {
        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(Repository::class);
        $repository->loadStore([
            [
                'id'          => 1,
                'referenceId' => 5,
                'name'        => null,
                'updatedBy'   => [
                    'id'       => 5,
                    'fullName' => "User Name",
                ],
            ],
        ]);

        /** @var Entity $entity */
        $entity = $repository->find(1);

        $this->assertInstanceOf(Entity::class, $entity);
        $this->assertInstanceOf(EntityUser::class, $entity->getUpdatedBy());
    }

    #[Test]
    public function createTwoLevelEntityOneToOneMapping(): void
    {
        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(RepositoryMulti::class);
        $repository->loadStore([
            [
                'id'    => 1,
                'owner' => [
                    'id'       => 5,
                    'fullName' => "User Name",
                ],
            ],
        ]);

        /** @var EntityMulti $entity */
        $entity = $repository->find(1);

        $this->assertInstanceOf(EntityMulti::class, $entity);
        $this->assertInstanceOf(EntityUser::class, $entity->getOwner());
    }

    #[Test]
    public function createTwoLevelEntityOneToManyMapping(): void
    {
        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(RepositoryMulti::class);
        $repository->loadStore([
            [
                'id'    => 1,
                'users' => [
                    [
                        'id'       => 5,
                        'fullName' => 'User Name 5',
                    ],
                    [
                        'id'       => 2,
                        'fullName' => 'User Name 2',
                    ],
                ],
            ],
        ]);

        /** @var EntityMulti $entity */
        $entity = $repository->find(1);

        $this->assertInstanceOf(EntityMulti::class, $entity);
        $users = $entity->getUsers()->toArray();
        $this->assertIsArray($users);
        $this->assertInstanceOf(EntityUser::class, $users[0]);
        $this->assertSame(5, $users[0]->getId());
        $this->assertSame('User Name 5', $users[0]->getFullName());
        $this->assertInstanceOf(EntityUser::class, $users[1]);
        $this->assertSame(2, $users[1]->getId());
        $this->assertSame('User Name 2', $users[1]->getFullName());
    }

    #[Test]
    public function createEntityMultiLevel(): void
    {
        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(RepositoryMulti::class);
        $repository->loadStore([
            [
                'id'       => 1,
                'subMulti' => [
                    'subMulti' => [
                        'owner' => [
                            'id'       => 5,
                            'fullName' => "User Name",
                        ],
                        'users' => [
                            [
                                'id'       => 5,
                                'fullName' => 'User Name 5',
                            ],
                            [
                                'id'       => 2,
                                'fullName' => 'User Name 2',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        /** @var EntityMulti $entity */
        $entity = $repository->find(1);

        $this->assertInstanceOf(EntityMulti::class, $entity);

        $subLevelOne = $entity->getSubMulti();
        $this->assertInstanceOf(EntityMulti::class, $subLevelOne);

        $subLevelTwo = $subLevelOne->getSubMulti();
        $this->assertInstanceOf(EntityMulti::class, $subLevelTwo);
        $this->assertInstanceOf(EntityUser::class, $subLevelTwo->getOwner());

        $users = $subLevelTwo->getUsers()->toArray();
        $this->assertIsArray($users);
        $this->assertInstanceOf(EntityUser::class, $users[0]);
        $this->assertInstanceOf(EntityUser::class, $users[1]);
    }

    #[Test]
    public function findExists(): void
    {
        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(Repository::class);
        $repository->loadStore([
            [
                'id'          => 1,
                'referenceId' => 5,
                'name'        => null,
            ],
        ]);

        /** @var ?Entity $entity */
        $entity = $repository->find(1);
        $this->assertSame(1, $entity->getId());
        $this->assertSame(5, $entity->getReferenceId());
        $this->assertSame(null, $entity->getName());

        $entity = $repository->find(2);
        $this->assertSame(null, $entity);
    }

    /**
     * @param array<string, array<string, mixed>> $criteria
     * @param array<string, mixed>|null $expected
     * @throws ReflectionException
     * @throws PHPUnitException
     */
    #[Test]
    #[DataProvider('dataProviderTestFindOneBy')]
    public function findOneBy(array $criteria, ?array $expected): void
    {
        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(Repository::class);
        $repository->loadStore([
            [
                'id'          => 1,
                'referenceId' => 5,
                'name'        => null,
            ],
            [
                'id'          => 2,
                'referenceId' => 7,
                'name'        => 'Name 2',
                'updatedBy'   => [
                    'id'       => 5,
                    'fullName' => 'User Name',
                ],
            ],
            [
                'id'          => 3,
                'referenceId' => 5,
                'name'        => 'Name 3',
            ],
            [
                'id'          => 4,
                'referenceId' => 4,
                'name'        => 'Name 4',
                'validTo'     => new DateTime('2023-10-16'),
            ],
        ]);

        /** @var ?Entity $entity */
        $entity = $repository->findOneBy($criteria);

        if ($expected !== null) {
            $this->assertNotNull($entity);
            $this->assertInstanceOf(Entity::class, $entity);
            $this->assertSame($expected['id'], $entity->getId());
            $this->assertSame($expected['referenceId'], $entity->getReferenceId());
            $this->assertSame($expected['name'], $entity->getName());
        } else {
            $this->assertNull($entity);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     * @throws ReflectionException
     */
    public static function dataProviderTestFindOneBy(): array
    {
        return [
            'only-one-record-in-db'      => [
                'criteria' => ['referenceId' => 7],
                'expected' => [
                    'id'          => 2,
                    'referenceId' => 7,
                    'name'        => 'Name 2',
                ],
            ],
            'two-criteria-one-record'   => [
                'criteria' => ['referenceId' => 7, 'name' => 'Name 2'],
                'expected' => [
                    'id'          => 2,
                    'referenceId' => 7,
                    'name'        => 'Name 2',
                ],
            ],
            'two-records-in-db-get-first' => [
                'criteria' => ['referenceId' => 5],
                'expected' => [
                    'id'          => 1,
                    'referenceId' => 5,
                    'name'        => null,
                ],
            ],
            'two-records-in-db-criteria-multi-value-get-first' => [
                'criteria' => ['id' => [1, 3]],
                'expected' => [
                    'id'          => 1,
                    'referenceId' => 5,
                    'name'        => null,
                ],
            ],
            'not-found'                  => [
                'criteria' => ['referenceId' => 100],
                'expected' => null,
            ],
            'not-found-2'                => [
                'criteria' => ['referenceId' => 7, 'name' => 'Name 77'],
                'expected' => null,
            ],
            'find-by-null-value'         => [
                'criteria' => ['name' => null],
                'expected' => [
                    'id'          => 1,
                    'referenceId' => 5,
                    'name'        => null,
                ],
            ],
            'find-by-object'             => [
                'criteria' => [
                    'updatedBy' => self::createFakeObject(EntityUser::class, ['id' => 5, 'fullName' => 'User Name']),
                ],
                'expected' => [
                    'id'          => 2,
                    'referenceId' => 7,
                    'name'        => 'Name 2',
                ],
            ],
            'find-by-date-object'     => [
                'criteria' => [
                    'validTo' => new DateTime('2023-10-16'),
                ],
                'expected' => [
                    'id'          => 4,
                    'referenceId' => 4,
                    'name'        => 'Name 4',
                ],
            ],
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $criteria
     * @param array<int, array<string, mixed>> $expected
     * @throws ReflectionException
     * @throws PHPUnitException
     */
    #[Test]
    #[DataProvider('dataProviderTestFindBy')]
    public function findBy(array $criteria, array $expected): void
    {
        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(Repository::class);
        $repository->loadStore([
            [
                'id'          => 1,
                'referenceId' => 5,
                'name'        => null,
            ],
            [
                'id'          => 2,
                'referenceId' => 7,
                'name'        => 'Name 2',
                'updatedBy'   => [
                    'id'       => 5,
                    'fullName' => 'User Name',
                ],
            ],
            [
                'id'          => 3,
                'referenceId' => 5,
                'name'        => 'Name 3',
            ],
            [
                'id'          => 4,
                'referenceId' => 4,
                'name'        => 'Name 4',
                'validTo'     => new DateTime('2023-10-16'),
            ],
            [
                'id'          => 5,
                'referenceId' => 4,
                'name'        => 'Name 4 v2',
                'validTo'     => new DateTime('2023-10-17'),
                'updatedBy'   => [
                    'id'       => 10,
                    'fullName' => 'User 10',
                ],
            ],
        ]);

        /** @var Entity[] $result */
        $result = $repository->findBy($criteria);

        $this->assertIsArray($result);
        if (count($expected) > 0) {
            $this->assertSameSize($expected, $result);
            foreach ($result as $item) {
                $this->assertInstanceOf(Entity::class, $item);
                $this->assertSame($expected[$item->getId()]['id'], $item->getId());
                $this->assertSame($expected[$item->getId()]['referenceId'], $item->getReferenceId());
                $this->assertSame($expected[$item->getId()]['name'], $item->getName());
            }
        } else {
            $this->assertCount(0, $result);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     * @throws ReflectionException
     */
    public static function dataProviderTestFindBy(): array
    {
        return [
            'only-one-record-in-db'    => [
                'criteria' => ['referenceId' => 7],
                'expected' => [
                    2 => [
                        'id'          => 2,
                        'referenceId' => 7,
                        'name'        => 'Name 2',
                    ],
                ],
            ],
            'two-criteria-one-record' => [
                'criteria' => ['referenceId' => 7, 'name' => 'Name 2'],
                'expected' => [
                    2 => [
                        'id'          => 2,
                        'referenceId' => 7,
                        'name'        => 'Name 2',
                    ],
                ],
            ],
            'two-records-in-db'         => [
                'criteria' => ['referenceId' => 5],
                'expected' => [
                    1 => [
                        'id'          => 1,
                        'referenceId' => 5,
                        'name'        => null,
                    ],
                    3 => [
                        'id'          => 3,
                        'referenceId' => 5,
                        'name'        => 'Name 3',
                    ],
                ],
            ],
            'two-records-in-db-criteria-multi-value' => [
                'criteria' => ['id' => [1, 3]],
                'expected' => [
                    1 => [
                        'id'          => 1,
                        'referenceId' => 5,
                        'name'        => null,
                    ],
                    3 => [
                        'id'          => 3,
                        'referenceId' => 5,
                        'name'        => 'Name 3',
                    ],
                ],
            ],
            'two-records-in-db-criteria-multi-date' => [
                'criteria' => ['validTo' => [new DateTime('2023-10-16'), new DateTime('2023-10-17')]],
                'expected' => [
                    4 => [
                        'id'          => 4,
                        'referenceId' => 4,
                        'name'        => 'Name 4',
                    ],
                    5 => [
                        'id'          => 5,
                        'referenceId' => 4,
                        'name'        => 'Name 4 v2',
                    ],
                ],
            ],
            'two-records-in-db-criteria-multi-object' => [
                'criteria' => ['updatedBy' => [
                    self::createFakeObject(EntityUser::class, ['id' => 5]),
                    self::createFakeObject(EntityUser::class, ['id' => 10]),
                ]],
                'expected' => [
                    2 => [
                        'id'          => 2,
                        'referenceId' => 7,
                        'name'        => 'Name 2',
                    ],
                    5 => [
                        'id'          => 5,
                        'referenceId' => 4,
                        'name'        => 'Name 4 v2',
                    ],
                ],
            ],
            'not-found'                => [
                'criteria' => ['referenceId' => 100],
                'expected' => [],
            ],
            'not-found-2'              => [
                'criteria' => ['referenceId' => 7, 'name' => 'Name 77'],
                'expected' => [],
            ],
            'find-by-null-value'       => [
                'criteria' => ['name' => null],
                'expected' => [
                    1 => [
                        'id'          => 1,
                        'referenceId' => 5,
                        'name'        => null,
                    ],
                ],
            ],
            'find-by-object'           => [
                'criteria' => [
                    'updatedBy' => self::createFakeObject(EntityUser::class, ['id' => 5, 'fullName' => 'User Name']),
                ],
                'expected' => [
                    2 => [
                        'id'          => 2,
                        'referenceId' => 7,
                        'name'        => 'Name 2',
                    ],
                ],
            ],
            'find-by-date-object'     => [
                'criteria' => [
                    'validTo' => new DateTime('2023-10-16'),
                ],
                'expected' => [
                    4 => [
                        'id'          => 4,
                        'referenceId' => 4,
                        'name'        => 'Name 4',
                    ],
                ],
            ],
        ];
    }

    #[Test]
    public function findByAfterSave(): void
    {
        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(Repository::class);
        $repository->loadStore([
            [
                'id'          => 2,
                'referenceId' => 7,
                'name'        => 'Name 2',
                'updatedBy'   => [
                    'id'       => 5,
                    'fullName' => 'User Name',
                ],
            ],
        ]);

        //
        // update mode
        //
        $user5 = $this->createFakeObject(EntityUser::class, ['id' => 5, 'fullName' => 'User Name']);
        $entity = $repository->find(2);
        $entity->setName('Name updated');
        $repository->save($entity);

        $found = $repository->findBy(['updatedBy' => $user5]);
        $this->assertIsArray($found);
        $this->assertArrayHasKey(0, $found);
        $this->assertSame($entity->getName(), $found[0]->getName());

        //
        // insert mode
        //
        $user10 = $this->createFakeObject(EntityUser::class, ['id' => 10, 'fullName' => 'User 10']);
        $entity = new Entity();
        $entity->setName('Name new');
        $entity->setUpdatedBy($user10);
        $repository->save($entity);

        $found = $repository->findBy(['updatedBy' => $user10]);
        $this->assertIsArray($found);
        $this->assertArrayHasKey(0, $found);
        $this->assertSame($entity->getName(), $found[0]->getName());
    }

    #[Test]
    public function saveAsUpdate(): void
    {
        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(Repository::class);
        $repository->loadStore([
            [
                'id'          => 7,
                'referenceId' => 5,
                'name'        => null,
            ],
        ]);

        /** @var ?Entity $entity */
        $entity = $repository->find(7);

        $entity->setName('Name updated');
        $entity->setReferenceId(17);

        $repository->save($entity);

        $entityUpdated = $repository->find(7);
        $this->assertSame(7, $entityUpdated->getId());
        $this->assertSame(17, $entityUpdated->getReferenceId());
        $this->assertSame('Name updated', $entityUpdated->getName());
    }

    #[Test]
    public function saveException(): void
    {
        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(Repository::class);
        $repository->loadStore([
            [
                'id'          => 1,
                'referenceId' => 5,
                'name'        => null,
            ],
        ]);

        $entity = self::createFakeObject(Entity::class, ['id' => 20]);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessageMatches("/^Method 'save' can't find entity with id '20'[.]$/");

        $repository->save($entity);
    }

    #[Test]
    public function saveAsInsertWithAutoIncrement(): void
    {
        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(Repository::class);
        $repository->loadStore([
            [
                'id'          => 7,
                'referenceId' => 5,
                'name'        => null,
            ],
            [
                'id'          => 2,
                'referenceId' => 2,
                'name'        => 'Entity 2',
            ],
        ]);

        $entity = new Entity();
        $entity->setName('New entity');
        $entity->setReferenceId(18);

        $repository->save($entity);

        /** @var Entity[] $entities */
        $entities = $repository->getStoreContent();
        $this->assertCount(3, $entities);
        foreach ($entities as $entityUpdated) {
            if ($entityUpdated->getId() === 7 || $entityUpdated->getId() === 2) {
                continue;
            }
            $this->assertSame(8, $entityUpdated->getId());
            $this->assertSame(18, $entityUpdated->getReferenceId());
            $this->assertSame('New entity', $entityUpdated->getName());
        }
    }

    #[Test]
    public function autoIncrementMovingOnlyForward(): void
    {
        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(Repository::class);
        $repository->loadStore([
            [
                'id'          => 7,
                'referenceId' => 5,
                'name'        => null,
            ],
        ]);

        $entity = $repository->find(7);
        $entity1 = new Entity();
        $entity1->setName('New entity');
        $entity1->setReferenceId(18);
        $entity2 = clone $entity1;

        $repository->save($entity1);
        $this->assertSame(8, $entity1->getId());

        $repository->save($entity2);
        $this->assertSame(9, $entity2->getId());

        $repository->remove($entity);
        $repository->remove($entity1);
        $repository->remove($entity2);

        $entities = $repository->getStoreContent();
        $this->assertCount(0, $entities);

        $entity3 = new Entity();
        $entity3->setName('Entity 3');
        $entity3->setReferenceId(20);

        $repository->save($entity3);
        $this->assertSame(10, $entity3->getId());
    }

    #[Test]
    public function autoIncrementStartsFromOne(): void
    {
        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(Repository::class);

        $entity1 = new Entity();
        $entity1->setName('New entity');

        $repository->save($entity1);
        $this->assertSame(1, $entity1->getId());

        $repository->loadStore([]);

        $entity2 = new Entity();
        $entity2->setName('New entity2');

        $repository->save($entity2);
        $this->assertSame(1, $entity2->getId());
    }

    #[Test]
    public function removeExistingEntity(): void
    {
        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(Repository::class);
        $repository->loadStore([
            [
                'id'          => 7,
                'referenceId' => 5,
                'name'        => null,
            ],
        ]);

        /** @var ?Entity $entity */
        $entity = $repository->find(7);

        $repository->remove($entity);

        /** @var Entity[] $entities */
        $entities = $repository->getStoreContent();
        $this->assertCount(0, $entities);
    }

    #[Test]
    public function removeInsertedEntity(): void
    {
        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(Repository::class);
        $repository->loadStore([
            [
                'id'          => 7,
                'referenceId' => 5,
                'name'        => null,
            ],
        ]);

        $entity = new Entity();
        $entity->setName('New entity');
        $entity->setReferenceId(18);

        $repository->save($entity);
        $repository->remove($entity);

        /** @var Entity[] $entities */
        $entities = $repository->getStoreContent();
        $this->assertCount(1, $entities);
        $this->assertSame(7, $entities[7]->getId());
    }

    #[Test]
    public function removeException(): void
    {
        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(Repository::class);
        $repository->loadStore([
            [
                'id'          => 1,
                'referenceId' => 5,
                'name'        => null,
            ],
        ]);

        $entity = $this->createFakeObject(Entity::class, ['id' => 20]);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessageMatches("/^Method 'remove' can't find entity with id '20'[.]$/");

        $repository->remove($entity);
    }

    #[Test]
    public function findByModifyFindBy(): void
    {
        // We check that modification of the retrieved object do not change original object in store.

        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(Repository::class);
        $repository->loadStore([
            [
                'id'          => 1,
                'referenceId' => 5,
                'name'        => null,
            ],
        ]);

        /** @var Entity $obj1 */
        $obj1 = $repository->findOneBy(['referenceId' => 5]);

        $obj1->setReferenceId(10);

        $obj2 = $repository->findOneBy(['referenceId' => 5]);

        $this->assertNotNull($obj2);
    }

    #[Test]
    public function findBySaveModifyFindBy(): void
    {
        // We check that modification of the object after save do not change object persisted in store.

        /** @var Repository|RepositoryMockObject $repository */
        $repository = $this->createRepositoryMock(Repository::class);
        $repository->loadStore([
            [
                'id'          => 1,
                'referenceId' => 5,
                'name'        => null,
            ],
        ]);

        /** @var Entity $obj1 */
        $obj1 = $repository->findOneBy(['referenceId' => 5]);

        $obj1->setReferenceId(10);
        $repository->save($obj1);

        $obj1->setReferenceId(15);

        $obj2 = $repository->findOneBy(['referenceId' => 10]);

        $this->assertNotNull($obj2);
    }

    #[Test]
    public function newSaveModifyFindBy(): void
    {
        // We check that modification of the object after save do not change object persisted in store.

        /** @var Repository|MockObject $repository */
        $repository = $this->createRepositoryMock(Repository::class);

        $obj1 = new Entity();
        $obj1->setReferenceId(10);
        $repository->save($obj1);

        $obj1->setReferenceId(15);

        $obj2 = $repository->findOneBy(['referenceId' => 10]);

        $this->assertNotNull($obj2);
    }
}
