<?php namespace Tests;
/**
 * Copyright 2021 OpenStack Foundation
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
use App\Services\Apis\MuxCredentials;
use App\Services\Model\IPresentationVideoMediaUploadProcessor;
use Illuminate\Support\Facades\App;
use DateInterval;
/**
 * Class MuxImportTest
 * @package Tests
 */
final class MuxImportTest extends TestCase
{
    use \InsertSummitTestData;

    protected function tearDown():void
    {
        self::clearTestData();
        parent::tearDown();
        \Mockery::close();
    }

    protected function setUp():void
    {
        parent::setUp();
        self::insertTestData();
        $time_zone = self::$summit->getTimeZone();
        self::$presentations[0]->setStreamingUrl(env("MUX_STREAM_URL1"));
        $begin_date = new \DateTime("now", $time_zone);
        self::$presentations[0]->setStartDate($begin_date);
        self::$presentations[0]->setEndDate((clone $begin_date)->add(new DateInterval("P1D")));
        self::$presentations[0]->publish();
        self::$presentations[1]->setStreamingUrl(env("MUX_STREAM_URL2"));
        self::$presentations[1]->setStartDate($begin_date);
        self::$presentations[1]->setEndDate((clone $begin_date)->add(new DateInterval("P1D")));
        self::$presentations[1]->publish();
        self::$em->persist(self::$presentations[0]);
        self::$em->persist(self::$presentations[1]);
        self::$em->flush();
    }

    public function testEnableMP4(){

        $service = App::make(IPresentationVideoMediaUploadProcessor::class);

        $res = $service->processSummitEventsStreamURLs
        (
            self::$summit->getId(),
            new MuxCredentials
            (
                env('MUX_TOKEN_ID'),
                env('MUX_TOKEN_SECRET')
            ),
            env('MUX_EMAIL_TO')
        );

        $this->assertTrue($res == 2);
    }

    public function testEnableMP4AndProcess(){

        $service = App::make(IPresentationVideoMediaUploadProcessor::class);

        $res = $service->processSummitEventsStreamURLs
        (
            self::$summit->getId(),
            new MuxCredentials
            (
                env('MUX_TOKEN_ID'),
                env('MUX_TOKEN_SECRET')
            ),
            env('MUX_EMAIL_TO')
        );

        $this->assertTrue($res == 2);

        $res = $service->createVideosFromMUXAssets
        (
            self::$summit->getId(),
            new MuxCredentials
            (
                env('MUX_TOKEN_ID'),
                env('MUX_TOKEN_SECRET')
            ),
            env('MUX_EMAIL_TO')
        );

        $this->assertTrue($res == 2);
        $this->assertTrue(self::$presentations[0]->getVideosWithExternalUrls()->count() > 0);
        $this->assertTrue(self::$presentations[1]->getVideosWithExternalUrls()->count() > 0);
    }
}