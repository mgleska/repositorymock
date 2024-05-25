<?php

declare(strict_types=1);

namespace App\RepositoryMock;

use BadMethodCallException;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use OutOfBoundsException;
use PHPUnit\Framework\MockObject\Exception as PHPUnitException;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use function array_key_exists;
use function array_keys;
use function class_exists;
use function count;
use function get_class;
use function in_array;
use function is_a;
use function is_array;
use function is_null;
use function is_object;
use function max;
use function method_exists;
use function serialize;
use function str_replace;
use function unserialize;

trait RepositoryMockTrait
{
    /**
     * @template T of object
     * @param class-string<T> $repositoryClassName
     * @param class-string|null $entityClassName
     * @return T&MockObject
     *
     * @throws ReflectionException
     * @throws PHPUnitException
     */
    protected function createRepositoryMock(
        string $repositoryClassName,
        string $entityClassName = null,
    ): MockObject
    {
        if (! class_exists($repositoryClassName)) {
            throw new BadMethodCallException('Repository class does not exist.');
        }
        if (! is_a($repositoryClassName, ServiceEntityRepository::class, true)) {
            throw new BadMethodCallException('Repository class does not extend class ServiceEntityRepository.');
        }

        if (! $entityClassName) {
            try {
                $saveMethod = new ReflectionMethod($repositoryClassName, 'save');
                $paramType = $saveMethod->getParameters()[0]->getType();
                if ($paramType instanceof ReflectionNamedType) {
                    $entityClassName = $paramType->getName();
                }
            } catch (ReflectionException) {
                throw new BadMethodCallException(
                    "Can't detect class of entity. Please specify class as second parameter."
                );
            }
        }
        if (! $entityClassName) {
            throw new BadMethodCallException("Can't detect class of entity. Please specify class as second parameter.");
        }

        /** @var class-string<T&RepositoryMockObject> $mockedClassName */
        $mockedClassName = str_replace('\\', '_', $repositoryClassName) . '_RepositoryMock';
        if (! class_exists($mockedClassName)) {
            $classTemplate = "
            class $mockedClassName extends $repositoryClassName {
                public function loadStore(array \$values): void {}
                public function getStoreContent(): array {}
            }";
            eval($classTemplate);
        }

        $repoClass = new ReflectionClass($repositoryClassName);
        $optionalMethods = [];
        foreach ($repoClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (in_array($method->getName(), ['save', 'remove'])) {
                $optionalMethods[] = $method->getName();
            }
        }

        $mock = $this->createMock($mockedClassName);

        $store = new \stdClass();
        $store->entityClassName = $entityClassName;
        $store->items = [];
        $store->autoIncrement = 1;

        $mock
            ->method('loadStore')
            ->willReturnCallback(
                function (array $values) use ($store): void {
                    $store->items = [];
                    foreach ($values as $row) {
                        $obj = self::createFakeObject($store->entityClassName, $row); // @phpstan-ignore argument.templateType
                        $store->items[$obj->getId()] = $obj;
                    }
                    $store->autoIncrement =
                        count(array_keys($store->items)) > 0 ? max(array_keys($store->items)) + 1 : 1;
                }
            );

        $mock
            ->method('getStoreContent')
            ->willReturnCallback(
                function () use ($store): array {
                    return $store->items;
                }
            );

        $mock
            ->method('find')
            ->willReturnCallback(
                function (int $id) use ($store): object|null {
                    if (! array_key_exists($id, $store->items)) {
                        return null;
                    }
                    return unserialize(serialize($store->items[$id]));
                }
            );

        $mock
            ->method('findOneBy')
            ->willReturnCallback(
                function (array $criteria) use ($store): object|null {
                    foreach ($store->items as $obj) { // @phpstan-ignore foreach.emptyArray
                        $matchCount = $this->repositoryMockFindByHelper($criteria, $obj);
                        if ($matchCount === count($criteria)) {
                            return unserialize(serialize($obj));
                        }
                    }
                    return null;
                }
            );

        $mock
            ->method('findBy')
            ->willReturnCallback(
                function (array $criteria) use ($store): array {
                    $ret = [];
                    foreach ($store->items as $obj) { // @phpstan-ignore foreach.emptyArray
                        $matchCount = $this->repositoryMockFindByHelper($criteria, $obj);
                        if ($matchCount === count($criteria)) {
                            $ret[] = unserialize(serialize($obj));
                        }
                    }
                    return $ret;
                }
            );

        if (in_array('save', $optionalMethods)) {
            $mock
                ->method('save')
                ->willReturnCallback(
                    function ($obj) use ($store): void {
                        $idReflection = new ReflectionProperty($obj, 'id');
                        if ($idReflection->isInitialized($obj) === false || is_null($idReflection->getValue($obj))) {
                            $storeKeys = array_keys($store->items);
                            $autoId = max(
                                $store->autoIncrement,
                                count($storeKeys) > 0 ? max($storeKeys) + 1 : 0 // @phpstan-ignore greater.alwaysFalse
                            );
                            $idReflection->setValue($obj, $autoId);
                            $store->autoIncrement = $autoId + 1;
                            $store->items[$autoId] = unserialize(serialize($obj));
                            return;
                        }
                        if (array_key_exists($obj->getId(), $store->items)) {
                            $store->items[$obj->getId()] = unserialize(serialize($obj));
                            return;
                        }
                        throw new OutOfBoundsException(
                            "Method 'save' can't find entity with id '" . $obj->getId() . "'."
                        );
                    }
                );
        }

        if (in_array('remove', $optionalMethods)) {
            $mock
                ->method('remove')
                ->willReturnCallback(
                    function ($obj) use ($store): void {
                        if (array_key_exists($obj->getId(), $store->items)) {
                            unset($store->items[$obj->getId()]);
                            return;
                        }
                        throw new OutOfBoundsException(
                            "Method 'remove' can't find entity with id '" . $obj->getId() . "'."
                        );
                    }
                );
        }

        return $mock;
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @param array<int|string, mixed> $objData
     * @param boolean $collectionMode
     * @return T|T[]
     *
     * @throws ReflectionException
     */
    private static function createFakeObject(string $className, array $objData, bool $collectionMode = false): mixed
    {
        $collection = new ArrayCollection();
        if ($collectionMode) {
            $collectionData = $objData;
        } else {
            $collectionData = [$objData];
        }
        foreach ($collectionData as $objData) {
            $obj = new $className();
            $reflection = new ReflectionClass($obj);
            foreach ($objData as $field => $value) {
                $prop = $reflection->getProperty($field);
                if (is_array($value)) {
                    $mapping = $prop->getAttributes(ManyToOne::class)[0] ??
                        $prop->getAttributes(OneToOne::class)[0] ??
                        $prop->getAttributes(OneToMany::class)[0] ??
                        null;
                    if (! $mapping) {
                        throw new BadMethodCallException(
                            'Automatic mocking of mapping type used for property "' . $field .
                            '" is not implemented (yet).'
                        );
                    }
                    if ($mapping->getName() === OneToOne::class || $mapping->getName() === ManyToOne::class) {
                        $targetClass = $prop->getType()->getName(); // @phpstan-ignore method.notFound
                    }
                    else {
                        $targetClass = $mapping->getArguments()['targetEntity'] ?? $mapping->getArguments()[0] ?? null;
                        if (! $targetClass) {
                            throw new BadMethodCallException('Not defined targetEntity for property "' . $field . '"');
                        }
                    }
                    $value = self::createFakeObject( // @phpstan-ignore argument.templateType
                        $targetClass,
                        $value,
                        $mapping->getName() === OneToMany::class
                    );
                }
                $prop->setValue($obj, $value);
            }
            if (! $collectionMode) {
                return $obj;
            }
            $collection->add($obj);
        }

        return $collection;
    }

    /**
     * @param array<string, mixed> $criteria
     * @throws ReflectionException
     */
    private function repositoryMockFindByHelper(array $criteria, object $obj): int
    {
        $matchCount = 0;
        foreach ($criteria as $field => $value) {
            $reflection = new ReflectionProperty($obj, $field);
            if ($reflection->isInitialized($obj) === false) {
                continue;
            }
            $fieldValue = $reflection->getValue($obj);
            if (is_object($value)) {
                if (
                    $value instanceof DateTime &&
                    $fieldValue instanceof DateTime &&
                    $value == $fieldValue
                ) {
                    $matchCount++;
                    continue;
                }
                if (
                    is_object($fieldValue)
                    && get_class($value) === get_class($fieldValue)
                    && method_exists($value, 'getId')
                    && method_exists($fieldValue, 'getId')
                    && $value->getId() === $fieldValue->getId()
                ) {
                    $matchCount++;
                }
                continue;
            }
            if (is_array($value)) {
                foreach ($value as $v) {
                    if (is_object($v)) {
                        if (
                            $v instanceof DateTime &&
                            $fieldValue instanceof DateTime &&
                            $v == $fieldValue
                        ) {
                            $matchCount++;
                            break;
                        }
                        if (
                            is_object($fieldValue)
                            && get_class($v) === get_class($fieldValue)
                            && method_exists($v, 'getId')
                            && method_exists($fieldValue, 'getId')
                            && $v->getId() === $fieldValue->getId()
                        ) {
                            $matchCount++;
                            break;
                        }
                        continue;
                    }
                    if ($fieldValue === $v) {
                        $matchCount++;
                        break;
                    }
                }
                continue;
            }
            if ($fieldValue === $value) {
                $matchCount++;
            }
        }

        return $matchCount;
    }
}
