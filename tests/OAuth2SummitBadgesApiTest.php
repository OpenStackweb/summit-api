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

use App\Models\Foundation\Main\IGroup;
use models\summit\SummitBadgeFeatureType;

/**
 * Class OAuth2SummitBadgesApiTest
 */
class OAuth2SummitBadgesApiTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    protected function setUp(): void
    {
        $this->current_group = IGroup::TrackChairs;
        parent::setUp();
        self::$defaultMember = self::$member;
        self::insertSummitTestData();

        // add a badge feature type and assign it to the badge so the inner join on features returns results
        $feature = new SummitBadgeFeatureType();
        $feature->setName('TEST_FEATURE');
        $feature->setDescription('Test badge feature');
        self::$summit->addFeatureType($feature);

        // find the attendee's badge and add the feature
        $attendees = self::$summit->getAttendees();
        foreach ($attendees as $attendee) {
            foreach ($attendee->getTickets() as $ticket) {
                $badge = $ticket->getBadge();
                if (!is_null($badge)) {
                    $badge->addFeature($feature);
                }
            }
        }

        self::$em->flush();
    }

    protected function tearDown(): void
    {
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testGetAllBySummit(){
        $params = [
            'id' => self::$summit->getId(),
            'expand' => 'ticket,ticket.order,type,type.access_levels,features'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $data = json_decode($content);
        $this->assertNotNull($data);
        $this->assertGreaterThan(0, $data->total);
        return $data;
    }

    public function testGetAllBySummitCSV(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgesApiController@getAllBySummitCSV",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $this->assertResponseStatus(200);
        $content = $response->getContent();
        $this->assertNotEmpty($content);
    }

    public function testGetAllBySummitWithFilter(){
        $params = [
            'id' => self::$summit->getId(),
            'filter' => 'owner_first_name=@' . substr(self::$member->getFirstName(), 0, 3),
            'expand' => 'ticket,type',
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $data = json_decode($content);
        $this->assertNotNull($data);
    }

    public function testGetAllBySummitWithOrder(){
        $params = [
            'id' => self::$summit->getId(),
            'order' => '-created',
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgesApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $data = json_decode($content);
        $this->assertNotNull($data);
        $this->assertGreaterThan(0, $data->total);
    }
}
