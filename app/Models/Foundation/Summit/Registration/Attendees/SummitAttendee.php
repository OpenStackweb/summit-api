<?php namespace models\summit;
/**
 * Copyright 2015 OpenStack Foundation
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

use App\Jobs\Emails\InviteAttendeeTicketEditionMail;
use App\Jobs\Emails\RevocationTicketEmail;
use App\Jobs\Emails\SummitAttendeeTicketEmail;
use App\libs\Utils\PunnyCodeHelper;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionAnswer;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use App\Models\Foundation\Main\ExtraQuestions\ExtraQuestionAnswerHolder;
use Carbon\Carbon;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use libs\utils\TextUtils;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Company;
use models\main\Member;
use models\main\SummitMemberSchedule;
use models\oauth2\IResourceServerContext;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitAttendeeRepository")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="attendees"
 *     )
 * })
 * @ORM\Table(name="SummitAttendee")
 * Class SummitAttendee
 * @package models\summit
 */
class SummitAttendee extends SilverstripeBaseModel
{
    use ExtraQuestionAnswerHolder;

    const StatusIncomplete = 'Incomplete';
    const StatusComplete = 'Complete';
    const AllowedStatus = [self::StatusComplete, self::StatusIncomplete];

    /**
     * @ORM\Column(name="FirstName", type="string")
     * @var string
     */
    private $first_name;

    /**
     * @ORM\Column(name="Surname", type="string")
     * @var string
     */
    private $surname;

    /**
     * @ORM\Column(name="Email", type="string")
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(name="SharedContactInfo", type="boolean")
     * @var bool
     */
    private $share_contact_info;

    /**
     * @ORM\Column(name="DisclaimerAcceptedDate", type="datetime")
     * @var \DateTime
     */
    private $disclaimer_accepted_date;

    /**
     * @ORM\Column(name="SummitHallCheckedInDate", type="datetime")
     * @var \DateTime
     */
    private $summit_hall_checked_in_date;

    /**
     * @ORM\Column(name="SummitVirtualCheckedInDate", type="datetime")
     * @var \DateTime
     */
    private $summit_virtual_checked_in_date;

    /**
     * @ORM\Column(name="InvitationEmailSentDate", type="datetime")
     * @var \DateTime
     */
    private $invitation_email_sent_date;

    /**
     * @ORM\Column(name="PublicEditionEmailSentDate", type="datetime")
     * @var \DateTime
     */
    private $public_edition_email_sent_date;

    /**
     * @ORM\Column(name="LastReminderEmailSentDate", type="datetime")
     * @var \DateTime
     */
    private $last_reminder_email_sent_date;

    /**
     * @ORM\Column(name="SummitHallCheckedIn", type="boolean")
     * @var boolean
     */
    private $summit_hall_checked_in;

    /**
     * @ORM\Column(name="ExternalId", type="string")
     * @var string
     */
    private $external_id;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member")
     * @ORM\JoinColumn(name="MemberID", referencedColumnName="ID", nullable=true)
     * @var Member
     */
    private $member;

    /**
     * @ORM\OneToMany(targetEntity="SummitOrderExtraQuestionAnswer", mappedBy="attendee", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitOrderExtraQuestionAnswer[]
     */
    private $extra_question_answers;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\PresentationAttendeeVote", mappedBy="voter", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var PresentationAttendeeVote[]
     */
    private $presentation_votes;

    /**
     * @ORM\Column(name="Company", type="string")
     * @var string
     */
    private $company_name;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Company")
     * @ORM\JoinColumn(name="CompanyID", referencedColumnName="ID", nullable=true)
     * @var Company
     */
    private $company;

    /**
     * @ORM\Column(name="Status", type="string")
     * @var string
     */
    private $status;

    /**
     * @ORM\Column(name="AdminNotes", type="string")
     * @var string
     */
    private $admin_notes;

    /**
     * @return \DateTime|null
     */
    public function getSummitHallCheckedInDate(): ?\DateTime
    {
        return $this->summit_hall_checked_in_date;
    }

    /**
     * @return bool
     */
    public function getSummitHallCheckedIn()
    {
        return (bool)$this->summit_hall_checked_in;
    }

    /**
     * @param bool $summit_hall_checked_in
     */
    public function setSummitHallCheckedIn(bool $summit_hall_checked_in): void
    {
        Log::debug(sprintf("SummitAttendee::setSummitHallCheckedIn for attendee %s", $this->getId()));
        $this->summit_hall_checked_in = $summit_hall_checked_in;
        $this->summit_hall_checked_in_date = $summit_hall_checked_in ? new \DateTime('now', new \DateTimeZone('UTC')) : null;
    }

    public function hasCheckedIn(): bool
    {
        return (bool)$this->summit_hall_checked_in;
    }

    /**
     * @return boolean
     */
    public function getSharedContactInfo()
    {
        return $this->share_contact_info;
    }

    /**
     * @param boolean $share_contact_info
     */
    public function setShareContactInfo($share_contact_info)
    {
        $this->share_contact_info = $share_contact_info;
    }

    /**
     * @return int
     */
    public function getMemberId()
    {
        try {
            return is_null($this->member) ? 0 : $this->member->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasMember()
    {
        return $this->getMemberId() > 0;
    }

    /**
     * @ORM\OneToMany(targetEntity="SummitAttendeeTicket", mappedBy="owner", cascade={"persist", "remove"})
     * @var SummitAttendeeTicket[]
     */
    private $tickets;

    /**
     * @return SummitAttendeeTicket[]
     */
    public function getTickets()
    {
        return $this->tickets;
    }

    /**
     * @param SummitAttendeeTicket $ticket
     */
    public function addTicket(SummitAttendeeTicket $ticket)
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
        }
        $ticket->setOwner($this);
    }

    /**
     * @return Member
     */
    public function getMember(): ?Member
    {
        return $this->member;
    }

    /**
     * @param Member $member
     */
    public function setMember(Member $member)
    {
        $this->member = $member;
    }

    public function clearMember()
    {
        $this->member = null;
    }

    use SummitOwned;

    public function __construct()
    {
        parent::__construct();
        $this->share_contact_info = false;
        $this->summit_hall_checked_in = false;
        $this->tickets = new ArrayCollection();
        $this->extra_question_answers = new ArrayCollection();
        $this->disclaimer_accepted_date = null;
        $this->summit_virtual_checked_in_date = null;
        $this->invitation_email_sent_date = null;
        $this->public_edition_email_sent_date = null;
        $this->status = self::StatusIncomplete;
        $this->presentation_votes = new ArrayCollection();
    }

    public function isVirtualCheckedIn(): bool
    {
        return !is_null($this->summit_virtual_checked_in_date);
    }

    /**
     * @return SummitEventFeedback[]
     */
    public function getEmittedFeedback()
    {

        return $this->member->getFeedback()->matching
        (
            Criteria::create()->orderBy(["id" => Criteria::ASC])
        );
    }

    /**
     * @param SummitEvent $event
     * @throws ValidationException
     * @deprecated use Member::add2Schedule instead
     */
    public function add2Schedule(SummitEvent $event)
    {
        $this->member->add2Schedule($event);
    }

    /**
     * @param SummitEvent $event
     * @throws ValidationException
     * @deprecated use Member::removeFromSchedule instead
     */
    public function removeFromSchedule(SummitEvent $event)
    {
        $this->member->removeFromSchedule($event);
    }

    /**
     * @param SummitEvent $event
     * @return bool
     * @deprecated use Member::isOnSchedule instead
     */
    public function isOnSchedule(SummitEvent $event)
    {
        return $this->member->isOnSchedule($event);
    }

    /**
     * @param SummitEvent $event
     * @return null| SummitMemberSchedule
     * @deprecated use Member::getScheduleByEvent instead
     */
    public function getScheduleByEvent(SummitEvent $event)
    {
        return $this->member->getScheduleByEvent($event);
    }

    /**
     * @return SummitMemberSchedule[]
     * @deprecated use Member::getScheduleBySummit instead
     */
    public function getSchedule()
    {
        return $this->member->getScheduleBySummit($this->summit);
    }

    /**
     * @return int[]
     * @deprecated use Member::getScheduledEventsIds instead
     */
    public function getScheduledEventsIds()
    {
        return $this->member->getScheduledEventsIds($this->summit);
    }

    /**
     * @param int $event_id
     * @return null|RSVP
     * @deprecated use Member::getRsvpByEvent instead
     */
    public function getRsvpByEvent($event_id)
    {
        return $this->member->getRsvpByEvent($event_id);
    }

    /**
     * @param int $ticket_id
     * @return SummitAttendeeTicket
     */
    public function getTicketById($ticket_id)
    {
        $ticket = $this->tickets->matching(
            $criteria = Criteria::create()
                ->where(Criteria::expr()->eq("id", $ticket_id))
        )->first();
        return $ticket ? $ticket : null;
    }

    /**
     * @param SummitAttendeeTicket $ticket
     * @return $this
     */
    public function removeTicket(SummitAttendeeTicket $ticket)
    {
        $this->tickets->removeElement($ticket);
        $ticket->clearOwner();
        return $this;
    }

    /**
     * @param SummitAttendeeTicket $ticket
     */
    public function sendRevocationTicketEmail(SummitAttendeeTicket $ticket)
    {
        if (!$ticket->hasOwner()) return;

        if ($ticket->getOwner()->getId() != $this->getId()) return;
        $email = $this->getEmail();
        $key = md5($email);
        if (Cache::add(sprintf("%s_revoke_ticket", $key), true, 600)) {
            RevocationTicketEmail::dispatch($this, $ticket);
        }
    }

    /**
     * @param SummitAttendeeTicket $ticket
     * @param bool $overrideTicketOwnerIsSameAsOrderOwnerRule
     */
    public function sendInvitationEmail(SummitAttendeeTicket $ticket, bool $overrideTicketOwnerIsSameAsOrderOwnerRule = false)
    {

        $email = $this->getEmail();
        $key = md5($email);

        Log::debug(sprintf("SummitAttendee::sendInvitationEmail attendee %s", $email));

        if ($ticket->getOwnerEmail() != $this->getEmail()) return;
        if (!$ticket->isPaid()) {
            Log::warning(sprintf("SummitAttendee::sendInvitationEmail attendee %s ticket is not paid", $email));
            return;
        }
        if (!$ticket->isActive()) {
            Log::warning(sprintf("SummitAttendee::sendInvitationEmail attendee %s ticket is not active", $email));
            return;
        }
        $this->updateStatus();
        $ticket->generateHash();

        if ($this->isComplete()) {
            Log::debug(sprintf("SummitAttendee::sendInvitationEmail attendee %s is complete", $email));
            // adds a threshold of 10 minutes to avoid duplicates emails
            if (Cache::add(sprintf("%s_emit_ticket", $key), true, 10)) {
                Log::debug(sprintf("SummitAttendee::sendInvitationEmail attendee %s sending SummitAttendeeTicketEmail", $email));
                SummitAttendeeTicketEmail::dispatch($ticket);
                $ticket->getOwner()->markInvitationEmailSentDate();
            }
            return;
        }
        Log::debug(sprintf("SummitAttendee::sendInvitationEmail attendee %s is not complete", $email));
        $order = $ticket->getOrder();
        // if order owner is ticket owner then dont sent this email
        // buyer is presented the option to fill in the details during the checkout process. Second, buyer will
        // receive daily reminder emails. So, I think that makes this email not really needed as the buyer already knows
        // they bought a ticket for themselves.
        if ($order->getOwnerEmail() !== $ticket->getOwnerEmail() || $overrideTicketOwnerIsSameAsOrderOwnerRule) {
            // no delay
            // adds a threshold of 10 minutes to avoid duplicates emails
            if (Cache::add(sprintf("%s_edit_ticket", $key), true, 10)) {
                Log::debug(sprintf("SummitAttendee::sendInvitationEmail attendee %s sending InviteAttendeeTicketEditionMail", $email));
                InviteAttendeeTicketEditionMail::dispatch($ticket);
                $ticket->getOwner()->markInvitationEmailSentDate();
            }
        }
    }

    /**
     * @return bool
     */
    public function hasTickets()
    {
        return $this->tickets->count() > 0;
    }

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        $res = $this->first_name;
        if (empty($res) && $this->hasMember()) {
            $res = $this->member->getFirstName();
        }
        return $res;
    }

    /**
     * @param string $first_name
     */
    public function setFirstName(string $first_name): void
    {
        $this->first_name = TextUtils::trim($first_name);
    }

    /**
     * @return string
     */
    public function getSurname(): ?string
    {
        $res = $this->surname;
        if (empty($res) && $this->hasMember()) {
            $res = $this->member->getLastName();
        }
        return $res;
    }

    /**
     * @param string $surname
     */
    public function setSurname(string $surname): void
    {
        $this->surname = TextUtils::trim($surname);
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        if ($this->hasMember()) {
            return $this->member->getEmail();
        }
        return PunnyCodeHelper::decodeEmail($this->email);
    }

    /**
     * @return string|null
     */
    public function getFullName(): ?string
    {
        $fullname = $this->first_name;
        if (!empty($this->surname)) {
            if (!empty($fullname)) $fullname .= ' ';
            $fullname .= $this->surname;
        }

        // fallback
        if (empty($fullname) && $this->hasMember()) {
            Log::debug(sprintf("SummitAttendee::getFullName id %s hasMember", $this->id));
            $fullname = $this->member->getFullName();
            Log::debug(sprintf("SummitAttendee::getFullName id %s Member Full Name %s", $this->id, $fullname));
            if (!empty($fullname))
                return $fullname;
        }

        return $fullname;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = PunnyCodeHelper::encodeEmail($email);
    }

    /**
     * @return \DateTime
     */
    public function getDisclaimerAcceptedDate(): ?\DateTime
    {
        return $this->disclaimer_accepted_date;
    }

    /**
     * @return bool
     */
    public function isDisclaimerAccepted(): bool
    {
        return !is_null($this->disclaimer_accepted_date);
    }

    /**
     * @param \DateTime $disclaimer_accepted_date
     */
    public function setDisclaimerAcceptedDate(\DateTime $disclaimer_accepted_date): void
    {
        $this->disclaimer_accepted_date = $disclaimer_accepted_date;
    }

    public function clearDisclaimerAcceptedDate(): void
    {
        $this->disclaimer_accepted_date = null;
    }

    /**
     * @return SummitOrderExtraQuestionAnswer[]
     */
    public function getExtraQuestionAnswers()
    {
        return $this->extra_question_answers;
    }

    /**
     * @param SummitOrderExtraQuestionType $question
     * @return SummitOrderExtraQuestionAnswer|null
     */
    public function getExtraQuestionAnswerByQuestion(SummitOrderExtraQuestionType $question): ?SummitOrderExtraQuestionAnswer
    {
        $answer = $this->extra_question_answers->matching(
            $criteria = Criteria::create()
                ->where(Criteria::expr()->eq("question", $question))
        )->first();
        return $answer ? $answer : null;
    }

    /**
     * @param SummitOrderExtraQuestionType $question
     * @return string|null
     */
    public function getExtraQuestionAnswerValueByQuestion(SummitOrderExtraQuestionType $question): ?string
    {
        try {
            $sql = <<<SQL
SELECT ExtraQuestionAnswer.Value FROM `SummitOrderExtraQuestionAnswer`
INNER JOIN ExtraQuestionAnswer ON ExtraQuestionAnswer.ID = SummitOrderExtraQuestionAnswer.ID
WHERE SummitOrderExtraQuestionAnswer.SummitAttendeeID = :owner_id AND ExtraQuestionAnswer.QuestionID = :question_id
SQL;
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(
                [
                    'owner_id' => $this->getId(),
                    'question_id' => $question->getId()
                ]
            );
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : null;
            return !is_null($res) ? $res : null;
        } catch (\Exception $ex) {
            Log::debug($ex);
        }
        return null;
    }

    public function clearExtraQuestionAnswers()
    {
        Log::debug(sprintf("SummitAttendee::clearExtraQuestionAnswers for attendee %s", $this->getId()));
        return $this->extra_question_answers->clear();
    }

    /**
     * @param SummitOrderExtraQuestionAnswer $answer
     */
    public function addExtraQuestionAnswer(SummitOrderExtraQuestionAnswer $answer): void
    {
        if ($this->extra_question_answers->contains($answer)) return;
        $this->extra_question_answers->add($answer);
        $answer->setAttendee($this);
    }

    /**
     * @param SummitOrderExtraQuestionAnswer $answer
     */
    public function removeExtraQuestionAnswer(SummitOrderExtraQuestionAnswer $answer)
    {
        if (!$this->extra_question_answers->contains($answer)) return;
        $this->extra_question_answers->removeElement($answer);
        $answer->clearAttendee();
    }

    /**
     * @return string
     */
    public function getCompanyName(): ?string
    {
        if ($this->hasCompany())
            return $this->company->getName();
        return $this->company_name;
    }

    /**
     * @return bool
     */
    public function hasCompany(): bool
    {
        return $this->getCompanyId() > 0;
    }

    /**
     * @return int
     */
    public function getCompanyId(): int
    {
        try {
            return is_null($this->company) ? 0 : $this->company->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    /**
     * @param string $company_name
     */
    public function setCompanyName(string $company_name): void
    {
        $this->company_name = $company_name;
    }

    /**
     * @return Company
     */
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    /**
     * @param Company $company
     */
    public function setCompany(Company $company): void
    {
        $this->company = $company;
    }

    /**
     * @return bool
     */
    public function needToFillDetails(): bool
    {
        return $this->getStatus() == self::StatusIncomplete;
    }

    /**
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->getStatus() == self::StatusComplete;
    }

    /**
     * @return string
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function updateStatus(): string
    {
        Log::debug(sprintf("SummitAttendee::updateStatus original status %s", $this->status));
        // ingested attendee
        if (!empty($this->external_id)) {
            $this->status = self::StatusComplete;
            Log::debug(sprintf("SummitAttendee::updateStatus StatusComplete for attendee %s (External).", $this->id));
            return $this->status;
        }

        $is_disclaimer_mandatory = $this->summit->isRegistrationDisclaimerMandatory();

        // mandatory fields
        if ($is_disclaimer_mandatory && !$this->isDisclaimerAccepted()) {
            $this->status = self::StatusIncomplete;
            Log::debug(sprintf("SummitAttendee::updateStatus StatusIncomplete for attendee %s (disclaimer mandatory)", $this->id));
            return $this->status;
        }

        if (empty($this->getFirstName())) {
            $this->status = self::StatusIncomplete;
            Log::debug(sprintf("SummitAttendee::updateStatus StatusIncomplete for attendee %s (first name empty)", $this->id));
            return $this->status;
        }

        if (empty($this->getSurname())) {
            $this->status = self::StatusIncomplete;
            Log::debug(sprintf("SummitAttendee::updateStatus StatusIncomplete for attendee %s (last name empty)", $this->id));
            return $this->status;
        }

        if (empty($this->getEmail())) {
            $this->status = self::StatusIncomplete;
            Log::debug(sprintf("SummitAttendee::updateStatus StatusIncomplete for attendee %s (email empty)", $this->id));
            return $this->status;
        }

        // check mandatory questions

        try {
            $res = $this->hadCompletedExtraQuestions();
            if (!$res) {
                $this->status = self::StatusIncomplete;
                return $this->status;
            }
        } catch (ValidationException $ex) {
            Log::warning($ex);
            $this->status = self::StatusIncomplete;
            return $this->status;
        }

        $this->status = self::StatusComplete;

        return $this->status;
    }

    /**
     * @return \DateTime
     */
    public function getLastReminderEmailSentDate(): ?\DateTime
    {
        $last_action_date = $this->last_reminder_email_sent_date;

        if (is_null($last_action_date)) {
            $last_action_date = $this->getCreatedUTC();
        }

        return $last_action_date;
    }

    /**
     * @param \DateTime $last_reminder_email_sent_date
     */
    public function setLastReminderEmailSentDate(\DateTime $last_reminder_email_sent_date): void
    {
        $this->last_reminder_email_sent_date = $last_reminder_email_sent_date;
    }

    public function getExternalId(): ?string
    {
        return $this->external_id;
    }

    /**
     * @param string $external_id
     */
    public function setExternalId(string $external_id): void
    {
        $this->external_id = $external_id;
    }

    /**
     * @return string
     */
    public function getAdminNotes(): ?string
    {
        return $this->admin_notes;
    }

    /**
     * @param string $admin_notes
     */
    public function setAdminNotes(string $admin_notes): void
    {
        $this->admin_notes = $admin_notes;
    }

    /**
     * @return \DateTime
     */
    public function getSummitVirtualCheckedInDate(): ?\DateTime
    {
        return $this->summit_virtual_checked_in_date;
    }

    /**
     * @param array $required_access_level_ids
     * @return bool
     * @throws EntityNotFoundException
     */
    public function checkAccessLevels(array $required_access_level_ids = []): bool
    {
        Log::debug(sprintf("SummitAttendee::checkAccessLevels id %s access levels %s", $this->id, json_encode($required_access_level_ids)));

        if (count($required_access_level_ids) === 0) return true;
        // check access levels
        $isAuth = false;
        $summit = $this->summit;

        foreach ($required_access_level_ids as $access_level_id) {
            $access_level = $summit->getBadgeAccessLevelTypeById($access_level_id);
            if (is_null($access_level))
                throw new EntityNotFoundException("Access Level not found.");
            if ($this->hasAccessLevel($access_level->getName())) {
                $isAuth = true;
                break;
            }
        }

        return $isAuth;
    }

    /**
     * @param string $access_level
     * @return bool
     */
    public function hasAccessLevel(string $access_level): bool
    {
        foreach ($this->tickets as $ticket) {
            if (!$ticket->isActive()) continue;
            if (!$ticket->hasBadge()) continue;
            $al = $ticket->getBadge()->getType()->getAccessLevelByName($access_level);
            if (!is_null($al)){
                Log::debug(sprintf("SummitAttendee::hasAccessLevel attendee %s has access level %s.", $this->id, $al->getName()));
                return true;
            }
        }
        return false;
    }

    /**
     * @throws \Exception
     */
    public function doVirtualChecking(): void
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        if (is_null($this->summit_virtual_checked_in_date)) {
            if (!$this->summit->isOpen()) {
                throw new ValidationException("Is not show time yet.");
            }
            if (!$this->hasAccessLevel(SummitAccessLevelType::VIRTUAL)) {
                throw new ValidationException("Attendee does not posses VIRTUAL access level.");
            }
            $this->summit_virtual_checked_in_date = $now;
        }
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public function markInvitationEmailSentDate(): \DateTime
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->invitation_email_sent_date = $now;
        return $now;
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public function markPublicEditionEmailSentDate(): \DateTime
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->public_edition_email_sent_date = $now;
        return $now;
    }

    public function addPresentationVote(PresentationAttendeeVote $vote)
    {
        if ($this->presentation_votes->contains($vote)) return;
        $this->addPresentationVote($vote);
    }

    public function removePresentationVote(PresentationAttendeeVote $vote)
    {
        if (!$this->presentation_votes->contains($vote)) return;
        $this->presentation_votes->removeElement($vote);
    }

    /**
     * @param int|null $begin_voting_date
     * @param int|null $end_voting_date
     * @param int|null $track_group_id
     * @return int
     */
    public function getVotesCount(?int $begin_voting_date = null, ?int $end_voting_date = null, ?int $track_group_id = null): int
    {
        return $this->getVotesRange($begin_voting_date, $end_voting_date, $track_group_id)->count();
    }

    /**
     * @param int|null $begin_voting_date
     * @param int|null $end_voting_date
     * @param int|null $track_group_id
     * @return ArrayCollection| PresentationAttendeeVote[]
     */
    public function getPresentationVotes(?int $begin_voting_date = null, ?int $end_voting_date = null, ?int $track_group_id = null)
    {
        return $this->getVotesRange($begin_voting_date, $end_voting_date, $track_group_id);
    }

    /**
     * @param int|null $begin_voting_date
     * @param int|null $end_voting_date
     * @param int|null $track_group_id
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection|PresentationAttendeeVote[]
     */
    private function getVotesRange(?int $begin_voting_date = null, ?int $end_voting_date = null, ?int $track_group_id = null)
    {
        $criteria = null;

        if ($begin_voting_date != null) {
            $begin_voting_date = Carbon::createFromTimestamp($begin_voting_date, new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone));
            $criteria = Criteria::create();
            $criteria->where(Criteria::expr()->gte('created', $begin_voting_date));
        }
        if ($end_voting_date != null) {
            $end_voting_date = Carbon::createFromTimestamp($end_voting_date, new \DateTimeZone(SilverstripeBaseModel::DefaultTimeZone));
            $expr = Criteria::expr()->lte('created', $end_voting_date);
            if ($criteria == null) {
                $criteria = Criteria::create();
                $criteria->where($expr);
            } else {
                $criteria->andWhere($expr);
            }
        }

        $res = $criteria != null ? $this->presentation_votes->matching($criteria) : $this->presentation_votes;
        if ($track_group_id != null) {
            $res = $res->filter(function ($v) use ($track_group_id) {
                if ($v instanceof PresentationAttendeeVote) {
                    return $v->getPresentation()->getCategory()->belongsToGroup($track_group_id);
                }
                return false;
            });
        }

        return $res;
    }

    /**
     * @return SummitOrderExtraQuestionAnswer[] | ArrayCollection
     */
    public function getExtraQuestions()
    {
        return $this->summit->getMainOrderExtraQuestionsByUsage(SummitOrderExtraQuestionTypeConstants::TicketQuestionUsage);
    }

    /**
     * @param int $questionId
     * @return ExtraQuestionType|null
     */
    public function getQuestionById(int $questionId): ?ExtraQuestionType
    {
        return $this->summit->getOrderExtraQuestionById($questionId);
    }

    /**
     * @return bool
     */
    public function canChangeAnswerValue(): bool
    {
        // caching it to avoid calculation costs
        $key = sprintf("SummitAttendee.canChangeAnswerValue.%s", $this->id);
        $res = Cache::get($key, null);
        if (!is_null($res)) {
            Log::debug(sprintf("SummitAttendee::canChangeAnswerValue cache hit id %s res %b", $this->id, $res));
            return $res;
        }

        $resource_server_ctx = App::make(IResourceServerContext::class);
        $currentUser = $resource_server_ctx->getCurrentUser(false, false);
        $currentUserIsAdmin = is_null($currentUser) ? false : ($currentUser->isAdmin() || $this->summit->isSummitAdmin($currentUser));
        Log::debug(sprintf("SummitAttendee::canChangeAnswerValue currentUserIsAdmin %b", $currentUserIsAdmin));
        $res = $currentUserIsAdmin || $this->summit->isAllowUpdateAttendeeExtraQuestions();

        Log::debug(sprintf("SummitAttendee::canChangeAnswerValue storing in cache id %s res %b", $this->id, $res));
        Cache::add($key, $res, 60);
        return $res;
    }


    /**
     * @return array
     */
    public function getBoughtTicketTypes(): array
    {
        try {
            $sql = <<<SQL

SELECT TicketTypeID As type_id, COUNT(ID) As qty FROM `SummitAttendeeTicket` 
where OwnerID = :owner_id AND
SummitAttendeeTicket.IsActive = 1 AND
SummitAttendeeTicket.Status = 'Paid'
GROUP BY OwnerID, TicketTypeID;
SQL;
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(['owner_id' => $this->id]);
            $res = $stmt->fetchAll();
            $res = count($res) > 0 ? $res : [];
            if (count($res) > 0) {
                $res = array_map(function ($e) {
                    $e['type_id'] = intval($e['type_id']);
                    $e['qty'] = intval($e['qty']);
                    return $e;
                }, $res);
            }
            return $res;
        } catch (\Exception $ex) {

        }
        return [];
    }

    public function getAllowedAccessLevels(): array
    {
        $bindings = [
            'owner_id' => $this->id
        ];

        $query = <<<SQL
SELECT DISTINCT E.*
                FROM SummitAccessLevelType E
INNER JOIN SummitBadgeType_AccessLevels ON SummitBadgeType_AccessLevels.SummitAccessLevelTypeID = E.ID
INNER JOIN SummitAttendeeBadge ON SummitAttendeeBadge.BadgeTypeID = SummitBadgeType_AccessLevels.SummitBadgeTypeID
INNER JOIN SummitAttendeeTicket ON SummitAttendeeTicket.ID = SummitAttendeeBadge.TicketID
WHERE SummitAttendeeTicket.OwnerID = :owner_id
SQL;
        $rsm = new ResultSetMappingBuilder($this->getEM());
        $rsm->addRootEntityFromClassMetadata(SummitAccessLevelType::class, 'E');

        // build rsm here
        $native_query = $this->getEM()->createNativeQuery($query, $rsm);

        foreach ($bindings as $k => $v)
            $native_query->setParameter($k, $v);

        return $native_query->getResult();
    }

    public function getAllowedBadgeFeatures(): array
    {
        $bindings = [
            'owner_id' => $this->id
        ];

        $query = <<<SQL
SELECT DISTINCT E.* 
FROM SummitBadgeFeatureType E
INNER JOIN SummitAttendeeBadge_Features ON SummitAttendeeBadge_Features.SummitBadgeFeatureTypeID = E.ID
INNER JOIN SummitAttendeeBadge ON SummitAttendeeBadge.ID = SummitAttendeeBadge_Features.SummitAttendeeBadgeID
INNER JOIN SummitAttendeeTicket ON SummitAttendeeTicket.ID = SummitAttendeeBadge.TicketID
WHERE SummitAttendeeTicket.OwnerID = :owner_id
UNION
SELECT DISTINCT E.* 
FROM SummitBadgeFeatureType E
INNER JOIN SummitBadgeType_BadgeFeatures ON SummitBadgeType_BadgeFeatures.SummitBadgeTypeID = E.ID
INNER JOIN SummitAttendeeBadge ON SummitAttendeeBadge.BadgeTypeID = SummitBadgeType_BadgeFeatures.SummitBadgeFeatureTypeID
INNER JOIN SummitAttendeeTicket ON SummitAttendeeTicket.ID = SummitAttendeeBadge.TicketID
WHERE SummitAttendeeTicket.OwnerID = :owner_id
SQL;
        $rsm = new ResultSetMappingBuilder($this->getEM());
        $rsm->addRootEntityFromClassMetadata(SummitBadgeFeatureType::class, 'E');

        // build rsm here
        $native_query = $this->getEM()->createNativeQuery($query, $rsm);

        foreach ($bindings as $k => $v)
            $native_query->setParameter($k, $v);

        return $native_query->getResult();
    }

}