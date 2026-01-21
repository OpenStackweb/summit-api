<?php

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

use App\Models\Foundation\Marketplace\CompanyService;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;
use Tests\ProtectedApiTest;

class OAuth2MarketplaceReviewApiTest extends ProtectedApiTest
{
    private $companyService;

    protected function setUp():void
    {
        parent::setUp();

        self::$em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        if (!self::$em ->isOpen()) {
            self::$em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }

        $this->company_service = new CompanyService();
        $this->company_service->setName('Test Company Service');
        $this->company_service->setSlug('test_company_service');
        $this->company_service->setIsActive(true);

        self::$em->persist($this->company_service);
        self::$em->flush();
    }

    public function testAddReview(){
        $params = [
            'company_service_id' => $this->company_service->getId(),
        ];

        $review_title = 'test review title';

        $data = [
            'title' => $review_title,
            'comment' => 'test review comment',
            'rating' => 9.5,
        ];

        $response = $this->action(
            "POST",
            "OAuth2ReviewsApiController@addReview",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $review = json_decode($content);
        $this->assertTrue(!is_null($review));
        $this->assertEquals($review_title, $review->title);
    }
}