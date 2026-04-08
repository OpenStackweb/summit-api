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

use Doctrine\ORM\Mapping AS ORM;

/**
 * @package models\summit
 */
#[ORM\Table(name: 'DomainAuthorizedSummitRegistrationPromoCode')]
#[ORM\Entity]
class DomainAuthorizedSummitRegistrationPromoCode extends SummitRegistrationPromoCode
    implements IDomainAuthorizedPromoCode
{
    use DomainAuthorizedPromoCodeTrait;
    use AutoApplyPromoCodeTrait;

    const ClassName = 'DOMAIN_AUTHORIZED_PROMO_CODE';

    /**
     * @return string
     */
    public function getClassName(){
        return self::ClassName;
    }

    public static $metadata = [
        'class_name'            => self::ClassName,
        'allowed_email_domains' => 'array',
        'quantity_per_account'  => 'integer',
        'auto_apply'            => 'boolean',
    ];

    /**
     * @return array
     */
    public static function getMetadata(){
        return array_merge(SummitRegistrationPromoCode::getMetadata(), self::$metadata);
    }

    /**
     * Override: any ticket type can be added regardless of audience value.
     * @param SummitTicketType $ticket_type
     */
    public function addAllowedTicketType(SummitTicketType $ticket_type)
    {
        parent::addAllowedTicketType($ticket_type);
    }

    /**
     * Transient property for remaining quantity per account (set by service layer).
     * @var int|null
     */
    private $remaining_quantity_per_account = null;

    /**
     * @return int|null
     */
    public function getRemainingQuantityPerAccount(): ?int
    {
        return $this->remaining_quantity_per_account;
    }

    /**
     * @param int|null $remaining
     */
    public function setRemainingQuantityPerAccount(?int $remaining): void
    {
        $this->remaining_quantity_per_account = $remaining;
    }
}
