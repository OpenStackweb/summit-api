<?php namespace ModelSerializers;
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
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\Sponsor;
/**
 * Class SponsorSerializer
 * @package ModelSerializers
 */
final class SponsorSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Order'         => 'order:json_int',
        'SummitId'      => 'summit_id:json_int',
        'CompanyId'     => 'company_id:json_int',
        'SponsorshipId' => 'sponsorship_id:json_int',
    ];

    protected static $allowed_relations = [
        'members',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array() )
    {
        $sponsor = $this->object;
        if (!$sponsor instanceof Sponsor) return [];
        if(!count($relations)) $relations = $this->getAllowedRelations();
        $values = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('members', $relations)) {
            $members = [];
            foreach ($sponsor->getMembers() as $member) {
                $members[] = $member->getId();
            }
            $values['members'] = $members;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {
                    case 'summit':
                        {
                            unset($values['summit_id']);
                            $values['summit'] =  SerializerRegistry::getInstance()->getSerializer($sponsor->getSummit())->serialize(AbstractSerializer::filterExpandByPrefix($expand,'summit'),[
                                'id',
                                'name',
                                'start_date',
                                'end_date',
                                'time_zone_id',
                                'order_qr_prefix',
                                'ticket_qr_prefix',
                                'badge_qr_prefix',
                                'qr_registry_field_delimiter',
                            ],['none']);
                        }
                        break;
                    case 'members':
                        {
                            unset($values['members']);
                            $members = [];
                            foreach ($sponsor->getMembers() as $member) {
                                $members[] =  SerializerRegistry::getInstance()->getSerializer($member)->serialize(AbstractSerializer::filterExpandByPrefix($expand,'members'));
                            }
                            $values['members'] = $members;
                        }
                        break;
                    case 'company':
                        {
                            if($sponsor->hasCompany()) {
                                unset($values['company_id']);
                                $values['company'] = SerializerRegistry::getInstance()->getSerializer($sponsor->getCompany())->serialize(AbstractSerializer::filterExpandByPrefix($expand,'company'));
                            }
                        }
                        break;
                    case 'sponsorship':
                        {
                            if($sponsor->hasSponsorship()) {
                                unset($values['sponsorship_id']);
                                $values['sponsorship'] = SerializerRegistry::getInstance()->getSerializer($sponsor->getSponsorship())->serialize(AbstractSerializer::filterExpandByPrefix($expand,'sponsorship'));
                            }
                        }
                        break;


                }
            }
        }
        return $values;
    }
}