<?php namespace Tests;
/*
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

use App\Models\Foundation\Summit\Registration\ISummitExternalRegistrationFeedType;
use App\Services\Apis\Samsung\ForumTypes;
use App\Services\Model\IMemberService;
use App\Services\Model\Strategies\TicketFinder\ITicketFinderStrategyFactory;
use App\Services\Model\Strategies\TicketFinder\Strategies\TicketFinderByExternalFeedStrategy;
use App\Utils\AES;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\App;
use Mockery;
use models\summit\ISummitAttendeeRepository;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\SummitAttendeeTicket;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use App\Services\Apis\Samsung\RequestTypes;
/**
 * Class ExternalRegistrationFeedTest
 * @package Tests
 */
final class ExternalRegistrationFeedTest extends TestCase
{

    use InsertSummitTestData;

    protected function setUp():void
    {
        parent::setUp();
        self::insertSummitTestData();
        self::$em->persist(self::$summit);
        self::$em->flush();
    }

    protected function tearDown():void
    {
        self::clearSummitTestData();
        Mockery::close();
        parent::tearDown();

    }
    public function testTicketFinderStrategyEmailCriteria(){

        self::$summit->setExternalSummitId(ForumTypes::SAFE);
        self::$summit->setExternalRegistrationFeedApiKey("12345601234567890123456789012345");
        self::$summit->setExternalRegistrationFeedType(ISummitExternalRegistrationFeedType::Samsung);
        self::$em->persist(self::$summit);
        self::$em->flush();

        $memberServiceMock = Mockery::mock(IMemberService::class);
        $memberServiceMock->shouldReceive("checkExternalUser")->withAnyArgs()->andReturn(null);

        $repoTicketMock = Mockery::mock(ISummitAttendeeTicketRepository::class);
        $repoTicketMock->shouldReceive("getBySummitAndExternalOrderIdAndExternalAttendeeIdExclusiveLock")->withAnyArgs()->andReturn(null);
        $repoTicketMock->shouldReceive("existNumber")->withAnyArgs()->andReturn(false);

        $repoAttendeeMock = Mockery::mock(ISummitAttendeeRepository::class);
        $repoAttendeeMock->shouldReceive("getBySummitAndEmail")->withAnyArgs()->andReturn(null);
        $repoAttendeeMock->shouldReceive("getBySummitAndExternalOrderIdAndExternalAttendeeIdExclusiveLock")->withAnyArgs()->andReturn(null);
        $repoAttendeeMock->shouldReceive("getBySummitAndExternalId")->withAnyArgs()->andReturn(null);


        App::singleton(
            IMemberService::class,
            function () use($memberServiceMock){
                return $memberServiceMock;
            });

        App::singleton(
            ISummitAttendeeTicketRepository::class,
            function () use($repoTicketMock){
                return $repoTicketMock;
            });

        App::singleton(
            ISummitAttendeeRepository::class,
            function () use($repoAttendeeMock){
                return $repoAttendeeMock;
            });

        $jsonResponse = sprintf(<<<JSON
[{"type":"%s","userId":"0CBl5NpPDg5kcFXzXhHkSx","email":"test@samsung.com","forum":"SFF \u0026 SAFE™ Forum","session":"SFF \u0026 SAFE™ 2023,Tech Session I - Advanced Technology and Design Infrastructure","country":"United States","firstName":"JP","lastName":"Maxwell","companyName":"Samsung","companyType":"Samsung","jobFunction":"Architect","jobTitle":"Architect","groupId":"Attendee","additional":"V5riM96EwXCfocPdp3WGeq,ReR7Jyqm5LEWYgWaIpiqiC"}]
JSON, RequestTypes::EmailCheck);

        $enc = AES::encrypt(self::$summit->getExternalRegistrationFeedApiKey(), $jsonResponse);

        $dict_data = ['data' => $enc->getData()];

        // mock events response
        $streamMock = Mockery::mock(StreamInterface::class);
        $streamMock->shouldReceive('getContents')->andReturn(json_encode($dict_data));

        $responseMock = Mockery::mock(ResponseInterface::class);
        $responseMock->shouldReceive('getStatusCode')->andReturn(200);
        $responseMock->shouldReceive('getBody')->andReturn($streamMock);

        $clientMock = Mockery::mock(ClientInterface::class);
        $clientMock->shouldReceive('request')->withAnyArgs()->andReturn($responseMock);

        // replace implementation with mock on IOC containter
        App::singleton(ClientInterface::class, function() use ($clientMock){
            return $clientMock;
        });

        $criteria = "test@samsung.com";
        $factory = App::make(ITicketFinderStrategyFactory::class);
        $strategy = $factory->build(self::$summit, $criteria);

        $this->assertTrue(!is_null($strategy));
        $this->assertTrue($strategy instanceof TicketFinderByExternalFeedStrategy);

        $res = $strategy->find();

        $this->assertTrue($res instanceof SummitAttendeeTicket);

        $this->assertTrue($res->getExternalOrderId() == "0CBl5NpPDg5kcFXzXhHkSx");
        $this->assertTrue($res->getOwnerEmail() === "test@samsung.com");
        $this->assertTrue($res->getBadge()->hasFeatureByName("Attendee"));
    }

    public function testTicketFinderStrategyQRCriteria(){

        self::$summit->setExternalSummitId(ForumTypes::SAFE);
        self::$summit->setExternalRegistrationFeedApiKey("12345601234567890123456789012345");
        self::$summit->setExternalRegistrationFeedType(ISummitExternalRegistrationFeedType::Samsung);
        self::$em->persist(self::$summit);
        self::$em->flush();

        $memberServiceMock = Mockery::mock(IMemberService::class);
        $memberServiceMock->shouldReceive("checkExternalUser")->withAnyArgs()->andReturn(null);

        $repoTicketMock = Mockery::mock(ISummitAttendeeTicketRepository::class);
        $repoTicketMock->shouldReceive("getBySummitAndExternalOrderIdAndExternalAttendeeIdExclusiveLock")->withAnyArgs()->andReturn(null);
        $repoTicketMock->shouldReceive("existNumber")->withAnyArgs()->andReturn(false);

        $repoAttendeeMock = Mockery::mock(ISummitAttendeeRepository::class);
        $repoAttendeeMock->shouldReceive("getBySummitAndEmail")->withAnyArgs()->andReturn(null);
        $repoAttendeeMock->shouldReceive("getBySummitAndExternalOrderIdAndExternalAttendeeIdExclusiveLock")->withAnyArgs()->andReturn(null);
        $repoAttendeeMock->shouldReceive("getBySummitAndExternalId")->withAnyArgs()->andReturn(null);

        App::singleton(
            IMemberService::class,
            function () use($memberServiceMock){
                return $memberServiceMock;
            });

        App::singleton(
            ISummitAttendeeTicketRepository::class,
            function () use($repoTicketMock){
                return $repoTicketMock;
            });

        App::singleton(
            ISummitAttendeeRepository::class,
            function () use($repoAttendeeMock){
                return $repoAttendeeMock;
            });

        $jsonResponse = sprintf(<<<JSON
[{"type":"%s","userId":"0CBl5NpPDg5kcFXzXhHkSx","email":"test@samsung.com","forum":"SFF \u0026 SAFE™ Forum","session":"SFF \u0026 SAFE™ 2023,Tech Session I - Advanced Technology and Design Infrastructure","country":"United States","firstName":"JP","lastName":"Maxwell","companyName":"Samsung","companyType":"Samsung","jobFunction":"Architect","jobTitle":"Architect","groupId":"Attendee","additional":"V5riM96EwXCfocPdp3WGeq,ReR7Jyqm5LEWYgWaIpiqiC"}]
JSON, RequestTypes::EmailCheck);

        $enc = AES::encrypt(self::$summit->getExternalRegistrationFeedApiKey(), $jsonResponse);

        $dict_data = ['data' => $enc->getData()];

        // mock events response
        $streamMock = Mockery::mock(StreamInterface::class);
        $streamMock->shouldReceive('getContents')->andReturn(json_encode($dict_data));

        $responseMock = Mockery::mock(ResponseInterface::class);
        $responseMock->shouldReceive('getStatusCode')->andReturn(200);
        $responseMock->shouldReceive('getBody')->andReturn($streamMock);

        $clientMock = Mockery::mock(ClientInterface::class);
        $clientMock->shouldReceive('request')->withAnyArgs()->andReturn($responseMock);

        // replace implementation with mock on IOC containter
        App::singleton(ClientInterface::class, function() use ($clientMock){
            return $clientMock;
        });

        $criteria = base64_encode('{"userId":"0CBl5NpPDg5kcFXzXhHkSx"}');
        $factory = App::make(ITicketFinderStrategyFactory::class);
        $strategy = $factory->build(self::$summit, $criteria);

        $this->assertTrue(!is_null($strategy));
        $this->assertTrue($strategy instanceof TicketFinderByExternalFeedStrategy);

        $res = $strategy->find();

        $this->assertTrue($res instanceof SummitAttendeeTicket);

        $this->assertTrue($res->getExternalOrderId() == "0CBl5NpPDg5kcFXzXhHkSx");
        $this->assertTrue($res->getOwnerEmail() === "test@samsung.com");
        $this->assertTrue($res->getBadge()->hasFeatureByName("Attendee"));
    }
}