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
use Doctrine\ORM\QueryBuilder;
use Illuminate\Support\Facades\Log;

/**
 * Generic helpers to load a root entity plus a flexible set of relations.
 *
 * Single-entity path (loadGraphBy):
 *   - Direct ToOne relations are joined in the root query.
 *   - Each requested top-level ToMany relation is loaded in a separate query.
 *   - Nested relations (dot-notation) are joined at any depth.
 *
 * Batch/list path (addExpandFetchJoins + batchLoadExpandedRelations):
 *   - ToOne associations are fetch-joined in the hydration query.
 *   - ToMany collections are batch-loaded per collection.
 *   - Nested relations (dot-notation) are recursively batch-loaded at any depth.
 *   - Uses ClassMetadata to auto-detect association types and entity ownership.
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

    // -----------------------------------------------------------------------
    // Internal DQL helpers
    // -----------------------------------------------------------------------

    /**
     * Build DQL LEFT JOIN chains from dot-notation paths.
     * Returns [$selects, $joins] arrays ready to be appended to a DQL query.
     *
     * For paths like ['affiliations', 'affiliations.organization'], builds:
     *   LEFT JOIN parent.affiliations alias_affiliations
     *   LEFT JOIN alias_affiliations.organization alias_affiliations_organization
     *
     * Deduplicates joins automatically (safe to call with overlapping paths).
     */
    private function buildNestedJoinChain(string $parentAlias, array $nestedPaths): array
    {
        $selects  = [];
        $joins    = [];
        $aliasMap = [];

        foreach ($nestedPaths as $path) {
            $segments      = explode('.', $path);
            $currentPrefix = '';
            $currentParent = $parentAlias;

            foreach ($segments as $segment) {
                $currentPrefix = $currentPrefix === '' ? $segment : $currentPrefix . '.' . $segment;

                if (isset($aliasMap[$currentPrefix])) {
                    $currentParent = $aliasMap[$currentPrefix];
                    continue;
                }

                $alias = $parentAlias . '_' . str_replace('.', '_', $currentPrefix);
                $joins[]   = "LEFT JOIN {$currentParent}.{$segment} {$alias}";
                $selects[] = $alias;
                $aliasMap[$currentPrefix] = $alias;
                $currentParent = $alias;
            }
        }

        return [$selects, $joins];
    }

    /**
     * Resolve a serializer expand name to a Doctrine field name and find which
     * entity class in the hierarchy owns it.
     *
     * @return array{string, string, int}|null  [ownerClass, doctrineField, assocType] or null
     */
    private function resolveExpandAssociation(
        string $expandName,
        ClassMetadata $baseMeta,
        string $baseEntityClass,
        array $subMetas,
        array $expandFieldMap
    ): ?array {
        $doctrineField = $expandFieldMap[$expandName] ?? $expandName;

        if (isset($baseMeta->associationMappings[$doctrineField])) {
            return [
                $baseEntityClass,
                $doctrineField,
                $baseMeta->associationMappings[$doctrineField]['type'],
            ];
        }

        foreach ($subMetas as $subClass => $subMeta) {
            if (isset($subMeta->associationMappings[$doctrineField])) {
                return [
                    $subClass,
                    $doctrineField,
                    $subMeta->associationMappings[$doctrineField]['type'],
                ];
            }
        }

        return null;
    }

    // -----------------------------------------------------------------------
    // Single-entity loading path
    // -----------------------------------------------------------------------

    /**
     * Partition requested relations into:
     *  - $toOneDirect: direct ToOne on the root entity
     *  - $topCollections: top-level ToMany relations on the root entity
     *  - $nestedByCollection: nested paths under a top-level collection (dot-notation, any depth)
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
                continue;
            }

            $type = $meta->associationMappings[$top]['type'] ?? null;

            if (count($parts) === 1) {
                if (in_array($type, [ClassMetadata::MANY_TO_ONE, ClassMetadata::ONE_TO_ONE], true)) {
                    $toOneDirect[] = $top;
                } else {
                    $topCollections[] = $top;
                }
            } else {
                // Dot-notation: top is a collection; store the remaining path as a single string
                $topCollections[]           = $top;
                $remainingPath              = implode('.', array_slice($parts, 1));
                $nestedByCollection[$top][] = $remainingPath;
            }
        }

        $toOneDirect    = array_values(array_unique($toOneDirect));
        $topCollections = array_values(array_unique($topCollections));
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
        callable $whereConfigurator
    ): ?object {
        $qb = $em->createQueryBuilder();
        $qb->select('r')
            ->from($entityClass, 'r');

        foreach ($toOneDirect as $rel) {
            $alias = 'r_' . $rel;
            $qb->leftJoin("r.$rel", $alias)->addSelect($alias);
        }

        $whereConfigurator($qb, 'r');

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * For a given root id, load exactly one top-level collection and optional
     * nested relations under items of that collection (supports multi-level dot-paths).
     *
     * This hydrates into the current UnitOfWork; no need to assign the result.
     */
    protected function hydrateCollectionWithNested(
        EntityManagerInterface $em,
        string $entityClass,
        string|int $rootId,
        string $collection,
        array $nestedPaths = []
    ): void {
        $rootAlias = 'r2';
        $colAlias  = 'c2_' . $collection;

        $selects = [$rootAlias, $colAlias];
        $joins   = ["LEFT JOIN $rootAlias.$collection $colAlias"];

        if (!empty($nestedPaths)) {
            [$nestedSelects, $nestedJoins] = $this->buildNestedJoinChain($colAlias, $nestedPaths);
            $selects = array_merge($selects, $nestedSelects);
            $joins   = array_merge($joins, $nestedJoins);
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
     * High-level convenience: load a root entity by an arbitrary field,
     * plus a flexible graph (ToOne direct + one query per requested top-level collection).
     * Supports multi-level nested relations via dot-notation.
     *
     * Returns the root entity or null.
     */
    protected function loadGraphBy(
        EntityManagerInterface $em,
        string $entityClass,
        array $relations,
        callable $whereConfigurator
    ): ?object {
        $meta       = $em->getClassMetadata($entityClass);
        $relations  = $this->normalizeRelations($relations);
        $partitions = $this->partitionRelations($meta, $relations);

        $root = $this->loadRootWithToOne(
            $em, $entityClass, $partitions['toOneDirect'], $whereConfigurator
        );

        if (!$root) {
            return null;
        }

        $idField = $meta->getSingleIdentifierFieldName();
        $getter  = 'get' . ucfirst($idField);
        $rootId  = $root->$getter();

        foreach ($partitions['topCollections'] as $collection) {
            if (!isset($meta->associationMappings[$collection])) {
                continue;
            }

            $nested = $partitions['nestedByCollection'][$collection] ?? [];
            $this->hydrateCollectionWithNested(
                $em, $entityClass, $rootId, $collection, $nested
            );
        }

        return $root;
    }

    // -----------------------------------------------------------------------
    // Batch/list loading path
    // -----------------------------------------------------------------------

    /**
     * Batch-load a single collection (+ optional nested relations at any depth)
     * for multiple root IDs at once. Results hydrate into the UnitOfWork.
     */
    protected function batchHydrateCollections(
        EntityManagerInterface $em,
        string $entityClass,
        array $rootIds,
        string $collection,
        array $nestedPaths = []
    ): void {
        if (empty($rootIds)) return;

        $rootAlias = 'r2';
        $colAlias  = 'c2_' . $collection;

        $selects = [$rootAlias, $colAlias];
        $joins   = ["LEFT JOIN $rootAlias.$collection $colAlias"];

        if (!empty($nestedPaths)) {
            [$nestedSelects, $nestedJoins] = $this->buildNestedJoinChain($colAlias, $nestedPaths);
            $selects = array_merge($selects, $nestedSelects);
            $joins   = array_merge($joins, $nestedJoins);
        }

        $dql = sprintf(
            'SELECT DISTINCT %s FROM %s %s %s WHERE %s.id IN (:ids)',
            implode(', ', $selects),
            $entityClass,
            $rootAlias,
            implode(' ', $joins),
            $rootAlias
        );

        try {
            $em->createQuery($dql)
                ->setParameter('ids', $rootIds)
                ->getResult();
        } catch (\Exception $ex) {
            Log::warning("GraphLoaderTrait::batchHydrateCollections failed for {$entityClass}::{$collection}", [
                'error' => $ex->getMessage(),
            ]);
        }
    }

    /**
     * Adds LEFT JOIN + addSelect for each requested toOne expand,
     * using ClassMetadata to auto-detect association type and owner entity.
     *
     * @param EntityManagerInterface $em
     * @param QueryBuilder $query
     * @param string[] $expands           Serializer expand names from the API
     * @param string $rootAlias           Root entity alias in the query
     * @param string $baseEntityClass     Root entity FQCN
     * @param array $expandFieldMap       Maps serializer name => Doctrine field (only for mismatches)
     * @param array $subclassAliases      Maps entity FQCN => query alias (e.g., Presentation::class => 'p')
     * @param array $skipFields           Doctrine fields already joined in the query
     * @return QueryBuilder
     */
    protected function addExpandFetchJoins(
        EntityManagerInterface $em,
        QueryBuilder $query,
        array $expands,
        string $rootAlias,
        string $baseEntityClass,
        array $expandFieldMap = [],
        array $subclassAliases = [],
        array $skipFields = []
    ): QueryBuilder {
        $baseMeta = $em->getClassMetadata($baseEntityClass);
        $subMetas = [];
        foreach ($baseMeta->subClasses as $subClass) {
            $subMetas[$subClass] = $em->getClassMetadata($subClass);
        }

        $joined = [];
        foreach ($expands as $expand) {
            if (str_contains($expand, '.')) continue;

            $resolved = $this->resolveExpandAssociation(
                $expand, $baseMeta, $baseEntityClass, $subMetas, $expandFieldMap
            );
            if ($resolved === null) continue;

            [$ownerClass, $doctrineField, $assocType] = $resolved;

            if (in_array($doctrineField, $skipFields, true)) continue;
            if (!in_array($assocType, [ClassMetadata::MANY_TO_ONE, ClassMetadata::ONE_TO_ONE], true)) continue;

            $joinKey = $ownerClass . '::' . $doctrineField;
            if (isset($joined[$joinKey])) continue;
            $joined[$joinKey] = true;

            $alias = 'exp_' . $doctrineField;
            if (in_array($alias, $query->getAllAliases(), true)) continue;

            $root = $subclassAliases[$ownerClass] ?? $rootAlias;
            $query->leftJoin("{$root}.{$doctrineField}", $alias)->addSelect($alias);
        }

        return $query;
    }

    /**
     * Main entry point for batch loading expanded relations after the hydration query.
     * Handles toMany collections (level 1) and nested relations (level 2+) at any depth.
     *
     * Uses ClassMetadata to auto-detect association types and entity ownership
     * in the class hierarchy.
     *
     * @param EntityManagerInterface $em
     * @param object[] $entities           The hydrated root entities
     * @param string[] $expands            Serializer expand names (may contain dots for nesting)
     * @param string $baseEntityClass      Root entity FQCN
     * @param array $expandFieldMap        Maps serializer name => Doctrine field (only for mismatches)
     * @param array $childEntityResolvers  Maps expand path => callable(collectionItem): ?object
     *                                     For unwrapping collection items (e.g., assignment => speaker).
     *                                     Keys use expand dot-paths: 'speakers' (level 1),
     *                                     'speakers.wrapped_items' (level 2+).
     * @param array $nestedFieldOverrides  Maps expand dot-path => Doctrine field name
     *                                     for nested levels where serializer names differ.
     *                                     E.g., 'speakers.speaker_affiliations' => 'affiliations'
     */
    protected function batchLoadExpandedRelations(
        EntityManagerInterface $em,
        array $entities,
        array $expands,
        string $baseEntityClass,
        array $expandFieldMap = [],
        array $childEntityResolvers = [],
        array $nestedFieldOverrides = []
    ): void {
        if (empty($entities) || empty($expands)) return;

        $baseMeta = $em->getClassMetadata($baseEntityClass);
        $subMetas = [];
        foreach ($baseMeta->subClasses as $subClass) {
            $subMetas[$subClass] = $em->getClassMetadata($subClass);
        }

        // Separate immediate (level 1) from nested (dot-notation) expands
        $immediateExpands = [];
        $nestedExpands    = [];

        foreach ($expands as $expand) {
            if (str_contains($expand, '.')) {
                $nestedExpands[] = $expand;
                // Ensure the top-level collection is also batch-loaded
                $topName = explode('.', $expand)[0];
                $immediateExpands[] = $topName;
            } else {
                $immediateExpands[] = $expand;
            }
        }
        $immediateExpands = array_values(array_unique($immediateExpands));

        // Classify immediate expands and collect toMany batches
        $toManyBatches = []; // entityClass => [doctrineField => true]

        foreach ($immediateExpands as $expand) {
            $resolved = $this->resolveExpandAssociation(
                $expand, $baseMeta, $baseEntityClass, $subMetas, $expandFieldMap
            );
            if ($resolved === null) continue;

            [$ownerClass, $doctrineField, $assocType] = $resolved;

            // Only batch-load toMany (toOne already handled by addExpandFetchJoins)
            if (in_array($assocType, [ClassMetadata::MANY_TO_ONE, ClassMetadata::ONE_TO_ONE], true)) continue;

            $toManyBatches[$ownerClass][$doctrineField] = true;
        }

        // Partition entity IDs by class
        $idsByClass = [$baseEntityClass => []];
        foreach (array_keys($subMetas) as $subClass) {
            $idsByClass[$subClass] = [];
        }
        foreach ($entities as $entity) {
            $id = $entity->getId();
            $idsByClass[$baseEntityClass][] = $id;
            foreach (array_keys($subMetas) as $subClass) {
                if ($entity instanceof $subClass) {
                    $idsByClass[$subClass][] = $id;
                }
            }
        }

        // Execute batch toMany queries
        foreach ($toManyBatches as $entityClass => $fields) {
            $ids = $idsByClass[$entityClass] ?? [];
            if (empty($ids)) continue;

            foreach (array_keys($fields) as $field) {
                try {
                    $em->createQueryBuilder()
                        ->select('root', 'col')
                        ->from($entityClass, 'root')
                        ->leftJoin("root.{$field}", 'col')
                        ->where('root.id IN (:ids)')
                        ->setParameter('ids', $ids)
                        ->getQuery()
                        ->getResult();
                } catch (\Exception $ex) {
                    Log::warning("GraphLoaderTrait::batchLoadExpandedRelations failed for {$entityClass}::{$field}", [
                        'error' => $ex->getMessage(),
                    ]);
                }
            }
        }

        // Handle nested relations (level 2+, recursive)
        if (!empty($nestedExpands)) {
            $this->batchLoadNestedExpands(
                $em, $entities, $nestedExpands, $baseEntityClass,
                $expandFieldMap, $childEntityResolvers, $nestedFieldOverrides
            );
        }
    }

    /**
     * Recursively batch-loads nested (dot-notation) relations at any depth.
     *
     * Given expands like ['speakers.member', 'speakers.affiliations', 'speakers.affiliations.organization']:
     * 1. Groups by top-level name ('speakers')
     * 2. Collects child entities from the 'speakers' collection
     * 3. Batch-loads immediate nested relations ('member', 'affiliations')
     * 4. Recursively processes deeper paths ('affiliations.organization')
     *
     * Both $nestedFieldOverrides and $childEntityResolvers use the full expand
     * dot-path as the key (e.g., 'speakers.affiliations' or 'speakers.affiliations.organization').
     * At the first recursion level, simple names are also checked as a fallback.
     */
    private function batchLoadNestedExpands(
        EntityManagerInterface $em,
        array $entities,
        array $nestedExpands,
        string $baseEntityClass,
        array $expandFieldMap,
        array $childEntityResolvers,
        array $nestedFieldOverrides,
        int $maxDepth = 10,
        string $pathPrefix = ''
    ): void {
        if (empty($entities) || empty($nestedExpands) || $maxDepth <= 0) return;

        // Group by top-level expand name
        $groups = [];
        foreach ($nestedExpands as $expand) {
            $parts     = explode('.', $expand, 2);
            $topName   = $parts[0];
            $remaining = $parts[1] ?? null;

            if (!isset($groups[$topName])) {
                $groups[$topName] = [];
            }
            if ($remaining !== null) {
                $groups[$topName][] = $remaining;
            }
        }

        // Get metadata for base and subclasses
        $baseMeta = $em->getClassMetadata($baseEntityClass);
        $subMetas = [];
        foreach ($baseMeta->subClasses as $subClass) {
            $subMetas[$subClass] = $em->getClassMetadata($subClass);
        }

        foreach ($groups as $topName => $remainingPaths) {
            // Build full expand path for override/resolver lookups
            $fullTopPath = $pathPrefix . $topName;

            // Resolve Doctrine field name for the top-level expand
            // Check expandFieldMap first (level 1), then nestedFieldOverrides by path (level 2+)
            $doctrineField = $expandFieldMap[$topName]
                ?? $nestedFieldOverrides[$fullTopPath]
                ?? $topName;

            // Find which entity class owns this association
            $ownerClass = null;
            if (isset($baseMeta->associationMappings[$doctrineField])) {
                $ownerClass = $baseEntityClass;
            } else {
                foreach ($subMetas as $subClass => $subMeta) {
                    if (isset($subMeta->associationMappings[$doctrineField])) {
                        $ownerClass = $subClass;
                        break;
                    }
                }
            }

            if ($ownerClass === null) continue;

            // Collect child entities from the collection
            // Check full path first (level 2+), then simple name (level 1 compat)
            $resolver = $childEntityResolvers[$fullTopPath]
                ?? $childEntityResolvers[$topName]
                ?? null;
            $getter   = 'get' . ucfirst($doctrineField);
            $childEntities = [];

            foreach ($entities as $rootEntity) {
                if (!method_exists($rootEntity, $getter)) continue;

                try {
                    $collection = $rootEntity->$getter();
                } catch (\Exception $ex) {
                    continue;
                }

                if ($collection === null) continue;

                $items = is_iterable($collection) ? $collection : [$collection];
                foreach ($items as $item) {
                    $child = $resolver ? $resolver($item) : $item;
                    if ($child !== null && is_object($child)) {
                        $oid = spl_object_id($child);
                        $childEntities[$oid] = $child;
                    }
                }
            }

            if (empty($childEntities)) continue;

            // Determine child entity class and metadata
            $firstChild = reset($childEntities);
            $childClass = get_class($firstChild);

            try {
                $childMeta = $em->getClassMetadata($childClass);
            } catch (\Exception $ex) {
                Log::warning("GraphLoaderTrait::batchLoadNestedExpands cannot get metadata for {$childClass}", [
                    'error' => $ex->getMessage(),
                ]);
                continue;
            }

            // Collect unique child IDs
            $childIds = [];
            foreach ($childEntities as $child) {
                if (method_exists($child, 'getId') && $child->getId() !== null) {
                    $childIds[] = $child->getId();
                }
            }
            $childIds = array_values(array_unique($childIds));
            if (empty($childIds)) continue;

            // Extract immediate names (this level) and deeper paths (next levels)
            $immediateNames = [];
            $deeperPaths    = [];

            foreach ($remainingPaths as $path) {
                $pathParts = explode('.', $path);
                $immediateNames[] = $pathParts[0];
                if (count($pathParts) > 1) {
                    $deeperPaths[] = $path;
                }
            }
            $immediateNames = array_values(array_unique($immediateNames));

            // Classify immediate names using child's ClassMetadata
            $nestedToOne  = [];
            $nestedToMany = [];

            foreach ($immediateNames as $nestedName) {
                // Resolve Doctrine field name using full expand path
                $fullNestedPath = $fullTopPath . '.' . $nestedName;
                $nestedDocField = $nestedFieldOverrides[$fullNestedPath] ?? $nestedName;

                if (!isset($childMeta->associationMappings[$nestedDocField])) continue;

                $assocType = $childMeta->associationMappings[$nestedDocField]['type'];
                if (in_array($assocType, [ClassMetadata::MANY_TO_ONE, ClassMetadata::ONE_TO_ONE], true)) {
                    $nestedToOne[] = $nestedDocField;
                } else {
                    $nestedToMany[] = $nestedDocField;
                }
            }

            // Batch fetch-join toOne relations in a single query
            $nestedToOne = array_values(array_unique($nestedToOne));
            if (!empty($nestedToOne)) {
                try {
                    $qb = $em->createQueryBuilder()
                        ->select('child')
                        ->from($childClass, 'child')
                        ->where('child.id IN (:ids)')
                        ->setParameter('ids', $childIds);

                    foreach ($nestedToOne as $rel) {
                        $alias = 'n_' . $rel;
                        $qb->leftJoin("child.{$rel}", $alias)->addSelect($alias);
                    }

                    $qb->getQuery()->getResult();
                } catch (\Exception $ex) {
                    Log::warning("GraphLoaderTrait::batchLoadNestedExpands toOne failed for {$childClass}", [
                        'error'     => $ex->getMessage(),
                        'relations' => $nestedToOne,
                    ]);
                }
            }

            // Batch-load each toMany collection separately
            $nestedToMany = array_values(array_unique($nestedToMany));
            foreach ($nestedToMany as $collectionField) {
                $this->batchHydrateCollections($em, $childClass, $childIds, $collectionField);
            }

            // Recurse for deeper levels (level 3+)
            if (!empty($deeperPaths)) {
                $this->batchLoadNestedExpands(
                    $em,
                    array_values($childEntities),
                    $deeperPaths,
                    $childClass,
                    [],  // No expandFieldMap at deeper levels (use nestedFieldOverrides instead)
                    $childEntityResolvers,
                    $nestedFieldOverrides,
                    $maxDepth - 1,
                    $fullTopPath . '.'
                );
            }
        }
    }
}
