<?php namespace models\summit;
/**
 * Copyright 2019 OpenStack Foundation
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
use models\exceptions\ValidationException;
use models\utils\SilverstripeBaseModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitOrderExtraQuestionTypeRepository")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="order_extra_questions"
 *     )
 * })
 * @ORM\Table(name="SummitOrderExtraQuestionType")
 * Class SummitOrderExtraQuestionType
 * @package models\summit
 */
class SummitOrderExtraQuestionType extends SilverstripeBaseModel
implements IOrderable
{
    use SummitOwned;

    /**
     * @ORM\Column(name="Name", type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="Type", type="string")
     * @var string
     */
    private $type;

    /**
     * @ORM\Column(name="Label", type="string")
     * @var string
     */
    private $label;

    /**
     * @ORM\Column(name="`Order`", type="integer")
     * @var int
     */
    private $order;

    /**
     * @ORM\Column(name="Mandatory", type="boolean")
     * @var boolean
     */
    private $mandatory;

    /**
     * @ORM\Column(name="`Usage`", type="string")
     * @var string
     */
    private $usage;

    /**
     * @ORM\Column(name="Placeholder", type="string")
     * @var string
     */
    private $placeholder;

    /**
     * @ORM\Column(name="Printable", type="boolean")
     * @var boolean
     */
    private $printable;

    /**
     * @ORM\OneToMany(targetEntity="SummitOrderExtraQuestionValue", mappedBy="question", cascade={"persist","remove"}, orphanRemoval=true)
     * @var SummitOrderExtraQuestionValue[]
     */
    private $values;

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
    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    /**
     * @param string $placeholder
     */
    public function setPlaceholder(string $placeholder): void
    {
        $this->placeholder = $placeholder;
    }

    /**
     * @param string $type
     * @throws ValidationException
     */
    public function setType(string $type): void
    {
        if(!in_array($type, SummitOrderExtraQuestionTypeConstants::ValidQuestionTypes))
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
     * @return string
     */
    public function getUsage(): string
    {
        return $this->usage;
    }

    /**
     * @param string $usage
     * @throws ValidationException
     */
    public function setUsage(string $usage): void
    {
        if(!in_array($usage, SummitOrderExtraQuestionTypeConstants::ValidQuestionUsages))
            throw new ValidationException(sprintf("%s usage is not valid", $usage));
        $this->usage = $usage;
    }

    /**
     * @return bool
     */
    public function isPrintable(): bool
    {
        return $this->printable;
    }

    /**
     * @param bool $printable
     */
    public function setPrintable(bool $printable): void
    {
        $this->printable = $printable;
    }

    public function __construct()
    {
        parent::__construct();
        $this->values = new ArrayCollection();
        $this->mandatory = false;
        $this->printable = false;
    }

    /**
     * @return SummitOrderExtraQuestionValue[]
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param SummitOrderExtraQuestionValue $value
     * @throws ValidationException
     */
    public function addValue(SummitOrderExtraQuestionValue $value){
        if(!$this->allowsValues())
            throw new ValidationException(sprintf("%s type does not allow multivalues", $this->type));
        if($this->values->contains($value)) return;
        $this->values->add($value);
        $value->setQuestion($this);
        $value->setOrder($this->getValueMaxOrder() + 1);
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
        return in_array($this->type, SummitOrderExtraQuestionTypeConstants::AllowedMultivalueQuestionType);
    }
    /**
     * @param SummitOrderExtraQuestionValue $value
     * @throws ValidationException
     */
    public function removeValue(SummitOrderExtraQuestionValue $value){
        if(!$this->allowsValues())
            throw new ValidationException(sprintf("%s type does not allow multivalues", $this->type));

        if(!$this->values->contains($value)) return;
        $this->values->removeElement($value);
    }

    /**
     * @param string $label
     * @return SummitOrderExtraQuestionValue|null
     */
    public function getValueByLabel(string $label):?SummitOrderExtraQuestionValue{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('label', trim($label)));
        $value = $this->values->matching($criteria)->first();
        return $value === false ? null : $value;
    }

    /**
     * @param string $name
     * @return SummitOrderExtraQuestionValue|null
     */
    public function getValueByName(string $name):?SummitOrderExtraQuestionValue{
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

        if($this->type == SummitOrderExtraQuestionTypeConstants::ComboBoxQuestionType)
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
     * @return SummitOrderExtraQuestionValue|null
     */
    public function getValueById(int $id):?SummitOrderExtraQuestionValue{
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('id', $id));
        $value = $this->values->matching($criteria)->first();
        return $value === false ? null : $value;
    }

    use OrderableChilds;

    /**
     * @param SummitOrderExtraQuestionValue $value
     * @param int $new_order
     * @throws ValidationException
     */
    public function recalculateValueOrder(SummitOrderExtraQuestionValue $value, $new_order){
        self::recalculateOrderForSelectable($this->values, $value, $new_order);
    }

    /**
     * @param string $value
     * @return string|null
     */
    public function getNiceValue(string $value):?string {
        if($this->values->count() == 0) return $value;

        $value      = explode(',' , $value);
        $dict       = [];
        $niceValues = [];

        foreach ($this->values as $questionValue){
            $dict[$questionValue->getId()] = $questionValue->getLabel();
        }

        foreach($value as $v){
            $nv = $dict[$v] ?? null;
            if(!empty($nv)){
                $niceValues[] = $nv;
            }
        }

        return implode(',', $niceValues);
    }

}