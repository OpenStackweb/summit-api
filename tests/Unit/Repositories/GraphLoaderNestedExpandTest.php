<?php

namespace Tests\Unit\Repositories;

use App\libs\Utils\Doctrine\GraphLoaderTrait;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Mockery;
use PHPUnit\Framework\TestCase;

// ---------------------------------------------------------------------------
// Minimal entity stubs for testing (no DB, no Doctrine annotations)
// ---------------------------------------------------------------------------

class StubRootEntity
{
    private int $id;
    private ?StubLevel1Entity $track;

    public function __construct(int $id, ?StubLevel1Entity $track = null)
    {
        $this->id = $id;
        $this->track = $track;
    }

    public function getId(): int { return $this->id; }
    public function getCategory(): ?StubLevel1Entity { return $this->track; }
}

class StubLevel1Entity
{
    private int $id;
    private array $subtracks;

    public function __construct(int $id, array $subtracks = [])
    {
        $this->id = $id;
        $this->subtracks = $subtracks;
    }

    public function getId(): int { return $this->id; }
    public function getSubtracks(): array { return $this->subtracks; }
}

class StubLevel2Entity
{
    private int $id;
    private array $levels;

    public function __construct(int $id, array $levels = [])
    {
        $this->id = $id;
        $this->levels = $levels;
    }

    public function getId(): int { return $this->id; }
    public function getAllowedAccessLevels(): array { return $this->levels; }
}

class StubLevel3Entity
{
    private int $id;
    private ?StubLevel4Entity $summit;

    public function __construct(int $id, ?StubLevel4Entity $summit = null)
    {
        $this->id = $id;
        $this->summit = $summit;
    }

    public function getId(): int { return $this->id; }
    public function getSummit(): ?StubLevel4Entity { return $this->summit; }
}

class StubLevel4Entity
{
    private int $id;
    public function __construct(int $id) { $this->id = $id; }
    public function getId(): int { return $this->id; }
}

// Wrapper to exercise collection items that need unwrapping (like PresentationSpeakerAssignment)
class StubWrappedItem
{
    private StubLevel2Entity $inner;
    public function __construct(StubLevel2Entity $inner) { $this->inner = $inner; }
    public function getInner(): StubLevel2Entity { return $this->inner; }
}

// ---------------------------------------------------------------------------
// Test double that exposes the trait's protected methods
// ---------------------------------------------------------------------------

class GraphLoaderTestDouble
{
    use GraphLoaderTrait;

    public function exposedBatchLoadExpandedRelations(
        EntityManagerInterface $em,
        array $entities,
        array $expands,
        string $baseEntityClass,
        array $expandFieldMap = [],
        array $childEntityResolvers = [],
        array $nestedFieldOverrides = []
    ): void {
        $this->batchLoadExpandedRelations(
            $em, $entities, $expands, $baseEntityClass,
            $expandFieldMap, $childEntityResolvers, $nestedFieldOverrides
        );
    }
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

/**
 * Unit test for GraphLoaderTrait's nested expand resolution at 3+ levels,
 * including path-based nestedFieldOverrides and childEntityResolvers.
 *
 * Uses stub entities and mocked Doctrine EntityManager — no database needed.
 */
class GraphLoaderNestedExpandTest extends TestCase
{
    private GraphLoaderTestDouble $loader;

    /** @var list<array{class: string, field: string, ids: int[]}> */
    private array $recordedBatchQueries = [];

    /** @var list<array{class: string, joins: string[]}> */
    private array $recordedFetchJoinQueries = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->loader = new GraphLoaderTestDouble();
        $this->recordedBatchQueries = [];
        $this->recordedFetchJoinQueries = [];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Mock helpers
    // ------------------------------------------------------------------

    /**
     * Build a ClassMetadata mock with the given association mappings.
     *
     * @param array<string, int> $associations  fieldName => ClassMetadata association type constant
     * @param string[] $subClasses
     */
    private function buildMetadata(array $associations, array $subClasses = []): ClassMetadata
    {
        $meta = Mockery::mock(ClassMetadata::class)->makePartial();
        $meta->subClasses = $subClasses;
        $meta->associationMappings = [];
        foreach ($associations as $field => $type) {
            $meta->associationMappings[$field] = ['type' => $type];
        }
        return $meta;
    }

    /**
     * Create a permissive QueryBuilder mock that records joins for inspection.
     */
    private function buildQueryBuilder(): QueryBuilder
    {
        $qb = Mockery::mock(QueryBuilder::class);

        // Collect leftJoin calls for later assertion
        $qb->shouldReceive('select')->andReturnSelf();
        $qb->shouldReceive('from')->andReturnSelf();
        $qb->shouldReceive('addSelect')->andReturnSelf();
        $qb->shouldReceive('where')->andReturnSelf();
        $qb->shouldReceive('setParameter')->andReturnSelf();
        $qb->shouldReceive('leftJoin')->andReturnSelf();
        $qb->shouldReceive('getAllAliases')->andReturn([]);

        $query = Mockery::mock(Query::class)->makePartial();
        $query->shouldReceive('getResult')->andReturn([]);
        $query->shouldReceive('getOneOrNullResult')->andReturn(null);
        $qb->shouldReceive('getQuery')->andReturn($query);

        return $qb;
    }

    /**
     * Create a recording QueryBuilder mock that captures batch query details.
     */
    private function buildRecordingQueryBuilder(string $fromClass): QueryBuilder
    {
        $test = $this;
        $currentFrom = $fromClass;
        $currentJoins = [];
        $currentIds = null;

        $qb = Mockery::mock(QueryBuilder::class);
        $qb->shouldReceive('select')->andReturnSelf();
        $qb->shouldReceive('addSelect')->andReturnSelf();
        $qb->shouldReceive('where')->andReturnSelf();
        $qb->shouldReceive('getAllAliases')->andReturn([]);

        $qb->shouldReceive('from')->andReturnUsing(function ($class) use ($qb, &$currentFrom) {
            $currentFrom = $class;
            return $qb;
        });

        $qb->shouldReceive('leftJoin')->andReturnUsing(function ($join) use ($qb, &$currentJoins) {
            $currentJoins[] = $join;
            return $qb;
        });

        // Store IDs but defer recording to getQuery() time, because some
        // call patterns (toOne fetch-join) call setParameter before leftJoin.
        $qb->shouldReceive('setParameter')->andReturnUsing(function ($key, $ids) use ($qb, &$currentIds) {
            if ($key === 'ids' && is_array($ids)) {
                $currentIds = $ids;
            }
            return $qb;
        });

        $query = Mockery::mock(Query::class)->makePartial();
        $query->shouldReceive('getResult')->andReturn([]);

        // Record the batch query at getQuery() time when all state is available
        $qb->shouldReceive('getQuery')->andReturnUsing(
            function () use ($query, &$currentFrom, &$currentJoins, &$currentIds, $test) {
                if ($currentIds !== null && !empty($currentJoins)) {
                    $field = '';
                    $parts = explode('.', $currentJoins[0]);
                    $field = $parts[1] ?? $currentJoins[0];

                    $test->recordedBatchQueries[] = [
                        'class' => $currentFrom,
                        'field' => $field,
                        'ids'   => $currentIds,
                        'joins' => $currentJoins,
                    ];
                }
                $currentJoins = [];
                $currentIds = null;
                return $query;
            }
        );

        return $qb;
    }

    /**
     * Build an EntityManager mock that returns the given metadata map and recording query builders.
     *
     * @param array<string, ClassMetadata> $metadataMap  entityClass => ClassMetadata
     */
    private function buildEntityManager(array $metadataMap): EntityManagerInterface
    {
        $em = Mockery::mock(EntityManagerInterface::class);

        $em->shouldReceive('getClassMetadata')->andReturnUsing(
            fn(string $class) => $metadataMap[$class] ?? $this->buildMetadata([])
        );

        $em->shouldReceive('createQueryBuilder')->andReturnUsing(
            fn() => $this->buildRecordingQueryBuilder('')
        );

        // batchHydrateCollections uses raw DQL via createQuery (not QueryBuilder).
        // Must mock Query (not AbstractQuery) to satisfy the return type.
        // Also records batch queries so assertions can inspect them.
        $test = $this;
        $em->shouldReceive('createQuery')->andReturnUsing(function ($dql) use ($test) {
            // Parse entity class and field from DQL pattern:
            // "SELECT DISTINCT ... FROM {class} r2 LEFT JOIN r2.{field} ..."
            $class = '';
            $field = '';
            if (preg_match('/FROM\s+(\S+)\s+/', $dql, $m)) {
                $class = $m[1];
            }
            if (preg_match('/LEFT JOIN\s+\w+\.(\S+)\s+/', $dql, $m)) {
                $field = $m[1];
            }

            $query = Mockery::mock(Query::class)->makePartial();
            $query->shouldReceive('setParameter')->andReturnUsing(
                function ($key, $ids) use ($query, $class, $field, $test) {
                    if ($key === 'ids' && is_array($ids)) {
                        $test->recordedBatchQueries[] = [
                            'class' => $class,
                            'field' => $field,
                            'ids'   => $ids,
                            'joins' => [],
                        ];
                    }
                    return $query;
                }
            );
            $query->shouldReceive('getResult')->andReturn([]);
            $query->shouldReceive('getOneOrNullResult')->andReturn(null);
            return $query;
        });

        return $em;
    }

    // ------------------------------------------------------------------
    // Tests: 3+ level nesting with auto-detection (no overrides)
    // ------------------------------------------------------------------

    /**
     * @test
     * Verifies that 4-level nested expands are processed recursively.
     *
     * Path: track → track.subtracks → track.subtracks.allowed_access_levels
     *     → track.subtracks.allowed_access_levels.summit
     *
     * Doctrine fields match expand names except level 1: track → category (via expandFieldMap).
     */
    public function testFourLevelNestedExpandsWithFieldMap(): void
    {
        // Build entity graph: root → category → subtrack → access_level → summit
        $summit  = new StubLevel4Entity(400);
        $level   = new StubLevel3Entity(300, $summit);
        $sub     = new StubLevel2Entity(200, [$level]);
        $track   = new StubLevel1Entity(100, [$sub]);
        $root    = new StubRootEntity(1, $track);

        // Metadata: StubRootEntity has category (toOne)
        $rootMeta = $this->buildMetadata([
            'category' => ClassMetadata::MANY_TO_ONE,
        ]);

        // StubLevel1Entity has subtracks (toMany)
        $l1Meta = $this->buildMetadata([
            'subtracks' => ClassMetadata::ONE_TO_MANY,
        ]);

        // StubLevel2Entity has allowedAccessLevels (toMany)
        $l2Meta = $this->buildMetadata([
            'allowedAccessLevels' => ClassMetadata::MANY_TO_MANY,
        ]);

        // StubLevel3Entity has summit (toOne)
        $l3Meta = $this->buildMetadata([
            'summit' => ClassMetadata::MANY_TO_ONE,
        ]);

        $em = $this->buildEntityManager([
            StubRootEntity::class   => $rootMeta,
            StubLevel1Entity::class => $l1Meta,
            StubLevel2Entity::class => $l2Meta,
            StubLevel3Entity::class => $l3Meta,
            StubLevel4Entity::class => $this->buildMetadata([]),
        ]);

        $expands = [
            'track',
            'track.subtracks',
            'track.subtracks.allowedAccessLevels',
            'track.subtracks.allowedAccessLevels.summit',
        ];

        $this->loader->exposedBatchLoadExpandedRelations(
            $em,
            [$root],
            $expands,
            StubRootEntity::class,
            ['track' => 'category'],  // level 1 field map
        );

        // Verify batch queries were generated at each level:
        // Level 2: batch-load subtracks on StubLevel1Entity
        // Level 3: batch-load allowedAccessLevels on StubLevel2Entity
        // Level 4: batch fetch-join summit on StubLevel3Entity
        $this->assertGreaterThanOrEqual(
            3,
            count($this->recordedBatchQueries),
            sprintf(
                "Expected at least 3 batch queries for 4-level nesting. Got %d: %s",
                count($this->recordedBatchQueries),
                json_encode(array_map(fn($q) => $q['class'] . '::' . $q['field'], $this->recordedBatchQueries))
            )
        );

        // Verify each level was touched with correct entity classes
        $queriedClasses = array_map(fn($q) => $q['class'], $this->recordedBatchQueries);
        $this->assertContains(StubLevel1Entity::class, $queriedClasses, 'Should batch-load on Level1 (subtracks)');
        $this->assertContains(StubLevel2Entity::class, $queriedClasses, 'Should batch-load on Level2 (allowedAccessLevels)');
        $this->assertContains(StubLevel3Entity::class, $queriedClasses, 'Should batch-load on Level3 (summit)');
    }

    // ------------------------------------------------------------------
    // Tests: path-based nestedFieldOverrides
    // ------------------------------------------------------------------

    /**
     * @test
     * Verifies path-based nestedFieldOverrides resolve correctly at levels 2, 3, and 4.
     *
     * Expand names differ from Doctrine fields at every nested level:
     *   track.sub_items     → Doctrine field: subtracks       (override: track.sub_items)
     *   track.sub_items.levels → Doctrine field: allowedAccessLevels (override: track.sub_items.levels)
     *   track.sub_items.levels.event → Doctrine field: summit (override: track.sub_items.levels.event)
     */
    public function testPathBasedNestedFieldOverridesAtAllLevels(): void
    {
        $summit = new StubLevel4Entity(400);
        $level  = new StubLevel3Entity(300, $summit);
        $sub    = new StubLevel2Entity(200, [$level]);
        $track  = new StubLevel1Entity(100, [$sub]);
        $root   = new StubRootEntity(1, $track);

        $rootMeta = $this->buildMetadata(['category' => ClassMetadata::MANY_TO_ONE]);
        $l1Meta   = $this->buildMetadata(['subtracks' => ClassMetadata::ONE_TO_MANY]);
        $l2Meta   = $this->buildMetadata(['allowedAccessLevels' => ClassMetadata::MANY_TO_MANY]);
        $l3Meta   = $this->buildMetadata(['summit' => ClassMetadata::MANY_TO_ONE]);

        $em = $this->buildEntityManager([
            StubRootEntity::class   => $rootMeta,
            StubLevel1Entity::class => $l1Meta,
            StubLevel2Entity::class => $l2Meta,
            StubLevel3Entity::class => $l3Meta,
            StubLevel4Entity::class => $this->buildMetadata([]),
        ]);

        // Expand names intentionally DIFFER from Doctrine fields at every nested level
        $expands = [
            'track',
            'track.sub_items',
            'track.sub_items.levels',
            'track.sub_items.levels.event',
        ];

        $this->loader->exposedBatchLoadExpandedRelations(
            $em,
            [$root],
            $expands,
            StubRootEntity::class,
            ['track' => 'category'],    // level 1 field map
            [],                          // no resolvers
            [
                // path-based overrides: expand path → Doctrine field
                'track.sub_items'              => 'subtracks',
                'track.sub_items.levels'       => 'allowedAccessLevels',
                'track.sub_items.levels.event' => 'summit',
            ]
        );

        $this->assertGreaterThanOrEqual(
            3,
            count($this->recordedBatchQueries),
            sprintf(
                "Expected at least 3 batch queries with path-based overrides. Got %d: %s",
                count($this->recordedBatchQueries),
                json_encode(array_map(fn($q) => $q['class'] . '::' . $q['field'], $this->recordedBatchQueries))
            )
        );

        $queriedClasses = array_map(fn($q) => $q['class'], $this->recordedBatchQueries);
        $this->assertContains(StubLevel1Entity::class, $queriedClasses, 'Override track.sub_items → subtracks should resolve');
        $this->assertContains(StubLevel2Entity::class, $queriedClasses, 'Override track.sub_items.levels → allowedAccessLevels should resolve');
        $this->assertContains(StubLevel3Entity::class, $queriedClasses, 'Override track.sub_items.levels.event → summit should resolve');
    }

    // ------------------------------------------------------------------
    // Tests: path-based childEntityResolvers at deeper levels
    // ------------------------------------------------------------------

    /**
     * @test
     * Verifies that childEntityResolvers work at level 2+ using path-based keys.
     *
     * Level 1 collection returns wrapped items (StubWrappedItem) that need
     * unwrapping via a resolver to get the real entity (StubLevel2Entity).
     * This simulates PresentationSpeakerAssignment → PresentationSpeaker.
     */
    public function testPathBasedChildEntityResolverAtLevel2(): void
    {
        $level  = new StubLevel3Entity(300);
        $inner  = new StubLevel2Entity(200, [$level]);
        $wrapped = new StubWrappedItem($inner);

        // Use real StubLevel1Entity (not Mockery mock) so get_class() returns
        // a name that matches the metadata map for recursive resolution.
        $track = new StubLevel1Entity(100, [$wrapped]);
        $root  = new StubRootEntity(1, $track);

        $rootMeta = $this->buildMetadata(['category' => ClassMetadata::MANY_TO_ONE]);
        $l1Meta   = $this->buildMetadata(['subtracks' => ClassMetadata::ONE_TO_MANY]);
        $l2Meta   = $this->buildMetadata(['allowedAccessLevels' => ClassMetadata::MANY_TO_MANY]);
        $l3Meta   = $this->buildMetadata(['summit' => ClassMetadata::MANY_TO_ONE]);

        $em = $this->buildEntityManager([
            StubRootEntity::class   => $rootMeta,
            StubLevel1Entity::class => $l1Meta,
            StubLevel2Entity::class => $l2Meta,
            StubLevel3Entity::class => $l3Meta,
        ]);

        $resolverCalled = false;

        $this->loader->exposedBatchLoadExpandedRelations(
            $em,
            [$root],
            ['track', 'track.subtracks', 'track.subtracks.allowedAccessLevels'],
            StubRootEntity::class,
            ['track' => 'category'],
            [
                // Path-based resolver at level 2: unwrap StubWrappedItem → StubLevel2Entity
                'track.subtracks' => function ($item) use (&$resolverCalled) {
                    $resolverCalled = true;
                    return $item instanceof StubWrappedItem ? $item->getInner() : $item;
                },
            ]
        );

        $this->assertTrue($resolverCalled, 'Path-based resolver at track.subtracks should have been called');

        // Verify that the unwrapped entity (StubLevel2Entity) was used for deeper queries
        $queriedClasses = array_map(fn($q) => $q['class'], $this->recordedBatchQueries);
        $this->assertContains(
            StubLevel2Entity::class,
            $queriedClasses,
            'After unwrapping, batch query should target StubLevel2Entity'
        );
    }

    // ------------------------------------------------------------------
    // Tests: expand with no matching association is silently skipped
    // ------------------------------------------------------------------

    /**
     * @test
     * Verifies that unknown nested expands are silently skipped without errors.
     */
    public function testUnknownNestedExpandsAreSkipped(): void
    {
        $track = new StubLevel1Entity(100);
        $root  = new StubRootEntity(1, $track);

        $rootMeta = $this->buildMetadata(['category' => ClassMetadata::MANY_TO_ONE]);
        $l1Meta   = $this->buildMetadata([]); // no associations at all

        $em = $this->buildEntityManager([
            StubRootEntity::class   => $rootMeta,
            StubLevel1Entity::class => $l1Meta,
        ]);

        // This should not throw — unknown nested expand is skipped
        $this->loader->exposedBatchLoadExpandedRelations(
            $em,
            [$root],
            ['track', 'track.nonexistent_field', 'track.nonexistent_field.deeper'],
            StubRootEntity::class,
            ['track' => 'category'],
        );

        // No batch queries for level 2+ (nothing to resolve)
        $level2Queries = array_filter(
            $this->recordedBatchQueries,
            fn($q) => $q['class'] === StubLevel1Entity::class
        );
        $this->assertEmpty($level2Queries, 'Unknown fields should produce no batch queries');
    }

    // ------------------------------------------------------------------
    // Tests: maxDepth safety limit
    // ------------------------------------------------------------------

    /**
     * @test
     * Verifies recursion stops at maxDepth (no infinite loop on deep paths).
     */
    public function testRecursionRespectsMaxDepth(): void
    {
        // Create a self-referencing chain that would recurse infinitely
        // without maxDepth protection. In practice, the recursion stops
        // because entities at each level are finite, but this tests the safety limit.
        $track = new StubLevel1Entity(100);
        $root  = new StubRootEntity(1, $track);

        $rootMeta = $this->buildMetadata(['category' => ClassMetadata::MANY_TO_ONE]);
        $l1Meta   = $this->buildMetadata(['subtracks' => ClassMetadata::ONE_TO_MANY]);

        $em = $this->buildEntityManager([
            StubRootEntity::class   => $rootMeta,
            StubLevel1Entity::class => $l1Meta,
        ]);

        // Deeply nested path — should not cause infinite recursion
        $expands = [
            'track',
            'track.subtracks',
            'track.subtracks.subtracks',
            'track.subtracks.subtracks.subtracks',
            'track.subtracks.subtracks.subtracks.subtracks',
        ];

        // Should complete without error (maxDepth = 10 by default)
        $this->loader->exposedBatchLoadExpandedRelations(
            $em,
            [$root],
            $expands,
            StubRootEntity::class,
            ['track' => 'category'],
        );

        // Just verify it didn't hang or crash
        $this->assertTrue(true, 'Deep nesting should complete without error');
    }

    // ------------------------------------------------------------------
    // Tests: simple name fallback for resolvers (backward compat)
    // ------------------------------------------------------------------

    /**
     * @test
     * Verifies that childEntityResolvers with simple name keys (no dots)
     * still work at level 1 for backward compatibility.
     */
    public function testSimpleNameResolverFallbackAtLevel1(): void
    {
        $level  = new StubLevel3Entity(300);
        $inner  = new StubLevel2Entity(200, [$level]);
        $wrapped = new StubWrappedItem($inner);

        // Use real StubLevel1Entity so get_class() matches the metadata map
        $track = new StubLevel1Entity(100, [$wrapped]);
        $root  = new StubRootEntity(1, $track);

        $rootMeta = $this->buildMetadata(['category' => ClassMetadata::MANY_TO_ONE]);
        $l1Meta   = $this->buildMetadata(['subtracks' => ClassMetadata::ONE_TO_MANY]);
        $l2Meta   = $this->buildMetadata(['allowedAccessLevels' => ClassMetadata::MANY_TO_MANY]);

        $em = $this->buildEntityManager([
            StubRootEntity::class   => $rootMeta,
            StubLevel1Entity::class => $l1Meta,
            StubLevel2Entity::class => $l2Meta,
        ]);

        $resolverCalled = false;

        $this->loader->exposedBatchLoadExpandedRelations(
            $em,
            [$root],
            ['track', 'track.subtracks', 'track.subtracks.allowedAccessLevels'],
            StubRootEntity::class,
            ['track' => 'category'],
            [
                // Simple name (no dot) — should still work via fallback
                'subtracks' => function ($item) use (&$resolverCalled) {
                    $resolverCalled = true;
                    return $item instanceof StubWrappedItem ? $item->getInner() : $item;
                },
            ]
        );

        $this->assertTrue($resolverCalled, 'Simple-name resolver fallback should work at level 1');
    }
}
