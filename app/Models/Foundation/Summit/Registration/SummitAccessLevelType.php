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
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
use Doctrine\Common\Collections\ArrayCollection;
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitAccessLevelType')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitAccessLevelTypeRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'badge_access_level_types')])]
class SummitAccessLevelType extends SilverstripeBaseModel
{
    use SummitOwned;

    const IN_PERSON = 'IN_PERSON';
    const VIRTUAL = 'VIRTUAL';
    const CHAT = 'CHAT';

    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Description', type: 'string')]
    private $description;

    /**
     * @var string
     */
    #[ORM\Column(name: 'TemplateContent', type: 'string')]
    private $template_content;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'IsDefault', type: 'boolean')]
    private $is_default;

    /**
     * @return string
     */
    public function getName(): ?string
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
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getTemplateContent(): ?string
    {
        return $this->template_content;
    }

    /**
     * @param string $template_content
     */
    public function setTemplateContent(string $template_content): void
    {
        $this->template_content = $template_content;
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


    public function __construct()
    {
        parent::__construct();
        $this->template_content = '';
        $this->is_default = false;
    }

}