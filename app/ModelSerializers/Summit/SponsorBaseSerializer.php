<?php namespace App\ModelSerializers\Summit;
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

use Illuminate\Support\Facades\Log;
use Libs\ModelSerializers\AbstractSerializer;
use models\main\Member;
use models\summit\Sponsor;
use models\summit\Summit;
use ModelSerializers\SerializerRegistry;
use ModelSerializers\SilverStripeSerializer;

/**
 * Class SponsorBaseSerializer
 * @package ModelSerializers
 */
abstract class SponsorBaseSerializer extends SilverStripeSerializer
{
    /**
     * @param Summit|null $summit
     * @param Sponsor $sponsor
     * @param Member $current_member
     * @return bool
     */
    protected static function isQREncKeyFieldAllowed(?Summit $summit, Sponsor $sponsor,Member $current_member, ):bool {
        $is_member_authz = $current_member->isSponsorUser() || $current_member->isAdmin();
        Log::debug
        (
            sprintf
            (
                "SponsorSerializer::isQREncKeyFieldAllowed summit %s sponsor %s current member %s(%s) is_member_authz %b.",
                is_null($summit) ? 'N/A': $summit->getId(),
                $sponsor->getId(),
                $current_member->getEmail(),
                $current_member->getId(),
                $is_member_authz
            )
        );


        $res = $is_member_authz &&
            ( (!is_null($summit) && $current_member->isSummitAllowed($summit))
        || $current_member->hasSponsorMembershipsFor($sponsor->getSummit(), $sponsor) );

        Log::debug
        (
            sprintf
            (
                "SponsorSerializer::isQREncKeyFieldAllowed summit %s sponsor %s current member %s(%s) is_member_authz %b res %b.",
                is_null($summit) ? 'N/A': $summit->getId(),
                $sponsor->getId(),
                $current_member->getEmail(),
                $current_member->getId(),
                $is_member_authz,
                $res
            )
        );
        return $res;

    }

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $sponsor = $this->object;
        if (!$sponsor instanceof Sponsor) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if (in_array('extra_questions', $relations) && !isset($values['extra_questions'])) {
            $extra_questions = [];
            foreach ($sponsor->getExtraQuestions() as $extra_question) {
                $extra_questions[] = $extra_question->getId();
            }
            $values['extra_questions'] = $extra_questions;
        }

        if (in_array('members', $relations) && !isset($values['members'])) {
            $members = [];
            foreach ($sponsor->getMembers() as $member) {
                $members[] = $member->getId();
            }
            $values['members'] = $members;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                $relation = trim($relation);
                if ($relation == 'summit') {
                    {
                        $current_member = $params['member'] ?? null;
                        $summit = $params['summit'] ?? null;
                        $serializer_type = SerializerRegistry::SerializerType_Public;
                        $fields = [
                            'id',
                            'name',
                            'start_date',
                            'end_date',
                            'time_zone_id',
                            'order_qr_prefix',
                            'ticket_qr_prefix',
                            'badge_qr_prefix',
                            'qr_registry_field_delimiter'
                        ];
                        if ($current_member instanceof Member && self::isQREncKeyFieldAllowed($summit, $sponsor, $current_member)) {
                            // this field is only for admin and sponsor users
                            $serializer_type = SerializerRegistry::SerializerType_Private;
                            $fields[] = 'qr_codes_enc_key';
                        }
                        unset($values['summit_id']);
                        $values['summit'] = SerializerRegistry::getInstance()
                            ->getSerializer($sponsor->getSummit(), $serializer_type)
                            ->serialize(AbstractSerializer::filterExpandByPrefix($expand, 'summit'), $fields, ['none']);
                    }
                }
            }
        }
        return $values;
    }
}