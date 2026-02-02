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
use Libs\ModelSerializers\Many2OneExpandSerializer;
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
        if(in_array('offices', $relations) && !isset($values['offices'])) {
            $offices = [];
            foreach ($consultant->getOffices() as $e) {
                $offices[] = $e->getId();
            }
            $values['offices'] = $offices;
        }
        if(in_array('clients', $relations) && !isset($values['clients'])) {
            $clients = [];
            foreach ($consultant->getClients() as $e) {
                $clients[] = $e->getId();
            }
            $values['clients'] = $clients;
        }
        if(in_array('spoken_languages', $relations) && !isset($values['spoken_languages'])) {
            $spoken_languages = [];
            foreach ($consultant->getSpokenLanguages() as $e) {
                $spoken_languages[] = $e->getId();
            }
            $values['spoken_languages'] = $spoken_languages;
        }
        if(in_array('configuration_management_expertise', $relations) && !isset($values['configuration_management_expertise'])) {
            $configuration_management_expertise = [];
            foreach ($consultant->getConfigurationManagementExpertise() as $e) {
                $configuration_management_expertise[] = $e->getId();
            }
            $values['configuration_management_expertise'] = $configuration_management_expertise;
        }
        if(in_array('expertise_areas', $relations) && !isset($values['expertise_areas'])) {
            $expertise_areas = [];
            foreach ($consultant->getExpertiseAreas() as $e) {
                $expertise_areas[] = $e->getId();
            }
            $values['expertise_areas'] = $expertise_areas;
        }
        if(in_array('services_offered', $relations) && !isset($values['services_offered'])) {
            $services_offered = [];
            foreach ($consultant->getServicesOffered() as $e) {
                $services_offered[] = $e->getId();
            }
            $values['services_offered'] = $services_offered;
        }
        return $values;
    }

    protected static $expand_mappings = [
        'offices' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getOffices',
        ],
        'clients' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getClients',
        ],
        'spoken_languages' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getSpokenLanguages',
        ],
        'configuration_management_expertise' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getConfigurationManagementExpertise',
        ],
        'expertise_areas' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getExpertiseAreas',
        ],
        'services_offered' => [
            'type' => Many2OneExpandSerializer::class,
            'getter' => 'getServicesOffered',
        ],
    ];
}