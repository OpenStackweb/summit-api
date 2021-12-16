<?php namespace App\Services\Model;
/**
 * Copyright 2018 OpenStack Foundation
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

use App\Events\NewMember;
use App\Models\Foundation\Main\IGroup;
use App\Models\Foundation\Main\Repositories\ILegalDocumentRepository;
use App\Models\Foundation\Summit\Repositories\ISummitOrderRepository;
use App\Services\Apis\IExternalUserApi;
use App\Services\Model\dto\ExternalUserDTO;
use DateTime;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use libs\utils\ICacheService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Affiliation;
use models\main\Group;
use models\main\IGroupRepository;
use models\main\IMemberRepository;
use models\main\IOrganizationRepository;
use models\main\LegalAgreement;
use models\main\Member;
use models\main\Organization;
use models\summit\ISpeakerRegistrationRequestRepository;
use models\summit\ISummitAttendeeRepository;
use models\summit\SummitAttendee;
use models\summit\SummitOrder;

/**
 * Class MemberService
 * @package App\Services\Model
 */
final class MemberService
    extends AbstractService
    implements IMemberService
{

    // in secs
    const SYNCH_GROUPS_TTL = 60;
    /**
     * @var ICacheService
     */
    private $cache_service;

    /**
     * @var IOrganizationRepository
     */
    private $organization_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var IExternalUserApi
     */
    private $user_ext_api;

    /**
     * @var IGroupRepository
     */
    private $group_repository;

    /**
     * @var IExternalUserApi
     */
    private $external_user_api;

    /**
     * @var ISpeakerRegistrationRequestRepository
     */
    private $speaker_registration_request_repository;

    /**
     * @var ILegalDocumentRepository
     */
    private $legal_document_repository;

    /**
     * @var ISummitOrderRepository
     */
    private $order_repository;

    /**
     * @var ISummitAttendeeRepository
     */
    private $attendee_repository;

    /**
     * MemberService constructor.
     * @param IMemberRepository $member_repository
     * @param IOrganizationRepository $organization_repository
     * @param IExternalUserApi $user_ext_api
     * @param IGroupRepository $group_repository
     * @param ICacheService $cache_service
     * @param IExternalUserApi $external_user_api
     * @param ISpeakerRegistrationRequestRepository $speaker_registration_request_repository
     * @param ILegalDocumentRepository $legal_document_repository
     * @param ISummitOrderRepository $order_repository
     * @param ISummitAttendeeRepository $attendee_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IMemberRepository $member_repository,
        IOrganizationRepository $organization_repository,
        IExternalUserApi $user_ext_api,
        IGroupRepository $group_repository,
        ICacheService $cache_service,
        IExternalUserApi $external_user_api,
        ISpeakerRegistrationRequestRepository $speaker_registration_request_repository,
        ILegalDocumentRepository $legal_document_repository,
        ISummitOrderRepository $order_repository,
        ISummitAttendeeRepository $attendee_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->organization_repository = $organization_repository;
        $this->member_repository = $member_repository;
        $this->user_ext_api = $user_ext_api;
        $this->group_repository = $group_repository;
        $this->cache_service = $cache_service;
        $this->external_user_api = $external_user_api;
        $this->speaker_registration_request_repository = $speaker_registration_request_repository;
        $this->legal_document_repository = $legal_document_repository;
        $this->order_repository = $order_repository;
        $this->attendee_repository = $attendee_repository;
    }

    /**
     * @param Member $member
     * @param int $affiliation_id
     * @param array $data
     * @return Affiliation
     */
    public function updateAffiliation(Member $member, $affiliation_id, array $data)
    {
        return $this->tx_service->transaction(function () use ($member, $affiliation_id, $data) {
            $affiliation = $member->getAffiliationById($affiliation_id);
            if (is_null($affiliation))
                throw new EntityNotFoundException(sprintf("affiliation id %s does not belongs to member id %s", $affiliation_id, $member->getId()));

            if (isset($data['is_current'])) {
                $affiliation->setIsCurrent(boolval($data['is_current']));
            }

            if (isset($data['start_date'])) {
                $start_date = intval($data['start_date']);
                $affiliation->setStartDate(new DateTime("@$start_date"));
            }

            if (!$affiliation->isCurrent() && isset($data['end_date'])) {
                $end_date = intval($data['end_date']);
                $affiliation->setEndDate($end_date > 0 ? new DateTime("@$end_date") : null);
            }

            if (isset($data['organization_id'])) {
                $org = $this->organization_repository->getById(intval($data['organization_id']));
                if (is_null($org))
                    throw new EntityNotFoundException(sprintf("organization id %s not found", $data['organization_id']));
                $affiliation->setOrganization($org);
            }

            if (isset($data['organization_name'])) {
                $org = $this->organization_repository->getByName(trim($data['organization_name']));
                if (is_null($org)) {
                    $org = new Organization();
                    $org->setName(trim($data['organization_name']));
                    $this->organization_repository->add($org);
                }

                $affiliation->setOrganization($org);
            }

            if (isset($data['job_title'])) {
                $affiliation->setJobTitle(trim($data['job_title']));
            }

            if ($affiliation->isCurrent()) {
                $affiliation->clearEndDate();
            }

            return $affiliation;
        });
    }

    /**
     * @param Member $member
     * @param $affiliation_id
     * @return void
     */
    public function deleteAffiliation(Member $member, $affiliation_id)
    {
        return $this->tx_service->transaction(function () use ($member, $affiliation_id) {
            $affiliation = $member->getAffiliationById($affiliation_id);
            if (is_null($affiliation))
                throw new EntityNotFoundException(sprintf("affiliation id %s does not belongs to member id %s", $affiliation_id, $member->getId()));

            $member->removeAffiliation($affiliation);
        });
    }

    /**
     * @param Member $member
     * @param int $rsvp_id
     * @return void
     */
    public function deleteRSVP(Member $member, $rsvp_id)
    {
        return $this->tx_service->transaction(function () use ($member, $rsvp_id) {
            $rsvp = $member->getRsvpById($rsvp_id);
            if (is_null($rsvp))
                throw new EntityNotFoundException(sprintf("rsvp id %s does not belongs to member id %s", $rsvp_id, $member->getId()));

            $member->removeRsvp($rsvp);
        });
    }

    /**
     * @param Member $member
     * @param array $data
     * @return Affiliation
     */
    public function addAffiliation(Member $member, array $data)
    {
        return $this->tx_service->transaction(function () use ($member, $data) {

            $affiliation = new Affiliation();

            if (isset($data['is_current']))
                $affiliation->setIsCurrent(boolval($data['is_current']));
            if (isset($data['start_date'])) {
                $start_date = intval($data['start_date']);
                $affiliation->setStartDate(new DateTime("@$start_date"));
            }
            if (isset($data['end_date'])) {
                $end_date = intval($data['end_date']);
                $affiliation->setEndDate($end_date > 0 ? new DateTime("@$end_date") : null);
            }

            if (isset($data['organization_id'])) {
                $org = $this->organization_repository->getById(intval($data['organization_id']));
                if (is_null($org))
                    throw new EntityNotFoundException(sprintf("organization id %s not found", $data['organization_id']));
                $affiliation->setOrganization($org);
            }

            if (isset($data['organization_name'])) {
                $org = $this->organization_repository->getByName(trim($data['organization_name']));
                if (is_null($org)) {
                    $org = new Organization();
                    $org->setName(trim($data['organization_name']));
                    $this->organization_repository->add($org);
                }

                $affiliation->setOrganization($org);
            }

            if (isset($data['job_title'])) {
                $affiliation->setJobTitle(trim($data['job_title']));
            }

            if ($affiliation->isCurrent() && $affiliation->getEndDate() != null)
                throw new ValidationException
                (
                    sprintf
                    (
                        "in order to set affiliation as current end_date should be null"
                    )
                );

            $member->addAffiliation($affiliation);
            return $affiliation;
        });
    }

    /**
     * @param ExternalUserDTO $userDTO
     * @return Member
     * @throws \Exception
     */
    public function registerExternalUser(ExternalUserDTO $userDTO): Member
    {
        return $this->tx_service->transaction(function () use ($userDTO) {
            Log::debug
            (
                sprintf
                (
                    "MemberService::registerExternalUser - user_external_id %s email %s first_name %s last_name %s",
                    $userDTO->getId(),
                    $userDTO->getEmail(),
                    $userDTO->getFirstName(),
                    $userDTO->getLastName()
                )
            );
            $member = $this->member_repository->getByExternalIdExclusiveLock($userDTO->getId());
            if(is_null($member)) {
                $member = new Member();
                $member->setUserExternalId($userDTO->getId());
                $member->setActive($userDTO->isActive());
                $member->setEmailVerified($userDTO->isEmailVerified());
                $member->setEmail($userDTO->getEmail());
                $member->setFirstName($userDTO->getFirstName());
                $member->setLastName($userDTO->getLastName());
                $this->member_repository->add($member, true);
                Event::dispatch(new NewMember($member->getId()));
            }
            return $member;
        });
    }


    /**
     * @param $user_external_id
     * @return Member
     * @throws \Exception
     */
    public function registerExternalUserById($user_external_id): Member
    {
        return $this->tx_service->transaction(function () use ($user_external_id) {
            // get external user from IDP
            $user_data = $this->user_ext_api->getUserById($user_external_id);
            if(is_null($user_data) || !isset($user_data['email'])){
                Log::error(sprintf("MemberService::registerExternalUserById user_external_id %s does not exists.", $user_external_id));
                throw new EntityNotFoundException(sprintf("MemberService::registerExternalUserById user_external_id %s does not exists.", $user_external_id));
            }
            $email = trim($user_data['email']);
            // first by external id due email could be updated
            Log::debug(sprintf("MemberService::registerExternalUserById trying to get user by external id %s", $user_external_id));
            $member = $this->member_repository->getByExternalIdExclusiveLock(intval($user_external_id));
            // if we dont registered yet a member with that external id try to get by email
            if(is_null($member)) {
                Log::debug(sprintf("MemberService::registerExternalUserById trying to get user by email %s", $email));
                $member = $this->member_repository->getByEmail($email);
            }
            $is_new = false;
            if(is_null($member)) {
                Log::debug(sprintf("MemberService::registerExternalUserById %s does not exists , creating it ...", $email));
                $member = new Member();
                $member->setActive(boolval($user_data['active']));
                $member->setEmailVerified(boolval($user_data['email_verified']));
                $member->setEmail($email);
                $member->setFirstName(trim($user_data['first_name']));
                $member->setLastName(trim($user_data['last_name']));
                $member->setBio($user_data['bio']);
                $member->setUserExternalId($user_external_id);
                if(isset($user_data['pic']))
                    $member->setExternalPic($user_data['pic']);
                $this->member_repository->add($member, true);
                $is_new = true;
            }
            else {
                Log::debug(sprintf("MemberService::registerExternalUserById %s already exists", $email));
                $member->setActive(boolval($user_data['active']));
                $member->setEmailVerified(boolval($user_data['email_verified']));
                $member->setEmail($email);
                $member->setFirstName(trim($user_data['first_name']));
                $member->setLastName(trim($user_data['last_name']));
                $member->setBio($user_data['bio']);
                if(isset($user_data['pic']))
                    $member->setExternalPic($user_data['pic']);
                $member->setUserExternalId($user_external_id);
            }

            $this->synchronizeGroups($member, $user_data['groups']);
            // check speaker registration request by email and no member set
            Log::debug(sprintf("MemberService::registerExternalUserById trying to get former registration request by email %s", $email));
            $request = $this->speaker_registration_request_repository->getByEmail($email);
            if(!is_null($request) && $request->hasSpeaker()){
                Log::debug(sprintf("MemberService::registerExternalUserById got former registration request by email %s", $email));
                $speaker = $request->getSpeaker();
                if(!is_null($speaker))
                    if(!$speaker->hasMember()) {
                        Log::debug(sprintf("MemberService::registerExternalUserById setting current member to speaker %s", $speaker->getId()));
                        $speaker->setMember($member);
                    }
            }

            if($is_new)
                Event::dispatch(new NewMember($member->getId()));

            return $member;
        });
    }

    /**
     * @param mixed $user_external_id
     * @throws EntityNotFoundException
     */
    public function deleteExternalUserById($user_external_id): void
    {
        $this->tx_service->transaction(function () use ($user_external_id) {
            Log::debug(sprintf("MemberService::deleteExternalUserById trying to get user by external id %s", $user_external_id));
            $member = $this->member_repository->getByExternalIdExclusiveLock(intval($user_external_id));
            // if we dont registered yet a member with that external id try to get by email
            if(is_null($member)) {
                throw new EntityNotFoundException(sprintf("Member not found (%s)", $user_external_id));
            }
            Log::debug(sprintf("MemberService::deleteExternalUserById deleting user %s (%s)", $member->getId(), $member->getEmail()));
            $this->member_repository->delete($member);
        });
    }
    /**
     * @param Member $member
     * @param array $groups
     * @return Member
     * @throws \Exception
     */
    public function synchronizeGroups(Member $member, array $groups): Member
    {
        return $this->tx_service->transaction(function () use ($member, $groups) {

            $val = $this->cache_service->getSingleValue(sprintf("member_%s_sync_groups", $member->getId()));

            if(!empty($val)){
                Log::debug(sprintf("MemberService::synchronizeGroups member %s email %s synch already done", $member->getId(), $member->getEmail()));
                return $member;
            }

            $groups2Remove = [];

            Log::debug(sprintf("MemberService::synchronizeGroups member %s email %s", $member->getId(), $member->getEmail()));

            foreach($member->getGroups() as $group){
                // if this group was added from idp, clear it, just in case we were deleted from that group
                if(!in_array($group->getCode(), $groups)){
                    // do not remove if we are super admins, since we do need this group too ( backward compatibility with SS CMS)
                    if($group->getCode() == IGroup::Administrators && in_array(IGroup::SuperAdmins, $groups))
                        continue;

                    // skipping this groups bc are managed by SS side
                    if(in_array($group->getCode(), [IGroup::FoundationMembers, IGroup::CommunityMembers, IGroup::TrackChairs])){
                        Log::debug(sprintf("MemberService::synchronizeGroups skipping group %s removal", $group->getCode()));
                        continue;
                    }

                    Log::debug(sprintf("MemberService::synchronizeGroups member %s email %s marking group %s to remove (external) dues is not on member current groups", $member->getId(), $member->getEmail(), $group->getCode()));
                    $groups2Remove[] = $group;
                }
            }

            // remove all groups that arent on our IDP profile anymore ...
            foreach ($groups2Remove as $externalGroup){
                if($externalGroup->getCode() === IGroup::SuperAdmins && $member->belongsToGroup(IGroup::Administrators)){
                    $group = $this->group_repository->getBySlug(IGroup::Administrators);
                    if(!is_null($group)) {
                        Log::debug(sprintf("MemberService::synchronizeGroups member %s email %s removing from group %s due is also a super admin", $member->getId(), $member->getEmail(), $group->getCode()));
                        $member->removeFromGroup($group);
                    }
                }
                Log::debug(sprintf("MemberService::synchronizeGroups member %s email %s removing from group %s", $member->getId(), $member->getEmail(), $externalGroup->getCode()));
                $member->removeFromGroup($externalGroup);
            }

            // sync

            foreach ($groups as $code) {

                Log::debug(sprintf("MemberService::synchronizeGroups member %s email %s processing group code %s", $member->getId(), $member->getEmail(), $code));

                if(!$member->belongsToGroup($code)){
                    $group = $this->group_repository->getBySlug($code);
                    if (is_null($group)) {
                        // create it
                        Log::debug(sprintf("MemberService::synchronizeGroups member %s email %s - group %s does not exists!, .. creating it ", $member->getId(), $member->getEmail(), $code));
                        $group = new Group();
                        $group->setCode($code);
                        $group->setExternal();
                        $group->setDescription($code);
                        $group->setTitle($code);
                        $this->group_repository->add($group, true);
                    }
                    $group->setExternal();
                    Log::debug(sprintf("MemberService::synchronizeGroups member %s email %s adding to group %s", $member->getId(), $member->getEmail(), $code));
                    $member->add2Group($group);
                }

                // map from super admin to admin ( special case )
                if ($code === IGroup::SuperAdmins) {
                    Log::debug(sprintf("MemberService::synchronizeGroups member %s email %s is on group %s, should be added to group %s", $member->getId(), $member->getEmail(), $code, IGroup::Administrators));
                    if(!$member->belongsToGroup(IGroup::Administrators)) {
                        Log::debug(sprintf("MemberService::synchronizeGroups member %s email %s is not on group %s", $member->getId(), $member->getEmail(), IGroup::Administrators));
                        $group = $this->group_repository->getBySlug(IGroup::Administrators);
                        if (is_null($group)) {
                            // create it
                            Log::debug(sprintf("MemberService::synchronizeGroups member %s email %s - group %s does not exists!, .. creating it ", $member->getId(), $member->getEmail(), $code));
                            $group = new Group();
                            $group->setCode(IGroup::Administrators);
                            $group->setDescription(IGroup::Administrators);
                            $group->setTitle(IGroup::Administrators);
                            $this->group_repository->add($group, true);
                        }
                        $group->setExternal();
                        Log::debug(sprintf("MemberService::synchronizeGroups member %s email %s adding to group %s", $member->getId(), $member->getEmail(), $group->getCode()));
                        $member->add2Group($group);
                    }
                }

            }

            $this->cache_service->setSingleValue(sprintf("member_%s_sync_groups", $member->getId()), 1, self::SYNCH_GROUPS_TTL);
            return $member;
        });
    }

    /**
     * @param string $email
     * @return array|null
     * @throws \Exception
     */
    public function checkExternalUser(string $email) {
        Log::debug(sprintf("MemberService::checkExternalUser - trying to get member %s from user api", $email));
        $user = $this->external_user_api->getUserByEmail($email);
        // check if primary email is the same if not disregard
        Log::debug(sprintf("MemberService::checkExternalUser got entity %s for email %s", json_encode($user), $email));
        $primary_email = $user['email'] ?? null;
        if (strcmp(strtolower($primary_email), strtolower($email)) !== 0) {
            Log::debug
            (
                sprintf
                (
                    "MemberService::checkExternalUser primary email %s differs from original email %s",
                    $primary_email,
                    $email
                )
            );

            // email are not equals , then is not the user bc primary emails differs ( could be a
            // match on a secondary email)
            $user = null; // set null on user and proceed to emit a registration request.
        }

        return $user;
    }

    /**
     * @param string $email
     * @param string $first_name
     * @param string $last_name
     * @return array
     * @throws \Exception
     */
    public function emitRegistrationRequest(string $email, string $first_name, string $last_name):array{
      // user does not exists , emit a registration request
      return $this->external_user_api->registerUser
      (
        $email,
        $first_name,
        $last_name
      );
    }

    /**
     * @inheritDoc
     */
    public function signFoundationMembership(Member $member): Member
    {
        return $this->tx_service->transaction(function() use($member){
            if($member->isFoundationMember())
                throw new ValidationException(sprintf("Member %s is already a foundation member", $member->getId()));
            $group = $this->group_repository->getBySlug(IGroup::FoundationMembers);
            if(is_null($group))
                throw new EntityNotFoundException(sprintf("Group %s not found", IGroup::FoundationMembers));

            $member->add2Group($group);
            $document = $this->legal_document_repository->getBySlug(LegalAgreement::Slug);
            if(is_null($document))
                throw new EntityNotFoundException(sprintf("Legal Document %s not found.",LegalAgreement::Slug));
            $member->signFoundationMembership($document);

            return $member;
        });
    }

    /**
     * @inheritDoc
     */
    public function signCommunityMembership(Member $member): Member
    {
        return $this->tx_service->transaction(function() use($member){
            if($member->isFoundationMember()) {
                $member->resignFoundationMembership();
            }

            $group = $this->group_repository->getBySlug(IGroup::CommunityMembers);
            if(is_null($group))
                throw new EntityNotFoundException(sprintf("Group %s not found", IGroup::CommunityMembers));

            $member->add2Group($group);

            return $member;
        });
    }

    /**
     * @inheritDoc
     */
    public function resignMembership(Member $member)
    {
        return $this->tx_service->transaction(function() use($member){

            $member->resignMembership();

            $this->member_repository->delete($member);

        });
    }

    public function assocSummitOrders(int $member_id):void{

        $this->tx_service->transaction(function() use($member_id){
            Log::debug(sprintf("MemberService::assocSummitOrders trying to get member id %s", $member_id));
            $member = $this->member_repository->getById($member_id);
            if(is_null($member) || !$member instanceof Member) return;

            // associate orders
            $orders = $this->order_repository->getAllByOwnerEmailAndOwnerNotSet($member->getEmail());
            if(!is_null($orders)) {
                foreach ($orders as $order) {
                    if (!$order instanceof SummitOrder) continue;
                    Log::debug(sprintf("MemberService::assocSummitOrders got order %s for member %s", $order->getNumber(), $member_id));
                    $member->addSummitRegistrationOrder($order);
                }
            }

            // associate attendees/tickets
            $attendees = $this->attendee_repository->getByEmailAndMemberNotSet($member->getEmail());
            if(!is_null($attendees)) {
                foreach ($attendees as $attendee) {
                    if (!$attendee instanceof SummitAttendee) continue;
                    Log::debug(sprintf("MemberService::assocSummitOrders got attendee %s for member %s", $attendee->getId(), $member_id));
                    $attendee->setMember($member);
                }
            }

        });
    }

    public function updateExternalUser(int $member_id, ?string $first_name, ?string $last_name, ?string $company_name):void {
        Log::debug(sprintf("MemberService::updateExternalUser - sending new profile info to user api for member %s", $member_id));
        $this->external_user_api->updateUser($member_id, $first_name, $last_name, $company_name);
    }

    public function updatePendingRegistrationRequest(string $email, bool $is_redeemed, ?string $first_name, ?string $last_name,
                                                     ?string $company_name, ?string $country):void {
        Log::debug(sprintf("MemberService::updatePendingRegistrationRequest - sending new profile info to user api for member %s", $email));
        $res = $this->external_user_api->getUserRegistrationRequest($email, $first_name, $last_name, $is_redeemed);
        if (!is_null($res)) {
            $this->external_user_api->updateUserRegistrationRequest($res["id"], $first_name, $last_name, $company_name, $country);
        }
    }
}