<?php namespace App\Http\Controllers;

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

use App\Models\Foundation\Marketplace\IReviewRepository;
use App\ModelSerializers\SerializerUtils;
use App\Services\Model\Marketplace\IReviewService;
use Illuminate\Support\Facades\Request;
use models\oauth2\IResourceServerContext;
use ModelSerializers\SerializerRegistry;

/**
 * Class OAuth2ReviewsApiController
 * @package App\Http\Controllers
 */
final class OAuth2ReviewsApiController extends AbstractCompanyServiceApiController
{
    /**
     * @var IReviewService
     */
    private $service;

    /**
     * ReviewsApiController constructor.
     * @param IReviewRepository $repository
     * @param IReviewService $review_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IReviewRepository      $repository,
        IReviewService         $review_service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($repository, $resource_server_context);

        $this->service = $review_service;
    }

    use RequestProcessor;

    use GetAndValidateJsonPayload;

     /**
     * @param $company_service_id
     * @return mixed
     */
    public function addReview($company_service_id)
    {
        return $this->processRequest(function () use ($company_service_id) {
            $payload = $this->getJsonPayload(ReviewValidationRulesFactory::buildForAdd(Request::all()));

            $review = $this->service->addReview(intval($company_service_id), $payload);

            return $this->created(SerializerRegistry::getInstance()->getSerializer($review)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations()
            ));
        });
    }
}