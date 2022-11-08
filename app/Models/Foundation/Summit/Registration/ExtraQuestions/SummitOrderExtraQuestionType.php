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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use App\Models\Foundation\Summit\ScheduleEntity;
use models\exceptions\ValidationException;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity(repositoryClass="App\Repositories\Summit\DoctrineSummitOrderExtraQuestionTypeRepository")
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(
 *          name="summit",
 *          inversedBy="order_extra_questions"
 *     )
 * })
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="SummitOrderExtraQuestionType")
 * Class SummitOrderExtraQuestionType
 * @package models\summit
 */
class SummitOrderExtraQuestionType extends ExtraQuestionType
{
    use SummitOwned;

    /**
     * @ORM\Column(name="`Usage`", type="string")
     * @var string
     */
    private $usage;

    /**
     * @ORM\Column(name="Printable", type="boolean")
     * @var boolean
     */
    private $printable;

    /**
     * @ORM\Column(name="`ExternalId`", type="string")
     * @var string
     */
    private $external_id;

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
        $this->printable = false;
        $this->external_id = null;
    }

    /**
     * @return string
     */
    public function getExternalId(): ?string
    {
        return $this->external_id;
    }

    /**
     * @param string $external_id
     */
    public function setExternalId(?string $external_id): void
    {
        $this->external_id = $external_id;
    }

    use ScheduleEntity;
}