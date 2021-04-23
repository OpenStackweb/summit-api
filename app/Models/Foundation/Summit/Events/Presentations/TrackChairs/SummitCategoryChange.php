<?php namespace models\summit;
/**
 * Copyright 2021 OpenStack Foundation
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
use models\main\Member;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitCategoryChangeRepository")
 * @ORM\Table(name="SummitCategoryChange")
 * Class SummitCategoryChange
 * @package models\summit;
 */
class SummitCategoryChange extends SilverstripeBaseModel
{
    /**
     * @ORM\Column(name="Reason", type="string")
     * @var string
     */
    private $reason;

    /**
     * @ORM\Column(name="Status", type="integer")
     * @var int
     */
    private $status;

    /**
    * @ORM\Column(name="ApprovalDate", type="datetime")
     * @var \DateTime
     */
    private $approval_date;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\Presentation", inversedBy="category_changes_requests")
     * @ORM\JoinColumn(name="PresentationID", referencedColumnName="ID", onDelete="SET NULL")
     * @var Presentation
     */
    private $presentation;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\PresentationCategory")
     * @ORM\JoinColumn(name="NewCategoryID", referencedColumnName="ID", onDelete="SET NULL")
     * @var PresentationCategory
     */
    private $new_category;

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\PresentationCategory")
     * @ORM\JoinColumn(name="OldCategoryID", referencedColumnName="ID", onDelete="SET NULL")
     * @var PresentationCategory
     */
    private $old_category;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member")
     * @ORM\JoinColumn(name="ReqesterID", referencedColumnName="ID", onDelete="SET NULL")
     * @var Member
     */
    private $requester;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member")
     * @ORM\JoinColumn(name="AdminApproverID", referencedColumnName="ID", onDelete="SET NULL")
     * @var Member
     */
    private $aprover;

    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getPresentationId' => 'presentation',
        'getNewCategoryId' => 'new_category',
        'getOldCategoryId' => 'old_category',
        'getRequesterId' => 'requester',
        'getAproverId' => 'aprover',
    ];

    protected $hasPropertyMappings = [
        'hasPresentation' => 'presentation',
        'hasNewCategory' => 'new_category',
        'hasOldCategory' => 'old_category',
        'hasAprover' => 'aprover',
    ];


    /**
     * @return string
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return \DateTime
     */
    public function getApprovalDate(): ?\DateTime
    {
        return $this->approval_date;
    }

    /**
     * @return Presentation
     */
    public function getPresentation(): Presentation
    {
        return $this->presentation;
    }

    /**
     * @param Presentation $presentation
     */
    public function setPresentation(Presentation $presentation): void
    {
        $this->presentation = $presentation;
    }

    /**
     * @return PresentationCategory
     */
    public function getNewCategory(): PresentationCategory
    {
        return $this->new_category;
    }

    /**
     * @param PresentationCategory $new_category
     */
    public function setNewCategory(PresentationCategory $new_category): void
    {
        $this->new_category = $new_category;
    }

    /**
     * @return PresentationCategory
     */
    public function getOldCategory(): PresentationCategory
    {
        return $this->old_category;
    }

    /**
     * @param PresentationCategory $old_category
     */
    public function setOldCategory(PresentationCategory $old_category): void
    {
        $this->old_category = $old_category;
    }

    /**
     * @return Member
     */
    public function getRequester(): Member
    {
        return $this->requester;
    }

    /**
     * @return Member
     */
    public function getAprover(): ?Member
    {
        return $this->aprover;
    }

    public function __construct()
    {
        parent::__construct();
        $this->status = ISummitCategoryChangeStatus::Pending;
    }

    /**
     * @return string
     */
    public function getNiceStatus():string{
        if($this->status == ISummitCategoryChangeStatus::Pending) return "Pending";
        if($this->status == ISummitCategoryChangeStatus::Rejected) return "Rejected";
        if($this->status == ISummitCategoryChangeStatus::Approved) return "Approved";
        return "Pending";
    }

    /**
     * @return bool
     */
    public function isPending():bool{
        return $this->status == ISummitCategoryChangeStatus::Pending;
    }

    /**
     * @param Member $aprover
     * @param string|null $reason
     * @throws \Exception
     */
    public function approve(Member $aprover, ?string $reason = null):void{
        $this->setStatus($aprover, ISummitCategoryChangeStatus::Approved, $reason);
    }

    /**
     * @param Member $aprover
     * @param string|null $reason
     * @throws \Exception
     */
    public function reject(Member $aprover, ?string $reason = null):void{
        $this->setStatus($aprover, ISummitCategoryChangeStatus::Rejected, $reason);
    }

    /**
     * @param Member $aprover
     * @param int $status
     * @param string|null $reason
     * @throws \Exception
     */
    private function setStatus(Member $aprover, int $status, ?string $reason = null):void{
        $this->aprover = $aprover;
        $this->approval_date = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->status = $status;
        $this->reason = empty($reason)? "No Reason":$reason;
    }

    /**
     * @param Presentation $presentation
     * @param Member $requester
     * @param PresentationCategory $newCategory
     * @return SummitCategoryChange
     */
    static public function create
    (
        Presentation $presentation,
        Member $requester,
        PresentationCategory $newCategory
    ):SummitCategoryChange{

        $request = new SummitCategoryChange();
        $request->requester = $requester;
        $request->presentation = $presentation;
        $request->old_category = $presentation->getCategory();
        $request->new_category = $newCategory;

        return $request;
    }
}