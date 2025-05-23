<?php namespace models\summit;
/**
 * Copyright 2024 OpenStack Foundation
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
use App\Models\Foundation\ExtraQuestions\ExtraQuestionAnswer;
use models\exceptions\ValidationException;
use models\utils\One2ManyPropertyTrait;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SponsorBadgeScanExtraQuestionAnswer')]
#[ORM\Entity]
class SponsorBadgeScanExtraQuestionAnswer extends ExtraQuestionAnswer
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getQuestionId' => 'question',
        'getBadgeScanId' => 'badge_scan',
    ];

    protected $hasPropertyMappings = [
        'hasQuestion' => 'question',
        'hasBadgeScan' => 'badge_scan',
    ];

    /**
     * @var SponsorBadgeScan
     */
    #[ORM\JoinColumn(name: 'SponsorBadgeScanID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\summit\SponsorBadgeScan::class, inversedBy: 'extra_question_answers')]
    private $badge_scan;

    /**
     * @return SponsorBadgeScan
     */
    public function getBadgeScan(): ?SponsorBadgeScan
    {
        return $this->badge_scan;
    }

    /**
     * @param SponsorBadgeScan $badge_scan
     */
    public function setBadgeScan(SponsorBadgeScan $badge_scan): void
    {
        $this->badge_scan = $badge_scan;
    }

    public function clearBadgeScan(){
        $this->badge_scan = null;
    }

    /**
     * @param string|array $value
     * @throws ValidationException
     */
    public function setValue($value): void
    {
        parent::setValue($value);
        if(!is_null($this->badge_scan))
            $this->badge_scan->updateLastEdited();
    }

    public function __toString():string
    {
        return sprintf("SponsorBadgeScanExtraQuestionAnswer badge scan %s question %s value %s",
            $this->badge_scan->getId(), $this->question->getId(), $this->value);
    }
}