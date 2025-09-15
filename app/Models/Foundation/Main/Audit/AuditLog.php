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
 * @package models\main
 */
#[ORM\Table(name: 'AuditLog')]
#[ORM\Entity(repositoryClass: \repositories\main\DoctrineAuditLogRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'ClassName', type: 'string')]
#[ORM\DiscriminatorMap(['SummitAuditLog' => 'SummitAuditLog', 'SummitEventAuditLog' => 'SummitEventAuditLog', 'SummitAttendeeBadgeAuditLog' => 'SummitAttendeeBadgeAuditLog'])] // Class AuditLog
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
     * @var Member
     */
    #[ORM\JoinColumn(name: 'UserID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class)]
    protected $user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Action', type: 'string')]
    private $action;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Metadata', type: 'string')]
    private $metadata;

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

    public function getMetadata(): string
    {
        return $this->metadata;
    }

    public function setMetadata(string $metadata)
    {
        $this->metadata = $metadata;
    }

    public function __construct(?Member $user, string $action, string $metadata = null)
    {
        parent::__construct();
        $this->user = $user;
        $this->action = $action;
        $this->metadata = $metadata;
    }
}