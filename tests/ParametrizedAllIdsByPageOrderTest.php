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

use models\summit\ISpeakerRepository;
use models\summit\PresentationSpeaker;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Covers the default-order fallback of DoctrineRepository::getParametrizedAllIdsByPage.
 *
 * The fallback used to be chained to $filter rather than to $order, so a paginated id query
 * that carried a filter was emitted with LIMIT/OFFSET and no ORDER BY at all. MySQL makes no
 * promise about the order of such a result, so a run that pages through it can skip rows or
 * hand back the same row twice. The bulk speaker send always reaches the repository with a
 * non-null Filter (ParametrizedSendEmails substitutes an empty one when none was given), so
 * in practice it never emitted an ORDER BY.
 *
 * The assertion is on the generated DQL rather than on paged data on purpose: an unordered
 * query frequently *happens* to come back in a stable order, so a data-level test would pass
 * by luck against the defect and would not protect against a regression.
 *
 * Class ParametrizedAllIdsByPageOrderTest
 */
final class ParametrizedAllIdsByPageOrderTest extends TestCase
{
    private function dqlFor(?Filter $filter): string
    {
        $repository = app(ISpeakerRepository::class);

        $query = null;
        $fnQuery = function () use (&$query) {
            $query = $this->app->make('registry')->getManager('model')
                ->createQueryBuilder()
                ->select('e.id')
                ->from(PresentationSpeaker::class, 'e')
                // the joins the real getSpeakersIdsBySummit query carries, so the
                // repository's filter mappings resolve against them
                ->leftJoin('e.registration_request', 'rr')
                ->leftJoin('e.member', 'm');
            return $query;
        };

        $repository->getParametrizedAllIdsByPage(
            $fnQuery,
            new PagingInfo(1, 100),
            $filter,
            null, // no explicit order: the default-order fallback has to take over
            function ($q) {
                return $q->addOrderBy('e.id', 'ASC');
            }
        );

        return $query->getDQL();
    }

    public function testDefaultOrderIsAppliedWhenAFilterIsPresent(): void
    {
        $filter = new Filter();
        $filter->addFilterCondition(FilterElement::makeEqual('first_name', 'test'));

        $this->assertStringContainsString(
            'ORDER BY',
            $this->dqlFor($filter),
            'a filtered paginated id query must still carry the default order'
        );
    }

    public function testDefaultOrderIsAppliedWhenNoFilterIsPresent(): void
    {
        $this->assertStringContainsString(
            'ORDER BY',
            $this->dqlFor(null),
            'an unfiltered paginated id query must carry the default order'
        );
    }

    public function testAnExplicitOrderStillWinsOverTheDefault(): void
    {
        $filter = new Filter();
        $filter->addFilterCondition(FilterElement::makeEqual('first_name', 'test'));
        $order = new \utils\Order([\utils\OrderElement::buildDescFor('last_name')]);

        $repository = app(ISpeakerRepository::class);
        $query = null;
        $repository->getParametrizedAllIdsByPage(
            function () use (&$query) {
                $query = $this->app->make('registry')->getManager('model')
                    ->createQueryBuilder()
                    ->select('e.id')
                    ->from(PresentationSpeaker::class, 'e')
                    ->leftJoin('e.registration_request', 'rr')
                    ->leftJoin('e.member', 'm');
                return $query;
            },
            new PagingInfo(1, 100),
            $filter,
            $order,
            function ($q) {
                return $q->addOrderBy('e.id', 'ASC');
            }
        );

        $dql = $query->getDQL();
        $this->assertStringContainsString('ORDER BY', $dql);
        $this->assertStringNotContainsString(
            'e.id ASC',
            $dql,
            'the default order must not be appended when an explicit order was given'
        );
    }
}
