<?php namespace App\Models\Foundation\Summit\EmailFlows;
/**
 * Copyright 2020 OpenStack Foundation
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
 * @package App\Models\Foundation\Summit\EmailFlows
 */
#[ORM\Table(name: 'SummitEmailEventFlowType')]
#[ORM\Entity]
class SummitEmailEventFlowType extends SilverstripeBaseModel
{
    /**
     * @var SummitEmailFlowType
     */
    #[ORM\JoinColumn(name: 'SummitEmailFlowTypeID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \SummitEmailFlowType::class, inversedBy: 'flow_event_types')]
    private $flow;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Slug', type: 'string')]
    private $slug;

    /**
     * @var string
     */
    #[ORM\Column(name: 'DefaultEmailTemplateIdentifier', type: 'string')]
    private $default_email_template;

    /**
     * @return SummitEmailFlowType
     */
    public function getFlow(): SummitEmailFlowType
    {
        return $this->flow;
    }

    /**
     * @param SummitEmailFlowType $flow
     */
    public function setFlow(SummitEmailFlowType $flow): void
    {
        $this->flow = $flow;
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
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getDefaultEmailTemplate(): string
    {
        return $this->default_email_template;
    }

    /**
     * @param string $default_email_template
     */
    public function setDefaultEmailTemplate(string $default_email_template): void
    {
        $this->default_email_template = $default_email_template;
    }

}