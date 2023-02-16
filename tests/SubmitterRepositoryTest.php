<?php namespace Tests;
use LaravelDoctrine\ORM\Facades\EntityManager;
use models\main\Member;
use models\main\SummitAuditLog;
use models\summit\Summit;
use utils\FilterParser;
use utils\Order;
use utils\OrderElement;
use utils\PagingInfo;

/**
 * Copyright 2023 OpenStack Foundation
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

/**
 * Class AuditModelTest
 */
class SubmitterRepositoryTest extends ProtectedApiTest
{
    public function testGetSubmittersBySummit(){
        $submitter_repository = EntityManager::getRepository(Member::class);
        $summit_repository = EntityManager::getRepository(Summit::class);

        $summit = $summit_repository->find(3363);

        $filter = FilterParser::parse(
            ["filter" => "last_name==Palenque"],
            ["last_name" => ['==']]
        );

        $order = new Order([
            OrderElement::buildDescFor("id"),
        ]);

        $submitters = $submitter_repository->getSubmittersBySummit($summit, new PagingInfo(1, 5), $filter, $order);

        self::assertNotEmpty($submitters);
    }

    public function testGetSubmittersIdsBySummit(){
        $submitter_repository = EntityManager::getRepository(Member::class);
        $summit_repository = EntityManager::getRepository(Summit::class);

        $summit = $summit_repository->find(3363);

        $filter = FilterParser::parse(
            ["filter" => "has_alternate_presentations==true"],
            ["has_alternate_presentations" => ['==']]
        );

        $order = new Order([
            OrderElement::buildDescFor("id"),
        ]);

        $submitterIds = $submitter_repository->getSubmittersIdsBySummit($summit, new PagingInfo(1, 5), $filter, $order);

        self::assertNotEmpty($submitterIds);
    }
}