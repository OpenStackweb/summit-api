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

use App\Models\Utils\Traits\HasImageTrait;
use models\main\File;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
use Doctrine\Common\Collections\ArrayCollection;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitBadgeFeatureTypeRepository")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="badge_features_types"
 *     )
 * })
 * @ORM\Table(name="SummitBadgeFeatureType")
 * Class SummitBadgeFeatureType
 * @package models\summit
 */
class SummitBadgeFeatureType extends SilverstripeBaseModel
{
    use SummitOwned;

    /**
     * @ORM\Column(name="Name", type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="Description", type="string")
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(name="TemplateContent", type="string")
     * @var string
     */
    private $template_content;

    /**
     * @ORM\ManyToOne(targetEntity="models\main\File", cascade={"persist"})
     * @ORM\JoinColumn(name="ImageID", referencedColumnName="ID")
     * @var File
     */
    private $image;

    /**
     * @ORM\ManyToMany(targetEntity="models\summit\SummitOrderExtraQuestionType", mappedBy="allowed_badge_features_types")
     * @var SummitOrderExtraQuestionType[]
     */
    private $extra_question_types;

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

    public function __construct()
    {
        parent::__construct();
        $this->template_content = '';
    }

    use HasImageTrait;

}