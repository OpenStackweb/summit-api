<?php namespace App\Models\Foundation\Summit\Events\RSVP;
/**
 * Copyright 2018 OpenStack Foundation
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
use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;
/**
 * @package App\Models\Foundation\Summit\Events\RSVP
 */
#[ORM\Table(name: 'RSVPQuestionTemplate')]
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'ClassName', type: 'string')]
#[ORM\DiscriminatorMap(['RSVPQuestionTemplate' => 'RSVPQuestionTemplate', 'RSVPLiteralContentQuestionTemplate' => 'RSVPLiteralContentQuestionTemplate', 'RSVPMultiValueQuestionTemplate' => 'RSVPMultiValueQuestionTemplate', 'RSVPSingleValueTemplateQuestion' => 'RSVPSingleValueTemplateQuestion', 'RSVPTextBoxQuestionTemplate' => 'RSVPTextBoxQuestionTemplate', 'RSVPTextAreaQuestionTemplate' => 'RSVPTextAreaQuestionTemplate', 'RSVPMemberEmailQuestionTemplate' => 'RSVPMemberEmailQuestionTemplate', 'RSVPMemberFirstNameQuestionTemplate' => 'RSVPMemberFirstNameQuestionTemplate', 'RSVPMemberLastNameQuestionTemplate' => 'RSVPMemberLastNameQuestionTemplate', 'RSVPCheckBoxListQuestionTemplate' => 'RSVPCheckBoxListQuestionTemplate', 'RSVPRadioButtonListQuestionTemplate' => 'RSVPRadioButtonListQuestionTemplate', 'RSVPDropDownQuestionTemplate' => 'RSVPDropDownQuestionTemplate'])] // Class RSVPQuestionTemplate
class RSVPQuestionTemplate extends SilverstripeBaseModel implements IOrderable
{

    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    protected $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Label', type: 'string')]
    protected $label;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'Mandatory', type: 'boolean')]
    protected $is_mandatory;

    /**
     * @var int
     */
    #[ORM\Column(name: '`Order`', type: 'integer')]
    protected $order;

    /**
     * @var boolean
     */
    #[ORM\Column(name: 'ReadOnly', type: 'boolean')]
    protected $is_read_only;

    /**
     * @var RSVPTemplate
     */
    #[ORM\JoinColumn(name: 'RSVPTemplateID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \RSVPTemplate::class, fetch: 'EXTRA_LAZY', inversedBy: 'questions')]
    protected $template;

    /**
     * @var RSVPQuestionDependsOn[]
     */
    #[ORM\OneToMany(targetEntity: \RSVPQuestionDependsOn::class, mappedBy: 'parent', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected $depends_on;

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
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return bool
     */
    public function isMandatory()
    {
        return $this->is_mandatory;
    }

    /**
     * @param bool $is_mandatory
     */
    public function setIsMandatory($is_mandatory)
    {
        $this->is_mandatory = $is_mandatory;
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param int $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return bool
     */
    public function isReadOnly()
    {
        return $this->is_read_only;
    }

    /**
     * @param bool $is_read_only
     */
    public function setIsReadOnly($is_read_only)
    {
        $this->is_read_only = $is_read_only;
    }

    /**
     * @return RSVPTemplate
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param RSVPTemplate $template
     */
    public function setTemplate(RSVPTemplate $template)
    {
        $this->template = $template;
    }

    /**
     * @return string
     */
    public function getClassName(){
        return 'RSVPQuestionTemplate';
    }

    public function clearTemplate(){
        $this->template = null;
    }

    public function __construct()
    {
        parent::__construct();
        $this->is_mandatory = false;
        $this->is_read_only = false;
        $this->order        = 0;
    }

    public static $metadata = [
        'name'         => 'string',
        'label'        => 'string',
        'is_mandatory' => 'boolean',
        'is_read_only' => 'boolean',
        'template_id'  => 'integer',
        'order'        => 'integer'
    ];

    /**
     * @return array
     */
    public static function getMetadata(){
        return self::$metadata;
    }

    /**
     * @param array|string|null $value
     * @return bool
     */
    public function isValidValue($value):bool {
        return true;
    }

}