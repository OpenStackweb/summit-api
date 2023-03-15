<?php namespace App\Services\Model\Imp\Traits;
/*
 * Copyright 2023 OpenStack Foundation
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
use libs\utils\TextUtils;
use models\exceptions\ValidationException;
use models\main\Company;
use models\summit\Summit;

/**
 * Trait AttendeeCompany
 * @package App\Services\Model\Imp\Traits
 */
trait SummitRegistrationCompany
{
    /**
     * @param Summit $summit
     * @param string|null $company_name
     * @return Summit
     * @throws ValidationException
     */
    public function registerCompanyFor(Summit $summit, ?string $company_name):?Company{
        $company = null;
        if(!empty($company_name)){
            Log::debug(sprintf("SummitRegistrationCompany::registerCompanyFor summit %s company %s", $summit->getId(), $company_name));
            $company_name = TextUtils::trim($company_name);
            // check if company exists ...
            $company = $this->company_repository->getByName($company_name);

            if(is_null($company)){
                // create it
                Log::debug(sprintf("SummitRegistrationCompany::registerCompanyFor summit %s creating company %s", $summit->getId(), $company_name));
                $company = $this->company_service->addCompany
                (
                    [
                        'name' => $company_name
                    ]
                );
            }
            // register on the summit
            Log::debug(sprintf("SummitRegistrationCompany::registerCompanyFor summit %s registering company %s", $summit->getId(), $company_name));
            $summit->addRegistrationCompany($company);
        }
        return $company;
    }
}