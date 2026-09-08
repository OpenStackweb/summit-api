<?php namespace Tests;
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

use App\Http\Utils\Filters\SQL\SQLRawFilterMapping;
use App\Http\Utils\Filters\SQL\SQLSwitchFilterMapping;
use App\Repositories\Summit\DoctrineMemberRepository;
use App\Repositories\Summit\DoctrineSpeakerRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use utils\Filter;
use utils\FilterElement;

/**
 * Class ActivitiesCountFilterMappingsTest
 *
 * Unit tests for the phase-2 filter mappings of the speakers/submitters activities count
 * and for the two raw SQL filter mappings they are built from. Nothing here touches the
 * database; the application is booted only because Filter::toRawSQL logs through a facade.
 */
class ActivitiesCountFilterMappingsTest extends TestCase
{
    // -----------------------------------------------------------------
    // SQLRawFilterMapping
    // -----------------------------------------------------------------

    public function testRawMappingBindsTheValueAndRendersTheOperator(): void
    {
        $mapping = new SQLRawFilterMapping('E.CategoryID :operator :value');

        $sql = $mapping->toRawSQL(FilterElement::makeEqual('presentations_track_id', '5'));

        $this->assertEquals('E.CategoryID = :param_1', $sql);
        $this->assertEquals(['param_1' => '5'], $mapping->getBindings());
        // the value must never be interpolated into the statement
        $this->assertStringNotContainsString('= 5', $sql);
    }

    public function testRawMappingContinuesTheCallersParameterNumbering(): void
    {
        $mapping = new SQLRawFilterMapping('E.TypeID :operator :value');

        $sql = $mapping->toRawSQL(
            FilterElement::makeEqual('presentations_type_id', '7'),
            ['param_1' => 'already taken', 'param_2' => 'also taken']
        );

        $this->assertEquals('E.TypeID = :param_3', $sql);
        $this->assertEquals(['param_3' => '7'], $mapping->getBindings());
    }

    public function testRawMappingRendersEachValueOfAMultiValueElement(): void
    {
        $mapping = new SQLRawFilterMapping('E.CategoryID :operator :value');

        $sql = $mapping->toRawSQL(
            FilterElement::makeEqual('presentations_track_id', ['5', '6'], 'OR')
        );

        $this->assertEquals('( E.CategoryID = :param_1 OR E.CategoryID = :param_2 )', $sql);
        $this->assertEquals(['param_1' => '5', 'param_2' => '6'], $mapping->getBindings());
    }

    public function testRawMappingHonoursTheAndSameFieldOperator(): void
    {
        $mapping = new SQLRawFilterMapping('X :operator :value');

        $sql = $mapping->toRawSQL(
            FilterElement::makeEqual('has_media_upload_with_type', ['1', '2'], 'AND')
        );

        $this->assertStringContainsString(' AND ', $sql);
        $this->assertStringNotContainsString(' OR ', $sql);
    }

    public function testRawMappingResetsItsBindingsBetweenUses(): void
    {
        $mapping = new SQLRawFilterMapping('E.CategoryID :operator :value');

        $mapping->toRawSQL(FilterElement::makeEqual('presentations_track_id', '5'));
        $mapping->toRawSQL(FilterElement::makeEqual('presentations_track_id', '9'), ['param_1' => '5']);

        $this->assertEquals(['param_2' => '9'], $mapping->getBindings());
    }

    public function testRawMappingLowersBothSidesOfATextCondition(): void
    {
        $mapping = new SQLRawFilterMapping('LOWER(E.Title) :operator LOWER(:value)');

        $sql = $mapping->toRawSQL(FilterElement::makeLike('presentations_title', 'keynote'));

        $this->assertEquals('LOWER(E.Title) like LOWER(:param_1)', $sql);
        // makeLike wraps the value in wildcards
        $this->assertEquals(['param_1' => '%keynote%'], $mapping->getBindings());
    }

    // -----------------------------------------------------------------
    // SQLSwitchFilterMapping
    // -----------------------------------------------------------------

    public function testSwitchMappingPicksTheConditionOfTheValue(): void
    {
        $mapping = new SQLSwitchFilterMapping([
            'true' => 'E.Published = 1',
            'false' => SQLSwitchFilterMapping::NoRestriction,
        ]);

        $this->assertEquals(
            '( E.Published = 1 )',
            $mapping->toRawSQL(FilterElement::makeEqual('has_published_presentations', 'true'))
        );
        $this->assertEquals(
            '( 1 = 1 )',
            $mapping->toRawSQL(FilterElement::makeEqual('has_published_presentations', 'false'))
        );
        $this->assertEmpty($mapping->getBindings());
    }

    public function testSwitchMappingOrsEveryValueOfAMultiValueElement(): void
    {
        $mapping = new SQLSwitchFilterMapping([
            'true' => 'E.Published = 1',
            'false' => SQLSwitchFilterMapping::NoRestriction,
        ]);

        // true OR no-restriction evaluates to no restriction, as in the Doctrine mapping
        $sql = $mapping->toRawSQL(
            FilterElement::makeEqual('has_published_presentations', ['true', 'false'], 'OR')
        );

        $this->assertEquals('( E.Published = 1 ) OR ( 1 = 1 )', $sql);
    }

    public function testSwitchMappingYieldsNothingForAnUnknownValue(): void
    {
        $mapping = new SQLSwitchFilterMapping(['true' => 'E.Published = 1']);

        $this->assertEquals(
            '',
            $mapping->toRawSQL(FilterElement::makeEqual('has_published_presentations', 'maybe'))
        );
    }

    // -----------------------------------------------------------------
    // Filter::toRawSQL over the activities count mappings
    // -----------------------------------------------------------------

    private function activitiesCountSQL(Filter $filter, string $repository_class = DoctrineSpeakerRepository::class): array
    {
        $sql = $filter->toRawSQL($this->getMappings($repository_class, 'getActivitiesCountFilterMappings'));

        return [$sql, $filter->getSQLBindings()];
    }

    private function filterOf(...$conditions): Filter
    {
        $filter = new Filter();
        foreach ($conditions as $condition) {
            $filter->addFilterCondition($condition);
        }
        return $filter;
    }

    public function testAPersonLevelFilterYieldsNoPresentationCondition(): void
    {
        [$sql, $bindings] = $this->activitiesCountSQL(
            $this->filterOf(FilterElement::makeEqual('id', '123'))
        );

        $this->assertEmpty($sql, 'id has no phase-2 mapping, so it must not restrict the count');
        $this->assertEmpty($bindings);
    }

    public function testCombinedFiltersAreAnded(): void
    {
        [$sql, $bindings] = $this->activitiesCountSQL(
            $this->filterOf(
                FilterElement::makeEqual('has_published_presentations', 'true'),
                FilterElement::makeEqual('presentations_track_id', '5')
            )
        );

        $this->assertStringContainsString('E.Published = 1', $sql);
        $this->assertStringContainsString('E.CategoryID = :param_1', $sql);
        $this->assertStringContainsString(') AND (', $sql);
        $this->assertEquals(['param_1' => '5'], $bindings);
    }

    public function testAPersonLevelFilterDoesNotStopTheOthersFromScoping(): void
    {
        [$sql] = $this->activitiesCountSQL(
            $this->filterOf(
                FilterElement::makeEqual('id', '123'),
                FilterElement::makeEqual('presentations_track_id', '5')
            )
        );

        $this->assertStringContainsString('E.CategoryID = :param_1', $sql);
    }

    public function testEveryReturnedBindingHasItsPlaceholderInTheStatement(): void
    {
        [$sql, $bindings] = $this->activitiesCountSQL(
            $this->filterOf(
                FilterElement::makeEqual(
                    'presentations_track_id',
                    ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11'],
                    'OR'
                ),
                FilterElement::makeEqual('presentations_type_id', '7')
            )
        );

        $this->assertCount(12, $bindings);
        foreach (array_keys($bindings) as $name) {
            $this->assertMatchesRegularExpression(
                '/:' . preg_quote($name, '/') . '\b/',
                $sql,
                sprintf('binding "%s" has no placeholder in the statement', $name)
            );
        }
    }

    public function testSelectionStatusConditionsUseTheSelectedListSemantics(): void
    {
        [$accepted] = $this->activitiesCountSQL(
            $this->filterOf(FilterElement::makeEqual('has_accepted_presentations', 'true'))
        );
        $this->assertStringContainsString('__sp.`Order` <= __cat.SessionCount', $accepted);
        $this->assertStringContainsString("__sp.Collection = 'selected'", $accepted);
        $this->assertStringContainsString("__spl.ListType = 'Group'", $accepted);
        $this->assertStringContainsString("__spl.ListClass = 'Session'", $accepted);
        $this->assertStringContainsString('OR E.Published = 1', $accepted);

        [$alternate] = $this->activitiesCountSQL(
            $this->filterOf(FilterElement::makeEqual('has_alternate_presentations', 'true'))
        );
        $this->assertStringContainsString('__sp.`Order` > __cat.SessionCount', $alternate);
        $this->assertStringNotContainsString('Published', $alternate);

        [$rejected] = $this->activitiesCountSQL(
            $this->filterOf(FilterElement::makeEqual('has_rejected_presentations', 'true'))
        );
        $this->assertStringContainsString('E.Published = 0', $rejected);
        $this->assertStringContainsString('AND NOT EXISTS', $rejected);
        // the rejected mapping ignores the order, it only asks for absence from the list
        $this->assertStringNotContainsString('SessionCount', $rejected);
    }

    public function testMediaUploadConditionJoinsTheParentMaterialTable(): void
    {
        [$sql] = $this->activitiesCountSQL(
            $this->filterOf(FilterElement::makeEqual('has_media_upload_with_type', '11'))
        );

        // PresentationMediaUpload is a JOINED subclass: PresentationID lives on the parent
        $this->assertStringContainsString('INNER JOIN PresentationMaterial __mat ON __mat.ID = __mu.ID', $sql);
        $this->assertStringContainsString('__mat.PresentationID = E.ID', $sql);
        $this->assertStringContainsString('__mu.SummitMediaUploadTypeID = :param_1', $sql);
    }

    // -----------------------------------------------------------------
    // buildActivitiesCountFilter - the entry point both repositories share
    // -----------------------------------------------------------------

    private function buildFor(?Filter $filter, string $repository_class = DoctrineSpeakerRepository::class): array
    {
        return $this->invokeOnRepository($repository_class, 'buildActivitiesCountFilter', $filter, 73);
    }

    #[DataProvider('repositoryProvider')]
    public function testBuildWithoutAFilterOnlyBindsTheSummit(string $repository_class): void
    {
        [$extra_filters, $bindings] = $this->buildFor(null, $repository_class);

        $this->assertEmpty($extra_filters);
        $this->assertEquals(['summit_id' => 73], $bindings);
    }

    #[DataProvider('repositoryProvider')]
    public function testBuildWithOnlyPersonLevelFiltersAddsNothing(string $repository_class): void
    {
        [$extra_filters, $bindings] = $this->buildFor(
            $this->filterOf(
                FilterElement::makeEqual('id', '123'),
                FilterElement::makeEqual('first_name', 'Sebastian')
            ),
            $repository_class
        );

        $this->assertEmpty($extra_filters);
        $this->assertEquals(['summit_id' => 73], $bindings);
    }

    #[DataProvider('repositoryProvider')]
    public function testBuildAppendsTheFragmentAndKeepsTheSummitBinding(string $repository_class): void
    {
        [$extra_filters, $bindings] = $this->buildFor(
            $this->filterOf(
                FilterElement::makeEqual('presentations_track_id', '5'),
                FilterElement::makeEqual('has_published_presentations', 'true')
            ),
            $repository_class
        );

        $this->assertStringStartsWith(' AND (', $extra_filters);
        $this->assertStringContainsString('E.CategoryID = :param_1', $extra_filters);
        $this->assertStringContainsString('E.Published = 1', $extra_filters);
        $this->assertEquals(['summit_id' => 73, 'param_1' => '5'], $bindings);
    }

    #[DataProvider('repositoryProvider')]
    public function testBuildProducesTheSameFragmentForBothRoles(string $repository_class): void
    {
        // the conditions correlate to the presentation, so both repositories must agree
        [$speakers] = $this->buildFor(
            $this->filterOf(FilterElement::makeEqual('presentations_type_id', '7')),
            DoctrineSpeakerRepository::class
        );
        [$submitters] = $this->buildFor(
            $this->filterOf(FilterElement::makeEqual('presentations_type_id', '7')),
            DoctrineMemberRepository::class
        );

        $this->assertEquals($speakers, $submitters);
    }

    // -----------------------------------------------------------------
    // anti drift guard: phase 1 and phase 2 must know the same filters
    // -----------------------------------------------------------------

    public static function repositoryProvider(): array
    {
        return [
            'speakers'   => [DoctrineSpeakerRepository::class],
            'submitters' => [DoctrineMemberRepository::class],
        ];
    }

    #[DataProvider('repositoryProvider')]
    public function testPhaseTwoKnowsEveryPresentationLevelFilterOfPhaseOne(string $repository_class): void
    {
        $phase_one = array_keys($this->getMappings($repository_class, 'getFilterMappings'));
        $phase_two = array_keys($this->getMappings($repository_class, 'getActivitiesCountFilterMappings'));

        $this->assertNotEmpty($phase_one);

        foreach ($phase_one as $key) {
            if (!$this->looksPresentationLevel($key)) continue;

            $this->assertContains(
                $key,
                $phase_two,
                sprintf(
                    '%s exposes the presentation-level filter "%s" in phase 1 but the ' .
                    'activities count does not scope phase 2 with it, so the count would over-count.',
                    $repository_class,
                    $key
                )
            );
        }
    }

    #[DataProvider('repositoryProvider')]
    public function testEveryPhaseTwoFilterExistsInPhaseOne(string $repository_class): void
    {
        $phase_one = array_keys($this->getMappings($repository_class, 'getFilterMappings'));
        $phase_two = array_keys($this->getMappings($repository_class, 'getActivitiesCountFilterMappings'));

        foreach ($phase_two as $key) {
            $this->assertContains(
                $key,
                $phase_one,
                sprintf('%s has no phase-1 mapping for "%s".', $repository_class, $key)
            );
        }
    }

    #[DataProvider('repositoryProvider')]
    public function testPhaseTwoCarriesNoPersonLevelFilter(string $repository_class): void
    {
        $phase_two = array_keys($this->getMappings($repository_class, 'getActivitiesCountFilterMappings'));

        foreach ($phase_two as $key) {
            $this->assertTrue(
                $this->looksPresentationLevel($key),
                sprintf(
                    '"%s" is not a presentation-level filter: scoping phase 2 with it would ' .
                    'count fewer presentations than the people phase 1 matched.',
                    $key
                )
            );
        }
    }

    /**
     * A filter name that phase 1 resolves through a presentation, and that therefore has
     * to scope phase 2 as well.
     */
    private function looksPresentationLevel(string $key): bool
    {
        if (str_starts_with($key, 'presentations_')) return true;
        if (str_starts_with($key, 'has_') && str_ends_with($key, '_presentations')) return true;
        if (str_contains($key, 'media_upload')) return true;
        return false;
    }

    /**
     * The trait's methods touch no $this beyond each other, so they can be called on an
     * instance built without its constructor -- no entity manager needed.
     */
    private function invokeOnRepository(string $repository_class, string $method, ...$args): array
    {
        $reflection = new \ReflectionClass($repository_class);
        $instance = $reflection->newInstanceWithoutConstructor();
        $m = $reflection->getMethod($method);
        $m->setAccessible(true);

        return $m->invoke($instance, ...$args);
    }

    private function getMappings(string $repository_class, string $method): array
    {
        return $this->invokeOnRepository($repository_class, $method, null);
    }
}
