<?php namespace App\ModelSerializers\Marketplace;
/**
 * Copyright 2017 OpenStack Foundation
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
use App\Models\Foundation\Marketplace\Consultant;
use ModelSerializers\SerializerRegistry;
/**
 * Class ConsultantSerializer
 * @package App\ModelSerializers\Marketplace
 */
final class ConsultantSerializer extends RegionalSupportedCompanyServiceSerializer
{
    protected static $allowed_relations = [
        'offices',
        'clients',
        'spoken_languages',
        'configuration_management_expertise',
        'expertise_areas',
        'services_offered',
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

        $consultant  = $this->object;
        if(!$consultant instanceof Consultant) return [];
        $values           = parent::serialize($expand, $fields, $relations, $params);

        if(in_array('offices', $relations)){
            $res = [];
            foreach ($consultant->getOffices() as $office){
                $res[] = SerializerRegistry::getInstance()
                    ->getSerializer($office)
                    ->serialize($expand);
            }
            $values['offices'] = $res;
        }

        if(in_array('clients', $relations)){
            $res = [];
            foreach ($consultant->getClients() as $client){
                $res[] = SerializerRegistry::getInstance()
                    ->getSerializer($client)
                    ->serialize($expand);
            }
            $values['clients'] = $res;
        }

        if(in_array('spoken_languages', $relations)){
            $res = [];
            foreach ($consultant->getSpokenLanguages() as $lang){
                $res[] = SerializerRegistry::getInstance()
                    ->getSerializer($lang)
                    ->serialize($expand);
            }
            $values['spoken_languages'] = $res;
        }

        if(in_array('configuration_management_expertise', $relations)){
            $res = [];
            foreach ($consultant->getConfigurationManagementExpertise() as $exp){
                $res[] = SerializerRegistry::getInstance()
                    ->getSerializer($exp)
                    ->serialize($expand);
            }
            $values['configuration_management_expertise'] = $res;
        }

        if(in_array('expertise_areas', $relations)){
            $res = [];
            foreach ($consultant->getExpertiseAreas() as $area){
                $res[] = SerializerRegistry::getInstance()
                    ->getSerializer($area)
                    ->serialize($expand);
            }
            $values['expertise_areas'] = $res;
        }

        if(in_array('services_offered', $relations)){
            $res = [];
            foreach ($consultant->getServicesOffered() as $service){
                $res[] = SerializerRegistry::getInstance()
                    ->getSerializer($service)
                    ->serialize($expand);
            }
            $values['services_offered'] = $res;
        }

        if (!empty($expand)) {
            $exp_expand = explode(',', $expand);
            foreach ($exp_expand as $relation) {
                switch (trim($relation)) {

                }
            }
        }
        return $values;
    }
}