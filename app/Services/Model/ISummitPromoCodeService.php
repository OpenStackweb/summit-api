<?php namespace services\model;
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

use Illuminate\Http\UploadedFile;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;
/**
 * Interface ISummitPromoCodeService
 * @package services\model
 */
interface ISummitPromoCodeService
{
    /**
     * @param Summit $summit
     * @param array $data
     * @param Member $current_user
     * @return SummitRegistrationPromoCode
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addPromoCode(Summit $summit, array $data, Member $current_user = null);

    /**
     * @param Summit $summit
     * @param int $promo_code_id
     * @param array $data
     * @param Member $current_user
     * @return SummitRegistrationPromoCode
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updatePromoCode(Summit $summit, $promo_code_id, array $data, Member $current_user = null);

    /**
     * @param Summit $summit
     * @param int $promo_code_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deletePromoCode(Summit $summit, $promo_code_id);

    /**
     * @param Summit $summit
     * @param int $promo_code_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function sendPromoCodeMail(Summit $summit, $promo_code_id);

    /**
     * @param Summit $summit
     * @param int $promo_code_id
     * @param int $ticket_type_id
     * @param array $data
     * @return SummitRegistrationPromoCode
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addPromoCodeTicketTypeRule(Summit $summit, int $promo_code_id, int $ticket_type_id, array $data):SummitRegistrationPromoCode;

    /**
     * @param Summit $summit
     * @param int $promo_code_id
     * @param int $ticket_type_id
     * @return SummitRegistrationPromoCode
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removePromoCodeTicketTypeRule(Summit $summit, int $promo_code_id, int $ticket_type_id):SummitRegistrationPromoCode;

    /**
     * @param Summit $summit
     * @param int $promo_code_id
     * @param int $badge_feature_id
     * @return SummitRegistrationPromoCode
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addPromoCodeBadgeFeature(Summit $summit, int $promo_code_id, int $badge_feature_id):SummitRegistrationPromoCode;

    /**
     * @param Summit $summit
     * @param int $promo_code_id
     * @param int $badge_feature_id
     * @return SummitRegistrationPromoCode
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removePromoCodeBadgeFeature(Summit $summit, int $promo_code_id, int $badge_feature_id):SummitRegistrationPromoCode;

    /**
     * @param Summit $summit
     * @param UploadedFile $csv_file
     * @param Member|null $current_user
     * @throws ValidationException
     */
    public function importPromoCodes(Summit $summit, UploadedFile $csv_file, ?Member $current_user = null):void;

}