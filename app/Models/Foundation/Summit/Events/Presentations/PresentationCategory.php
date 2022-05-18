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
use Doctrine\ORM\Mapping AS ORM;
use App\Models\Foundation\Summit\Events\Presentations\TrackQuestions\TrackQuestionTemplate;
use Doctrine\Common\Collections\Criteria;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\main\File;
use models\main\Member;
use models\main\Tag;
use models\utils\SilverstripeBaseModel;
use Doctrine\Common\Collections\ArrayCollection;
use App\Models\Foundation\Main\IOrderable;
/**
 * Class PresentationCategory
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitTrackRepository")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="presentation_categories"
 *     )
 * })
 * @ORM\Table(name="PresentationCategory")
 * @package models\summit
 */
class PresentationCategory extends SilverstripeBaseModel
    implements IOrderable
{

    use SummitOwned;

    /**
     * @ORM\Column(name="Title", type="string")
     * @var string
     */
    private $title;

    /**
     * @ORM\Column(name="Description", type="string")
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(name="Code", type="string")
     * @var string
     */
    private $code;

    /**
     * @ORM\Column(name="Slug", type="string")
     * @var string
     */
    private $slug;

    /**
     * @ORM\Column(name="SessionCount", type="integer")
     * @var int
     */
    private $session_count;

    /**
     * @ORM\Column(name="AlternateCount", type="integer")
     * @var int
     */
    private $alternate_count;

    /**
     * @ORM\Column(name="LightningCount", type="integer")
     * @var int
     */
    private $lightning_count;

    /**
     * @ORM\Column(name="LightningAlternateCount", type="integer")
     * @var int
     */
    private $lightning_alternate_count;

    /**
     * @ORM\Column(name="VotingVisible", type="boolean")
     * @var boolean
     */
    private $voting_visible;

    /**
     * @ORM\Column(name="ChairVisible", type="boolean")
     * @var boolean
     */
    private $chair_visible;

    /**
     * @ORM\Column(name="`CustomOrder`", type="integer")
     * @var int
     */
    protected $order;

    /**
     * @ORM\ManyToMany(targetEntity="models\summit\SummitAccessLevelType", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="PresentationCategory_SummitAccessLevelType",
     *      joinColumns={@ORM\JoinColumn(name="PresentationCategoryID", referencedColumnName="ID", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="SummitAccessLevelTypeID", referencedColumnName="ID", onDelete="CASCADE")}
     *      )
     */
    protected $allowed_access_levels;

    /**
     * @ORM\Column(name="Color", type="string")
     * @var string
     */
    protected $color;

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @ORM\ManyToMany(targetEntity="models\summit\PresentationCategoryGroup", mappedBy="categories")
     * @var PresentationCategoryGroup[]
     */
    private $groups;

    /**
     * @ORM\ManyToMany(targetEntity="models\summit\SummitTrackChair", mappedBy="categories")
     * @var SummitTrackChair[]
     */
    private $track_chairs;

    /**
     * @ORM\ManyToMany(targetEntity="models\main\Tag", cascade={"persist"})
     * @ORM\JoinTable(name="PresentationCategory_AllowedTags",
     *      joinColumns={@ORM\JoinColumn(name="PresentationCategoryID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="TagID", referencedColumnName="ID")}
     *      )
     * @var Tag[]
     */
    protected $allowed_tags;

    /**
     * @ORM\ManyToMany(targetEntity="App\Models\Foundation\Summit\Events\Presentations\TrackQuestions\TrackQuestionTemplate", cascade={"persist"}, inversedBy="tracks")
     * @ORM\JoinTable(name="PresentationCategory_ExtraQuestions",
     *      joinColumns={@ORM\JoinColumn(name="PresentationCategoryID", referencedColumnName="ID")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="TrackQuestionTemplateID", referencedColumnName="ID")}
     *      )
     * @var TrackQuestionTemplate[]
     */
    protected $extra_questions;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\File", cascade={"persist","remove"})
     * @ORM\JoinColumn(name="IconID", referencedColumnName="ID")
     * @var File
     */
    protected $icon;

    /**
     * @ORM\OneToMany(targetEntity="models\summit\SummitSelectedPresentationList", mappedBy="category", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitSelectedPresentationList[]
     */
    protected $selection_lists;

    /**
     * @return TrackQuestionTemplate[]|ArrayCollection
     */
    public function getExtraQuestions(){
        return $this->extra_questions;
    }

    /**
     * @param TrackQuestionTemplate $extra_question
     * @return $this
     */
    public function addExtraQuestion(TrackQuestionTemplate $extra_question){
        if($this->extra_questions->contains($extra_question)) return $this;
        $this->extra_questions->add($extra_question);
        return $this;
    }

    /**
     * @param TrackQuestionTemplate $extra_question
     * @return $this
     */
    public function removeExtraQuestion(TrackQuestionTemplate $extra_question){
        if(!$this->extra_questions->contains($extra_question)) return $this;
        $this->extra_questions->removeElement($extra_question);
        return $this;
    }

    /**
     * @return $this
     */
    public function clearExtraQuestions(){
        $this->extra_questions->clear();
        return $this;
    }
    /**
     * @param int $id
     * @return TrackQuestionTemplate|null
     */
    public function getExtraQuestionById($id){
        $res = $this->extra_questions->filter(function(TrackQuestionTemplate $question) use($id){
           return $question->getIdentifier() == $id;
        });
        $res = $res->first();
        return $res === false ? null : $res;
    }

    /**
     * @param string $name
     * @return TrackQuestionTemplate|null
     */
    public function getExtraQuestionByName($name){
        $res = $this->extra_questions->filter(function(TrackQuestionTemplate $question) use($name){
            return $question->getName() == trim($name);
        });
        $res = $res->first();
        return $res === false ? null : $res;
    }

    public function __construct()
    {
        parent::__construct();

        $this->groups                    = new ArrayCollection;
        $this->allowed_tags              = new ArrayCollection;
        $this->extra_questions           = new ArrayCollection;
        $this->track_chairs              = new ArrayCollection;
        $this->selection_lists           = new ArrayCollection();
        $this->allowed_access_levels     = new ArrayCollection();
        $this->session_count             = 0;
        $this->alternate_count           = 0;
        $this->lightning_alternate_count = 0;
        $this->lightning_count           = 0;
        $this->chair_visible             = false;
        $this->voting_visible            = false;
        $this->order = 0;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return PresentationCategoryGroup[]
     */
    public function getGroups(){
        return $this->groups;
    }

    /**
     * @param PresentationCategoryGroup $group
     * @return $this
     */
    public function addToGroup(PresentationCategoryGroup $group){
        if($this->groups->contains($group)) return $this;
        $this->groups->add($group);
        return $this;
    }

    /**
     * @param PresentationCategoryGroup $group
     * @return $this
     */
    public function removeFromGroup(PresentationCategoryGroup $group){
        if(!$this->groups->contains($group)) return $this;
        $this->groups->removeElement($group);
        return $this;
    }

    /**
     * @param SummitTrackChair $trackChair
     * @return $this
     */
    public function addToTrackChairs(SummitTrackChair $trackChair){
        if($this->track_chairs->contains($trackChair)) return $this;
        $this->track_chairs->add($trackChair);
        return $this;
    }

    /**
     * @return ArrayCollection|SummitTrackChair[]
     */
    public function getTrackChairs(){
        return $this->track_chairs;
    }

    /**
     * @param SummitTrackChair $trackChair
     * @return $this
     */
    public function removeFromTrackChairs(SummitTrackChair $trackChair){
        if(!$this->track_chairs->contains($trackChair)) return $this;
        $this->track_chairs->removeElement($trackChair);
        return $this;
    }

    /**
     * @return Tag[]
     */
    public function getAllowedTags(){
        return $this->allowed_tags;
    }

    public function clearAllowedTags(){
        $this->allowed_tags->clear();
    }

    /**
     * @param string $tag_value
     * @return Tag|null
     */
    public function getAllowedTagByVal($tag_value){
        $res = $this->allowed_tags->filter(function($e) use($tag_value){
            return strtolower(trim($e->getTag())) == strtolower(trim($tag_value));
        });
        return $res->count() > 0 ? $res->first() : null;
    }

    /**
     * @param Tag $tag
     * @return $this
     */
    public function addAllowedTag(Tag $tag){
        if($this->allowed_tags->contains($tag)) return $this;
        $this->allowed_tags->add($tag);
        return $this;
    }

    /**
     * @param int $group_id
     * @return PresentationCategoryGroup|null
     */
    public function getGroupById($group_id){
        //$criteria = Criteria::create();
        //$criteria->where(Criteria::expr()->eq('id', intval($group_id)));
        //$res = $this->groups->matching($criteria)->first();
        //return $res === false ? null : $res;

        return $this->groups->filter(function($g){
            if($g instanceof PresentationCategoryGroup){
                return $g->hasCategory($this->id);
            }
            return false;
        });
    }

    /**
     * @param int $group_id
     * @return bool
     */
    public function belongsToGroup(int $group_id):bool{
        try {
            $sql = <<<SQL
SELECT COUNT(*) FROM PresentationCategoryGroup_Categories
WHERE PresentationCategoryGroupID = :group_id AND 
      PresentationCategoryID = :track_id
SQL;


            $stmt = $this->prepareRawSQL($sql);
            $stmt->execute(
                [
                    'track_id' => $this->getId(),
                    'group_id' => $group_id
                ]
            );
            $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $res = count($res) > 0 ? $res[0] : 0;
            return $res > 0;
        }
        catch (\Exception $ex){
            Log::warning($ex);
        }
        return false;
    }

    /**
     * @return int
     */
    public function getSessionCount()
    {
        return $this->session_count;
    }

    /**
     * @param int $session_count
     */
    public function setSessionCount($session_count)
    {
        $this->session_count = $session_count;
    }

    /**
     * @return int
     */
    public function getAlternateCount()
    {
        return $this->alternate_count;
    }

    /**
     * @param int $alternate_count
     */
    public function setAlternateCount($alternate_count)
    {
        $this->alternate_count = $alternate_count;
    }

    /**
     * @return int
     */
    public function getLightningCount()
    {
        return $this->lightning_count;
    }

    /**
     * @param int $lightning_count
     */
    public function setLightningCount($lightning_count)
    {
        $this->lightning_count = $lightning_count;
    }

    /**
     * @return int
     */
    public function getLightningAlternateCount()
    {
        return $this->lightning_alternate_count;
    }

    /**
     * @param int $lightning_alternate_count
     */
    public function setLightningAlternateCount($lightning_alternate_count)
    {
        $this->lightning_alternate_count = $lightning_alternate_count;
    }

    /**
     * @return bool
     */
    public function isVotingVisible()
    {
        return $this->voting_visible;
    }

    /**
     * @param bool $voting_visible
     */
    public function setVotingVisible($voting_visible)
    {
        $this->voting_visible = $voting_visible;
    }

    /**
     * @return bool
     */
    public function isChairVisible()
    {
        return $this->chair_visible;
    }

    /**
     * @param bool $chair_visible
     */
    public function setChairVisible($chair_visible)
    {
        $this->chair_visible = $chair_visible;
    }

    /**
     * @return $this
     */
    public function calculateSlug(){
        if(empty($this->title)) return $this;
        $clean_title = preg_replace ("/[^a-zA-Z0-9 ]/", "", $this->title);
        $this->slug = preg_replace('/\s+/', '-', strtolower($clean_title));
        return $this;
    }

    /**
     * @return SummitEvent[]
     */
    public function getRelatedPublishedSummitEvents(){
        $query = <<<SQL
SELECT e  
FROM  models\summit\SummitEvent e
WHERE 
e.published = 1
AND e.summit = :summit
AND e.category = :track
SQL;

        $native_query = $this->getEM()->createQuery($query);

        $native_query->setParameter("summit", $this->summit);
        $native_query->setParameter("track", $this);

        $res =  $native_query->getResult();

        return $res;
    }

    /**
     * @return int[]
     */
    public function getRelatedPublishedSummitEventsIds(){
        $query = <<<SQL
SELECT e.id  
FROM  models\summit\SummitEvent e
WHERE 
e.published = 1
AND e.summit = :summit
AND e.category = :track
SQL;

        $native_query = $this->getEM()->createQuery($query);

        $native_query->setParameter("summit", $this->summit);
        $native_query->setParameter("track", $this);

        $res =  $native_query->getResult();

        return $res;
    }

    /**
     * @return string|null
     */
    public function getColor():?string
    {
        return $this->color;
    }

    /**
     * @param string|null $color
     */
    public function setColor(?string $color)
    {
        $this->color = $color;
    }

    /**
     * @return File|null
     */
    public function getIcon(): ?File
    {
        return $this->icon;
    }

    /**
     * @param File $icon
     */
    public function setIcon(File $icon): void
    {
        $this->icon = $icon;
    }

    public function clearIcon():void{
        $this->icon = null;
    }

    /**
     * @return bool
     */
    public function hasIcon(){
        return $this->getIconId() > 0;
    }

    /**
     * @return int
     */
    public function getIconId()
    {
        try{
            if(is_null($this->icon)) return 0;
            return $this->icon->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return string|null
     */
    public function getIconUrl():?string{
        $photoUrl = null;
        if($this->hasIcon() && $photo = $this->getIcon()){
            $photoUrl =  $photo->getUrl();
        }
        return $photoUrl;
    }

    public function addSelectionList(SummitSelectedPresentationList $selection_list){
        if($this->selection_lists->contains($selection_list)) return;
        $this->selection_lists->add($selection_list);
        $selection_list->setCategory($this);
    }

    public function removeSelectionList(SummitSelectedPresentationList $selection_list){
        if(!$this->selection_lists->contains($selection_list)) return;
        $this->selection_lists->removeElement($selection_list);
        $selection_list->clearCategory($this);
    }

    /**
     * @param string $list_type
     * @param Member|null $owner
     * @return SummitSelectedPresentationList|null
     * @throws ValidationException
     */
    public function getSelectionListByTypeAndOwner(string $list_type, ?Member $owner = null):?SummitSelectedPresentationList{
        if(!in_array($list_type, SummitSelectedPresentationList::ValidListTypes))
            throw new ValidationException(sprintf("List Type %s is not valid.", $list_type));

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('list_type', $list_type));
        $criteria->andWhere(Criteria::expr()->eq('category', $this));

        if($list_type == SummitSelectedPresentationList::Individual){
            $criteria->andWhere(Criteria::expr()->eq('owner', $owner));
        }

        $list = $this->selection_lists->matching($criteria)->first();
        return $list === false ? null : $list;
    }

    /**
     * @return ArrayCollection|SummitSelectedPresentationList[]
     */
    public function getSelectionLists(){
        return $this->selection_lists;
    }

    /**
     * @param int $list_id
     * @return SummitSelectedPresentationList|null
     */
    public function getSelectionListById(int $list_id):?SummitSelectedPresentationList{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $list_id));

        $list = $this->selection_lists->matching($criteria)->first();
        return $list === false ? null : $list;
    }

    public function isTrackChair(Member $member):bool{
        return $this->track_chairs->filter(function($t) use($member){
            return $t->getMember()->getId() == $member->getId();
        })->count() > 0;
    }

    public function getTrackChairAvailableSlots():int{
        return $this->session_count + $this->alternate_count;
    }

    /**
     * @param SummitAccessLevelType $access_level
     */
    public function addAllowedAccessLevel(SummitAccessLevelType $access_level):void{
        if($this->allowed_access_levels->contains($access_level)) return;
        $this->allowed_access_levels->add($access_level);
    }

    public function clearAllowedAccessLevels():void{
        $this->allowed_access_levels->clear();
    }

    /**
     * @return ArrayCollection
     */
    public function getAllowedAccessLevels(){
        return $this->allowed_access_levels;
    }

    /**
     * @return array|int[]
     */
    public function getAllowedAccessLevelsIds():array{
        return $this->allowed_access_levels->map(function($al){ return $al->getId();})->toArray();
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * @param int $order
     */
    public function setOrder($order): void
    {
        $this->order = $order;
    }

}