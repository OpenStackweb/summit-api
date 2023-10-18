<?php namespace models\main;
/**
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
use Doctrine\ORM\Mapping AS ORM;
use models\summit\Summit;
use models\summit\SummitAttendeeBadge;
use models\utils\One2ManyPropertyTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="SummitAttendeeBadgeAuditLog")
 * Class SummitAttendeeBadgeAuditLog
 * @package models\main
 */
class SummitAttendeeBadgeAuditLog extends SummitAuditLog
{
    const ClassName = 'SummitAttendeeBadgeAuditLog';

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getSummitAttendeeBadgeID' => 'related_entity',
        'getUserId'  => 'user',
    ];

    protected $hasPropertyMappings = [
        'hasSummitAttendeeBadge' => 'related_entity',
        'hasUser'  => 'user',
    ];

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\SummitAttendeeBadge")
     * @ORM\JoinColumn(name="SummitAttendeeBadgeID", referencedColumnName="ID")
     * @var SummitAttendeeBadge
     */
    private $related_entity;

    public function getClassName(): string
    {
        return self::ClassName;
    }

    public function getAttendeeBadge(): SummitAttendeeBadge
    {
        return $this->related_entity;
    }

    public function setAttendeeBadge(SummitAttendeeBadge $attendee_badge)
    {
        $this->related_entity = $attendee_badge;
    }

    public function __construct(?Member $user, string $action, Summit $summit, SummitAttendeeBadge $attendee_badge)
    {
        parent::__construct($user, $action, $summit);
        $this->related_entity = $attendee_badge;
    }
}