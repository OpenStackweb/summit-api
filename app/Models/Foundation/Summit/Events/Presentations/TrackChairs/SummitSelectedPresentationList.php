<?php namespace models\summit;
/**
 * Copyright 2017 OpenStack Foundation
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
use Doctrine\Common\Collections\Criteria;
use models\exceptions\ValidationException;
use models\utils\SilverstripeBaseModel;
use Doctrine\Common\Collections\ArrayCollection;
use models\main\Member;
/**
 * Class SummitSelectedPresentationList
 * @ORM\Entity
 * @ORM\Table(name="SummitSelectedPresentationList")
 * @package models\summit
 */
class SummitSelectedPresentationList extends SilverstripeBaseModel
{
    // list type
    const Individual = 'Individual';
    const Group      = 'Group';

    const ValidListTypes = [ self::Individual, self::Group];

    // list class
    const Session    = 'Session';
    const Lightning  = 'Lightning';

    /**
     * @ORM\Column(name="Name", type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="ListType", type="string")
     * @var string
     */
    private $list_type;

    /**
     * @ORM\Column(name="ListClass", type="string")
     * @var string
     */
    private $list_class;

    /**
     * @ORM\Column(name="Hash", type="string")
     * @var string
     */
    private $hash;

    /**
     * @ORM\ManyToOne(targetEntity="PresentationCategory", inversedBy="selection_lists", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="CategoryID", referencedColumnName="ID")
     * @var PresentationCategory
     */
    private $category = null;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\Member", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="MemberID", referencedColumnName="ID")
     * @var Member
     */
    private $owner = null;

    /**
     * @ORM\OneToMany(targetEntity="SummitSelectedPresentation", mappedBy="list", cascade={"persist", "remove"}, orphanRemoval=true)
     * @var SummitSelectedPresentation[]
     */
    private $selected_presentations;

    public function __construct()
    {
        parent::__construct();
        $this->selected_presentations = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getListType()
    {
        return $this->list_type;
    }

    /**
     * @param string $list_type
     */
    public function setListType($list_type)
    {
        $this->list_type = $list_type;
    }

    /**
     * @return string
     */
    public function getListClass()
    {
        return $this->list_class;
    }

    /**
     * @param string $list_class
     */
    public function setListClass($list_class)
    {
        $this->list_class = $list_class;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return PresentationCategory
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param PresentationCategory $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    public function clearCategory(){
        $this->category = null;
    }

    public function getCategoryId():int{
        try{
            return is_null($this->category)? 0: $this->category->getId();
        }
        catch (\Exception $ex){
            return 0;
        }
    }

    /**
     * @return int
     */
    public function getOwnerId():int{
        try{
            return is_null($this->owner)? 0: $this->owner->getId();
        }
        catch (\Exception $ex){
            return 0;
        }
    }

    /**
     * @return Member
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Member $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return SummitSelectedPresentation[]
     */
    public function getSelectedPresentations()
    {
        return $this->selected_presentations;
    }

    /**
     * @param string $collection
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection
     * @throws ValidationException
     */
    public function getSelectedPresentationsByCollection(string $collection){
        if(!in_array($collection, SummitSelectedPresentation::ValidCollectionTypes))
            throw new ValidationException(sprintf("Collection type %s is not valid.", $collection));

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('collection', $collection));
        $criteria->orderBy(['order'=> 'ASC']);

        return $this->selected_presentations->matching($criteria);
    }


    public function isOwner(Member $owner):bool{
        return $this->getOwnerId() === $owner->getId();
    }

    /**
     * @param Member $member
     * @return bool
     */
    public function canEdit(Member $member):bool
    {

        // if is individual only owner can edit it
        if ($this->list_type == self::Individual && $this->isOwner($member)){
            return true;
        }

        if($this->list_type == self::Group){
            return $this->category->getSummit()->isTrackChair($member, $this->category) || $this->category->getSummit()->isTrackChairAdmin($member);
        }

        return false;
    }

    public function compareHash(string $oldHash):bool{
        if ($this->hash == null) return true;
        return ($this->hash == $oldHash);
    }


    public function isGroup():bool {
        return ($this->list_type == SummitSelectedPresentationList::Group);
    }

    /**
     * @param SummitSelectedPresentation $selection
     */
    public function removeSelection(SummitSelectedPresentation $selection){
        if(!$this->selected_presentations->contains($selection)) return;
        $this->selected_presentations->removeElement($selection);
        $selection->clearList();
    }

    public function clearSelections(){
        $this->selected_presentations->clear();
    }

    /**
     * @param SummitSelectedPresentation $selection
     */
    public function addSelection(SummitSelectedPresentation $selection){
        if($this->selected_presentations->contains($selection)) return;
        $this->selected_presentations->add($selection);
        $selection->setList($this);
    }

    /**
     * @param Presentation $presentation
     * @return SummitSelectedPresentation|null
     */
    public function getSelectionByPresentation(Presentation $presentation):?SummitSelectedPresentation{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('presentation', $presentation));

        $res = $this->selected_presentations->matching($criteria)->first();

        return $res === false ? null : $res;
    }

    public function recalculateHash():string{
        if(!$this->isGroup()){
            throw new ValidationException("You could only calculate hash on Team list");
        }
        $criteria = Criteria::create();
        $criteria->orderBy(['order'=> 'ASC']);
        $hash = '';
        foreach ($this->selected_presentations->matching($criteria) as $p){
            $hash .= strval($p->getId());
        }
        $this->hash = md5($hash);

        return $this->hash;
    }

    /**
     * @param string $collection
     * @return int
     * @throws ValidationException
     */
    public function getHighestOrderInListByCollection(string $collection):int{
        if(!in_array($collection, SummitSelectedPresentation::ValidCollectionTypes))
            throw new ValidationException(sprintf("collection %s is not valid.", $collection));

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('collection', $collection));
        $criteria->orderBy(['order'=> 'DESC']);
        $highest = $this->selected_presentations->matching($criteria)->first();
        return $highest === false ? 0: $highest->getOrder();
    }
}