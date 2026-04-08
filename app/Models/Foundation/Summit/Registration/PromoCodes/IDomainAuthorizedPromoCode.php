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

/**
 * Interface IDomainAuthorizedPromoCode
 * @package models\summit
 *
 * Marker interface for domain-authorized promo code subtypes.
 * Used by strategy and discovery logic for type-checking.
 */
interface IDomainAuthorizedPromoCode
{
    /**
     * @return array
     */
    public function getAllowedEmailDomains(): array;

    /**
     * @return int
     */
    public function getQuantityPerAccount(): int;

    /**
     * @param string $email
     * @return bool
     */
    public function matchesEmailDomain(string $email): bool;
}
