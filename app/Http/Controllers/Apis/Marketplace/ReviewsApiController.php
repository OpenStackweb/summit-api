<?php namespace App\Http\Controllers;

use App\ModelSerializers\SerializerUtils;
use Illuminate\Support\Facades\Request;
use ModelSerializers\SerializerRegistry;

/**
 * Copyright 2026 OpenStack Foundation
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
 * Class ReviewsApiController
 * @package App\Http\Controllers
 */
final class ReviewsApiController extends AbstractCompanyServiceApiController
{
    use RequestProcessor;

    use GetAndValidateJsonPayload;

     /**
     * @param $company_service_id
     * @return mixed
     */
    public function addReview($company_service_id)
    {
        return $this->processRequest(function () use ($company_service_id) {

            $payload = $this->getJsonPayload(EventTypeValidationRulesFactory::build(Request::all()));

            $review = $this->event_type_service->addEventType($company_service_id, $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($review)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }
}