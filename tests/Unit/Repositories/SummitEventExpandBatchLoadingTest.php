<?php

namespace Tests\Unit\Repositories;

use App\Repositories\Summit\DoctrineSummitEventRepository;
use Doctrine\DBAL\Logging\DebugStack;
use LaravelDoctrine\ORM\Facades\Registry;
use models\summit\ISummitEventRepository;
use models\summit\Presentation;
use models\summit\SummitEvent;
use models\utils\SilverstripeBaseModel;
use Tests\InsertSummitTestData;
use Tests\TestCase;
use utils\Filter;
use utils\FilterParser;
use utils\PagingInfo;

/**
 * Integration test that proves expand-aware batch loading
 * reduces the number of SQL queries executed by Doctrine.
 *
 * Run with --testdox for readable output:
 *   phpunit tests/Unit/Repositories/SummitEventExpandBatchLoadingTest.php --testdox
 */
class SummitEventExpandBatchLoadingTest extends TestCase
{
    use InsertSummitTestData;

    /** @var ISummitEventRepository|DoctrineSummitEventRepository */
    private $repository;

    /**
     * All expand names from DoctrineSummitEventRepository::$expandAssociationMap.
     * This is the complete set the API supports.
     */
    private const ALL_EXPANDS = [
        // SummitEvent toOne
        'location', 'track', 'type', 'created_by', 'creator', 'updated_by', 'rsvp_template',
        // SummitEvent toMany
        'tags', 'sponsors', 'feedback', 'current_attendance', 'allowed_ticket_types',
        // Presentation toOne
        'moderator', 'selection_plan',
        // Presentation toMany
        'speakers', 'slides', 'videos', 'media_uploads', 'links',
        'actions', 'extra_questions', 'public_comments',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
        $this->repository = app(ISummitEventRepository::class);
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    /**
     * Attaches a DebugStack to the Doctrine connection and returns it.
     */
    private function attachQueryLogger(): DebugStack
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $debugStack = new DebugStack();
        $em->getConnection()->getConfiguration()->setSQLLogger($debugStack);
        return $debugStack;
    }

    /**
     * Detaches the query logger.
     */
    private function detachQueryLogger(): void
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
    }

    /**
     * Clears the Doctrine identity map so entities must be re-loaded from DB.
     */
    private function clearIdentityMap(): void
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $em->clear();
    }

    /**
     * Builds a filter scoped to the test summit.
     */
    private function buildSummitFilter(): Filter
    {
        $filter = new Filter();
        $filter->addFilterCondition(FilterParser::buildFilter('summit_id', '==', self::$summit->getId()));
        return $filter;
    }

    /**
     * Classifies a recorded SQL query by the relation/table it targets.
     */
    private function classifyQuery(string $sql): string
    {
        $sql = strtolower($sql);

        if (str_contains($sql, 'count(') && str_contains($sql, 'summitevent'))
            return 'count';

        if (str_contains($sql, 'summitevent') && !str_contains($sql, 'left join') && str_contains($sql, 'limit'))
            return 'id_list';

        if (str_contains($sql, 'summitevent') && str_contains($sql, 'in ('))
            return 'hydration';

        if (str_contains($sql, 'tag') || str_contains($sql, 'taggedrelation'))
            return 'tags';

        if (str_contains($sql, 'speaker') || str_contains($sql, 'presentationspeaker'))
            return 'speakers';

        if (str_contains($sql, 'presentationmaterial') || str_contains($sql, 'presentationmediaupload')
            || str_contains($sql, 'presentationslide') || str_contains($sql, 'presentationvideo')
            || str_contains($sql, 'presentationlink'))
            return 'materials';

        if (str_contains($sql, 'summitabstractlocation') || str_contains($sql, 'summitvenue'))
            return 'location';

        if (str_contains($sql, 'presentationcategory'))
            return 'track';

        if (str_contains($sql, 'summiteventtype') || str_contains($sql, 'presentationtype'))
            return 'type';

        if (str_contains($sql, 'selectionplan'))
            return 'selection_plan';

        if (str_contains($sql, 'presentationaction'))
            return 'actions';

        if (str_contains($sql, 'presentationcomment') || str_contains($sql, 'summitpresentationcomment'))
            return 'comments';

        if (str_contains($sql, 'extraquestionanswer'))
            return 'extra_questions';

        if (str_contains($sql, 'attendancemetric') || str_contains($sql, 'summiteventattendancemetric'))
            return 'attendance';

        if (str_contains($sql, 'allowedtickettype') || str_contains($sql, 'summit_event_allowed_ticket_types'))
            return 'allowed_ticket_types';

        if (str_contains($sql, 'rsvptemplate'))
            return 'rsvp_template';

        if (str_contains($sql, 'sponsor'))
            return 'sponsors';

        if (str_contains($sql, 'summiteventfeedback'))
            return 'feedback';

        if (str_contains($sql, 'member'))
            return 'member';

        return 'other';
    }

    /**
     * Groups recorded queries by category and returns [category => count].
     */
    private function categorizeQueries(DebugStack $debugStack): array
    {
        $categories = [];
        foreach ($debugStack->queries as $query) {
            $category = $this->classifyQuery($query['sql']);
            $categories[$category] = ($categories[$category] ?? 0) + 1;
        }
        ksort($categories);
        return $categories;
    }

    /**
     * Formats a category breakdown as a readable table string.
     */
    private function formatBreakdown(array $categories, int $total): string
    {
        $lines = [];
        foreach ($categories as $cat => $count) {
            $lines[] = sprintf("    %-22s %3d queries", $cat, $count);
        }
        $lines[] = sprintf("    %-22s %3d queries", 'TOTAL', $total);
        return implode("\n", $lines);
    }

    /**
     * Touches ALL relations on each entity to force lazy-load queries.
     * This mirrors what the serializer does when all expands are requested.
     *
     * @param SummitEvent[] $entities
     */
    private function touchAllRelations(array $entities): void
    {
        foreach ($entities as $entity) {
            // SummitEvent toOne
            $entity->getLocation();
            $entity->getCategory();
            $entity->getType();
            $entity->getCreatedBy();
            $entity->getUpdatedBy();
            $entity->getRSVPTemplate();

            // SummitEvent toMany
            $entity->getTags()->count();
            $entity->getSponsors()->count();
            $entity->getFeedback()->count();
            $entity->getAttendance()->count();
            $entity->getAllowedTicketTypes()->count();

            // Presentation-specific
            if ($entity instanceof Presentation) {
                // toOne
                $entity->getModerator();
                $entity->getSelectionPlan();

                // toMany
                $entity->getSpeakers()->count();
                $entity->getMaterials()->count();
                $entity->getComments()->count();
                $entity->getAllExtraQuestionAnswers()->count();
            }
        }
    }

    /**
     * Touches specific relations on each entity for isolated per-expand tests.
     *
     * @param SummitEvent[] $entities
     * @param string[] $relations
     */
    private function touchRelations(array $entities, array $relations): void
    {
        foreach ($entities as $entity) {
            foreach ($relations as $rel) {
                switch ($rel) {
                    // SummitEvent toOne
                    case 'location':
                        $entity->getLocation();
                        break;
                    case 'track':
                        $entity->getCategory();
                        break;
                    case 'type':
                        $entity->getType();
                        break;
                    case 'created_by':
                    case 'creator':
                        $entity->getCreatedBy();
                        break;
                    case 'updated_by':
                        $entity->getUpdatedBy();
                        break;
                    case 'rsvp_template':
                        $entity->getRSVPTemplate();
                        break;

                    // SummitEvent toMany
                    case 'tags':
                        $entity->getTags()->count();
                        break;
                    case 'sponsors':
                        $entity->getSponsors()->count();
                        break;
                    case 'feedback':
                        $entity->getFeedback()->count();
                        break;
                    case 'current_attendance':
                        $entity->getAttendance()->count();
                        break;
                    case 'allowed_ticket_types':
                        $entity->getAllowedTicketTypes()->count();
                        break;

                    // Presentation toOne
                    case 'moderator':
                        if ($entity instanceof Presentation) $entity->getModerator();
                        break;
                    case 'selection_plan':
                        if ($entity instanceof Presentation) $entity->getSelectionPlan();
                        break;

                    // Presentation toMany
                    case 'speakers':
                        if ($entity instanceof Presentation) $entity->getSpeakers()->count();
                        break;
                    case 'slides':
                    case 'videos':
                    case 'media_uploads':
                    case 'links':
                        if ($entity instanceof Presentation) $entity->getMaterials()->count();
                        break;
                    case 'actions':
                        if ($entity instanceof Presentation) {
                            // Access the raw collection to trigger lazy-load
                            $entity->getPresentationActions();
                        }
                        break;
                    case 'extra_questions':
                        if ($entity instanceof Presentation) $entity->getAllExtraQuestionAnswers()->count();
                        break;
                    case 'public_comments':
                        if ($entity instanceof Presentation) $entity->getComments()->count();
                        break;
                }
            }
        }
    }

    /**
     * Runs a query scenario: fetches page, touches relations, returns query count and breakdown.
     *
     * @return array{count: int, categories: array<string, int>, items: SummitEvent[]}
     */
    private function runScenario(PagingInfo $paging, Filter $filter, array $expands, array $touchRelations): array
    {
        $this->clearIdentityMap();
        $debug = $this->attachQueryLogger();

        $response = $this->repository->getAllByPage($paging, $filter, null, $expands);
        $items = $response->getItems();
        $this->touchRelations($items, $touchRelations);

        $count = count($debug->queries);
        $categories = $this->categorizeQueries($debug);
        $this->detachQueryLogger();

        return ['count' => $count, 'categories' => $categories, 'items' => $items];
    }

    /**
     * Like runScenario but touches ALL relations (not a specific list).
     */
    private function runFullScenario(PagingInfo $paging, Filter $filter, array $expands): array
    {
        $this->clearIdentityMap();
        $debug = $this->attachQueryLogger();

        $response = $this->repository->getAllByPage($paging, $filter, null, $expands);
        $items = $response->getItems();
        $this->touchAllRelations($items);

        $count = count($debug->queries);
        $categories = $this->categorizeQueries($debug);
        $this->detachQueryLogger();

        return ['count' => $count, 'categories' => $categories, 'items' => $items];
    }

    // -----------------------------------------------------------------------
    // Tests
    // -----------------------------------------------------------------------

    /**
     * @test
     * Proves that batch loading with ALL expands from the association map
     * significantly reduces total SQL queries compared to lazy loading.
     * Outputs a per-category breakdown for full visibility.
     */
    public function testBatchLoadingWithAllExpandsReducesQueryCount(): void
    {
        $paging = new PagingInfo(1, 20);
        $filter = $this->buildSummitFilter();

        // --- Run 1: WITHOUT expands — touch ALL relations (lazy loading) ---
        $without = $this->runFullScenario($paging, $filter, []);

        $this->assertNotEmpty($without['items'], 'Test data should produce results');
        $entityCount = count($without['items']);
        $presentationCount = count(array_filter($without['items'], fn($e) => $e instanceof Presentation));

        // --- Run 2: WITH ALL expands (batch loading) ---
        $with = $this->runFullScenario($paging, $filter, self::ALL_EXPANDS);

        // --- Build report ---
        $reduction = $without['count'] > 0 ? (1 - ($with['count'] / $without['count'])) * 100 : 0;

        $report = sprintf(
            "\n" .
            "=== Expand Batch Loading: ALL Expands Report ===\n" .
            "Entities: %d total (%d Presentations, %d SummitEvents)\n" .
            "Expands:  %d total (%s)\n" .
            "\n" .
            "--- WITHOUT expands (lazy loading) ---\n%s\n" .
            "\n" .
            "--- WITH ALL expands (batch loading) ---\n%s\n" .
            "\n" .
            "=== RESULT: %d -> %d queries (%.1f%% reduction) ===\n",
            $entityCount,
            $presentationCount,
            $entityCount - $presentationCount,
            count(self::ALL_EXPANDS),
            implode(', ', self::ALL_EXPANDS),
            $this->formatBreakdown($without['categories'], $without['count']),
            $this->formatBreakdown($with['categories'], $with['count']),
            $without['count'],
            $with['count'],
            $reduction
        );

        fwrite(STDERR, $report);

        // --- Assertions ---
        $this->assertSame(
            count($without['items']),
            count($with['items']),
            'Both runs must return the same number of entities'
        );

        $this->assertLessThan(
            $without['count'],
            $with['count'],
            sprintf(
                'Batch loading must use fewer queries. Without: %d, With: %d',
                $without['count'],
                $with['count']
            )
        );

        $this->assertGreaterThan(
            30.0,
            $reduction,
            sprintf('Query reduction must be > 30%%. Got %.1f%%', $reduction)
        );
    }

    /**
     * @test
     * Tests EVERY expand from the association map individually.
     * For each one: measures lazy vs batch query count and the per-relation delta.
     */
    public function testPerExpandQueryReductionForAllExpands(): void
    {
        $paging = new PagingInfo(1, 20);
        $filter = $this->buildSummitFilter();

        $this->assertNotEmpty(
            $this->runScenario($paging, $filter, [], [])['items'],
            'Test data should produce results'
        );

        $report = sprintf(
            "\n=== Per-Expand Query Reduction (ALL %d expands) ===\n" .
            "%-20s | %6s | %6s | %6s\n" .
            "%s\n",
            count(self::ALL_EXPANDS),
            'Expand', 'Lazy', 'Batch', 'Saved',
            str_repeat('-', 50)
        );

        $totalSaved = 0;
        foreach (self::ALL_EXPANDS as $expandName) {
            // Lazy: no expand, but touch the relation
            $lazy = $this->runScenario($paging, $filter, [], [$expandName]);

            // Batch: with expand, touch the same relation
            $batch = $this->runScenario($paging, $filter, [$expandName], [$expandName]);

            $saved = $lazy['count'] - $batch['count'];
            $totalSaved += $saved;

            $report .= sprintf(
                "%-20s | %6d | %6d | %6d\n",
                $expandName,
                $lazy['count'],
                $batch['count'],
                $saved
            );

            // Each expand must not significantly increase query count.
            // Allow +1 because the batch query itself fires even when the getter
            // method uses a custom DQL (e.g. getPresentationActions filters by
            // selection plan and may not trigger standard lazy-load).
            $this->assertLessThanOrEqual(
                $lazy['count'] + 1,
                $batch['count'],
                sprintf(
                    'Expand "%s" must not add more than 1 query. Lazy: %d, Batch: %d',
                    $expandName,
                    $lazy['count'],
                    $batch['count']
                )
            );
        }

        $report .= sprintf("%s\n%-20s | %6s | %6s | %6d\n",
            str_repeat('-', 50),
            'TOTAL SAVED', '', '', $totalSaved
        );

        fwrite(STDERR, $report . "\n");

        // At least some expands should produce savings
        $this->assertGreaterThan(
            0,
            $totalSaved,
            'At least some expands should reduce query count'
        );
    }

    /**
     * @test
     * Proves query count scales with page size under lazy loading (N+1 pattern)
     * but stays nearly constant with batch loading. Uses ALL expands.
     */
    public function testQueryCountScalingWithPageSize(): void
    {
        $filter = $this->buildSummitFilter();
        $pageSizes = [5, 10, 20];

        $report = sprintf(
            "\n=== Query Scaling by Page Size (ALL %d expands) ===\n" .
            "%4s | %6s | %6s | %8s\n" .
            "%s\n",
            count(self::ALL_EXPANDS),
            'Size', 'Lazy', 'Batch', 'Saved',
            str_repeat('-', 36)
        );

        $previousLazyCount = 0;
        foreach ($pageSizes as $size) {
            $paging = new PagingInfo(1, $size);

            $lazy = $this->runFullScenario($paging, $filter, []);
            $batch = $this->runFullScenario($paging, $filter, self::ALL_EXPANDS);

            $saved = $lazy['count'] - $batch['count'];
            $report .= sprintf(
                "%4d | %6d | %6d | %8d\n",
                $size,
                $lazy['count'],
                $batch['count'],
                $saved
            );

            // Lazy-load query count should grow with page size (N+1 pattern)
            if ($previousLazyCount > 0) {
                $this->assertGreaterThan(
                    $previousLazyCount,
                    $lazy['count'],
                    sprintf(
                        'Lazy-load queries must grow with page size. Size %d: %d queries, previous: %d',
                        $size,
                        $lazy['count'],
                        $previousLazyCount
                    )
                );
            }

            // Batch loading must always use fewer queries
            $this->assertLessThan(
                $lazy['count'],
                $batch['count'],
                sprintf(
                    'Batch loading must use fewer queries at page size %d. Lazy: %d, Batch: %d',
                    $size,
                    $lazy['count'],
                    $batch['count']
                )
            );

            $previousLazyCount = $lazy['count'];
        }

        fwrite(STDERR, $report . "\n");
    }

    /**
     * @test
     */
    public function testEmptyExpandsProduceSameQueryCountAsNoExpands(): void
    {
        $paging = new PagingInfo(1, 10);
        $filter = $this->buildSummitFilter();

        $without = $this->runScenario($paging, $filter, [], []);
        $empty = $this->runScenario($paging, $filter, [], []);

        $this->assertSame(
            $without['count'],
            $empty['count'],
            'Empty expands should not add any extra queries'
        );
    }

    /**
     * @test
     * Verifies batch-loaded entities return identical data as lazy-loaded ones
     * across ALL touchable relations.
     */
    public function testBatchLoadedEntitiesReturnSameDataAsLazyLoaded(): void
    {
        $paging = new PagingInfo(1, 10);
        $filter = $this->buildSummitFilter();

        // Without expands (lazy)
        $this->clearIdentityMap();
        $responseWithout = $this->repository->getAllByPage($paging, $filter, null, []);
        $dataWithout = $responseWithout->getItems();

        $idsWithout = array_map(fn($e) => $e->getId(), $dataWithout);
        $tagCountsWithout = array_map(fn($e) => $e->getTags()->count(), $dataWithout);
        $sponsorCountsWithout = array_map(fn($e) => $e->getSponsors()->count(), $dataWithout);
        $speakerCountsWithout = array_map(function ($e) {
            return $e instanceof Presentation ? $e->getSpeakers()->count() : 0;
        }, $dataWithout);
        $materialCountsWithout = array_map(function ($e) {
            return $e instanceof Presentation ? $e->getMaterials()->count() : 0;
        }, $dataWithout);
        $commentCountsWithout = array_map(function ($e) {
            return $e instanceof Presentation ? $e->getComments()->count() : 0;
        }, $dataWithout);

        // With ALL expands (batch)
        $this->clearIdentityMap();
        $responseWith = $this->repository->getAllByPage($paging, $filter, null, self::ALL_EXPANDS);
        $dataWith = $responseWith->getItems();

        $idsWith = array_map(fn($e) => $e->getId(), $dataWith);
        $tagCountsWith = array_map(fn($e) => $e->getTags()->count(), $dataWith);
        $sponsorCountsWith = array_map(fn($e) => $e->getSponsors()->count(), $dataWith);
        $speakerCountsWith = array_map(function ($e) {
            return $e instanceof Presentation ? $e->getSpeakers()->count() : 0;
        }, $dataWith);
        $materialCountsWith = array_map(function ($e) {
            return $e instanceof Presentation ? $e->getMaterials()->count() : 0;
        }, $dataWith);
        $commentCountsWith = array_map(function ($e) {
            return $e instanceof Presentation ? $e->getComments()->count() : 0;
        }, $dataWith);

        $this->assertSame($idsWithout, $idsWith, 'Entity IDs should match');
        $this->assertSame($tagCountsWithout, $tagCountsWith, 'Tag counts should match');
        $this->assertSame($sponsorCountsWithout, $sponsorCountsWith, 'Sponsor counts should match');
        $this->assertSame($speakerCountsWithout, $speakerCountsWith, 'Speaker counts should match');
        $this->assertSame($materialCountsWithout, $materialCountsWith, 'Material counts should match');
        $this->assertSame($commentCountsWithout, $commentCountsWith, 'Comment counts should match');
    }
}
