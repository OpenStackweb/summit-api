<?php namespace models\summit;
/**
 * Copyright 2026 OpenStack Foundation
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
use InvalidArgumentException;
use models\main\Member;
use models\utils\SilverstripeBaseModel;

/**
 * Durable per-member usage counter for a single promo code.
 *
 * Written atomically inside the exclusive row lock held on the parent
 * SummitRegistrationPromoCode (via getByValueExclusiveLock) so that
 * concurrent order reservations serialize their QuantityPerAccount
 * check-and-increment.
 *
 * @package models\summit
 */
#[ORM\Table(name: 'SummitPromoCodeMemberReservation')]
#[ORM\UniqueConstraint(name: 'UQ_PromoCode_Member', columns: ['PromoCodeID', 'MemberID'])]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrineSummitPromoCodeMemberReservationRepository::class)]
class SummitPromoCodeMemberReservation extends SilverstripeBaseModel
{
    /**
     * @var int
     */
    #[ORM\Column(name: 'QtyUsed', type: 'integer', nullable: false)]
    private $qty_used;

    /**
     * @var SummitRegistrationPromoCode
     */
    #[ORM\ManyToOne(targetEntity: \models\summit\SummitRegistrationPromoCode::class)]
    #[ORM\JoinColumn(name: 'PromoCodeID', referencedColumnName: 'ID', nullable: false, onDelete: 'CASCADE')]
    private $promo_code;

    /**
     * @var Member
     */
    #[ORM\ManyToOne(targetEntity: \models\main\Member::class)]
    #[ORM\JoinColumn(name: 'MemberID', referencedColumnName: 'ID', nullable: false, onDelete: 'CASCADE')]
    private $member;

    public function __construct(SummitRegistrationPromoCode $promo_code, Member $member, int $qty_used = 0)
    {
        parent::__construct();
        if ($qty_used < 0) {
            throw new InvalidArgumentException('qty_used must be non-negative');
        }
        $this->promo_code = $promo_code;
        $this->member = $member;
        $this->qty_used = $qty_used;
    }

    public function getQtyUsed(): int
    {
        return (int)$this->qty_used;
    }

    public function getPromoCode(): SummitRegistrationPromoCode
    {
        return $this->promo_code;
    }

    public function getMember(): Member
    {
        return $this->member;
    }

    /**
     * Atomically raise qty_used by $by.
     *
     * @throws InvalidArgumentException when $by is negative.
     */
    public function increment(int $by): void
    {
        if ($by < 0) {
            throw new InvalidArgumentException('increment amount must be non-negative');
        }
        $this->qty_used += $by;
    }

    /**
     * Lower qty_used by $by, clamping at zero. Used by saga undo paths.
     *
     * @throws InvalidArgumentException when $by is negative.
     */
    public function decrement(int $by): void
    {
        if ($by < 0) {
            throw new InvalidArgumentException('decrement amount must be non-negative');
        }
        $this->qty_used = max(0, $this->qty_used - $by);
    }
}
