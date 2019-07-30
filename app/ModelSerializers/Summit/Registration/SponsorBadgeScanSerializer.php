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
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\SponsorBadgeScan;
use ModelSerializers\SerializerRegistry;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class SponsorBadgeScanSerializer
 * @package ModelSerializers
 */
final class SponsorBadgeScanSerializer extends SilverStripeSerializer
{

    protected static $array_mappings = [
        'ScanDate'     => 'scan_date:datetime_epoch',
        'QRCode'       => 'qr_code:json_string',
        'SponsorId'    => 'sponsor_id:json_int',
        'UserId'       => 'user_id:json_int',
        'BadgeId'      => 'badge_id:json_int',
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
        $scan = $this->object;
        if (!$scan instanceof SponsorBadgeScan) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {
                    case 'sponsor': {
                        if(!$scan->hasSponsor()) continue;
                        unset($values['sponsor_id']);
                        $values['sponsor'] = SerializerRegistry::getInstance()->getSerializer($scan->getSponsor())->serialize(AbstractSerializer::filterExpandByPrefix($expand, "sponsor"));
                    }
                    break;
                    case 'user': {
                        if(!$scan->hasUser()) continue;
                        unset($values['user_id']);
                        $values['user'] = SerializerRegistry::getInstance()->getSerializer($scan->getUser())->serialize(AbstractSerializer::filterExpandByPrefix($expand, "user"));
                    }
                        break;
                    case 'badge': {
                        if(!$scan->hasBadge()) continue;
                        unset($values['badge_id']);
                        $values['badge'] = SerializerRegistry::getInstance()->getSerializer($scan->getBadge())->serialize(AbstractSerializer::filterExpandByPrefix($expand, "badge"));
                    }
                        break;
                }

            }
        }
        return $values;
    }
}