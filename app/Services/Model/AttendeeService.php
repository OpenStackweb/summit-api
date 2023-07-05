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

use App\Jobs\Emails\ProcessAttendeesEmailRequestJob;
use App\Models\Foundation\Summit\Repositories\ISummitAttendeeBadgeRepository;
use App\Services\Model\Strategies\EmailActions\EmailActionsStrategyFactory;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\ICompanyRepository;
use models\main\IMemberRepository;
use models\main\Member;
use models\summit\factories\SummitAttendeeFactory;
use models\summit\ISummitAttendeeRepository;
use models\summit\ISummitAttendeeTicketRepository;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\ISummitRepository;
use models\summit\ISummitTicketTypeRepository;
use models\summit\Summit;
use models\summit\SummitAttendee;
use models\summit\SummitAttendeeBadge;
use models\summit\SummitAttendeeTicket;
use services\apis\IEventbriteAPI;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Class AttendeeService
 * @package App\Services\Model
 */
final class AttendeeService extends AbstractService implements IAttendeeService
{
    /**
     * @var ISummitAttendeeRepository
     */
    private $attendee_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISummitTicketTypeRepository
     */
    private $ticket_type_repository;

    /**
     * @var ISummitAttendeeTicketRepository
     */
    private $ticket_repository;

    /**
     * @var IEventbriteAPI
     */
    private $eventbrite_api;

    /**
     * @var ISummitRegistrationPromoCodeRepository
     */
    private $promo_code_repository;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitAttendeeBadgeRepository
     */
    private $badge_repository;

    /**
     * @var ICompanyRepository
     */
    private $company_repository;

    /**
     * @param ISummitAttendeeRepository $attendee_repository
     * @param IMemberRepository $member_repository
     * @param ISummitAttendeeTicketRepository $ticket_repository
     * @param ISummitTicketTypeRepository $ticket_type_repository
     * @param ISummitRegistrationPromoCodeRepository $promo_code_repository
     * @param ISummitRepository $summit_repository
     * @param ISummitAttendeeBadgeRepository $badge_repository
     * @param IEventbriteAPI $eventbrite_api
     * @param ICompanyRepository $company_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitAttendeeRepository $attendee_repository,
        IMemberRepository $member_repository,
        ISummitAttendeeTicketRepository $ticket_repository,
        ISummitTicketTypeRepository $ticket_type_repository,
        ISummitRegistrationPromoCodeRepository $promo_code_repository,
        ISummitRepository $summit_repository,
        ISummitAttendeeBadgeRepository $badge_repository,
        IEventbriteAPI $eventbrite_api,
        ICompanyRepository $company_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->attendee_repository    = $attendee_repository;
        $this->ticket_repository      = $ticket_repository;
        $this->member_repository      = $member_repository;
        $this->ticket_type_repository = $ticket_type_repository;
        $this->promo_code_repository  = $promo_code_repository;
        $this->eventbrite_api         = $eventbrite_api;
        $this->summit_repository      = $summit_repository;
        $this->badge_repository       = $badge_repository;
        $this->company_repository     = $company_repository;
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @return mixed|SummitAttendee
     * @throws \Exception
     */
    public function addAttendee(Summit $summit, array $data)
    {
        return $this->tx_service->transaction(function() use($summit, $data){

            $member    = null;
            $member_id = $data['member_id'] ?? 0;
            $member_id = intval($member_id);
            $email     = $data['email'] ?? null;

            if($member_id > 0 && !empty($email)){
                // both are defined
                throw new ValidationException("you should define a member_id or an email, not both");
            }

            if($member_id > 0 ) {

                $member = $this->member_repository->getById($member_id);
                if (is_null($member) || !$member instanceof Member)
                    throw new EntityNotFoundException("member not found");

                $old_attendee = $this->attendee_repository->getBySummitAndMember($summit, $member);

                if (!is_null($old_attendee))
                    throw new ValidationException(sprintf("attendee already exist for summit id %s and member id %s", $summit->getId(), $member->getIdentifier()));

            }

            if(!empty($email)) {
                $old_attendee = $this->attendee_repository->getBySummitAndEmail($summit, trim($email));
                if (!is_null($old_attendee))
                    throw new ValidationException(sprintf("attendee already exist for summit id %s and email %s", $summit->getId(), trim($data['email'])));
            }

            $attendee = SummitAttendeeFactory::build($summit, $data, $member);

            $this->attendee_repository->add($attendee);

            $attendee->updateStatus();

            return $attendee;
        });
    }

    /**
     * @param Summit $summit
     * @param int $attendee_id
     * @return void
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteAttendee(Summit $summit, $attendee_id)
    {
        return $this->tx_service->transaction(function() use($summit, $attendee_id){

            $attendee = $summit->getAttendeeById($attendee_id);
            if(is_null($attendee))
                throw new EntityNotFoundException();

            $this->attendee_repository->delete($attendee);
        });
    }

    /**
     * @param Summit $summit
     * @param int $attendee_id
     * @param array $data
     * @return SummitAttendee
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateAttendee(Summit $summit, $attendee_id, array $data)
    {
        return $this->tx_service->transaction(function() use($summit, $attendee_id, $data){

            $attendee = $summit->getAttendeeById($attendee_id);
            if(is_null($attendee))
                throw new EntityNotFoundException(sprintf("Attendee does not belongs to summit id %s.", $summit->getId()));

            $member = null;
            if(isset($data['member_id']) && intval($data['member_id']) > 0) {
                $member_id = intval($data['member_id']);
                $member = $this->member_repository->getById($member_id);

                if (is_null($member) || !$member instanceof Member)
                    throw new EntityNotFoundException("Member not found.");

                $old_attendee = $this->attendee_repository->getBySummitAndMember($summit, $member);
                if(!is_null($old_attendee) && $old_attendee->getId() != $attendee->getId())
                    throw new ValidationException(sprintf("Another attendee (%s) already exist for summit id %s and member id %s.", $old_attendee->getId(), $summit->getId(), $member->getIdentifier()));
            }

            if(isset($data['email'])) {
                $old_attendee = $this->attendee_repository->getBySummitAndEmail($summit, trim($data['email']));
                if(!is_null($old_attendee) && $old_attendee->getId() != $attendee->getId())
                    throw new ValidationException(sprintf("Attendee already exist for summit id %s and email %s.", $summit->getId(), trim($data['email'])));
            }

            // check if attendee already exist for this summit

            SummitAttendeeFactory::populate($summit, $attendee , $data, $member);
            $attendee->updateStatus();
            return $attendee;
        });
    }

    /**
     * @param SummitAttendee $attendee
     * @param int $ticket_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return SummitAttendeeTicket
     */
    public function deleteAttendeeTicket(SummitAttendee $attendee, $ticket_id)
    {
        return $this->tx_service->transaction(function() use($attendee, $ticket_id){
            $ticket = $attendee->getTicketById($ticket_id);
            if(is_null($ticket)){
                throw new EntityNotFoundException(sprintf("ticket id %s does not belongs to attendee id %s", $ticket_id, $attendee->getId()));
            }
            $attendee->removeTicket($ticket);
        });
    }

    /**
     * @param Summit $summit
     * @param int $page_nbr
     * @return mixed
     */
    public function updateRedeemedPromoCodes(Summit $summit, $page_nbr = 1)
    {
        return $this->tx_service->transaction(function() use($summit, $page_nbr){
            $response = $this->eventbrite_api->getAttendees($summit, $page_nbr);

            if(!isset($response['pagination'])) return false;
            if(!isset($response['attendees'])) return false;
            $pagination = $response['pagination'];
            $attendees  = $response['attendees'];
            $has_more_items = boolval($pagination['has_more_items']);

            foreach($attendees as $attendee){
                if(!isset($attendee['promotional_code'])) continue;
                $promotional_code = $attendee['promotional_code'];
                if(!isset($promotional_code['code'])) continue;
                $code = $promotional_code['code'];

                $promo_code = $this->promo_code_repository->getByCode($code);
                if(is_null($promo_code)) continue;
                $promo_code->setRedeemed(true);
            }

            return $has_more_items;
        });
    }

    /**
     * @param Summit $summit
     * @param SummitAttendee $attendee
     * @param Member $other_member
     * @param int $ticket_id
     * @return SummitAttendeeTicket
     * @throws \Exception
     */
    public function reassignAttendeeTicketByMember(Summit $summit, SummitAttendee $attendee, Member $other_member, int $ticket_id):SummitAttendeeTicket
    {
        return $this->tx_service->transaction(function() use($summit, $attendee, $other_member, $ticket_id){
            $ticket = $this->ticket_repository->getByIdExclusiveLock($ticket_id);

            if(is_null($ticket) || !$ticket instanceof SummitAttendeeTicket){
                throw new EntityNotFoundException("ticket not found");
            }

            $new_owner = $this->attendee_repository->getBySummitAndMember($summit, $other_member);
            if(is_null($new_owner)){
                $new_owner = SummitAttendeeFactory::build($summit,[
                    'first_name' => $other_member->getFirstName(),
                    'last_name'  => $other_member->getLastName(),
                    'email'      => $other_member->getEmail(),
                ], $other_member);
                $this->attendee_repository->add($new_owner);
            }

            $attendee->sendRevocationTicketEmail($ticket);

            $attendee->removeTicket($ticket);

            $new_owner->addTicket($ticket);

            $ticket->generateQRCode();
            $ticket->generateHash();
            if($summit->isRegistrationSendTicketEmailAutomatically())
                $new_owner->sendInvitationEmail($ticket);

            return $ticket;
        });
    }


    /**
     * @param Summit $summit
     * @param SummitAttendee $attendee
     * @param int $ticket_id
     * @param array $payload
     * @return SummitAttendeeTicket
     * @throws \Exception
     */
    public function reassignAttendeeTicket(Summit $summit, SummitAttendee $attendee, int $ticket_id, array $payload):SummitAttendeeTicket
    {
        return $this->tx_service->transaction(function() use($summit, $attendee, $ticket_id, $payload){

            Log::debug
            (
                sprintf
                (
                    "AttendeeService::reassignAttendeeTicket summit %s attendee %s ticket %s payload %s.",
                    $summit->getId(),
                    $attendee->getId(),
                    $ticket_id,
                    json_encode($payload)
                )
            );

            $ticket = $this->ticket_repository->getByIdExclusiveLock($ticket_id);

            if(is_null($ticket) || !$ticket instanceof SummitAttendeeTicket){
                throw new EntityNotFoundException("ticket not found.");
            }

            $attendee_email = $payload['attendee_email'] ?? null;

            Log::debug
            (
                sprintf
                (
                    "AttendeeService::reassignAttendeeTicket trying to get attendee %s for summit %s.",
                    $attendee_email,
                    $summit->getId()
                )
            );

            $new_owner = $this->attendee_repository->getBySummitAndEmail($summit , $attendee_email);

            if(is_null($new_owner)){
                Log::debug(sprintf("AttendeeService::reassignAttendeeTicket attendee %s does no exists .. creating it.", $attendee_email));
                $attendee_payload = [
                    'email'  => $attendee_email
                ];

                $new_owner = SummitAttendeeFactory::build
                (
                    $summit,
                    $attendee_payload,
                    $this->member_repository->getByEmail($attendee_email)
                );

                $this->attendee_repository->add($new_owner, true);
            }

            $attendee_payload = [];

            if(isset($payload['attendee_first_name']))
                $attendee_payload['first_name'] = $payload['attendee_first_name'];

            if(isset($payload['attendee_last_name']))
                $attendee_payload['last_name'] = $payload['attendee_last_name'];

            if(isset($payload['attendee_company']))
                $attendee_payload['company'] = $payload['attendee_company'];

            if(isset($payload['extra_questions']))
                $attendee_payload['extra_questions'] = $payload['extra_questions'];

            SummitAttendeeFactory::populate($summit, $new_owner, $attendee_payload, $new_owner->getMember());

            Log::debug
            (
                sprintf
                (
                    "AttendeeService::reassignAttendeeTicket revoking ticket %s from attendee %s (%s).",
                    $ticket_id,
                    $attendee->getId(),
                    $attendee->getEmail()
                )
            );

            $attendee->sendRevocationTicketEmail($ticket);
            $attendee->removeTicket($ticket);
            $attendee->updateStatus();

            Log::debug
            (
                sprintf
                (
                    "AttendeeService::reassignAttendeeTicket adding ticket %s to attendee %s (%s).",
                    $ticket_id,
                    $new_owner->getId(),
                    $new_owner->getEmail()
                )
            );

            $new_owner->addTicket($ticket);

            $ticket->generateQRCode();
            $ticket->generateHash();
            $new_owner->updateStatus();


            if($summit->isRegistrationSendTicketEmailAutomatically()) {
                Log::debug
                (
                    sprintf
                    (
                        "AttendeeService::reassignAttendeeTicket sending invitation email to new owner %s (%s).",
                         $new_owner->getId(),
                         $new_owner->getEmail()
                    )
                );
                $new_owner->sendInvitationEmail($ticket);
            }

            return $ticket;
        });

    }

    /**
     * @inheritDoc
     */
    public function triggerSend(Summit $summit, array $payload, $filter = null): void
    {
        ProcessAttendeesEmailRequestJob::dispatch($summit, $payload, $filter);
    }

    /**
     * @inheritDoc
     */
    public function send(int $summit_id, array $payload, Filter $filter = null): void
    {
        $emailActionsStrategyFactory = new EmailActionsStrategyFactory();
        $flow_event = trim($payload['email_flow_event']);
        $done = isset($payload['attendees_ids']); // we have provided only ids and not a criteria
        $page = 1;
        $count = 0;
        $maxPageSize = 100;

        do {
            Log::debug(sprintf("AttendeeService::send summit id %s flow_event %s filter %s", $summit_id, $flow_event, is_null($filter) ? '' : $filter->__toString()));

            $ids = $this->tx_service->transaction(function () use ($summit_id, $payload, $filter, $page, $maxPageSize) {
                if (isset($payload['attendees_ids'])) {
                    Log::debug(sprintf("AttendeeService::send summit id %s attendees_ids %s", $summit_id,
                        json_encode($payload['attendees_ids'])));
                    return $payload['attendees_ids'];
                }
                Log::debug(sprintf("AttendeeService::send summit id %s getting by filter", $summit_id));
                if (is_null($filter)) {
                    $filter = new Filter();
                }
                if (!$filter->hasFilter("summit_id"))
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit_id));

                Log::debug(sprintf("AttendeeService::send page %s", $page));
                return $this->attendee_repository->getAllIdsByPage(new PagingInfo($page, $maxPageSize), $filter);
            });

            Log::debug(sprintf("AttendeeService::send summit id %s flow_event %s filter %s page %s got %s records", $summit_id, $flow_event, is_null($filter) ? '' : $filter->__toString(), $page, count($ids)));

            if (!count($ids)) {
                // if we are processing a page, then break it
                Log::debug(sprintf("AttendeeService::send summit id %s page is empty, ending processing.", $summit_id));
                break;
            }

            foreach ($ids as $attendee_id) {
                try {
                    $this->tx_service->transaction(function () use ($flow_event, $attendee_id, $emailActionsStrategyFactory) {

                        Log::debug(sprintf("AttendeeService::send processing attendee id  %s", $attendee_id));

                        $attendee = $this->attendee_repository->getByIdExclusiveLock(intval($attendee_id));
                        if (is_null($attendee) || !$attendee instanceof SummitAttendee) return;

                        $strategy = $emailActionsStrategyFactory->build($flow_event);
                        if ($strategy != null) {
                            $strategy->process($attendee);
                        }
                    });
                } catch (\Exception $ex) {
                    Log::warning($ex);
                }
                $count++;
            }
            $page++;
        } while(!$done);

        Log::debug(sprintf("AttendeeService::send summit id %s flow_event %s filter %s had processed %s records",
            $summit_id, $flow_event, is_null($filter) ? '' : $filter->__toString(), $count));
    }

    public function recalculateAttendeeStatus(int $summit_id):void{
        $this->tx_service->transaction(function() use($summit_id){
            $summit = $this->summit_repository->getById($summit_id);
            if(is_null($summit) || !$summit instanceof Summit) return;

            foreach($summit->getAttendees() as $attendee){
                $attendee->updateStatus();
            }
        });
    }

    public function doVirtualCheckin(Summit $summit, int $attendee_id): ?SummitAttendee
    {
        return $this->tx_service->transaction(function() use($summit, $attendee_id){

            $attendee = $summit->getAttendeeById($attendee_id);
            if(is_null($attendee))
                throw new EntityNotFoundException(sprintf("Attendee does not belongs to summit id %s.", $summit->getId()));

            $attendee->doVirtualChecking();

            return $attendee;
        });
    }

    public function doCheckIn(Summit $summit, String $qr_code): void
    {
        $this->tx_service->transaction(function() use($summit, $qr_code){

            $fields        = SummitAttendeeBadge::parseQRCode($qr_code);
            $ticket_number = $fields['ticket_number'];
            $prefix        = $fields['prefix'];

            if($summit->getBadgeQRPrefix() != $prefix)
                throw new ValidationException
                (
                    sprintf
                    (
                        "%s qr code is not valid for summit %s.",
                        $qr_code,
                        $summit->getId()
                    )
                );

            $badge = $this->badge_repository->getBadgeByTicketNumber($ticket_number);

            if(is_null($badge))
                throw new EntityNotFoundException("Badge not found.");

            $ticket = $badge->getTicket();

            if (is_null($ticket))
                throw new EntityNotFoundException("Badge ticket not found.");

            if (!$ticket->hasOwner())
                throw new EntityNotFoundException("Badge ticket hasn't an owner.");

            $owner = $ticket->getOwner();

            if ($owner->hasCheckedIn())
                throw new ValidationException
                (
                    sprintf
                    (
                        "Attendee %s is already checked in for summit %s.",
                        $owner->getFullName(),
                        $summit->getId()
                    )
                );

            $owner->setSummitHallCheckedIn(true);
        });
    }

    /**
     * @param int $member_id
     */
    public function updateAttendeesByMemberId(int $member_id): void
    {
        $this->tx_service->transaction(function() use($member_id){

            $member = $this->member_repository->getByIdRefreshed($member_id);

            if(!$member instanceof Member){
                Log::debug(sprintf("AttendeeService::updateAttendeesByMemberId member %s not found.", $member_id));
                return;
            }

            $fname = $member->getFirstName();
            $lname = $member->getLastName();
            $email = $member->getEmail();
            // free text
            $company_name = $member->getCompany();

            Log::debug
            (
                sprintf
                (
                    "AttendeeService::updateAttendeesByMemberId member %s fname %s lname %s email %s company name %s",
                    $member_id,
                    $fname,
                    $lname,
                    $email,
                    $company_name
                )
            );

            $attendees = $this->attendee_repository->getByMember($member);
            if(!is_null($attendees)) {
                foreach ($attendees as $attendee) {
                    if (!$attendee instanceof SummitAttendee) continue;
                    Log::debug(sprintf("AttendeeService::updateAttendeesByMemberId updating attendee %s with member %s", $attendee->getId(), $member_id));
                    // try to register the company for the summit and get it
                    $summit = $attendee->getSummit();
                    if (!$summit instanceof Summit) continue;
                    $company = $this->company_repository->getByName($company_name);

                    $attendee->setFirstName($fname);
                    $attendee->setSurname($lname);
                    $attendee->setEmail($email);
                    // company logic
                    if(!empty($company_name)) {
                        $company = $this->company_repository->getByName($company_name);
                        if (!is_null($company)) {
                            $attendee->setCompany($company);
                            $company_name = $company->getName();
                        }
                        $attendee->setCompanyName($company_name);
                    }
                    else{
                        $attendee->clearCompany();
                    }
                }
            }

            $attendees = $this->attendee_repository->getByEmailAndMemberNotSet($member->getEmail());
            if(!is_null($attendees)) {
                foreach ($attendees as $attendee) {
                    if (!$attendee instanceof SummitAttendee) continue;
                    Log::debug(sprintf("AttendeeService::updateAttendeesByMemberId updating attendee %s with member %s ( member null )", $attendee->getId(), $member_id));

                    // try to register the company for the summit and get it
                    $summit = $attendee->getSummit();
                    if (!$summit instanceof Summit) continue;

                    // set the member
                    $attendee->setMember($member);
                    $attendee->setFirstName($fname);
                    $attendee->setSurname($lname);
                    $attendee->setEmail($email);
                    // company logic
                    if(!empty($company_name)) {
                        $company = $this->company_repository->getByName($company_name);
                        if (!is_null($company)) {
                            $attendee->setCompany($company);
                            $company_name = $company->getName();
                        }

                        if (!empty($company_name))
                            $attendee->setCompanyName($company_name);
                    }
                    else{
                        $attendee->clearCompany();
                    }
                }
            }
        });
    }
}