<?php namespace Tests;
/**
 * Copyright 2020 OpenStack Foundation
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
use models\summit\SummitEvent;
use models\summit\Presentation;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Illuminate\Support\Facades\DB;
use DateTimeZone;
use DateTime;
use DateInterval;
use Mockery;
/**
 * Class SummitEventMetricsTest
 */
class SummitEventMetricsTest extends ProtectedApiTest
{
    use InsertSummitTestData;

    /**
     * @var SummitEvent
     */
    static $event1;

    /**
     * @var SummitEvent
     */
    static $event2;

    /**
     * @var ObjectRepository
     */
    static $event_repository;


    protected function setUp():void
    {
        parent::setUp();
        self::insertTestData();
        DB::setDefaultConnection("model");
        DB::table("Presentation")->delete();
        DB::table("SummitEvent")->delete();
        self::$event_repository = EntityManager::getRepository(SummitEvent::class);
        $time_zone = new DateTimeZone("America/Chicago");
        $now = new DateTime("now", $time_zone);
        self::$event1 = new Presentation();
        self::$event1->setTitle("PRESENTATION 1");
        self::$event1->setStartDate((clone $now)->add(new DateInterval("P1D")));
        self::$event1->setEndDate((clone $now)->add(new DateInterval("P2D")));

        self::$summit->addEvent(self::$event1);
        self::$event1->publish();
        self::$em->persist(self::$event1);
        self::$em->flush();
    }

    public function tearDown():void
    {
        self::clearTestData();
        Mockery::close();
    }

    public function testEventEnter(){

        $params = [
            'id' => self::$summit->getId(),
            'member_id' => 'me',
            'event_id' => self::$event1->getId()
        ];

        $data = [

        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitMembersApiController@enterToEvent",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }

}