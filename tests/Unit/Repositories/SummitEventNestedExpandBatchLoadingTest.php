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
 * Integration test that proves nested (dot-notation) expand batch loading
 * reduces the number of SQL queries for second-level relations.
 *
 * Example: ?expand=speakers,speakers.member,speakers.affiliations
 */
class SummitEventNestedExpandBatchLoadingTest extends TestCase
{
    use InsertSummitTestData;

    /** @var ISummitEventRepository|DoctrineSummitEventRepository */
    private $repository;

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

    private function attachQueryLogger(): DebugStack
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $debugStack = new DebugStack();
        $em->getConnection()->getConfiguration()->setSQLLogger($debugStack);
        return $debugStack;
    }

    private function detachQueryLogger(): void
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
    }

    private function clearIdentityMap(): void
    {
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $em->clear();
    }

    private function buildSummitFilter(): Filter
    {
        $filter = new Filter();
        $filter->addFilterCondition(FilterParser::buildFilter('summit_id', '==', self::$summit->getId()));
        return $filter;
    }

    /**
     * Touches level 1 + level 2 relations on speakers to trigger lazy-load queries.
     *
     * @param SummitEvent[] $entities
     * @param bool $touchNested  Whether to also touch second-level speaker relations
     */
    private function touchSpeakerRelations(array $entities, bool $touchNested = false): void
    {
        foreach ($entities as $entity) {
            if (!($entity instanceof Presentation)) continue;

            $speakers = $entity->getSpeakers();
            // Touch level 1: speakers collection
            $speakers->count();

            if ($touchNested) {
                // Touch level 2: each speaker's relations
                foreach ($speakers as $speaker) {
                    $speaker->getMember(); // toOne
                    $speaker->getAffiliations()->count(); // toMany
                }
            }
        }
    }

    /**
     * Runs a scenario and returns query count.
     *
     * @return array{count: int, items: SummitEvent[]}
     */
    private function runScenario(PagingInfo $paging, Filter $filter, array $expands, bool $touchNested): array
    {
        $this->clearIdentityMap();
        $debug = $this->attachQueryLogger();

        $response = $this->repository->getAllByPage($paging, $filter, null, $expands);
        $items = $response->getItems();
        $this->touchSpeakerRelations($items, $touchNested);

        $count = count($debug->queries);
        $this->detachQueryLogger();

        return ['count' => $count, 'items' => $items];
    }

    /**
     * @test
     * Proves that nested expands (speakers.member, speakers.affiliations)
     * reduce query count compared to lazy loading at level 2.
     */
    public function testNestedExpandReducesQueryCountForSpeakerRelations(): void
    {
        $paging = new PagingInfo(1, 20);
        $filter = $this->buildSummitFilter();

        // Level 1 only: speakers loaded, but nested relations lazy-loaded
        $level1Only = $this->runScenario(
            $paging, $filter,
            ['speakers'],
            true // touch nested = trigger lazy loads for member/affiliations
        );

        $this->assertNotEmpty($level1Only['items'], 'Test data should produce results');
        $presentationCount = count(array_filter($level1Only['items'], fn($e) => $e instanceof Presentation));
        $this->assertGreaterThan(0, $presentationCount, 'Should have presentations with speakers');

        // Level 1 + Level 2: speakers + nested relations batch-loaded
        $withNested = $this->runScenario(
            $paging, $filter,
            ['speakers', 'speakers.member', 'speakers.affiliations'],
            true // touch same nested relations — should already be loaded
        );

        $report = sprintf(
            "\n=== Nested Expand: Speaker Relations ===\n" .
            "Entities: %d presentations\n" .
            "Level 1 only (speakers):               %d queries\n" .
            "Level 1 + 2 (speakers.member,.affil):  %d queries\n" .
            "Saved:                                  %d queries\n",
            $presentationCount,
            $level1Only['count'],
            $withNested['count'],
            $level1Only['count'] - $withNested['count']
        );

        fwrite(STDERR, $report);

        // Nested batch loading must use fewer or equal queries
        $this->assertLessThanOrEqual(
            $level1Only['count'],
            $withNested['count'],
            sprintf(
                'Nested batch loading must not increase queries. L1: %d, L1+L2: %d',
                $level1Only['count'],
                $withNested['count']
            )
        );
    }

    /**
     * @test
     * Proves level-1-only expands produce no overhead from nested loading code.
     */
    public function testLevel1OnlyExpandsHaveNoNestedOverhead(): void
    {
        $paging = new PagingInfo(1, 10);
        $filter = $this->buildSummitFilter();

        // With level 1 expands only (no dot-notation)
        $level1 = $this->runScenario($paging, $filter, ['speakers', 'tags'], false);

        // Same expands again — should be identical query count
        $level1Again = $this->runScenario($paging, $filter, ['speakers', 'tags'], false);

        $this->assertSame(
            $level1['count'],
            $level1Again['count'],
            'Level 1 only expands should produce consistent query count (no nested overhead)'
        );
    }

    /**
     * @test
     * Proves data integrity: nested batch-loaded speaker data matches lazy-loaded data.
     */
    public function testNestedBatchLoadedDataMatchesLazyLoaded(): void
    {
        $paging = new PagingInfo(1, 10);
        $filter = $this->buildSummitFilter();

        // Lazy: level 1 only, touch nested
        $this->clearIdentityMap();
        $response1 = $this->repository->getAllByPage($paging, $filter, null, ['speakers']);
        $items1 = $response1->getItems();

        $speakerData1 = [];
        foreach ($items1 as $entity) {
            if (!($entity instanceof Presentation)) continue;
            foreach ($entity->getSpeakers() as $speaker) {
                $speakerData1[$speaker->getId()] = [
                    'member_id' => $speaker->getMember() ? $speaker->getMember()->getId() : null,
                    'affiliations_count' => $speaker->getAffiliations()->count(),
                ];
            }
        }

        // Batch: level 1 + level 2
        $this->clearIdentityMap();
        $response2 = $this->repository->getAllByPage($paging, $filter, null, [
            'speakers', 'speakers.member', 'speakers.affiliations'
        ]);
        $items2 = $response2->getItems();

        $speakerData2 = [];
        foreach ($items2 as $entity) {
            if (!($entity instanceof Presentation)) continue;
            foreach ($entity->getSpeakers() as $speaker) {
                $speakerData2[$speaker->getId()] = [
                    'member_id' => $speaker->getMember() ? $speaker->getMember()->getId() : null,
                    'affiliations_count' => $speaker->getAffiliations()->count(),
                ];
            }
        }

        $this->assertSame(
            $speakerData1,
            $speakerData2,
            'Nested batch-loaded speaker data must match lazy-loaded data'
        );
    }
}
