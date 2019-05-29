<?php namespace Tests;
/**
 * Copyright 2019 OpenStack Foundation
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
use Illuminate\Support\Facades\Redis;
use models\summit\Summit;
/**
 * Class MeetingRoomTest
 * @package Tests
 */
class MeetingRoomTest extends TestCase
{
    use CreatesApplication;

    private $redis;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    public function setUp()
    {
        parent::setUp(); // Don't forget this!
        $this->redis = Redis::connection();
        $this->redis->flushall();
        $this->createApplication();
    }

    public function testMeetingRoomsAvalableSlots(){
        $repository = EntityManager::getRepository(Summit::class);
        $summit = $repository->getBySlug('shanghai-2019');
        $rooms = $summit->getBookableRooms();
        $this->assertTrue(count($rooms) > 0);

        $room = $rooms[0];

        $room->getAvailableSlots(new \DateTime("2019-11-05"));
    }

}