<?php namespace models\summit;
/**
 * Copyright 2018 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ISummitOwnedEntityRepository;
/**
 * Interface ISummitRegistrationPromoCodeRepository
 * @package models\summit
 */
interface ISummitRegistrationPromoCodeRepository extends ISummitOwnedEntityRepository
{
    /**
     * @param Summit $summit
     * @return array
     */
    public function getMetadata(Summit $summit);

    /**
     * @param string $code
     * @return SummitRegistrationPromoCode|null
     */
    public function getByCode(string $code):?SummitRegistrationPromoCode;

    /**
     * @param Summit $summit
     * @param array $codes
     * @return mixed
     */
    public function getByValuesExclusiveLock(Summit $summit, array $codes);

    /**
     * @param Summit $summit
     * @param string $code
     * @return SummitRegistrationPromoCode|null
     */
    public function getByValueExclusiveLock(Summit $summit, string $code):?SummitRegistrationPromoCode;

}