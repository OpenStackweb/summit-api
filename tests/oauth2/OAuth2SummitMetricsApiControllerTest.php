<?php namespace Tests;
use App\Models\Foundation\Main\IGroup;

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

class OAuth2SummitMetricsApiControllerTest extends ProtectedApiTestCase
{

    use InsertSummitTestData;

    use InsertMemberTestData;

    /**
     * @var array original $_SERVER values overridden by the tests
     */
    private $original_server = [];

    private const OverriddenServerKeys = ['HTTP_USER_AGENT', 'HTTP_REFERER', 'HTTP_X_FORWARDED_FOR'];

    protected function setUp():void
    {
        parent::setUp();
        foreach (self::OverriddenServerKeys as $key) {
            $this->original_server[$key] = array_key_exists($key, $_SERVER) ? $_SERVER[$key] : null;
        }
        self::insertMemberTestData(IGroup::TrackChairs);
        self::$defaultMember = self::$member;
        self::insertSummitTestData();
    }

    protected function tearDown():void
    {
        foreach ($this->original_server as $key => $value) {
            if (is_null($value)) unset($_SERVER[$key]);
            else $_SERVER[$key] = $value;
        }
        self::clearSummitTestData();
        self::clearMemberTestData();
        parent::tearDown();
    }

    public function testEnter(){

        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'type' => \models\summit\ISummitMetricType::General,
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json",
            'REMOTE_ADDR'         => '10.1.0.1',
            'HTTP_REFERER'        => 'https://www.test.com'
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitMetricsApiController@enter",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $metric = json_decode($content);
        $this->assertTrue(!is_null($metric));
        return $metric;
    }

    private function enterMetric(array $extra = [])
    {
        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = array_merge(['type' => \models\summit\ISummitMetricType::General], $extra);

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json",
        ];

        return $this->action(
            "PUT",
            "OAuth2SummitMetricsApiController@enter",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );
    }

    public function testEnterTruncatesLongHeaders(){
        $_SERVER['HTTP_USER_AGENT'] = str_repeat('a', 300);
        $_SERVER['HTTP_REFERER'] = str_repeat('b', 300);
        $_SERVER['HTTP_X_FORWARDED_FOR'] = str_repeat('1', 300);

        $response = $this->enterMetric();
        $this->assertResponseStatus(201);
        $metric = json_decode($response->getContent());
        $this->assertEquals(str_repeat('a', 255), $metric->browser);
        $this->assertEquals(str_repeat('b', 255), $metric->origin);
        $this->assertEquals(str_repeat('1', 255), $metric->ip);
    }

    public function testEnterTruncatesMultibyteUserAgent(){
        $ua = str_repeat('é', 300);
        $_SERVER['HTTP_USER_AGENT'] = $ua;

        $response = $this->enterMetric();
        $this->assertResponseStatus(201);
        $metric = json_decode($response->getContent());
        $this->assertEquals(mb_substr($ua, 0, 255), $metric->browser);
        $this->assertEquals(255, mb_strlen($metric->browser));
        $this->assertTrue(mb_check_encoding($metric->browser, 'UTF-8'));
    }

    public function testEnterKeepsShortUserAgent(){
        $ua = str_repeat('a', 255);
        $_SERVER['HTTP_USER_AGENT'] = $ua;

        $response = $this->enterMetric();
        $this->assertResponseStatus(201);
        $metric = json_decode($response->getContent());
        $this->assertEquals($ua, $metric->browser);
    }

    public function testEnterLocationTooLong(){
        $this->enterMetric(['location' => str_repeat('l', 256)]);
        $this->assertResponseStatus(412);
    }

    public function testEnterLocationMaxLength(){
        $this->enterMetric(['location' => str_repeat('l', 255)]);
        $this->assertResponseStatus(201);
    }

    public function testLeaveLocationLength(){
        $this->enterMetric();
        $this->assertResponseStatus(201);

        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json",
        ];

        $leave = function (string $location) use ($params, $headers) {
            return $this->action(
                "POST",
                "OAuth2SummitMetricsApiController@leave",
                $params,
                [],
                [],
                [],
                $headers,
                json_encode([
                    'type' => \models\summit\ISummitMetricType::General,
                    'location' => $location,
                ])
            );
        };

        // the pending metric is kept, so the 412 can only come from the location validation
        $leave(str_repeat('l', 256));
        $this->assertResponseStatus(412);

        $leave(str_repeat('l', 255));
        $this->assertResponseStatus(201);
    }

    public function testEnterEvent(){

        $params = [
            'id' => self::$summit->getId(),
            'event_id' => self::$presentations[0]->getId(),
            'member_id' => 'me'
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json",
            'REMOTE_ADDR'         => '10.1.0.1',
            'HTTP_REFERER'        => 'https://www.test.com'
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitMetricsApiController@enterToEvent",
            $params,
            [],
            [],
            [],
            $headers
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $metric = json_decode($content);
        $this->assertTrue(!is_null($metric));
        return $metric;
    }

    public function testEnterOnSite(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json",
            'REMOTE_ADDR'         => '10.1.0.1',
            'HTTP_REFERER'        => 'https://www.test.com'
        ];

        $attendees =  self::$summit->getAttendees();
        $rooms =  self::$venue_rooms;
        $access_levels = self::$summit->getBadgeAccessLevelTypes();
        $data = [
            'attendee_id' => $attendees[0]->getId(),
            'room_id' => $rooms[0]->getId(),
            'required_access_levels' => [$access_levels[0]->getId()]
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitMetricsApiController@onSiteEnter",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $metric = json_decode($content);
        $this->assertTrue(!is_null($metric));
        return $metric;
    }

    public function testEnterOnSiteTwice(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $headers = [
            "HTTP_Authorization"  => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json",
            'REMOTE_ADDR'         => '10.1.0.1',
            'HTTP_REFERER'        => 'https://www.test.com'
        ];

        $attendees =  self::$summit->getAttendees();
        $rooms =  self::$venue_rooms;
        $access_levels = self::$summit->getBadgeAccessLevelTypes();
        $data = [
            'attendee_id' => $attendees[0]->getId(),
            'room_id' => $rooms[0]->getId(),
            'required_access_levels' => [$access_levels[0]->getId()]
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitMetricsApiController@onSiteEnter",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $metric = json_decode($content);
        $this->assertTrue(!is_null($metric));

        $response = $this->action(
            "PUT",
            "OAuth2SummitMetricsApiController@onSiteEnter",
            $params,
            [],
            [],
            [],
            $headers,
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $metric = json_decode($content);
        $this->assertTrue(!is_null($metric));

        return $metric;
    }
}