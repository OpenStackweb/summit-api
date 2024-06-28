<?php namespace Tests;
/**
 * Copyright 2024 OpenStack Foundation
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

use LaravelDoctrine\ORM\Facades\EntityManager;
use models\summit\SummitRegistrationDiscountCode;
use models\summit\SummitRegistrationPromoCode;
use utils\FilterParser;
use utils\Order;
use utils\OrderElement;
use utils\PagingInfo;

/**
 * Class SummitRegistrationPromoCodeRepositoryTest
 */
class SummitRegistrationPromoCodeRepositoryTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        parent::setUp();
        self::insertSummitTestData();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetBySummitAndOrFilters(){

        $repository = EntityManager::getRepository(SummitRegistrationPromoCode::class);

        $term = "test";

        $filter = FilterParser::parse(
            [
                "code=@{$term},creator=@{$term},creator_email=@{$term},owner=@{$term},owner_email=@{$term},speaker=@{$term},speaker_email=@{$term},sponsor=@{$term}"
            ],
            [
                "code"          => ['=@'],
                "creator"       => ['=@'],
                "creator_email" => ['=@'],
                "owner"         => ['=@'],
                "owner_email"   => ['=@'],
                "speaker"       => ['=@'],
                "speaker_email" => ['=@'],
                "sponsor"       => ['=@'],
            ]
        );

        $order = new Order([
            OrderElement::buildDescFor("id"),
        ]);

        $page = $repository->getBySummit(self::$summit, new PagingInfo(1, 5), $filter, $order);

        self::assertNotNull($page);
        self::assertNotEmpty($page->getItems());
    }

    public function testGetBySummitAndClassNameFilter(){
        $repository = EntityManager::getRepository(SummitRegistrationPromoCode::class);

        $term = "test";

        $filter = FilterParser::parse(
            [
                sprintf("or(class_name==%s||%s)", SummitRegistrationPromoCode::ClassName, SummitRegistrationDiscountCode::ClassName),
                "or(code=@{$term})",
            ],
            [
                'code' => ['@@', '=@', '=='],
                'description' => ['@@', '=@'],
                'creator' => ['@@', '=@', '=='],
                'creator_email' => ['@@', '=@', '=='],
                'owner' => ['@@', '=@', '=='],
                'owner_email' => ['@@', '=@', '=='],
                'speaker' => ['@@', '=@', '=='],
                'speaker_email' => ['@@', '=@', '=='],
                'sponsor' => ['@@', '=@', '=='],
                'class_name' => ['=='],
                'type' => ['=='],
                'tag' => ['@@','=@', '=='],
                'tag_id' => ['=='],
            ]
        );

        $order = new Order([
            OrderElement::buildDescFor("id"),
        ]);

        $page = $repository->getBySummit(self::$summit, new PagingInfo(1, 5), $filter, $order);

        self::assertNotNull($page);
        self::assertNotEmpty($page->getItems());
    }
}