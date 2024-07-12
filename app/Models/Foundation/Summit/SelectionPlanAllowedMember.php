<?php namespace App\Models\Foundation\Summit;
/*
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

use Doctrine\ORM\Mapping as ORM;
use App\Models\Utils\BaseEntity;
use models\main\Member;
use models\utils\One2ManyPropertyTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="SelectionPlan_AllowedMembers")
 * Class SelectionPlanAllowedMember
 * @package App\Models\Foundation\Summit
 */
class SelectionPlanAllowedMember extends BaseEntity {
  use One2ManyPropertyTrait;

  protected $getIdMappings = [
    "getMemberId" => "member",
  ];

  protected $hasPropertyMappings = [
    "hasMember" => "member",
  ];

  /**
   * @ORM\Column(name="Email", type="string")
   * @var String
   */
  private $email;

  /**
   * @ORM\ManyToOne(targetEntity="models\main\Member")
   * @ORM\JoinColumn(name="MemberID", referencedColumnName="ID", onDelete="CASCADE")
   * @var Member
   */
  private $member;

  /**
   * @ORM\ManyToOne(targetEntity="App\Models\Foundation\Summit\SelectionPlan", inversedBy="allowed_members", fetch="EXTRA_LAZY")
   * @ORM\JoinColumn(name="SelectionPlanID", referencedColumnName="ID")
   * @var SelectionPlan
   */
  private $selection_plan;

  /**
   * @return string
   */
  public function getEmail(): string {
    $email = $this->email;
    if (empty($email) && $this->hasMember()) {
      $email = $this->member->getEmail();
    }
    return strtolower(trim($email));
  }

  public function setEmail(string $email): void {
    $this->email = strtolower(trim($email));
  }

  /**
   * @param SelectionPlan $selection_plan
   * @param string $email
   */
  public function __construct(SelectionPlan $selection_plan, string $email) {
    $this->selection_plan = $selection_plan;
    $this->email = strtolower(trim($email));
  }

  /**
   * @return SelectionPlan
   */
  public function getSelectionPlan(): SelectionPlan {
    return $this->selection_plan;
  }

  public function clearSelectionPlan(): void {
    $this->selection_plan = null;
  }
}
