### A package to facilitate the testing of classes/methods which uses ORM Repository and ORM Entity objects.


## Installation
```sh
composer require --dev mgleska/repositorymock:"^1"
```

## Features

1. Provides ready-made mocks for functions:
    * `find()`
    * `findOneBy()`
    * `findBy()`
        * by scalar value
        * by list of values
        * by object
    * `save()`
    * `remove()`


2. Allow to create mocks for Entity objects from array of values.
    * simple Entity (without relations)
    * multi-level Entity
        * `Doctrine\ORM\Mapping\OneToOne`
        * `Doctrine\ORM\Mapping\ManyToOne`
        * `Doctrine\ORM\Mapping\OneToMany`


3. Full compatibility with PHPUnit mock. An object of type RepositoryMock can be treated as a regular mock created by PHPUnit.<br>For example, you can use `$mockedRepository->method('myCustomMethod')->willReturn(...)`.


4. Provides the `createFakeObject(string $className, array $objData)` method which can be used to create Entity object for use in `willReturn()`.<br>Particularly useful for multi-level Entities and those where the `id` field has no setter (because it is autoincrement).


5. Provides the ability to create test scenarios for update/edit and create/delete actions.<br>The mock for the `save()` function stores the data sent towards the database. Which gives the possibility to observe the "state of the database" after the tested operation `$sut->action()` is completed.
    * `save()` does:
        * update if entity exists
        * insert with autoincrement for `id`
    * than `getStoreContent()` method allow to make assertions on entities stored in database. 

## Usage

1. Mock for simple `find()`.
```php
// src/Service/SutService.php

class SutService
{
    public function __construct(
        private readonly Repository $repository,
    ) {
    }

    public function getFirst(): ?Entity
    {
        return $this->repository->find(1);
    }
}
```

```php
// tests/Service/SutServiceTest.php

use RepositoryMock\RepositoryMockObject;
use RepositoryMock\RepositoryMockTrait;
...

class SutServiceTest extends TestCase
{
    use RepositoryMockTrait;

    private Sutservice $sut;

    private Repository|RepositoryMockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createRepositoryMock(Repository::class);

        $this->sut = new SutService(
            $this->repository,
        );
    }

    #[Test]
    public function getFirstFound(): void
    {
        $this->repository->loadStore([
            [ // values of selected properties of Entity
                'id' => 1,
                'name' => 'test',
            ],
        ]);

        $result = $this->sut->getFirst();

        $this->assertInstanceOf(Entity::class, $result);
    }

    #[Test]
    public function getFirstNotFound(): void
    {
        $this->repository->loadStore([
            [
                'id' => 22,
                'name' => 'test 22',
            ],
        ]);

        $result = $this->sut->getFirst();

        $this->assertNull($result);
    }
}
```

2. More usage examples in file `tests/SutServiceTest.php`.
