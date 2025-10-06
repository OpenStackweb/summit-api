<?php namespace App\libs\Utils\Doctrine;
/*
 * Copyright 2025 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
*/
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;

/**
 * Generic helpers to load a root entity plus a flexible set of relations.
 * - Direct ToOne relations are joined in the root query.
 * - Each requested top-level ToMany relation is loaded in a separate query.
 * - Nested ToOne under a top-level collection (dot-notation) are joined in that same per-collection query.
 */
trait GraphLoaderTrait
{
    /**
     * Normalize user-provided relations (trim, unique, remove empties).
     */
    protected function normalizeRelations(array $relations): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn($r) => trim((string)$r),
            $relations
        ))));
    }

    /**
     * Partition requested relations into:
     *  - $toOneDirect: direct ToOne on the root entity
     *  - $topCollections: top-level ToMany relations on the root entity
     *  - $nestedByCollection: nested ToOne under a top-level collection (dot-notation)
     *
     * @return array{toOneDirect: string[], topCollections: string[], nestedByCollection: array<string, string[]>}
     */
    protected function partitionRelations(ClassMetadata $meta, array $relations): array
    {
        $toOneDirect        = [];
        $topCollections     = [];
        $nestedByCollection = [];

        foreach ($relations as $r) {
            $parts = explode('.', $r);
            $top   = $parts[0];

            if (!isset($meta->associationMappings[$top])) {
                // Not a mapped association on the root entity; skip.
                continue;
            }

            $type = $meta->associationMappings[$top]['type'] ?? null;

            if (count($parts) === 1) {
                // Direct relation on root
                if (in_array($type, [ClassMetadata::MANY_TO_ONE, ClassMetadata::ONE_TO_ONE], true)) {
                    $toOneDirect[] = $top;
                } else {
                    // ONE_TO_MANY or MANY_TO_MANY
                    $topCollections[] = $top;
                }
            } else {
                // Dot-notation: top is a collection; the rest are assumed ToOne
                $topCollections[]                     = $top;
                $nestedByCollection[$top] = array_merge(
                    $nestedByCollection[$top] ?? [],
                    array_slice($parts, 1)
                );
            }
        }

        // De-duplicate
        $toOneDirect        = array_values(array_unique($toOneDirect));
        $topCollections     = array_values(array_unique($topCollections));
        foreach ($nestedByCollection as $k => $arr) {
            $nestedByCollection[$k] = array_values(array_unique($arr));
        }

        return compact('toOneDirect', 'topCollections', 'nestedByCollection');
    }

    /**
     * Build and execute the root query:
     *  - SELECT root alias
     *  - LEFT JOIN + addSelect for each direct ToOne relation
     *
     * Returns the hydrated root entity or null.
     */
    protected function loadRootWithToOne(
        EntityManagerInterface $em,
        string $entityClass,
        array $toOneDirect,
        callable $whereConfigurator // function(QueryBuilder $qb, string $rootAlias): void
    ): ?object {
        $qb = $em->createQueryBuilder();
        $qb->select('r')
            ->from($entityClass, 'r');

        foreach ($toOneDirect as $rel) {
            $alias = 'r_' . $rel;
            $qb->leftJoin("r.$rel", $alias)->addSelect($alias);
        }

        // Let caller apply WHERE / params
        $whereConfigurator($qb, 'r');

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * For a given root id, load exactly one top-level collection and optional nested ToOne under items of that collection.
     * Example: root -> badgeFeatureTypes (collection) -> file (ToOne)
     *
     * This hydrates into the current UnitOfWork; no need to assign the result.
     */
    protected function hydrateCollectionWithNestedToOne(
        EntityManagerInterface $em,
        string $entityClass,
        string|int $rootId,
        string $collection,
        array $nestedToOne = []
    ): void {
        $rootAlias = 'r2';
        $colAlias  = 'c2_' . $collection;

        $selects = [$rootAlias, $colAlias];
        $joins   = ["LEFT JOIN $rootAlias.$collection $colAlias"];

        foreach ($nestedToOne as $nestedRel) {
            $nestedAlias = $colAlias . '_' . $nestedRel;
            $joins[]     = "LEFT JOIN $colAlias.$nestedRel $nestedAlias";
            $selects[]   = $nestedAlias;
        }

        $dql = sprintf(
            'SELECT DISTINCT %s FROM %s %s %s WHERE %s.id = :id',
            implode(', ', $selects),
            $entityClass,
            $rootAlias,
            implode(' ', $joins),
            $rootAlias
        );

        $em->createQuery($dql)
            ->setParameter('id', $rootId)
            ->getOneOrNullResult();
    }

    /**
     * High-level convenience: load a root entity by an arbitrary field using a where configurator,
     * plus a flexible graph (ToOne direct + one query per requested top-level collection).
     *
     * Returns the root entity or null.
     */
    protected function loadGraphBy(
        EntityManagerInterface $em,
        string $entityClass,
        array $relations,
        callable $whereConfigurator // function(QueryBuilder $qb, string $rootAlias): void
    ): ?object {
        $meta       = $em->getClassMetadata($entityClass);
        $relations  = $this->normalizeRelations($relations);
        $partitions = $this->partitionRelations($meta, $relations);

        // 1) Root + direct ToOne
        $root = $this->loadRootWithToOne(
            $em,
            $entityClass,
            $partitions['toOneDirect'],
            $whereConfigurator
        );

        if (!$root) {
            return null;
        }

        // 2) Per collection query (+ optional nested ToOne)
        $idField = $meta->getSingleIdentifierFieldName();
        $getter  = 'get' . ucfirst($idField);
        $rootId  = $root->$getter();

        foreach ($partitions['topCollections'] as $collection) {
            // Defensive: ensure mapping still exists
            if (!isset($meta->associationMappings[$collection])) {
                continue;
            }

            $nested = $partitions['nestedByCollection'][$collection] ?? [];
            $this->hydrateCollectionWithNestedToOne(
                $em,
                $entityClass,
                $rootId,
                $collection,
                $nested
            );
        }

        return $root;
    }
}
