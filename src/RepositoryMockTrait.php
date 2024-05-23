<?php

declare(strict_types=1);

namespace App\RepositoryMock;

use BadMethodCallException;
use DateTime;
// use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Driver\AttributeReader;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use OutOfBoundsException;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use function is_array;

trait RepositoryMockTrait
{
    /**
     * @template T
     * @param class-string<T> $className
     * @param array $objData
     * @param boolean $collectionMode
     * @return T|T[]
     *
     * @throws ReflectionException
     */
    private function createFakeObject(string $className, array $objData, bool $collectionMode = false): mixed
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
                        $targetClass = $prop->getType()->getName();
                    }
                    else {
                        $targetClass = $mapping->getArguments()['targetEntity'] ?? $mapping->getArguments()[0] ?? null;
                        if (! $targetClass) {
                            throw new BadMethodCallException('Not defined targetEntity for property "' . $field . '"');
                        }
                    }
                    $value = $this->createFakeObject(
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
