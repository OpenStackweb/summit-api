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
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package App\Models\Foundation\ExtraQuestions
 */
#[ORM\Table(name: 'ExtraQuestionTypeValue')]
#[ORM\Entity]
class ExtraQuestionTypeValue extends SilverstripeBaseModel
implements IOrderable
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getQuestionId' => 'question',
    ];

    protected $hasPropertyMappings = [
        'hasQuestion' => 'question',
    ];
    /**
     * @var string
     */
    #[ORM\Column(name: 'Label', type: 'string')]
    protected $label;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Value', type: 'string')]
    protected $value;

    /**
     * @var int
     */
    #[ORM\Column(name: '`Order`', type: 'integer')]
    protected $order;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'IsDefault', type: 'boolean')]
    protected $is_default;

    /**
     * @var ExtraQuestionType
     */
    #[ORM\JoinColumn(name: 'QuestionID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \ExtraQuestionType::class, inversedBy: 'values')]
    protected $question;

    /**
     * @return string
     */
    public function getLabel(): ?string
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
     * @return string
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
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
     * @return ExtraQuestionType
     */
    public function getQuestion(): ExtraQuestionType
    {
        return $this->question;
    }

    /**
     * @param ExtraQuestionType $question
     */
    public function setQuestion(ExtraQuestionType $question): void
    {
        $this->question = $question;
    }

    /**
     * @param string $value
     * @param string $label
     * @param bool $is_default
     */
    public function __construct(string $value = '', string $label = '', bool $is_default = false)
    {
        parent::__construct();
        $this->value = $value;
        $this->label = $label;
        $this->order = 1;
        $this->is_default = $is_default;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * @param bool $is_default
     */
    public function setIsDefault(bool $is_default): void
    {
        $this->is_default = $is_default;
    }

    public function resetDefaultValue():void{
        $this->is_default = false;
    }

}