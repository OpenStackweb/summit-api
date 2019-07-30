<?php namespace App\Services\Model\Imp;
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

use App\Models\Foundation\Main\Factories\CompanyFactory;
use App\Services\Model\AbstractService;
use App\Services\Model\ICompanyService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Company;
use models\main\ICompanyRepository;
/**
 * Class CompanyService
 * @package App\Services\Model\Imp
 */
final class CompanyService
    extends AbstractService
    implements ICompanyService
{

    /**
     * @var ICompanyRepository
     */
    private $repository;

    /**
     * CompanyService constructor.
     * @param ICompanyRepository $repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ICompanyRepository $repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->repository = $repository;
    }

    /**
     * @param array $payload
     * @throws ValidationException
     * @return Company
     */
    public function addCompany(array $payload): Company
    {
       return $this->tx_service->transaction(function() use($payload){
           $company_name = trim($payload['name']);
           $former_company = $this->repository->getByName($company_name);
           if(!is_null($former_company)){
               throw new ValidationException(sprintf("company %s already exists", $company_name));
           }
           $company = CompanyFactory::build($payload);
           $this->repository->add($company);
           return $company;
       });
    }

    /**
     * @param int $company_id
     * @param array $payload
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return Company
     */
    public function updateCompany(int $company_id, array $payload): Company
    {
        // TODO: Implement updateCompany() method.
    }

    /**
     * @param int $company_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteCompany(int $company_id): void
    {
        // TODO: Implement deleteCompany() method.
    }
}