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
use App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType;
use App\Models\Foundation\Summit\Repositories\ISponsorRepository;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgeRepository;
use App\Services\Model\Imp\SponsorUserInfoGrantService;
use App\Services\Model\ISponsorUserInfoGrantService;
use App\Services\Utils\Exceptions\UnacquiredLockException;
use App\Services\Utils\ILockManagerService;
use Doctrine\DBAL\Driver\PDO\Exception as PDODriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Illuminate\Support\Facades\App;
use LaravelDoctrine\ORM\Facades\Registry;
use Libs\ModelSerializers\AbstractSerializer;
use libs\utils\ITransactionService;
use Mockery;
use models\main\Group;
use models\main\IMemberRepository;
use models\summit\ISponsorUserInfoGrantRepository;
use models\summit\ISummitAttendeeRepository;
use models\summit\Sponsor;
use models\summit\SponsorBadgeScan;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeBadge;
use models\summit\SummitAttendeeTicket;
use models\summit\SummitLeadReportSetting;
use models\summit\SummitOrder;
use models\utils\SilverstripeBaseModel;
/**
 * Class OAuth2SummitBadgeScanApiControllerTest
 */
class OAuth2SummitBadgeScanApiControllerTest extends ProtectedApiTestCase
{
    use InsertSummitTestData;

    private $external_sponsor_group;

    private $sponsor_group;

    protected function setUp():void
    {
        parent::setUp();
        self::$defaultMember = self::$member;
        self::$defaultMember2 = self::$member2;
        self::insertSummitTestData();

        $this->external_sponsor_group = new Group();
        $this->external_sponsor_group->setCode(IGroup::SponsorExternalUsers);
        $this->external_sponsor_group->setTitle(IGroup::SponsorExternalUsers);
        self::$em->persist($this->external_sponsor_group);

        $this->sponsor_group = new Group();
        $this->sponsor_group->setCode(IGroup::Sponsors);
        $this->sponsor_group->setTitle(IGroup::Sponsors);
        self::$em->persist($this->sponsor_group);

        // Pre-wire self::$member as a permissioned sponsor user for sponsors[0] so that
        // tests which exercise the happy path do not need per-test boilerplate.
        self::$member->add2Group($this->sponsor_group);
        $sponsor0 = self::$sponsors[0];
        $sponsor0->addUser(self::$member);
        self::$em->persist(self::$member);
        self::$em->persist($sponsor0);
        self::$em->flush();

        // Write IGroup::Sponsors into Sponsor_Users.Permissions so hasSponsorMembershipsFor passes.
        self::$member->addSponsorPermission($sponsor0->getId(), IGroup::Sponsors);
    }

    protected function tearDown():void
    {
        Mockery::close();
        self::clearSummitTestData();
        parent::tearDown();
    }

    public function testAddEncryptedBadgeScan(){
        // set test data
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$member->add2Group($this->external_sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        self::$member->addSponsorPermission($sponsor->getId(), IGroup::SponsorExternalUsers);

        $attendee =  self::$summit->getAttendees()[0];

        self::$summit->setQRCodesEncKey('35NVOF4I5T6AAM28IJPKB8KRUW98KPDO');
        self::$em->persist(self::$summit);
        self::$em->flush();

        $this->assertTrue($sponsor->hasUser(self::$member));
        $this->assertGreaterThan(0, self::$member->getAccessibleSponsorsBySummit(self::$summit)->count());

        $badge = $attendee->getFirstTicket()->getBadge();
        $badge_qr_code = $badge->generateQRCode();

        $params = [
            'id' => self::$summit->getId()
        ];

        $data = [
            'qr_code'    => base64_encode($badge_qr_code),
            'scan_date'  => 1572019200,
            'sponsor_id' => $sponsor->getId(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(201);
        $content = $response->getContent();
        $badge_scan = json_decode($content);
        $this->assertNotNull($badge_scan);
        $this->assertEquals(self::$member->getId(), $badge_scan->scanned_by_id);
        $this->assertEquals($badge->getId(), $badge_scan->badge_id);
        $this->assertEquals($sponsor->getId(), $badge_scan->sponsor_id);
    }

    public function testAddBadgeScanWithOneSponsorPerMember(){
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();

        $data = [
            'qr_code'    => $badge->generateQRCode(),
            'scan_date'  => 1572019200,
            'sponsor_id' => $sponsor->getId(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $scan = json_decode($content);
        $this->assertTrue(!is_null($scan));
        $this->assertEquals(\models\summit\SponsorBadgeScan::Source_QR, $scan->source);
        $this->assertEquals($sponsor->getId(), $scan->sponsor_id);
        return $scan;
    }

    /**
     * SUP-86b9fp53j: the scanning app retries an upload whenever its own
     * client-side timeout elapses, with no guarantee the original request
     * didn't already reach the server and commit - the retry carries the
     * exact same qr_code/scan_date/sponsor_id as the first attempt, since
     * the app never changes a scan's captured timestamp between attempts.
     * Two POSTs of that identical payload must produce exactly one
     * SponsorBadgeScan, with the second response returning the same one
     * the first created (not a validation error, and not a second row).
     */
    public function testAddBadgeScanIsIdempotentOnRetry(){
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();

        // Generated once and reused across both requests: a real retry
        // resends the exact same body, it doesn't re-derive the QR code.
        $data = [
            'qr_code'    => $badge->generateQRCode(),
            'scan_date'  => 1572019200,
            'sponsor_id' => $sponsor->getId(),
        ];
        $body = json_encode($data);

        $first_response = $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            $body
        );

        $this->assertResponseStatus(201);
        $first_scan = json_decode($first_response->getContent());
        $this->assertTrue(!is_null($first_scan));

        $second_response = $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            $body
        );

        $this->assertResponseStatus(201);
        $second_scan = json_decode($second_response->getContent());
        $this->assertTrue(!is_null($second_scan));

        $this->assertEquals($first_scan->id, $second_scan->id,
            "a retry of the identical scan must return the same entity, not create a new one");

        $count = self::$em->getRepository(\models\summit\SponsorBadgeScan::class)
            ->count(['sponsor' => $sponsor, 'badge' => $badge]);
        $this->assertEquals(1, $count,
            "exactly one SponsorBadgeScan row must exist for this sponsor+badge+scan_date, not two");
    }

    /**
     * A different scan_date for the same sponsor+badge must NOT be
     * deduplicated - it's a genuine second scan (e.g. the sponsor scanned
     * this attendee again later), not a retry of the same attempt.
     */
    public function testAddBadgeScanWithDifferentScanDateIsNotDeduplicated(){
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();
        $qr_code = $badge->generateQRCode();

        $first_response = $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode(['qr_code' => $qr_code, 'scan_date' => 1572019200, 'sponsor_id' => $sponsor->getId()])
        );
        $this->assertResponseStatus(201);
        $first_scan = json_decode($first_response->getContent());

        $second_response = $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode(['qr_code' => $qr_code, 'scan_date' => 1572019260, 'sponsor_id' => $sponsor->getId()])
        );
        $this->assertResponseStatus(201);
        $second_scan = json_decode($second_response->getContent());

        $this->assertNotEquals($first_scan->id, $second_scan->id,
            "a genuinely later scan of the same badge must not be collapsed into the earlier one");
    }

    /**
     * The two tests above prove the end result (one row survives two
     * identical POSTs), but a plain "check then insert" with no locking at
     * all would pass them too, since PHPUnit calls are strictly sequential -
     * they never actually overlap two in-flight requests. This test proves
     * the lock itself is what SponsorUserInfoGrantService::addBadgeScan
     * acquires: holding the exact lock name it should use externally, then
     * calling the real service directly (bypassing HTTP, so the thrown
     * exception type is visible), and asserting it fails to acquire the
     * lock and gives up - the concurrency-closing mechanism this fix
     * actually depends on for a genuine race, not just the happy path.
     */
    public function testAddBadgeScanBlocksOnAConcurrentLockHolder(){
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();
        $qr_code = $badge->generateQRCode();
        $scan_date_epoch = 1572019200;

        // Same construction as SponsorUserInfoGrantService::addBadgeScanLocked's
        // $lock_name - deliberately duplicated (not called via a shared
        // constant) so this test also catches a future change to that
        // format silently no longer matching what's held here.
        $lock_name = sprintf('badge_scan.%d.%d.%d.lock', $sponsor->getId(), $badge->getId(), $scan_date_epoch);

        $lock_service = App::make(ILockManagerService::class);
        $held_token = $lock_service->acquireLock($lock_name, 10);

        $data = [
            'qr_code'    => $qr_code,
            'scan_date'  => $scan_date_epoch,
            'sponsor_id' => $sponsor->getId(),
        ];

        $service = App::make(ISponsorUserInfoGrantService::class);

        $threw = false;
        try {
            $service->addBadgeScan(self::$summit, self::$member, $data);
        } catch (UnacquiredLockException $ex) {
            $threw = true;
        } finally {
            $lock_service->releaseLock($lock_name, $held_token);
        }
        $this->assertTrue($threw,
            "addBadgeScan must fail to acquire a lock already held under the exact name this test holds - ".
            "either it isn't locking on (sponsor, badge, scan_date) at all, or the name format drifted");

        // With the external holder gone, the same call now succeeds.
        $scan = $service->addBadgeScan(self::$summit, self::$member, $data);
        $this->assertNotNull($scan);
        $this->assertEquals($sponsor->getId(), $scan->getSponsor()->getId());
    }

    public function testAddBadgeScanByAttendeeEmail(){
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        // Build a dedicated single-ticket attendee+order+badge inline to avoid the
        // shared-badge quirk in InsertSummitTestData (defaultMember has 5 tickets
        // sharing one badge, so only the last ticket's TicketID FK is persisted).
        $attendee = new SummitAttendee();
        $attendee->setEmail('badge-scan-email-target@example.com');
        $attendee->setFirstName('Badge');
        $attendee->setSurname('ScanTarget');

        $order = new SummitOrder();
        $order->setOwner(self::$defaultMember);
        $order->setSummit(self::$summit);

        $ticket = new SummitAttendeeTicket();
        $ticket->setTicketType(self::$default_ticket_type);
        $ticket->activate();
        $order->addTicket($ticket);
        $attendee->addTicket($ticket);

        $badge = new SummitAttendeeBadge();
        $badge->setType(self::$default_badge_type);
        $ticket->setBadge($badge);

        $order->setPaid();
        $order->generateNumber();
        $ticket->generateNumber();
        $ticket->generateQRCode();
        $badge->generateQRCode();

        self::$summit->addAttendee($attendee);
        self::$summit->addOrder($order);
        self::$em->persist($attendee);
        self::$em->persist($order);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'attendee_email' => $attendee->getEmail(),
            'scan_date'      => 1572019200,
            'sponsor_id'     => $sponsor->getId(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $scan = json_decode($content);
        $this->assertTrue(!is_null($scan));
        $this->assertEquals(self::$member->getId(), $scan->scanned_by_id);
        $this->assertEquals($badge->getId(), $scan->badge_id);
        $this->assertEquals(\models\summit\SponsorBadgeScan::Source_Attendee_Email, $scan->source);
        $this->assertEquals($sponsor->getId(), $scan->sponsor_id);
        return $scan;
    }

    public function testAddBadgeScanByAttendeeEmailWithNoQRCode(){
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        // Build a dedicated single-ticket attendee+order+badge inline, but
        // DO NOT generate the badge QR code to reproduce the null QR code bug.
        $attendee = new SummitAttendee();
        $attendee->setEmail('badge-scan-no-qr@example.com');
        $attendee->setFirstName('NoQR');
        $attendee->setSurname('Badge');

        $order = new SummitOrder();
        $order->setOwner(self::$defaultMember);
        $order->setSummit(self::$summit);

        $ticket = new SummitAttendeeTicket();
        $ticket->setTicketType(self::$default_ticket_type);
        $ticket->activate();
        $order->addTicket($ticket);
        $attendee->addTicket($ticket);

        $badge = new SummitAttendeeBadge();
        $badge->setType(self::$default_badge_type);
        $ticket->setBadge($badge);

        $order->setPaid();
        $order->generateNumber();
        $ticket->generateNumber();
        $ticket->generateQRCode();
        // OMIT: $badge->generateQRCode(); — this is the bug trigger

        self::$summit->addAttendee($attendee);
        self::$summit->addOrder($order);
        self::$em->persist($attendee);
        self::$em->persist($order);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'attendee_email' => $attendee->getEmail(),
            'scan_date'      => 1572019200,
            'sponsor_id'     => $sponsor->getId(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $scan = json_decode($content);
        $this->assertTrue(!is_null($scan));
        $this->assertEquals(self::$member->getId(), $scan->scanned_by_id);
        $this->assertEquals($badge->getId(), $scan->badge_id);
        $this->assertEquals(\models\summit\SponsorBadgeScan::Source_Attendee_Email, $scan->source);
        $this->assertEquals($sponsor->getId(), $scan->sponsor_id);
    }

    public function testAddBadgeScanMissingQrCodeAndEmailFails(){
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'scan_date' => 1572019200,
        ];

        $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(412);
    }

    public function testAddBadgeScanFailsWhenSponsorHasNoPermissionSlug()
    {
        // member2 is in the global sponsors group so hasSponsorMembershipsFor doesn't
        // short-circuit, but the Sponsor_Users row has no permission slug
        // (simulates the MQ group event never arriving).
        self::$member2->add2Group($this->sponsor_group);
        $sponsor = self::$sponsors[0];
        $sponsor->addUser(self::$member2);
        self::$em->persist(self::$member2);
        self::$em->persist($sponsor);
        self::$em->flush();
        // Sponsor_Users row was created with Permissions = NULL — deliberately no addSponsorPermission call.

        // Impersonate member2 for this request.
        self::$service->setUserId(self::$member2->getUserExternalId());
        self::$service->setUserExternalId(self::$member2->getUserExternalId());
        self::$service->setUserEmail(self::$member2->getEmail());
        self::$service->setUserFirstName(self::$member2->getFirstName());
        self::$service->setUserLastName(self::$member2->getLastName());

        $params = [
            'id' => self::$summit->getId(),
        ];

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();

        $data = [
            'qr_code'   => $badge->generateQRCode(),
            'scan_date' => 1572019200,
        ];

        $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(412);
    }

    public function testAddBadgeScanByUnknownAttendeeEmailFails(){
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        $params = [
            'id' => self::$summit->getId(),
        ];

        $data = [
            'attendee_email' => 'no-such-attendee@example.com',
            'scan_date' => 1572019200,
        ];

        $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(404);
    }

    public function testAddBadgeScanWithMultipleSponsorsWithoutSponsorId()
    {
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor1 = self::$sponsors[0];
        $sponsor1->addUser(self::$member);
        self::$em->persist($sponsor1);

        $sponsor2 = self::$sponsors[1];
        $sponsor2->addUser(self::$member);
        self::$em->persist($sponsor2);

        self::$em->flush();

        self::$member->addSponsorPermission($sponsor1->getId(), IGroup::Sponsors);
        self::$member->addSponsorPermission($sponsor2->getId(), IGroup::Sponsors);

        $this->assertGreaterThan(1, self::$member->getAccessibleSponsorsBySummit(self::$summit)->count());

        $params = [
            'id' => self::$summit->getId(),
        ];

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();

        $data = [
            'qr_code'   => $badge->generateQRCode(),
            'scan_date' => 1572019200,
        ];

        $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(412);
    }

    public function testAddBadgeScanWithMultipleSponsorsWithSponsorId()
    {
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor1 = self::$sponsors[0];
        $sponsor1->addUser(self::$member);
        self::$em->persist($sponsor1);

        $sponsor2 = self::$sponsors[1];
        $sponsor2->addUser(self::$member);
        self::$em->persist($sponsor2);

        self::$em->flush();

        self::$member->addSponsorPermission($sponsor1->getId(), IGroup::Sponsors);
        self::$member->addSponsorPermission($sponsor2->getId(), IGroup::Sponsors);

        $this->assertGreaterThan(1, self::$member->getAccessibleSponsorsBySummit(self::$summit)->count());

        $params = [
            'id' => self::$summit->getId(),
        ];

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();

        $data = [
            'qr_code'    => $badge->generateQRCode(),
            'scan_date'  => 1572019200,
            'sponsor_id' => $sponsor1->getId(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $scan = json_decode($content);
        $this->assertNotNull($scan);
        $this->assertEquals(self::$member->getId(), $scan->scanned_by_id);
        $this->assertEquals($badge->getId(), $scan->badge_id);
        $this->assertEquals($sponsor1->getId(), $scan->sponsor_id);
    }

    /** Admin path: admin can attribute a scan to any summit sponsor, not just one they belong to. */
    public function testAddBadgeScanAsAdminForNonMemberSponsor()
    {
        // Keep the default admin posture (no clearGroups) — isAuthzFor() returns true via token external groups.
        $sponsor0 = self::$sponsors[0];
        $sponsor0->addUser(self::$member);
        self::$em->persist($sponsor0);
        self::$em->flush();
        self::$member->addSponsorPermission($sponsor0->getId(), IGroup::Sponsors);

        // sponsor1 is the target: self::$member is NOT a user of it.
        $sponsor1 = self::$sponsors[1];

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge    = $attendee->getFirstTicket()->getBadge();

        $params = ['id' => self::$summit->getId()];
        $data   = [
            'qr_code'    => $badge->generateQRCode(),
            'scan_date'  => 1572019200,
            'sponsor_id' => $sponsor1->getId(),
        ];

        $response = $this->action(
            "POST",
            "OAuth2SummitBadgeScanApiController@add",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $scan = json_decode($content);
        $this->assertNotNull($scan);
        $this->assertEquals($sponsor1->getId(), $scan->sponsor_id);
    }

    public function testUpdateBadgeScan(){
        $scan = $this->testAddBadgeScanWithOneSponsorPerMember();

        $params = [
            'id' => self::$summit->getId(),
            'scan_id' => $scan->id,
        ];

        $data = [
            'extra_questions' => [
                ['question_id' => 519, 'answer' => 'None'],
            ],
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitBadgeScanApiController@update",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $scan = json_decode($content);
        $this->assertTrue(!is_null($scan));
        return $scan;
    }

    public function testGetAllMyBadgeScans(){
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        $params = [
            'id'    =>  self::$summit->getId(),
            'filter'=> 'attendee_email=@santi',
            'expand' => 'sponsor,badge,badge.ticket,badge.ticket.owner,extra_question_answers'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeScanApiController@getAllMyBadgeScans",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $data = json_decode($content);
        $this->assertTrue(!is_null($data));
        return $data;
    }

    public function testCheckInBadgeScan(){
        $params = [
            'id' => self::$summit->getId(),
        ];

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();
        $data = [
            'qr_code' => $badge->generateQRCode(),
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitBadgeScanApiController@checkIn",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
        $scan = json_decode($content);
        $this->assertTrue(!is_null($scan));
        return $scan;
    }

    public function testGetAllSummitBadgeScans(){

        $params = [
            'id'    =>  self::$summit->getId(),
            'expand' => 'sponsor,badge,badge.ticket,badge.ticket.owner,extra_question_answers'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeScanApiController@getAllBySummit",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $page = json_decode($content);
        $this->assertTrue(!is_null($page));
        return $page;
    }

    public function testGetSummitBadgeScan(){
        $badge_scan = $this->testAddBadgeScanWithOneSponsorPerMember();

        $params = [
            'id'      =>  self::$summit->getId(),
            'scan_id' =>  $badge_scan->id,
            'expand'  => 'sponsor,badge,badge.ticket,badge.ticket.owner,extra_question_answers'
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeScanApiController@get",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $scan = json_decode($content);
        $this->assertTrue(!is_null($scan));
        return $scan;
    }

    public function testExportSummitBadgeScans(){

        $this->testAddBadgeScanWithOneSponsorPerMember();

        $params = [
            'id'    =>  self::$summit->getId(),
            'columns'  => 'scan_date,attendee_first_name,attendee_last_name,attendee_email,attendee_company',
        ];

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeScanApiController@getAllBySummitCSV",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertNotEmpty($content);
    }

    public function testExportSummitBadgeScansWithReportSettingsRestriction(){

        $this->testAddBadgeScanWithOneSponsorPerMember();

        $sponsor = self::$summit->getSummitSponsors()[0];
        if (!$sponsor instanceof Sponsor) self::fail();

        $sponsor_question = $sponsor->getExtraQuestions()[0];
        if (!$sponsor_question instanceof SummitSponsorExtraQuestionType) self::fail();

        $params = [
            'id'    =>  self::$summit->getId(),
            'columns'  => 'scan_date,attendee_first_name,attendee_last_name,attendee_email,attendee_company',
        ];

        // set up allowed columns

        $allowed_columns = [
            'scan_date',
            'extra_questions' => [
                [
                    'id'   => $sponsor_question->getId(),
                    'name' => $sponsor_question->getName()
                ]
            ]
        ];

        $data = [
            'allowed_columns' => $allowed_columns
        ];

        $this->action(
            "PUT",
            "OAuth2SummitApiController@updateLeadReportSettings",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $this->assertResponseStatus(201);

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeScanApiController@getAllBySummitCSV",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertNotEmpty($content);
        $this->assertTrue(str_contains($content, AbstractSerializer::getCSVLabel($sponsor_question->getLabel())));
        $this->assertTrue(str_contains($content, 'scan_date'));
    }

    public function testExportSummitBadgeScansWithAllReportSettingsRestriction(){

        $this->testAddBadgeScanWithOneSponsorPerMember();

        $sponsor = self::$summit->getSummitSponsors()[0];
        if (!$sponsor instanceof Sponsor) self::fail();

        $sponsor_question = $sponsor->getExtraQuestions()[0];
        if (!$sponsor_question instanceof SummitSponsorExtraQuestionType) self::fail();

        $params = [
            'id'    =>  self::$summit->getId(),
            'columns'  => 'scan_date,attendee_first_name,attendee_last_name,attendee_email,attendee_company',
        ];

        $allowed_columns = [
            'scan_date',
            SummitLeadReportSetting::AttendeeExtraQuestionsKey => [],
            SummitLeadReportSetting::SponsorExtraQuestionsKey => []
        ];

        $data = [
            'allowed_columns' => $allowed_columns
        ];

        $response = $this->action(
            "PUT",
            "OAuth2SummitApiController@updateLeadReportSettings",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders(),
            json_encode($data)
        );

        $content = $response->getContent();
        $settings = json_decode($content);

        $this->assertResponseStatus(201);

        $response = $this->action(
            "GET",
            "OAuth2SummitBadgeScanApiController@getAllBySummitCSV",
            $params,
            [],
            [],
            [],
            $this->getAuthHeaders()
        );

        $content = $response->getContent();
        $this->assertResponseStatus(200);
        $this->assertNotEmpty($content);
    }

    /**
     * The dedup lock cannot guarantee one row per scan on its own - it has a
     * TTL, no renewal and no fencing token, so it can lapse while the
     * transaction it wraps is still running. The SponsorBadgeScan.ScanDedupKey
     * UNIQUE index is what does, so this asserts the index is actually there
     * and rejecting: two rows carrying the same key must not both persist,
     * whatever the service layer above happens to do.
     */
    public function testScanDedupKeyUniqueIndexRejectsADuplicateRow(){
        $sponsor = self::$summit->getSummitSponsors()[0];
        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();
        $scan_date = new \DateTime("@1572019200");

        $dedup_key = \models\summit\SponsorBadgeScan::buildDedupKey($sponsor, $badge, $scan_date);

        $first = new \models\summit\SponsorBadgeScan();
        $first->setScanDate($scan_date);
        $first->setQRCode('dedup-index-test');
        $first->setUser(self::$member);
        $first->setBadge($badge);
        $first->setNotes('');
        $first->setScanDedupKey($dedup_key);
        $sponsor->addUserInfoGrant($first);
        self::$em->persist($first);
        self::$em->flush();

        // Byte-identical key, which is the whole point: a second physical row for
        // one scan is what the production bug produced and what the index forbids.
        $second = new \models\summit\SponsorBadgeScan();
        $second->setScanDate($scan_date);
        $second->setQRCode('dedup-index-test');
        $second->setUser(self::$member);
        $second->setBadge($badge);
        $second->setNotes('');
        $second->setScanDedupKey($dedup_key);
        $sponsor->addUserInfoGrant($second);
        self::$em->persist($second);

        $threw = false;
        try {
            self::$em->flush();
        } catch (UniqueConstraintViolationException $ex) {
            $threw = true;
        }

        $this->assertTrue($threw,
            "SponsorBadgeScan_ScanDedupKey must reject a second row with the same dedup key - ".
            "without that index the lock's TTL is the only thing preventing duplicates, which it cannot be");
    }

    /**
     * The index above only protects rows that actually carry a key, so this
     * asserts the service populates it: a scan created through the real
     * addBadgeScan path must come out with the ScanDedupKey its
     * (sponsor, badge, scan_date) tuple implies. If this regressed, every new
     * row would go in with NULL - which MySQL allows without limit in a UNIQUE
     * index - and the duplicate protection would silently be gone.
     */
    public function testAddBadgeScanPopulatesTheScanDedupKey(){
        self::$member->clearGroups();
        self::$member->add2Group($this->sponsor_group);
        self::$em->persist(self::$member);
        self::$em->flush();

        $sponsor = self::$summit->getSummitSponsors()[0];
        $sponsor->addUser(self::$member);
        self::$em->persist($sponsor);
        self::$em->flush();

        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();
        $scan_date_epoch = 1572019200;

        $service = App::make(ISponsorUserInfoGrantService::class);
        $scan = $service->addBadgeScan(self::$summit, self::$member, [
            'qr_code'    => $badge->generateQRCode(),
            'scan_date'  => $scan_date_epoch,
            'sponsor_id' => $sponsor->getId(),
        ]);

        $this->assertNotNull($scan);
        $this->assertEquals(
            sprintf('%d:%d:%d', $sponsor->getId(), $badge->getId(), $scan_date_epoch),
            $scan->getScanDedupKey(),
            "addBadgeScan must stamp the dedup key on the new scan, otherwise the UNIQUE index protects nothing"
        );
    }

    /**
     * Builds the real SponsorUserInfoGrantService, except that every
     * findExistingBadgeScan call goes through $find($call_number, $real),
     * where $real() runs the real repository query. Blinding that check is
     * how a sequential test reproduces the race the UNIQUE index exists for:
     * the pre-INSERT existence check misses a row that is already committed,
     * exactly as it does when the lock lapsed and a concurrent request won.
     * Throwing from it is how a test injects a failure into a given attempt
     * of the transaction.
     * @param \Closure $find
     * @param int $calls counts every findExistingBadgeScan call, by reference
     * @return ISponsorUserInfoGrantService
     */
    private function buildServiceWithExistenceCheck(\Closure $find, int &$calls): ISponsorUserInfoGrantService
    {
        $real_repository = App::make(ISponsorUserInfoGrantRepository::class);
        $repository = Mockery::mock(ISponsorUserInfoGrantRepository::class);
        $repository->shouldReceive('findExistingBadgeScan')
            ->andReturnUsing(function(Sponsor $sponsor, SummitAttendeeBadge $badge, \DateTime $scan_date) use($real_repository, $find, &$calls){
                $calls++;
                return $find($calls, fn() => $real_repository->findExistingBadgeScan($sponsor, $badge, $scan_date));
            });

        return new SponsorUserInfoGrantService(
            $repository,
            App::make(ISummitAttendeeRepository::class),
            App::make(ISummitAttendeeBadgeRepository::class),
            App::make(ISponsorRepository::class),
            App::make(IMemberRepository::class),
            App::make(ITransactionService::class),
            App::make(ILockManagerService::class)
        );
    }

    /**
     * Persists the scan that "won the race": same (sponsor, badge, scan_date)
     * and therefore the same ScanDedupKey the service is about to INSERT.
     * @param Sponsor $sponsor
     * @param SummitAttendeeBadge $badge
     * @param \DateTime $scan_date
     * @return SponsorBadgeScan
     */
    private function insertWinningScan(Sponsor $sponsor, SummitAttendeeBadge $badge, \DateTime $scan_date): SponsorBadgeScan
    {
        $winner = new SponsorBadgeScan();
        $winner->setScanDate($scan_date);
        $winner->setQRCode('dedup-race-winner');
        $winner->setUser(self::$member);
        $winner->setBadge($badge);
        $winner->setNotes('');
        $winner->setScanDedupKey(SponsorBadgeScan::buildDedupKey($sponsor, $badge, $scan_date));
        $sponsor->addUserInfoGrant($winner);
        self::$em->persist($winner);
        self::$em->flush();
        return $winner;
    }

    /**
     * The UniqueConstraintViolationException handler in addBadgeScanLocked is
     * what actually guarantees one row per scan, and the sequential POST tests
     * above never reach it (their second request stops at the existence check).
     * Here the check is blinded once, so the INSERT really hits the index: the
     * flush fails, the EntityManager is closed, and the handler has to re-read
     * the winner in a fresh transaction and return it instead of failing or
     * writing a second row.
     */
    public function testAddBadgeScanResolvesAUniqueViolationToTheWinningScan(){
        $sponsor = self::$summit->getSummitSponsors()[0];
        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();
        $scan_date_epoch = 1572019200;
        $scan_date = new \DateTime("@$scan_date_epoch");

        $winner = $this->insertWinningScan($sponsor, $badge, $scan_date);
        $winner_id = $winner->getId();
        $dedup_key = $winner->getScanDedupKey();

        $calls = 0;
        $service = $this->buildServiceWithExistenceCheck(fn(int $n, \Closure $real) => $n === 1 ? null : $real(), $calls);

        $scan = $service->addBadgeScan(self::$summit, self::$member, [
            'qr_code'    => $badge->generateQRCode(),
            'scan_date'  => $scan_date_epoch,
            'sponsor_id' => $sponsor->getId(),
        ]);

        $this->assertEquals(2, $calls,
            "the existence check must run once before the INSERT and once more in the unique-violation handler");
        $this->assertEquals($winner_id, $scan->getId(),
            "a unique violation on the dedup key must resolve to the scan that won the race");

        // The failed flush closed the manager the test started with.
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $count = $em->getRepository(SponsorBadgeScan::class)->count(['scan_dedup_key' => $dedup_key]);
        $this->assertEquals(1, $count, "the losing request must not leave a second row behind");
    }

    /**
     * The handler only swallows a violation it can attribute to this scan's
     * own tuple. If the re-read finds nothing, the violation came from some
     * other constraint and must surface, not be turned into a bogus success.
     * Simulated by keeping the existence check blind on the re-read too.
     */
    public function testAddBadgeScanRethrowsAUniqueViolationItCannotResolve(){
        $sponsor = self::$summit->getSummitSponsors()[0];
        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();
        $scan_date_epoch = 1572019200;

        $this->insertWinningScan($sponsor, $badge, new \DateTime("@$scan_date_epoch"));

        $calls = 0;
        $service = $this->buildServiceWithExistenceCheck(fn() => null, $calls);

        $threw = false;
        try {
            $service->addBadgeScan(self::$summit, self::$member, [
                'qr_code'    => $badge->generateQRCode(),
                'scan_date'  => $scan_date_epoch,
                'sponsor_id' => $sponsor->getId(),
            ]);
        } catch (UniqueConstraintViolationException $ex) {
            $threw = true;
        }

        $this->assertEquals(2, $calls,
            "the handler must have re-checked for the winner before giving up");
        $this->assertTrue($threw,
            "a unique violation the handler cannot match to an existing scan must be rethrown");
    }

    /**
     * A reconnectable error (deadlock, lock wait timeout, lost connection)
     * makes DoctrineTransactionService discard the EntityManager and re-run
     * the closure against a fresh one. If the closure reused the Sponsor,
     * badge and Member resolved before the transaction, they would be unknown
     * to that new manager: the cascade from the sponsor never fires, the
     * COMMIT succeeds with nothing in it and a transient scan with id 0 comes
     * back as a 201 - a scan the app then marks uploaded and never resends.
     * The retried attempt must persist exactly one real row instead.
     */
    public function testAddBadgeScanPersistsTheScanWhenTheFirstAttemptDeadlocks(){
        $sponsor = self::$summit->getSummitSponsors()[0];
        $attendee = self::$summit->getAttendeeByMemberId(self::$defaultMember->getId());
        $badge = $attendee->getFirstTicket()->getBadge();
        $scan_date_epoch = 1572019200;
        $sponsor_id = $sponsor->getId();
        $badge_id = $badge->getId();

        $calls = 0;
        $service = $this->buildServiceWithExistenceCheck(function(int $n, \Closure $real){
            if($n === 1)
                throw new DeadlockException(
                    PDODriverException::new(new \PDOException('Deadlock found when trying to get lock; try restarting transaction', 1213)),
                    null
                );
            return $real();
        }, $calls);

        $scan = $service->addBadgeScan(self::$summit, self::$member, [
            'qr_code'    => $badge->generateQRCode(),
            'scan_date'  => $scan_date_epoch,
            'sponsor_id' => $sponsor_id,
        ]);

        $this->assertEquals(2, $calls, "the deadlocked attempt must have been retried once");
        $this->assertGreaterThan(0, $scan->getId(),
            "the retried attempt must return a persisted scan, not a transient one with id 0");

        // The deadlock discarded the manager the test started with.
        $em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        $count = $em->getRepository(SponsorBadgeScan::class)->count([
            'scan_dedup_key' => sprintf('%d:%d:%d', $sponsor_id, $badge_id, $scan_date_epoch),
        ]);
        $this->assertEquals(1, $count, "exactly one row must be committed by the retried attempt");
    }
}
