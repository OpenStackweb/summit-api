<?php namespace App\Models\Foundation\Summit\Factories;
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
use models\summit\MemberSummitRegistrationDiscountCode;
use models\summit\MemberSummitRegistrationPromoCode;
use models\summit\PrePaidSummitRegistrationDiscountCode;
use models\summit\PrePaidSummitRegistrationPromoCode;
use models\summit\SpeakersRegistrationDiscountCode;
use models\summit\SpeakersSummitRegistrationPromoCode;
use models\summit\SpeakerSummitRegistrationDiscountCode;
use models\summit\SpeakerSummitRegistrationPromoCode;
use models\summit\SponsorSummitRegistrationDiscountCode;
use models\summit\SponsorSummitRegistrationPromoCode;
use models\summit\Summit;
use models\summit\SummitRegistrationDiscountCode;
use models\summit\SummitRegistrationPromoCode;
/**
 * Class SummitPromoCodeFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitPromoCodeFactory
{
    /**
     * @param Summit $summit
     * @param array $data
     * @param array $params
     * @return SummitRegistrationPromoCode|null
     */
    public static function build(Summit $summit, array $data, array $params = []){
        $promo_code = null;
        switch ($data['class_name']){
            case SummitRegistrationPromoCode::ClassName:{
                $promo_code = new SummitRegistrationPromoCode();
            }
            break;
            case SummitRegistrationDiscountCode::ClassName:{
                $promo_code = new SummitRegistrationDiscountCode();
            }
            break;
            case MemberSummitRegistrationPromoCode::ClassName:{
                $promo_code = new MemberSummitRegistrationPromoCode();
            }
            break;
            case SpeakerSummitRegistrationPromoCode::ClassName:{
                $promo_code = new SpeakerSummitRegistrationPromoCode();
            }
            break;
            case SponsorSummitRegistrationPromoCode::ClassName:{
                $promo_code = new SponsorSummitRegistrationPromoCode();
            }
            break;
            case MemberSummitRegistrationDiscountCode::ClassName:{
                $promo_code = new MemberSummitRegistrationDiscountCode();
            }
            break;
            case SpeakerSummitRegistrationDiscountCode::ClassName:{
                $promo_code = new SpeakerSummitRegistrationDiscountCode();
            }
            break;
            case SponsorSummitRegistrationDiscountCode::ClassName:{
                $promo_code = new SponsorSummitRegistrationDiscountCode();
            }
            break;
            case SpeakersSummitRegistrationPromoCode::ClassName:{
                $promo_code = new SpeakersSummitRegistrationPromoCode();
            }
            break;
            case SpeakersRegistrationDiscountCode::ClassName:{
                $promo_code = new SpeakersRegistrationDiscountCode();
            }
            break;
            case PrePaidSummitRegistrationPromoCode::ClassName:{
                $promo_code = new PrePaidSummitRegistrationPromoCode();
            }
            break;
            case PrePaidSummitRegistrationDiscountCode::ClassName:{
                $promo_code = new PrePaidSummitRegistrationDiscountCode();
            }
            break;
        }

        if(is_null($promo_code)) return null;
        return self::populate($promo_code, $summit, $data, $params);
    }

    /**
     * @param SummitRegistrationPromoCode $promo_code
     * @param Summit $summit
     * @param array $data
     * @param array $params
     * @return SummitRegistrationPromoCode
     * @throws \models\exceptions\ValidationException
     */
    public static function populate(SummitRegistrationPromoCode $promo_code, Summit $summit, array $data, array $params = []){

        // common members

        if(isset($params['allowed_ticket_types'])){
            $promo_code->clearTicketTypes();
            foreach ($params['allowed_ticket_types'] as $ticket_type){
                $promo_code->addAllowedTicketType($ticket_type);
            }
        }

        if(isset($params['badge_features'])){
            foreach ($params['badge_features'] as $feature){
                $promo_code->addBadgeFeatureType($feature);
            }
        }

        if(isset($data['external_id'])){
            $promo_code->setExternalId(trim($data['external_id']));
        }

        if(isset($data['code']))
            $promo_code->setCode(trim($data['code']));

        if(isset($data['description']))
            $promo_code->setDescription(trim($data['description']));

        if(isset($data['notes']))
            $promo_code->setNotes(trim($data['notes']));

        if(isset($data['valid_since_date'])) {
            $val = intval($data['valid_since_date']);
            if($val > 0) {
                $val = new \DateTime("@$val");
                $val->setTimezone($summit->getTimeZone());
                $promo_code->setValidSinceDate($summit->convertDateFromTimeZone2UTC($val));
            }
            else{
                $promo_code->setValidSinceDate(null);
            }
        }

        if(isset($data['valid_until_date'])) {
            $val = intval($data['valid_until_date']);
            if($val > 0) {
                $val = new \DateTime("@$val");
                $val->setTimezone($summit->getTimeZone());
                $promo_code->setValidUntilDate($summit->convertDateFromTimeZone2UTC($val));
            }
            else{
                $promo_code->setValidUntilDate(null);
            }
        }

        switch ($data['class_name']){
            case SummitRegistrationDiscountCode::ClassName:
            case PrePaidSummitRegistrationDiscountCode::ClassName:{
                if(isset($data['amount']))
                    $promo_code->setAmount(floatval($data['amount']));
                if(isset($data['rate']))
                    $promo_code->setRate(floatval($data['rate']));
                if(isset($data['quantity_available']))
                    $promo_code->setQuantityAvailable(intval($data['quantity_available']));
            }
            break;
            case MemberSummitRegistrationPromoCode::ClassName:{
                if(isset($params['owner']))
                    $promo_code->setOwner($params['owner']);
                if(isset($data['type']))
                    $promo_code->setType($data['type']);
                if(isset($data['first_name']))
                    $promo_code->setFirstName(trim($data['first_name']));
                if(isset($data['last_name']))
                    $promo_code->setLastName(trim($data['last_name']));
                if(isset($data['email']))
                    $promo_code->setEmail(trim($data['email']));
                if(isset($data['quantity_available']))
                    $promo_code->setQuantityAvailable(intval($data['quantity_available']));
            }
            break;
            case SpeakerSummitRegistrationPromoCode::ClassName:{
                if(isset($data['type']))
                    $promo_code->setType($data['type']);
                if(isset($params['speaker']))
                    $promo_code->setSpeaker($params['speaker']);
                if(isset($data['quantity_available']))
                    $promo_code->setQuantityAvailable(intval($data['quantity_available']));
            }
            break;
            case SpeakersSummitRegistrationPromoCode::ClassName:{
                if(isset($data['type']))
                    $promo_code->setType($data['type']);
            }
            break;
            case SponsorSummitRegistrationPromoCode::ClassName:{
                if(isset($data['contact_email']))
                    $promo_code->setContactEmail(trim($data['contact_email']));

                if(isset($data['quantity_available']))
                    $promo_code->setQuantityAvailable(intval($data['quantity_available']));

                if(isset($params['sponsor']))
                    $promo_code->setSponsor($params['sponsor']);
            }
            break;
            case MemberSummitRegistrationDiscountCode::ClassName:{
                if(isset($params['owner']))
                    $promo_code->setOwner($params['owner']);
                if(isset($data['type']))
                    $promo_code->setType($data['type']);
                if(isset($data['first_name']))
                    $promo_code->setFirstName(trim($data['first_name']));
                if(isset($data['last_name']))
                    $promo_code->setLastName(trim($data['last_name']));
                if(isset($data['email']))
                    $promo_code->setEmail(trim($data['email']));
                if(isset($data['amount']))
                    $promo_code->setAmount(floatval($data['amount']));
                if(isset($data['rate']))
                    $promo_code->setRate(floatval($data['rate']));
                if(isset($data['quantity_available']))
                    $promo_code->setQuantityAvailable(intval($data['quantity_available']));
            }
            break;
            case SpeakerSummitRegistrationDiscountCode::ClassName:{
                if(isset($data['type']))
                    $promo_code->setType($data['type']);
                if(isset($params['speaker']))
                    $promo_code->setSpeaker($params['speaker']);
                if(isset($data['amount']))
                    $promo_code->setAmount(floatval($data['amount']));
                if(isset($data['rate']))
                    $promo_code->setRate(floatval($data['rate']));
                if(isset($data['quantity_available']))
                    $promo_code->setQuantityAvailable(intval($data['quantity_available']));
            }
            break;
            case SpeakersRegistrationDiscountCode::ClassName: {
                if(isset($data['type']))
                    $promo_code->setType($data['type']);
                if(isset($data['amount']))
                    $promo_code->setAmount(floatval($data['amount']));
                if(isset($data['rate']))
                    $promo_code->setRate(floatval($data['rate']));
            }
            break;
            case SponsorSummitRegistrationDiscountCode::ClassName:{
                if(isset($data['contact_email']))
                    $promo_code->setContactEmail(trim($data['contact_email']));
                if(isset($data['amount']))
                    $promo_code->setAmount(floatval($data['amount']));
                if(isset($data['rate']))
                    $promo_code->setRate(floatval($data['rate']));
                if(isset($params['sponsor']))
                    $promo_code->setSponsor($params['sponsor']);
                if(isset($data['amount']))
                    $promo_code->setAmount(floatval($data['amount']));
                if(isset($data['rate']))
                    $promo_code->setRate(floatval($data['rate']));
                if(isset($data['quantity_available']))
                    $promo_code->setQuantityAvailable(intval($data['quantity_available']));
            }
            break;
        }

        $summit->addPromoCode($promo_code);
        return $promo_code;
    }
}