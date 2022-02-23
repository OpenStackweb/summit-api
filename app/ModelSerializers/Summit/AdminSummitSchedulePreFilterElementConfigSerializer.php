<?php namespace ModelSerializers;
/*
 * Copyright 2022 OpenStack Foundation
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

use Illuminate\Support\Facades\App;
use models\main\ICompanyRepository;
use models\main\ITagRepository;
use models\summit\ISpeakerRepository;
use models\summit\SummitScheduleFilterElementConfig;
use models\summit\SummitSchedulePreFilterElementConfig;

/**
 * Class SummitSchedulePreFilterElementConfigSerializer
 * @package ModelSerializers
 */
final class AdminSummitSchedulePreFilterElementConfigSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Type' => 'type:json_string',
        'ConfigId' => 'config_id:json_int'
    ];

    protected static $allowed_relations = [
        'values',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relation
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array())
    {
        $filter = $this->object;
        if (!$filter instanceof SummitSchedulePreFilterElementConfig) return [];

        if (!count($relations)) $relations = $this->getAllowedRelations();
        $values  = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('values', $relations) && !isset($values['values'])){
            $values['values'] = $filter->getValues();
        }

        switch(trim($expand)){
            case 'values':
            {
                $res = [];
                if($filter->getType() === SummitScheduleFilterElementConfig::Type_Company){
                    $company_repository = App::make(ICompanyRepository::class);
                    foreach ($filter->getValues() as $id){
                        $company = $company_repository->getById(intval($id));
                        if(is_null($company)) continue;
                        $res[] = [
                            'id' => $company->getId(),
                            'name' => $company->getName()
                        ];
                    }
                }
                if($filter->getType() === SummitScheduleFilterElementConfig::Type_Speakers){
                    $speakers_repository = App::make(ISpeakerRepository::class);
                    foreach ($filter->getValues() as $id){
                        $speaker = $speakers_repository->getById(intval($id));
                        if(is_null($speaker)) continue;
                        $res[] = [
                            'id' => $speaker->getId(),
                            'first_name' => $speaker->getFirstName(),
                            'last_name' => $speaker->getLastName(),
                            'email' => $speaker->getEmail(),
                        ];
                    }
                }
                $values['values'] = count($res) ? $res : $values['values'];
            }
            break;
        }

        return $values;
    }
}