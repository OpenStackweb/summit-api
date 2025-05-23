<?php namespace App\Models\Foundation\Elections;
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
use Doctrine\ORM\Mapping AS ORM;
use models\main\Member;
use models\utils\One2ManyPropertyTrait;
use models\utils\SilverstripeBaseModel;

/**
 * @package App\Models\Foundation\Elections
 */
#[ORM\Table(name: 'CandidateNomination')]
#[ORM\Entity]
class Nomination extends SilverstripeBaseModel
{
    use One2ManyPropertyTrait;

    protected $getIdMappings = [
        'getCandidateId' => 'candidate',
        'getNominatorId' => 'nominator',
        'getElectionId' => 'election',
    ];

    protected $hasPropertyMappings = [
        'hasCandidate' => 'candidate',
        'hasNominator' => 'nominator',
        'hasElection' => 'election',
    ];

    /**
     * @var Election
     */
    #[ORM\JoinColumn(name: 'ElectionID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \App\Models\Foundation\Elections\Election::class, inversedBy: 'nominations')]
    private $election;

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'CandidateID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class, inversedBy: 'election_applications')]
    private $candidate;

    /**
     * @var Member
     */
    #[ORM\JoinColumn(name: 'MemberID', referencedColumnName: 'ID')]
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class, inversedBy: 'election_nominations')]
    private $nominator;

    /**
     * Nomination constructor.
     * @param Member $nominator
     * @param Member $candidate
     * @param Election $election
     */
    public function __construct
    (
        Member $nominator,
        Member $candidate,
        Election $election
    )
    {
        parent::__construct();
        $this->nominator = $nominator;
        $this->candidate = $candidate;
        $this->election = $election;
    }

    /**
     * @return Election
     */
    public function getElection(): Election
    {
        return $this->election;
    }

    /**
     * @return Member
     */
    public function getCandidate(): Member
    {
        return $this->candidate;
    }

    /**
     * @return Member
     */
    public function getNominator(): Member
    {
        return $this->nominator;
    }

}