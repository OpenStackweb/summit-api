<?php namespace App\ModelSerializers\Summit\Registration;
/*
 * Copyright 2021 OpenStack Foundation
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
use App\ModelSerializers\Summit\SponsorUserInfoGrantSerializer;
use models\summit\SponsorUserInfoGrant;

class SponsorUserInfoGrantCSVSerializer extends SponsorUserInfoGrantSerializer
{
    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $grant= $this->object;
        if (!$grant instanceof SponsorUserInfoGrant) return [];
        $values = parent::serialize($expand, $fields, $relations, $params);
        $values['notes'] = 'VIRTUAL';
        return $values;
    }
}