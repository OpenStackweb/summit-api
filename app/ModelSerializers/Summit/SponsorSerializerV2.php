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

use Libs\ModelSerializers\Many2OneExpandSerializer;
use models\summit\Sponsor;

/**
 * Class SponsorSerializerV2
 * @package ModelSerializers
 */
final class SponsorSerializerV2 extends SponsorBaseSerializer
{
    protected static $allowed_relations = [
        'sponsorships',
    ];

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

        if (in_array('sponsorships', $relations) && !isset($values['sponsorships'])) {
            $sponsorships = [];
            foreach ($sponsor->getSponsorships() as $sponsorship) {
                $sponsorships[] = $sponsorship->getId();
            }
            $values['sponsorships'] = $sponsorships;
        }

        return $values;
    }

    protected static $expand_mappings = [
        'sponsorships' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getSponsorships',
        ],
    ];
}