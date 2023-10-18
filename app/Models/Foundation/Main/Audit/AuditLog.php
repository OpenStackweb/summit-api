<?php namespace models\main;
/**
 * Copyright 2022 OpenStack Foundation
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
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;

/**
 * @ORM\Entity(repositoryClass="repositories\main\DoctrineAuditLogRepository")
 * @ORM\Table(name="AuditLog")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="ClassName", type="string")
 * @ORM\DiscriminatorMap({
 *     "SummitAuditLog" = "SummitAuditLog",
 *     "SummitEventAuditLog" = "SummitEventAuditLog",
 *     "SummitAttendeeBadgeAuditLog" = "SummitAttendeeBadgeAuditLog"
 * })
 * Class AuditLog
 * @package models\main
 */
abstract class AuditLog extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getUserId' => 'user',
    ];

    protected $hasPropertyMappings = [
        'hasUser' => 'user',
    ];

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member")
     * @ORM\JoinColumn(name="UserID", referencedColumnName="ID")
     * @var Member
     */
    protected $user;

    /**
     * @ORM\Column(name="Action", type="string")
     * @var string
     */
    private $action;

    public function getUser(): ?Member
    {
        return $this->user;
    }

    public function setUser(Member $user)
    {
        $this->user = $user;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action)
    {
        $this->action = $action;
    }

    public function __construct(?Member $user, string $action)
    {
        parent::__construct();
        $this->user = $user;
        $this->action = $action;
    }
}