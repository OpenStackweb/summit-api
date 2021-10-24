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

use App\Models\Foundation\Main\IOrderable;
use App\Models\Foundation\Main\OrderableChilds;
use Doctrine\Common\Collections\Criteria;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use models\exceptions\ValidationException;
use models\utils\SilverstripeBaseModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="ExtraQuestionType")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="ClassName", type="string")
 * @ORM\DiscriminatorMap({
 *     "ExtraQuestionType" = "ExtraQuestionType",
 *     "SummitSelectionPlanExtraQuestionType" = "App\Models\Foundation\Summit\ExtraQuestions\SummitSelectionPlanExtraQuestionType",
 *     "SummitOrderExtraQuestionType" = "models\summit\SummitOrderExtraQuestionType",
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
     * @ORM\Column(name="`Order`", type="integer")
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
     * @ORM\OneToMany(targetEntity="ExtraQuestionTypeValue", mappedBy="question", cascade={"persist","remove"}, orphanRemoval=true)
     * @var ExtraQuestionTypeValue[]
     */
    protected $values;

    public function __construct()
    {
        parent::__construct();
        $this->values = new ArrayCollection();
        $this->mandatory = false;
        $this->order = 1;
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
        if(empty($placeholder)) return;
        if(!in_array($this->type, ExtraQuestionTypeConstants::AllowedPlaceHolderQuestionType))
            throw new ValidationException(sprintf("%s type does not allows a placeholder", $this->type));
        $this->placeholder = $placeholder;
    }

    /**
     * @param string $type
     * @throws ValidationException
     */
    public function setType(string $type): void
    {
        if(!in_array($type, ExtraQuestionTypeConstants::ValidQuestionTypes))
            throw new ValidationException(sprintf("%s type is not valid", $type));

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
        $this->label = $label;
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

        foreach (explode(',',$value) as $v)
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
        $value = explode(',', $value);

        if(!empty($dict))
            $dict = json_decode($dict, true);

        if(empty($dict)) {
            foreach ($this->values as $questionValue) {
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

        return implode(',', $niceValues);
    }

    /**
     * @return ExtraQuestionTypeValue[]|ArrayCollection
     */
    public function getValues(){
        return $this->values;
    }

}