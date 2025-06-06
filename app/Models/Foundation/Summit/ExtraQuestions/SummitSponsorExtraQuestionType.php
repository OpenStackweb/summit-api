<?php namespace App\Models\Foundation\Summit\ExtraQuestions;
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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use Doctrine\ORM\Mapping AS ORM;
use models\summit\Sponsor;
use models\utils\One2ManyPropertyTrait;

/**
 * @package App\Models\Foundation\Summit\ExtraQuestions
 */
#[ORM\Table(name: 'SummitSponsorExtraQuestionType')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSponsorExtraQuestionTypeRepository::class)]
class SummitSponsorExtraQuestionType extends ExtraQuestionType
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getSponsorId' => 'sponsor'
    ];

    protected $hasPropertyMappings = [
        'hasSponsor' => 'sponsor'
    ];

    /**
     * @var Sponsor
     */
    #[ORM\JoinColumn(name: 'SponsorID', referencedColumnName: 'ID', onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: \models\summit\Sponsor::class, inversedBy: 'extra_questions')]
    private $sponsor;

    /**
     * @return Sponsor
     */
    public function getSponsor(): Sponsor
    {
        return $this->sponsor;
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
}