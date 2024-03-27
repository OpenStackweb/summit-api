<?php namespace App\Models\Foundation\ExtraQuestions;
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

use App\Models\Foundation\Main\CountryCodes;
use App\Models\Foundation\Main\ExtraQuestions\ExtraQuestionAnswerSet;
use App\Models\Foundation\Main\ExtraQuestions\SubQuestionRule;
use App\Models\Foundation\Main\IOrderable;
use App\Models\Foundation\Main\OrderableChilds;
use Doctrine\Common\Collections\Criteria;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use libs\utils\TextUtils;
use models\exceptions\ValidationException;
use models\summit\PresentationCategory;
use models\utils\SilverstripeBaseModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="ExtraQuestionType")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="ClassName", type="string")
 * @ORM\HasLifecycleCallbacks
 * @ORM\DiscriminatorMap({
 *     "ExtraQuestionType" = "ExtraQuestionType",
 *     "SummitSelectionPlanExtraQuestionType" = "App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType",
 *     "SummitOrderExtraQuestionType" = "models\summit\SummitOrderExtraQuestionType",
 *     "SummitSponsorExtraQuestionType" = "App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType",
 * })
 * Class ExtraQuestionType
 * @package App\Models\Foundation\ExtraQuestions
 */
abstract class ExtraQuestionType extends SilverstripeBaseModel
    implements IOrderable
{
    /**
     * @ORM\Column(name="Name", type="string")
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(name="Type", type="string")
     * @var string
     */
    protected $type;

    /**
     * @ORM\Column(name="Label", type="string")
     * @var string
     */
    protected $label;

    /**
     * @ORM\Column(name="CustomOrder", type="integer")
     * @var int
     */
    protected $order;

    /**
     * @ORM\Column(name="Mandatory", type="boolean")
     * @var boolean
     */
    protected $mandatory;

    /**
     * @ORM\Column(name="Placeholder", type="string")
     * @var string
     */
    protected $placeholder;

    /**
     * @ORM\Column(name="`MaxSelectedValues`", type="integer")
     * @var int
     */
    protected $max_selected_values;

    /**
     * @ORM\OneToMany(targetEntity="ExtraQuestionTypeValue", mappedBy="question", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var ExtraQuestionTypeValue[]
     */
    protected $values;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Main\ExtraQuestions\SubQuestionRule", mappedBy="parent_question", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SubQuestionRule[]
     */
    protected $sub_question_rules;

    /**
     * @ORM\OneToMany(targetEntity="App\Models\Foundation\Main\ExtraQuestions\SubQuestionRule", mappedBy="sub_question", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     * @var SubQuestionRule[]
     */
    protected $parent_rules;

    public function __construct()
    {
        parent::__construct();
        $this->values = new ArrayCollection();
        $this->mandatory = false;
        $this->order = 1;
        $this->sub_question_rules = new ArrayCollection();
        $this->parent_rules = new ArrayCollection();
        // zero means no limit
        $this->max_selected_values = 0;
    }

    public function getClass():string{
        if($this->parent_rules->count() > 0)
            return ExtraQuestionTypeConstants::QuestionClassSubQuestion;
        return ExtraQuestionTypeConstants::QuestionClassMain;
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
    public function setName(string $name): void
    {
        $name = TextUtils::trim($name);
        if(empty($name))
            throw new ValidationException("Name is mandatory.");
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    /**
     * @param string $placeholder
     * @throws ValidationException
     */
    public function setPlaceholder(string $placeholder): void
    {
        $placeholder = TextUtils::trim($placeholder);
        if(empty($placeholder)) return;
        if(!in_array($this->type, ExtraQuestionTypeConstants::AllowedPlaceHolderQuestionType))
            throw new ValidationException(sprintf("%s type does not allows a placeholder.", $this->type));
        $this->placeholder = $placeholder;
    }

    /**
     * @param string $type
     * @throws ValidationException
     */
    public function setType(string $type): void
    {
        if(!in_array($type, ExtraQuestionTypeConstants::ValidQuestionTypes))
            throw new ValidationException(sprintf("%s type is not valid.", $type));

        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $label = TextUtils::trim($label);
        if(empty($label))
            throw new ValidationException("Label is mandatory.");
        $this->label = trim($label);
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

    /**
     * @return bool
     */
    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    /**
     * @param bool $mandatory
     */
    public function setMandatory(bool $mandatory): void
    {
        $this->mandatory = $mandatory;
    }

    /**
     * @param ExtraQuestionTypeValue $value
     * @throws ValidationException
     */
    public function addValue(ExtraQuestionTypeValue $value){
        if(!$this->allowsValues())
            throw new ValidationException(sprintf("%s type does not allow multiple values.", $this->type));
        if($this->values->contains($value)) return;
        $value->setOrder($this->getValueMaxOrder() + 1);
        $this->values->add($value);
        $value->setQuestion($this);
    }

    public function clearValues():void{
        $this->values->clear();
    }

    /**
     * @return int
     */
    private function getValueMaxOrder():int{
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $value = $this->values->matching($criteria)->first();
        return $value === false ? 0 : $value->getOrder();
    }

    public function allowsValues():bool {
        return in_array($this->type, ExtraQuestionTypeConstants::AllowedMultiValueQuestionType);
    }
    /**
     * @param ExtraQuestionTypeValue $value
     * @throws ValidationException
     */
    public function removeValue(ExtraQuestionTypeValue $value){
        if(!$this->allowsValues())
            throw new ValidationException(sprintf("%s type does not allow multiple values.", $this->type));

        if(!$this->values->contains($value)) return;
        $this->values->removeElement($value);
    }

    /**
     * @param string $label
     * @return ExtraQuestionTypeValue|null
     */
    public function getValueByLabel(string $label):?ExtraQuestionTypeValue{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('label', trim($label)));
        $value = $this->values->matching($criteria)->first();
        return $value === false ? null : $value;
    }

    /**
     * @param string $name
     * @return ExtraQuestionTypeValue|null
     */
    public function getValueByName(string $name):?ExtraQuestionTypeValue{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('value', trim($name)));
        $value = $this->values->matching($criteria)->first();
        return $value === false ? null : $value;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function allowValue(string $value):bool{

        if(empty($value) && !$this->isMandatory()) return true;

        if($this->type == ExtraQuestionTypeConstants::ComboBoxQuestionType)
            return !is_null($this->getValueById(intval($value)));

        foreach (explode(self::QuestionChoicesCharSeparator, $value) as $v)
        {
            if(is_null($this->getValueById(intval($v))))
                return false;
        }
        return true;
    }

    /**
     * @param int $id
     * @return ExtraQuestionTypeValue|null
     */
    public function getValueById(int $id):?ExtraQuestionTypeValue{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $id));
        $value = $this->values->matching($criteria)->first();
        return $value === false ? null : $value;
    }

    use OrderableChilds;

    /**
     * @param ExtraQuestionTypeValue $value
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateValueOrder(ExtraQuestionTypeValue $value, $new_order){
        self::recalculateOrderForSelectable($this->values, $value, $new_order);
    }

    /**
     * @param string $value
     * @return string|null
     */
    public function getNiceValue(string $value):?string {
        $cacheKey = sprintf("nice_values_question_%s", $this->id);
        if ($this->values->count() == 0) return $value;
        $niceValues = [];
        $dict = Cache::get($cacheKey);
        $value = explode( self::QuestionChoicesCharSeparator, $value);

        if(!empty($dict))
            $dict = json_decode($dict, true);

        if(empty($dict)) {
            $criteria = Criteria::create();
            $criteria->orderBy(['order' => 'ASC']);

            foreach ($this->values->matching($criteria) as $questionValue) {
                $dict[$questionValue->getId()] = $questionValue->getLabel();
            }
           // sore it for 600 secs
           Cache::add($cacheKey, json_encode($dict), 600);
        }

        foreach($value as $v){
            $nv = $dict[$v] ?? null;
            if(!empty($nv)){
                $niceValues[] = $nv;
            }
        }

        return implode(self::QuestionChoicesCharSeparator, $niceValues);
    }

    /**
     * @return ExtraQuestionTypeValue[]|ArrayCollection
     */
    public function getValues(){
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'ASC']);
        return $this->values->matching($criteria);
    }

    const QuestionChoicesCharSeparator = ',';

    /**
     * @return SubQuestionRule[]
     */
    public function getSubQuestionRules()
    {
        return $this->sub_question_rules;
    }

    /**
     * @return ArrayCollection|\Doctrine\Common\Collections\Collection|SubQuestionRule[]
     */
    public function getOrderedSubQuestionRules(){
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'ASC']);
        return $this->sub_question_rules->matching($criteria);
    }

    public function addSubQuestionRule(SubQuestionRule $rule):void{
        if($this->sub_question_rules->contains($rule)) return;
        $this->sub_question_rules->add($rule);
        $rule->setOrder($this->getSubQuestionRuleMaxOrder() + 1);
        $rule->setParentQuestion($this);
    }

    public function removeSubQuestionRule(SubQuestionRule $rule):void{
        if(!$this->sub_question_rules->contains($rule)) return;
        $this->sub_question_rules->removeElement($rule);
        $rule->clearParentQuestion();
    }

    /**
     * @param int $ruleId
     * @return SubQuestionRule|null
     */
    public function getSubQuestionRulesById(int $ruleId):?SubQuestionRule{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $ruleId));
        $rule = $this->sub_question_rules->matching($criteria)->first();
        return $rule === false ? null : $rule;
    }

    /**
     * @return SubQuestionRule[]
     */
    public function getParentRules()
    {
        return $this->parent_rules;
    }

    /**
     * @param SubQuestionRule $rule
     */
    public function addParentRule(SubQuestionRule $rule):void{
        if($this->parent_rules->contains($rule)) return;
        $this->parent_rules->add($rule);
        $rule->setSubQuestion($this);
    }

    /**
     * @param SubQuestionRule $rule
     */
    public function removeParentRule(SubQuestionRule $rule):void{
        if(!$this->parent_rules->contains($rule)) return;
        $this->parent_rules->removeElement($rule);
        $rule->clearSubQuestion();
    }

    /**
     * @return int
     */
    public function getMaxSelectedValues(): int
    {
        return $this->max_selected_values;
    }

    /**
     * @param int $max_selected_values
     */
    public function setMaxSelectedValues(int $max_selected_values): void
    {
        $this->max_selected_values = $max_selected_values;
    }

    /**
     * @param ExtraQuestionAnswerSet $answers
     * @return bool
     * @throws ValidationException
     */
    public function isAnswered(ExtraQuestionAnswerSet $answers):bool{
        $answer = $answers->getAnswerFor($this);
        // root question
        if($this->getClass() === ExtraQuestionTypeConstants::QuestionClassMain){
            if(!$this->isMandatory()) return true;

            if(is_null($answer)) return false;
            if(!$answer->hasValue()) return false;
            $value = $answer->getValue();

            if ($this->allowsValues() && !$this->allowValue($value)) {
                Log::warning(sprintf("value %s is not allowed for question %s", $value, $this->getName()));
                throw new ValidationException
                (
                    sprintf
                    (
                        "The answer you provided (%s) for question '%s' (%s) is invalid",
                        $value,
                        $this->getName(),
                        $this->getId()
                    )
                );
            }

           return true;
        }
        // has parent rules , verify those ( its a sub question )
        foreach ($this->parent_rules as $parent_rule){
            if(!$parent_rule->isSubQuestionVisible($answers->getAnswerFor($parent_rule->getParentQuestion()))) {
                // we should disregard the answer if we have one
                if(!is_null($answer)){
                    $answer->markForDeletion();
                }
                continue;
            }
            if(!$this->isMandatory()) return true;
            if(is_null($answer)) return false;
            if(!$answer->hasValue()) return false;
            $value = $answer->getValue();
            if ($this->allowsValues() && !$this->allowValue($value)) {
                Log::warning(sprintf("value %s is not allowed for question %s", $value, $this->getName()));
                throw new ValidationException
                (
                    sprintf
                    (
                        "The answer you provided (%s) for question '%s' (%s) is invalid",
                        $value,
                        $this->getName(),
                        $this->getId()
                    )
                );
            }
            return true;
        }
        return true;
    }

    /**
     * @return int
     */
    private function getSubQuestionRuleMaxOrder(): int
    {
        $criteria = Criteria::create();
        $criteria->orderBy(['order' => 'DESC']);
        $rule = $this->sub_question_rules->matching($criteria)->first();
        return $rule === false ? 0 : $rule->getOrder();
    }

    /**
     * @param PresentationCategory $track
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateSubQuestionRuleOrder(SubQuestionRule $rule, $new_order)
    {
        self::recalculateOrderForSelectable($this->sub_question_rules, $rule, $new_order);
    }

    public function seed():void{
        if($this->getType() === ExtraQuestionTypeConstants::CountryComboBoxQuestionType){
            foreach (CountryCodes::$iso_3166_countryCodes as $iso => $name){
                $value = new ExtraQuestionTypeValue($iso, $name);
                $this->addValue($value);
            }
        }
    }

    public function resetDefaultValues():void{
        foreach ($this->values as $value){
            $value->resetDefaultValue();
        }
    }
}