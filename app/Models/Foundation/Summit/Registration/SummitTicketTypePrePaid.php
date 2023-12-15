<?php namespace models\summit;
/**
 * Copyright 2023 OpenStack Foundation
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
final class SummitTicketTypePrePaid extends SummitTicketTypeWithPromo
{
    const PREPAID_NAME_SUFFIX = '[PREPAID]';

    public function __construct(SummitTicketType $type, SummitRegistrationPromoCode $promo_code)
    {
        parent::__construct($type, $promo_code);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return parent::getName() . ' ' . self::PREPAID_NAME_SUFFIX;
    }

    /**
     * @return float
     */
    public function getCost(): float
    {
        return 0; //already paid
    }

    /**
     * @return int
     */
    public function getCostInCents(): int
    {
        return 0; //already paid
    }

    /**
     * @return float
     */
    public function getFinalAmount(): float
    {
        return 0; //already paid
    }

    /**
     * @return int
     */
    public function getQuantity2Sell(): int
    {
        return 1;
    }

    /**
     * @return int
     */
    public function getMaxQuantityPerOrder(): int
    {
        return 1;
    }

    /**
     * @return string
     */
    public function getSubType(): string
    {
        return SummitTicketType::Subtype_PrePaid;
    }
}