<?php namespace App\Services\Model;
/**
 * Copyright 2025 OpenStack Foundation
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

use models\summit\Summit;
use models\summit\SummitSponsorship;

/**
 * Interface ISummitSponsorshipService
 * @package services\model
 */
interface ISummitSponsorshipService
{
    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int[] $summit_sponsorship_type_ids
     * @return SummitSponsorship[]
     * @throws \Exception
     */
    public function addSponsorships(Summit $summit, int $sponsor_id, array $summit_sponsorship_type_ids): array;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $sponsorship_id
     * @return void
     */
    public function removeSponsorship(Summit $summit, int $sponsor_id, int $sponsorship_id): void;
}