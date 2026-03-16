<?php

namespace Tests\OpenTelemetry\Formatters\Support;

/**
 * Copyright 2026 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use ReflectionClass;

class PersistentCollectionTestHelper
{
    public static function buildManyToManyCollection(
        string $entityClass,
        string $fieldName,
        string $targetEntity,
        array $snapshotIds = [],
        array $currentIds = []
    ): PersistentCollection {
        $entityManager = \Mockery::mock(EntityManager::class);

        $metadata = new ClassMetadata($entityClass);
        $metadata->mapManyToMany([
            'fieldName' => $fieldName,
            'targetEntity' => $targetEntity,
        ]);

        $association = $metadata->associationMappings[$fieldName];

        $entityPool = self::createEntityPool(array_merge($snapshotIds, $currentIds));

        $collection = new PersistentCollection(
            $entityManager,
            $metadata,
            new ArrayCollection(self::entitiesFromPool($snapshotIds, $entityPool))
        );

        $collection->setOwner(new \stdClass(), $association);
        $collection->takeSnapshot();

        self::replaceCollectionItems(
            $collection,
            new ArrayCollection(self::entitiesFromPool($currentIds, $entityPool))
        );

        return $collection;
    }

    private static function createEntityPool(array $ids): array
    {
        $pool = [];
        foreach (array_values(array_unique($ids)) as $id) {
            $pool[$id] = new class($id) {
                public function __construct(private int $id) {}
                public function getId(): int
                {
                    return $this->id;
                }
            };
        }
        return $pool;
    }

    private static function entitiesFromPool(array $ids, array $pool): array
    {
        $entities = [];
        foreach ($ids as $id) {
            if (isset($pool[$id])) {
                $entities[] = $pool[$id];
            }
        }
        return $entities;
    }

    private static function replaceCollectionItems(PersistentCollection $collection, ArrayCollection $items): void
    {
        $reflection = new ReflectionClass(PersistentCollection::class);
        $wrapped = $reflection->getProperty('collection');
        $wrapped->setAccessible(true);
        $wrapped->setValue($collection, $items);
    }
}
