<?php namespace models\summit;
/**
 * Copyright 2026 OpenStack Foundation
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
use Doctrine\ORM\Mapping as ORM;
use models\main\Member;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;

/**
 * Append-only record of a physical check-in / check-out of an attendee.
 * Rows are never updated nor deleted by application code; the boolean/date pair on
 * SummitAttendee remains the current-state read model.
 * @package models\summit
 */
#[ORM\Table(name: 'SummitAttendeeCheckInLog')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitAttendeeCheckInLogRepository::class)]
class SummitAttendeeCheckInLog extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    const ActionCheckedIn = 'CHECKED_IN';
    const ActionCheckedOut = 'CHECKED_OUT';
    const AllowedActions = [self::ActionCheckedIn, self::ActionCheckedOut];

    const SourceAdminUI = 'ADMIN_UI';
    const SourceBadgeScan = 'BADGE_SCAN';
    const SourceBadgePrint = 'BADGE_PRINT';
    const AllowedSources = [self::SourceAdminUI, self::SourceBadgeScan, self::SourceBadgePrint];

    protected $getIdMappings = [
        'getAttendeeId' => 'attendee',
        'getActorId' => 'actor',
    ];

    protected $hasPropertyMappings = [
        'hasAttendee' => 'attendee',
        'hasActor' => 'actor',
    ];

    /**
     * @var SummitAttendee
     */
    #[ORM\JoinColumn(name: 'AttendeeID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \models\summit\SummitAttendee::class)]
    private $attendee;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Action', type: 'string')]
    private $action;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Source', type: 'string')]
    private $source;

    /**
     * Member that triggered the change, null when the token does not resolve to a member.
     * @var Member|null
     */
    #[ORM\JoinColumn(name: 'ActorMemberID', referencedColumnName: 'ID', nullable: true, onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class)]
    private $actor;

    /**
     * OAuth2 client id of the access token used.
     * @var string|null
     */
    #[ORM\Column(name: 'ClientID', type: 'string', nullable: true)]
    private $client_id;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'Reason', type: 'text', nullable: true)]
    private $reason;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'IpAddress', type: 'string', length: 45, nullable: true)]
    private $ip_address;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'UserAgent', type: 'string', length: 512, nullable: true)]
    private $user_agent;

    /**
     * @param SummitAttendee $attendee
     * @param string $action
     * @param string $source
     * @param Member|null $actor
     * @param string|null $client_id
     * @param string|null $reason
     * @param string|null $ip_address
     * @param string|null $user_agent
     * @return SummitAttendeeCheckInLog
     * @throws \InvalidArgumentException
     */
    public static function build
    (
        SummitAttendee $attendee,
        string         $action,
        string         $source,
        ?Member        $actor = null,
        ?string        $client_id = null,
        ?string        $reason = null,
        ?string        $ip_address = null,
        ?string        $user_agent = null
    ): SummitAttendeeCheckInLog
    {
        if (!in_array($action, self::AllowedActions))
            throw new \InvalidArgumentException(sprintf("Invalid check in log action %s.", $action));

        if (!in_array($source, self::AllowedSources))
            throw new \InvalidArgumentException(sprintf("Invalid check in log source %s.", $source));

        if (is_null($actor) && empty($client_id))
            throw new \InvalidArgumentException("A check in log requires an actor member or a client id.");

        $log = new SummitAttendeeCheckInLog();
        $log->attendee = $attendee;
        $log->action = $action;
        $log->source = $source;
        $log->actor = $actor;
        $log->client_id = $client_id;
        $log->reason = $reason;
        $log->ip_address = $ip_address;
        $log->user_agent = is_null($user_agent) ? null : mb_substr($user_agent, 0, 512);

        return $log;
    }

    public function getAttendee(): SummitAttendee
    {
        return $this->attendee;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getActor(): ?Member
    {
        return $this->actor;
    }

    public function getClientId(): ?string
    {
        return $this->client_id;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }
}
