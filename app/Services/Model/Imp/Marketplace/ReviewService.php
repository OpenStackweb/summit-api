<?php namespace App\Services\Model\Imp\Marketplace;
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

use App\Models\Foundation\Marketplace\CompanyService;
use App\Models\Foundation\Marketplace\Factories\ReviewFactory;
use App\Models\Foundation\Marketplace\ICompanyServiceRepository;
use App\Models\Foundation\Marketplace\MarketPlaceReview;
use App\Services\Model\AbstractService;
use App\Services\Model\Marketplace\IReviewService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;

/**
 * Class ReviewService
 * @package App\Services\Model\Imp\Marketplace
 */
final class ReviewService
    extends AbstractService
    implements IReviewService
{
    /**
     * @var ICompanyServiceRepository
     */
    private $repository;

    /**
     * ReviewService constructor.
     * @param ICompanyServiceRepository $repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ICompanyServiceRepository $repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->repository = $repository;
    }

    /**
     * @inheritDoc
     */
    public function addReview($company_service_id, array $payload): MarketPlaceReview
    {
       return $this->tx_service->transaction(function() use($company_service_id, $payload){
           $company_service = $this->repository->getById($company_service_id);
           if(is_null($company_service) || !$company_service instanceof CompanyService)
                throw new EntityNotFoundException(sprintf("company service %s not found.", $company_service_id));

           $review = ReviewFactory::build($payload);
           $company_service->addReview($review);
           return $review;
       });
    }
}