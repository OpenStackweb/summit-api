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

use Doctrine\Common\Collections\Criteria;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class PresentationCategoryGroup
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrinePresentationCategoryGroupRepository")
 * @ORM\Table(name="PresentationCategoryGroup")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="ClassName", type="string")
 * @ORM\DiscriminatorMap({
 *     "PresentationCategoryGroup" = "PresentationCategoryGroup",
 *     "PrivatePresentationCategoryGroup" = "PrivatePresentationCategoryGroup"
 * })
 * @package models\summit
 */
class PresentationCategoryGroup extends SilverstripeBaseModel
{

    /**
     * @ORM\Column(name="Name", type="string")
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(name="Color", type="string")
     * @var string
     */
    protected $color;

    /**
     * @ORM\Column(name="Description", type="string")
     * @var string
     */
    protected $description;

    /**
     * @ORM\Column(name="BeginAttendeeVotingPeriodDate", type="datetime")
     * @var \DateTime
     */
    protected $begin_attendee_voting_period_date;

    /**
     * @ORM\Column(name="EndAttendeeVotingPeriodDate", type="datetime")
     * @var \DateTime
     */
    protected $end_attendee_voting_period_date;

    /**
     * @ORM\Column(name="MaxUniqueAttendeeVotes", type="integer")
     * @var int
     */
    protected $max_attendee_votes;


    public function __construct()
    {
        parent::__construct();
        $this->begin_attendee_voting_period_date = null;
        $this->end_attendee_voting_period_date = null;
        $this->max_attendee_votes = 0;
        $this->categories = new ArrayCollection;
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param mixed $color
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @ORM\ManyToOne(targetEntity="Summit", inversedBy="category_groups")
     * @ORM\JoinColumn(name="SummitID", referencedColumnName="ID")
     * @var Summit
     */
    protected $summit;

    public function setSummit($summit)
    {
        $this->summit = $summit;
    }

    /**
     * @return int
     */
    public function getSummitId()
    {
        try {
            return is_null($this->summit) ? 0 : $this->summit->getId();
        } catch (\Exception $ex) {
            return 0;
        }
    }

    public function clearSummit()
    {
        $this->summit = null;
    }

    /**
     * @return Summit
     */
    public function getSummit()
    {
        return $this->summit;
    }

    /**
     * owning side
     * @ORM\ManyToMany(targetEntity="models\summit\PresentationCategory", inversedBy="groups")
     * @ORM\JoinTable(name="PresentationCategoryGroup_Categories",
     *      joinColumns={@ORM\JoinColumn(name="PresentationCategoryGroupID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="PresentationCategoryID", referencedColumnName="ID")}
     * )
     * @var PresentationCategory[]
     */
    protected $categories;

    /**
     * @return PresentationCategory[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param PresentationCategory $track
     */
    public function addCategory(PresentationCategory $track)
    {
        $track->addToGroup($this);
        $this->categories[] = $track;
    }

    /**
     * @param PresentationCategory $track
     */
    public function removeCategory(PresentationCategory $track)
    {
        $track->removeFromGroup($this);
        $this->categories->removeElement($track);
    }

    /**
     * @param int $category_id
     * @return PresentationCategory|null
     */
    public function getCategoryById($category_id)
    {
        /*$criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', intval($category_id)));
        $res = $this->categories->matching($criteria)->first();
        return $res === false ? null : $res;*/
        $res = $this->categories->filter(function (PresentationCategory $t) use ($category_id) {
            return $t->getId() == $category_id;
        })->first();

        return $res == false ? null : $res;
    }

    /**
     * @param int $category_id
     * @return bool
     */
    public function hasCategory($category_id)
    {
        return $this->getCategoryById($category_id) != null;
    }

    const ClassName = 'PresentationCategoryGroup';

    /**
     * @return string
     */
    public function getClassName()
    {
        return self::ClassName;
    }

    public static $metadata = [
        'class_name' => self::ClassName,
        'id' => 'integer',
        'summit_id' => 'integer',
        'name' => 'string',
        'color' => 'string',
        'description' => 'string',
        'categories' => 'array',
        'begin_attendee_voting_period_date' => 'datetime',
        'end_attendee_voting_period_date' => 'datetime',
        'max_attendee_votes' => 'integer',
    ];

    /**
     * @return array
     */
    public static function getMetadata()
    {
        return self::$metadata;
    }

    /**
     * @return \DateTime
     */
    public function getBeginAttendeeVotingPeriodDate(): ?\DateTime
    {
        return $this->begin_attendee_voting_period_date;
    }

    /**
     * @param \DateTime $begin_attendee_voting_period_date
     */
    public function setBeginAttendeeVotingPeriodDate(?\DateTime $begin_attendee_voting_period_date): void
    {
        $this->begin_attendee_voting_period_date = $begin_attendee_voting_period_date;
    }

    /**
     * @return \DateTime
     */
    public function getEndAttendeeVotingPeriodDate(): ?\DateTime
    {
        return $this->end_attendee_voting_period_date;
    }

    /**
     * @param \DateTime $end_attendee_voting_period_date
     */
    public function setEndAttendeeVotingPeriodDate(?\DateTime $end_attendee_voting_period_date): void
    {
        $this->end_attendee_voting_period_date = $end_attendee_voting_period_date;
    }

    /**
     * @return int
     */
    public function getMaxAttendeeVotes(): int
    {
        return $this->max_attendee_votes;
    }

    /**
     * @param int $max_attendee_votes
     */
    public function setMaxAttendeeVotes(int $max_attendee_votes): void
    {
        $this->max_attendee_votes = $max_attendee_votes;
    }

    /**
     * @throws \Exception
     */
    public function isAttendeeVotingPeriodOpen(): bool
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        if (!is_null($this->begin_attendee_voting_period_date) && !is_null($this->end_attendee_voting_period_date)) {
            return $now >= $this->begin_attendee_voting_period_date && $now <= $this->end_attendee_voting_period_date;
        }
        return true;
    }

    public function isNotLimitedAttendeeVotingCount(): bool
    {
        return $this->max_attendee_votes == 0;
    }

    /**
     * @param SummitAttendee $attendee
     * @return bool
     */
    public function canEmitAttendeeVote(SummitAttendee $attendee): bool
    {
        if ($this->isNotLimitedAttendeeVotingCount()) return true;
        try {
            $sql = <<<SQL
SELECT COUNT(DISTINCT(PresentationAttendeeVote.ID)) FROM `PresentationAttendeeVote`
INNER JOIN Presentation ON Presentation.ID = PresentationAttendeeVote.PresentationID
INNER JOIN SummitEvent ON SummitEvent.ID = Presentation.ID
INNER JOIN PresentationCategoryGroup_Categories ON PresentationCategoryGroup_Categories.PresentationCategoryID = SummitEvent.CategoryID
WHERE PresentationAttendeeVote.SummitAttendeeID = :attendee_id
AND PresentationCategoryGroup_Categories.PresentationCategoryGroupID = :id
SQL;
            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute
            ([
                'id' => $this->id,
                'attendee_id' => $attendee->getId(),
            ]);
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : 0;
            $res = !is_null($res) ? $res : 0;
            Log::debug
            (
                sprintf
                (
                    "PresentationCategoryGroup::canEmitAttendeeVote group %s attendee %s votes %s max vote %s",
                    $this->id,
                    $attendee->getId(),
                    $res,
                    $this->max_attendee_votes
                )
            );
            return ($res + 1) < $this->max_attendee_votes;
        } catch (\Exception $ex) {
            Log::warning($ex);
        }
        return true;
    }

    public function clearAttendeeVotingPeriod():void{
        $this->begin_attendee_voting_period_date = null;
        $this->end_attendee_voting_period_date = null;
    }

}