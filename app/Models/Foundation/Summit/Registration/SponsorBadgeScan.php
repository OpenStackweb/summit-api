<?php namespace models\summit;
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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionAnswer;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use App\Models\Foundation\Main\ExtraQuestions\ExtraQuestionAnswerHolder;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Illuminate\Support\Facades\Log;
use models\main\Member;
use models\utils\One2ManyPropertyTrait;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="SponsorBadgeScan")
 * Class SponsorBadgeScan
 * @package models\summit
 */
class SponsorBadgeScan extends SponsorUserInfoGrant
{

    const ClassName = 'SponsorBadgeScan';

    use One2ManyPropertyTrait;

    use ExtraQuestionAnswerHolder;

    protected $getIdMappings = [
        'getUserId'    => 'user',
        'getBadgeId'   => 'badge',
        'getSponsorId' => 'sponsor',
        'getAllowedUserId'    => 'allowed_user',
    ];

    protected $hasPropertyMappings = [
        'hasUser'    => 'user',
        'hasBadge'   => 'badge',
        'hasSponsor' => 'sponsor',
        'hasAllowedUser' => 'allowed_user',
    ];

    /**
     * @ORM\Column(name="QRCode", type="string")
     * @var string
     */
    private $qr_code;

    /**
     * @var \DateTime
     * @ORM\Column(name="ScanDate", type="datetime")
     */
    private $scan_date;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member")
     * @ORM\JoinColumn(name="UserID", referencedColumnName="ID")
     * @var Member
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitAttendeeBadge")
     * @ORM\JoinColumn(name="BadgeID", referencedColumnName="ID")
     * @var SummitAttendeeBadge
     */
    private $badge;

    /**
     * @ORM\Column(name="Notes", type="string")
     * @var string
     */
    private $notes;

    /**
     * @ORM\OneToMany(targetEntity="SponsorBadgeScanExtraQuestionAnswer", mappedBy="badge_scan", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SponsorBadgeScanExtraQuestionAnswer[]
     */
    private $extra_question_answers;

    public function __construct()
    {
        parent::__construct();
        $this->extra_question_answers = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getQRCode(): string
    {
        return $this->qr_code;
    }

    /**
     * @param string $qr_code
     */
    public function setQRCode(string $qr_code): void
    {
        $this->qr_code = $qr_code;
    }

    /**
     * @return Member
     */
    public function getUser(): Member
    {
        return $this->user;
    }

    /**
     * @param Member $user
     */
    public function setUser(Member $user): void
    {
        $this->user = $user;
    }

    /**
     * @return SummitAttendeeBadge
     */
    public function getBadge(): SummitAttendeeBadge
    {
        return $this->badge;
    }

    /**
     * @param SummitAttendeeBadge $badge
     */
    public function setBadge(SummitAttendeeBadge $badge): void
    {
        $this->badge = $badge;
    }

    /**
     * @return \DateTime
     */
    public function getScanDate(): \DateTime
    {
        return $this->scan_date;
    }

    /**
     * @param \DateTime $scan_date
     */
    public function setScanDate(\DateTime $scan_date): void
    {
        $this->scan_date = $scan_date;
    }

    public function getAttendeeFirstName():?string{
        $attendee = $this->getBadge()->getTicket()->getOwner();
        return $attendee->hasMember() ? $attendee->getMember()->getFirstName() : $attendee->getFirstName();
    }

    public function getAttendeeLastName():?string{
        $attendee = $this->getBadge()->getTicket()->getOwner();
        return  $attendee->hasMember() ? $attendee->getMember()->getLastName() :$attendee->getSurname();
    }

    public function getAttendeeEmail():?string{
        $attendee = $this->getBadge()->getTicket()->getOwner();
        return $attendee->getEmail();
    }

    public function getAttendeeCompany():?string{
        $attendee = $this->getBadge()->getTicket()->getOwner();
        return $attendee->getCompanyName();
    }

    /**
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     */
    public function setNotes(string $notes): void
    {
        $this->notes = $notes;
    }

    /**
     * @return SponsorBadgeScanExtraQuestionAnswer[]
     */
    public function getExtraQuestionAnswers()
    {
        return $this->extra_question_answers;
    }

    public function clearExtraQuestionAnswers(): void
    {
        $this->extra_question_answers->clear();
    }

    /**
     * @param ExtraQuestionAnswer $answer
     */
    public function addExtraQuestionAnswer(ExtraQuestionAnswer $answer): void
    {
        if(!$answer instanceof SponsorBadgeScanExtraQuestionAnswer) return;
        if ($this->extra_question_answers->contains($answer)) return;
        $this->extra_question_answers->add($answer);
        $answer->setBadgeScan($this);
    }

    /**
     * @param ExtraQuestionAnswer $answer
     */
    public function removeExtraQuestionAnswer(ExtraQuestionAnswer $answer)
    {
        if(!$answer instanceof SponsorBadgeScanExtraQuestionAnswer) return;
        if (!$this->extra_question_answers->contains($answer)) return;
        $this->extra_question_answers->removeElement($answer);
        $answer->clearBadgeScan();
    }

    /**
     * @return ExtraQuestionType[] | ArrayCollection
     */
    public function getExtraQuestions(): array
    {
        return $this->sponsor->getExtraQuestions()->toArray();
    }

    /**
     * @param int $questionId
     * @return ExtraQuestionType|null
     */
    public function getQuestionById(int $questionId): ?ExtraQuestionType
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $questionId));
        $question = $this->sponsor->getExtraQuestions()->matching($criteria)->first();
        return $question === false ? null : $question;
    }

    /**
     * @param ExtraQuestionType $q
     * @return bool
     */
    public function canChangeAnswerValue(ExtraQuestionType $q): bool
    {
        return true;
    }

    /**
     * @param ExtraQuestionType $q
     * @return bool
     */
    public function isAllowedQuestion(ExtraQuestionType $q): bool
    {
        return true;
    }

    public function buildExtraQuestionAnswer(): ExtraQuestionAnswer
    {
        return new SponsorBadgeScanExtraQuestionAnswer();
    }

    /**
     * @param SummitSponsorExtraQuestionType $question
     * @return string|null
     */
    public function getExtraQuestionAnswerValueByQuestion(SummitSponsorExtraQuestionType $question): ?string
    {
        try {
            $sql = <<<SQL
SELECT ExtraQuestionAnswer.Value FROM `SponsorBadgeScanExtraQuestionAnswer`
INNER JOIN ExtraQuestionAnswer ON ExtraQuestionAnswer.ID = SponsorBadgeScanExtraQuestionAnswer.ID
WHERE SponsorBadgeScanExtraQuestionAnswer.SponsorBadgeScanID = :scan_id AND ExtraQuestionAnswer.QuestionID = :question_id
SQL;
            $stmt = $this->prepareRawSQL($sql);
            $res = $stmt->execute(
                [
                    'scan_id'     => $this->getId(),
                    'question_id' => $question->getId()
                ]
            );
            $res = $res->fetchFirstColumn();
            $res = count($res) > 0 ? $res[0] : null;
            return !is_null($res) ? $res : null;
        } catch (\Exception $ex) {
            Log::debug($ex);
        }
        return null;
    }
}