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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping AS ORM;
use models\exceptions\ValidationException;
use models\main\Member;
use models\utils\SilverstripeBaseModel;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitTrackChairRepository")
 * @ORM\Table(name="SummitTrackChair")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="track_chairs"
 *     )
 * })
 * Class SummitTrackChair
 * @package models\summit;
 */
class SummitTrackChair extends SilverstripeBaseModel
{
    use SummitOwned;
    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member")
     * @ORM\JoinColumn(name="MemberID", referencedColumnName="ID", onDelete="SET NULL")
     * @var Member
     */
    private $member;

    /**
     * owning side
     * @ORM\ManyToMany(targetEntity="models\summit\PresentationCategory", inversedBy="track_chairs")
     * @ORM\JoinTable(name="SummitTrackChair_Categories",
     *      joinColumns={@ORM\JoinColumn(name="SummitTrackChairID", referencedColumnName="ID")},
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
     * @throws ValidationException
     */
    public function addCategory(PresentationCategory $track){
        if($this->categories->contains($track))
            return;
        if(!$track->isChairVisible())
            throw new ValidationException(sprintf("Category %s is not visible by track chairs.", $track->getId()));

        $track->addToTrackChairs($this);
        $this->categories[] = $track;
    }

    /**
     * @param PresentationCategory $track
     * @throws ValidationException
     */
    public function removeCategory(PresentationCategory $track){
        if(!$this->categories->contains($track))
            return;
        $track->removeFromTrackChairs($this);
        $this->categories->removeElement($track);

        $list = $track->getSelectionListByTypeAndOwner(SummitSelectedPresentationList::Individual, $track, $this->member);
        // if we remove the track , then we need to remove the selection lists
        if(!is_null($list)){
            $track->removeSelectionList($list);
        }
    }

    /**
     * @return Member
     */
    public function getMember(): Member
    {
        return $this->member;
    }

    /**
     * @param Member $member
     */
    public function setMember(Member $member): void
    {
        $this->member = $member;
    }

    public function isCategoryAllowed(PresentationCategory $category):bool{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $category->getId()));
        $category = $this->categories->matching($criteria)->first();
        return $category === false ? false : true;
    }


    public function clearCategories():void{
        $this->categories->clear();
    }

    /**
     * @return int
     */
    public function getMemberId(){
        try {
            return is_null($this->member) ? 0 : $this->member->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->categories = new ArrayCollection();
    }
}