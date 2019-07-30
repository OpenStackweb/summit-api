<?php namespace App\Http\Controllers;
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
use App\Models\Foundation\Summit\PromoCodes\PromoCodesConstants;
use models\exceptions\ValidationException;
use models\summit\MemberSummitRegistrationDiscountCode;
use models\summit\MemberSummitRegistrationPromoCode;
use models\summit\SpeakerSummitRegistrationDiscountCode;
use models\summit\SpeakerSummitRegistrationPromoCode;
use models\summit\SponsorSummitRegistrationDiscountCode;
use models\summit\SponsorSummitRegistrationPromoCode;
/**
 * Class PromoCodesValidationRulesFactory
 * @package App\Http\Controllers
 */
final class PromoCodesValidationRulesFactory
{
    /**
     * @param array $data
     * @param bool $update
     * @return array
     * @throws ValidationException
     */
    public static function build(array $data, $update = false){
        if(!isset($data['class_name']))
            throw new ValidationException("class_name parameter is mandatory");

        $class_name = trim($data['class_name']);

        if(!in_array($class_name, PromoCodesConstants::$valid_class_names)){
            throw new ValidationException(
                sprintf
                (
                    "class_name param has an invalid value ( valid values are %s",
                    implode(", ", PromoCodesConstants::$valid_class_names)
                )
            );
        }

        $base_rules = [
            'code'                 => $update ? 'sometimes|string|max:255' : 'required|string|max:255',
            'quantity_available'   => 'sometimes|integer|min:0',
            'valid_since_date'     => 'nullable|date_format:U',
            'valid_until_date'     => 'nullable|required_with:valid_since_date|date_format:U|after:valid_since_date',
            'badge_type_id'        => 'nullable|integer',
            'allowed_ticket_types' => 'sometimes|int_array',
            'badge_features'       => 'sometimes|int_array',
        ];

        $specific_rules = [];
        $discount_code_rules = [
            'amount'     => 'sometimes|numeric|min:0',
            'rate'       => 'sometimes|numeric|min:0',
        ];

        switch ($class_name){
            case MemberSummitRegistrationPromoCode::ClassName:{
                $specific_rules = [
                    'first_name' => 'required_without:owner_id|string',
                    'last_name'  => 'required_without:owner_id|string',
                    'email'      => 'required_without:owner_id|email|max:254',
                    'type'       => 'required|string|in:'.join(",", PromoCodesConstants::MemberSummitRegistrationPromoCodeTypes),
                    'owner_id'   => 'required_without:first_name,last_name,email|integer'
                ];
            }
            break;
            case SpeakerSummitRegistrationPromoCode::ClassName:
            {
                $specific_rules = [
                    'type'       => 'required|string|in:'.join(",", PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypes),
                    'speaker_id' => 'required|integer'
                ];
            }
            break;
            case SponsorSummitRegistrationPromoCode::ClassName:
            {
                $specific_rules = [
                    'sponsor_id' => 'required|integer'
                ];
            }
            break;
            case MemberSummitRegistrationDiscountCode::ClassName:
                {
                    $specific_rules = array_merge([
                        'first_name' => 'required_without:owner_id|string',
                        'last_name'  => 'required_without:owner_id|string',
                        'email'      => 'required_without:owner_id|email|max:254',
                        'type'       => 'required|string|in:'.join(",", PromoCodesConstants::MemberSummitRegistrationPromoCodeTypes),
                        'owner_id'   => 'required_without:first_name,last_name,email|integer',
                    ], $discount_code_rules);
                }
                break;
            case SpeakerSummitRegistrationDiscountCode::ClassName:
                {
                    $specific_rules = array_merge([
                        'type'       => 'required|string|in:'.join(",", PromoCodesConstants::SpeakerSummitRegistrationPromoCodeTypes),
                        'speaker_id' => 'required|integer',
                        'amount'     => 'sometimes|required_without:rate',
                        'rate'       => 'sometimes|required_without:amount',
                    ], $discount_code_rules);
                }
                break;
            case SponsorSummitRegistrationDiscountCode::ClassName:
                {
                    $specific_rules = array_merge([
                        'sponsor_id' => 'required|integer'
                    ],$discount_code_rules);

                }
                break;
        }

        return array_merge($base_rules, $specific_rules);
    }
}