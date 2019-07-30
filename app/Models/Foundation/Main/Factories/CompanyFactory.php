<?php namespace App\Models\Foundation\Main\Factories;
use models\main\Company;

/**
 * Copyright 2020 OpenStack Foundation
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

/**
 * Class CompanyFactory
 * @package App\Models\Foundation\Main\Factories
 */
final class CompanyFactory
{
    /**
     * @param array $data
     * @return Company
     */
    public static function build(array $data){
        $company = new Company();
        self::populate($company, $data);
        return $company;
    }

    /**
     * @param Company $company
     * @param array $data
     * @return Company
     */
    public static function populate(Company $company, array $data):Company
    {
        if (isset($data['name']))
            $company->setName(trim($data['name']));

        if (isset($data['description']))
            $company->setDescription(trim($data['description']));

        if (isset($data['url']))
            $company->setUrl(trim($data['url']));

        if (isset($data['industry']))
            $company->setIndustry(trim($data['industry']));

        if (isset($data['city']))
            $company->setCity(trim($data['city']));

        if (isset($data['state']))
            $company->setState(trim($data['state']));

        if (isset($data['country']))
            $company->setCountry(trim($data['country']));

        return $company;
    }
}