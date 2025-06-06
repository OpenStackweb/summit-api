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
/**
 * @package models\summit
 */
#[ORM\Table(name: 'SummitRefundPolicyType')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitRefundPolicyTypeRepository::class)]
#[ORM\AssociationOverrides([new ORM\AssociationOverride(name: 'summit', inversedBy: 'refund_policies')])]
class SummitRefundPolicyType extends SilverstripeBaseModel
{
    use SummitOwned;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Name', type: 'string')]
    private $name;

    /**
     * @var int
     */
    #[ORM\Column(name: 'UntilXDaysBeforeEventStarts', type: 'integer')]
    private $until_x_days_before_event_starts;

    /**
     * @var float
     */
    #[ORM\Column(name: 'RefundRate', type: 'float')]
    private $refund_rate;

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
     * @return int
     */
    public function getUntilXDaysBeforeEventStarts(): int
    {
        return $this->until_x_days_before_event_starts;
    }

    /**
     * @param int $until_x_days_before_event_starts
     */
    public function setUntilXDaysBeforeEventStarts(int $until_x_days_before_event_starts): void
    {
        $this->until_x_days_before_event_starts = $until_x_days_before_event_starts;
    }

    /**
     * @return float
     */
    public function getRefundRate(): float
    {
        return $this->refund_rate;
    }

    /**
     * @param float $refund_rate
     */
    public function setRefundRate(float $refund_rate): void
    {
        $this->refund_rate = $refund_rate;
    }

    public function __construct()
    {
        parent::__construct();
        $this->refund_rate = 0.0;
    }


}