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

use Doctrine\ORM\Mapping as ORM;
use models\exceptions\ValidationException;
use models\utils\SilverstripeBaseModel;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitLeadReportSetting')]
#[ORM\Entity]
class SummitLeadReportSetting extends SilverstripeBaseModel
{
    use SummitOwned;

    const AttendeeExtraQuestionsKey = 'attendee_extra_questions';
    const SponsorExtraQuestionsKey = 'extra_questions';

    /**
     * @var Sponsor
     */
    #[ORM\JoinColumn(name: 'SponsorID', referencedColumnName: 'ID', onDelete: 'SET NULL')]
    #[ORM\ManyToOne(targetEntity: \models\summit\Sponsor::class)]
    private $sponsor;

    /**
     * @var array
     */
    #[ORM\Column(name: 'Columns', type: 'json')]
    protected $columns;

    /**
     * Sponsor constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->columns = [];
    }

    /**
     * @return Sponsor
     */
    public function getSponsor(): Sponsor
    {
        return $this->sponsor;
    }

    /**
     * @return int
     */
    public function getSponsorId(): int
    {
        try {
            return is_null($this->sponsor) ? 0: $this->sponsor->getId();
        }
        catch(\Exception $ex){
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function hasSponsor(): bool
    {
        return $this->getSponsorId() > 0;
    }

    /**
     * @param Sponsor $sponsor
     */
    public function setSponsor(Sponsor $sponsor): void
    {
        $this->sponsor = $sponsor;
    }

    public function clearSponsor(): void
    {
        $this->sponsor = null;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     */
    public function setColumns(array  $columns): void
    {
        $this->columns = $columns;
    }

    public function validateFor(Summit $summit, ?Sponsor $sponsor = null): void
    {
        $columns = $this->getColumns();

        // check if the extra questions belongs to the summit
        if (array_key_exists(SummitLeadReportSetting::AttendeeExtraQuestionsKey, $columns)) {
            foreach ($columns[SummitLeadReportSetting::AttendeeExtraQuestionsKey] as $extra_question) {
                if (is_array($extra_question) && array_key_exists('id', $extra_question) &&
                    is_null($summit->getOrderExtraQuestionById($extra_question['id']))) {
                    throw new ValidationException(
                        sprintf("Attendee extra question id %s doesn't belong to summit %s", $extra_question['id'], $summit->getId()));
                }
            }
        }

        // check if the extra questions belongs to the sponsor
        if (!is_null($sponsor) && array_key_exists(SummitLeadReportSetting::SponsorExtraQuestionsKey, $columns)) {
            foreach ($columns[SummitLeadReportSetting::SponsorExtraQuestionsKey] as $extra_question) {
                if (is_array($extra_question) && array_key_exists('id', $extra_question) &&
                    is_null($sponsor->getExtraQuestionById($extra_question['id']))) {
                    throw new ValidationException(
                        sprintf("Sponsor extra question id %s doesn't belong to sponsor %s", $extra_question['id'], $sponsor->getId()));
                }
            }
        }
    }
}