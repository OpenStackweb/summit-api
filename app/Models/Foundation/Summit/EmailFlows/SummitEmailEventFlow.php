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
use models\summit\SummitOwned;
use models\utils\SilverstripeBaseModel;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitEmailEventFlowRepository")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="email_flows_events"
 *     )
 * })
 * @ORM\Table(name="SummitEmailEventFlow")
 * Class SummitEmailEventFlow
 * @package App\Models\Foundation\Summit\EmailFlows
 */
class SummitEmailEventFlow extends SilverstripeBaseModel
{
    use SummitOwned;

    /**
     * @ORM\Column(name="EmailTemplateIdentifier", type="string")
     * @var string
     */
    private $email_template_identifier;

    /**
     * @ORM\ManyToOne(targetEntity="SummitEmailEventFlowType")
     * @ORM\JoinColumn(name="SummitEmailEventFlowTypeID", referencedColumnName="ID")
     * @var SummitEmailEventFlowType
     */
    private $event_type;

    /**
     * @return string
     */
    public function getEmailTemplateIdentifier(): string
    {
        return $this->email_template_identifier;
    }

    public function getFlowName():string{
        return $this->event_type->getFlow()->getName();
    }

    public function getEventTypeName():string{
        return $this->event_type->getName();
    }

    /**
     * @param string $email_template_identifier
     */
    public function setEmailTemplateIdentifier(string $email_template_identifier): void
    {
        $this->email_template_identifier = $email_template_identifier;
    }

    /**
     * @return SummitEmailEventFlowType
     */
    public function getEventType(): SummitEmailEventFlowType
    {
        return $this->event_type;
    }

    /**
     * @param SummitEmailEventFlowType $event_type
     */
    public function setEventType(SummitEmailEventFlowType $event_type): void
    {
        $this->event_type = $event_type;
    }

}