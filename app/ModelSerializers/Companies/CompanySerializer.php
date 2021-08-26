<?php namespace ModelSerializers;
/**
 * Copyright 2016 OpenStack Foundation
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
use models\main\Company;
use Libs\ModelSerializers\AbstractSerializer;
/**
 * Class CompanySerializer
 * @package ModelSerializers
 */
final class CompanySerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'Name' => 'name:json_string',
        'Url' => 'url:json_string',
        'DisplayOnSite' => 'display_on_site:json_boolean',
        'Featured' => 'featured:json_boolean',
        'City' => 'city:json_string',
        'State' => 'state:json_string',
        'Country' => 'country:json_string',
        'Description' => 'description:json_string',
        'Industry' => 'industry:json_string',
        'Contributions' => 'contributions:json_string',
        'ContactEmail' => 'contact_email:json_string',
        'MemberLevel' => 'member_level:json_string',
        'AdminEmail' => 'admin_email:json_string',
        'Overview' => 'overview:json_string',
        'Products' => 'products:json_string',
        'Commitment' => 'commitment:json_string',
        'CommitmentAuthor' => 'commitment_author:json_string',
        'LogoUrl' => 'logo:json_url',
        'BigLogoUrl' => 'big_logo:json_url',
        'Color' => 'color:json_color',
    ];

    protected static $allowed_relations = [
        'sponsorships',
        'project_sponsorships',
    ];

    /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [] )
    {
        $values = parent::serialize($expand, $fields, $relations, $params);
        $company = $this->object;
        if(!count($relations)) $relations = $this->getAllowedRelations();
        if(!$company instanceof Company) return $values;

        if (in_array('sponsorships', $relations)) {
            $sponsorships = [];
            foreach ($company->getSponsorships() as $s) {
                $sponsorships[] = $s->getId();
            }
            $values['sponsorships'] = $sponsorships;
        }

        if (in_array('sponsorships', $relations)) {
            $project_sponsorships = [];
            foreach ($company->getProjectSponsorships() as $ps) {
                $project_sponsorships[] = $ps->getId();
            }
            $values['project_sponsorships'] = $project_sponsorships;
        }

        if (!empty($expand)) {
            foreach (explode(',', $expand) as $relation) {
                $relation = trim($relation);
                switch ($relation) {
                    case 'sponsorships':
                    {
                        $sponsorships = [];
                        foreach ($company->getSponsorships() as $s) {
                            $sponsorships[] = SerializerRegistry::getInstance()->getSerializer($s)
                                ->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['sponsorships'] = $sponsorships;
                    }
                    break;
                    case 'project_sponsorships':
                    {
                        $project_sponsorships = [];
                        foreach ($company->getProjectSponsorships() as $ps) {
                            $project_sponsorships[] = SerializerRegistry::getInstance()->getSerializer($ps)
                                ->serialize(AbstractSerializer::filterExpandByPrefix($expand, $relation));
                        }
                        $values['project_sponsorships'] = $project_sponsorships;
                    }
                    break;
                  }
            }
        }

        return $values;
    }
}