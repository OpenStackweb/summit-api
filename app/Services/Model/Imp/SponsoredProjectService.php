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
use App\Models\Foundation\Main\Factories\ProjectSponsorshipTypeFactory;
use App\Models\Foundation\Main\Factories\SponsoredProjectFactory;
use App\Models\Foundation\Main\Repositories\ISponsoredProjectRepository;
use App\Services\Model\AbstractService;
use App\Services\Model\ISponsoredProjectService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Company;
use models\main\ICompanyRepository;
use models\main\ProjectSponsorshipType;
use models\main\SponsoredProject;
use models\main\SupportingCompany;

/**
 * Class SponsoredProjectService
 * @package App\Services\Model\Imp
 */
final class SponsoredProjectService
    extends AbstractService
    implements ISponsoredProjectService
{

    /**
     * @var ISponsoredProjectRepository
     */
    private $repository;

    /**
     * @var ICompanyRepository
     */
    private $company_repository;

    /**
     * SponsoredProjectService constructor.
     * @param ISponsoredProjectRepository $repository
     * @param ICompanyRepository $company_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISponsoredProjectRepository $repository,
        ICompanyRepository $company_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->repository = $repository;
        $this->company_repository = $company_repository;
    }

    /**
     * @inheritDoc
     */
    public function add(array $payload): SponsoredProject
    {
       return $this->tx_service->transaction(function() use ($payload){
            $name = trim($payload['name']);
            $formerProject = $this->repository->getByName($name);
            if(!is_null($formerProject)){
                throw new ValidationException(sprintf("sponsored project %s already exists.", $name));
            }

            $sponsoredProject = SponsoredProjectFactory::build($payload);

            $this->repository->add($sponsoredProject);

            return $sponsoredProject;
       });
    }

    /**
     * @inheritDoc
     */
    public function update(int $project_id, array $payload): SponsoredProject
    {
        return $this->tx_service->transaction(function() use ($project_id, $payload){
            if(isset($payload['name'])) {
                $name = trim($payload['name']);
                $formerProject = $this->repository->getByName($name);
                if (!is_null($formerProject) && $formerProject->getId() !== $project_id) {
                    throw new ValidationException(sprintf("sponsored project %s already exists.", $name));
                }
            }

            $sponsoredProject = $this->repository->getById($project_id);

            if(is_null($sponsoredProject) || !$sponsoredProject instanceof SponsoredProject)
                throw new EntityNotFoundException(sprintf("sponsored project %s not found.", $project_id));

            SponsoredProjectFactory::populate($sponsoredProject, $payload);

            return $sponsoredProject;
        });
    }

    /**
     * @inheritDoc
     */
    public function delete(int $project_id): void
    {
        $this->tx_service->transaction(function() use ($project_id){
            $sponsoredProject = $this->repository->getById($project_id);

            if(is_null($sponsoredProject) || !$sponsoredProject instanceof SponsoredProject)
                throw new EntityNotFoundException(sprintf("sponsored project %s not found.", $project_id));

            $this->repository->delete($sponsoredProject);
        });
    }

    /**
     * @inheritDoc
     */
    public function addProjectSponsorshipType(int $project_id, array $payload): ProjectSponsorshipType
    {
        return $this->tx_service->transaction(function() use ($project_id, $payload){
            $sponsoredProject = $this->repository->getById($project_id);

            if(is_null($sponsoredProject) || !$sponsoredProject instanceof SponsoredProject)
                throw new EntityNotFoundException(sprintf("sponsored project %s not found.", $project_id));

            $name = trim($payload['name']);
            if($sponsoredProject->getSponsorshipTypeByName($name)){
                throw new ValidationException(sprintf("sponsorship type %s already exists.", $name));
            }

            $projectSponsorshipType = ProjectSponsorshipTypeFactory::build($payload);
            $sponsoredProject->addSponsorshipType($projectSponsorshipType);

            return $projectSponsorshipType;
        });
    }

    /**
     * @inheritDoc
     */
    public function updateProjectSponsorshipType(int $project_id, int $sponsorship_id, array $payload): ProjectSponsorshipType
    {
        return $this->tx_service->transaction(function() use ($project_id, $sponsorship_id, $payload){
            $sponsoredProject = $this->repository->getById($project_id);

            if(is_null($sponsoredProject) || !$sponsoredProject instanceof SponsoredProject)
                throw new EntityNotFoundException(sprintf("sponsored project %s not found.", $project_id));

            if(isset($payload['name'])) {
                $name = trim($payload['name']);
                $formerProjectSponsorshipType = $sponsoredProject->getSponsorshipTypeByName($name);
                if ($formerProjectSponsorshipType && $formerProjectSponsorshipType->getId() !== $sponsorship_id) {
                    throw new ValidationException(sprintf("sponsorship type %s already exists.", $name));
                }
            }

            $projectSponsorshipType = $sponsoredProject->getSponsorshipTypeById($sponsorship_id);
            if(is_null($projectSponsorshipType) || !$projectSponsorshipType instanceof ProjectSponsorshipType)
                throw new EntityNotFoundException(sprintf("sponsorship type %s not found.", $project_id));

            $projectSponsorshipType = ProjectSponsorshipTypeFactory::populate($projectSponsorshipType, $payload);

            if (isset($payload['order']) && intval($payload['order']) != $projectSponsorshipType->getOrder()) {
                // request to update order
                $sponsoredProject->recalculateProjectSponsorshipTypeOrder($projectSponsorshipType, intval($payload['order']));
            }

            return $projectSponsorshipType;
        });
    }

    /**
     * @param int $project_id
     * @param int $sponsorship_id
     * @throws \Exception
     */
    public function deleteProjectSponsorshipType(int $project_id, int $sponsorship_id): void
    {
        $this->tx_service->transaction(function() use ($project_id, $sponsorship_id){
            $sponsoredProject = $this->repository->getById($project_id);

            if(is_null($sponsoredProject) || !$sponsoredProject instanceof SponsoredProject)
                throw new EntityNotFoundException(sprintf("sponsored project %s not found.", $project_id));

            $projectSponsorshipType = $sponsoredProject->getSponsorshipTypeById($sponsorship_id);
            if(is_null($projectSponsorshipType) || !$projectSponsorshipType instanceof ProjectSponsorshipType)
                throw new EntityNotFoundException(sprintf("sponsorship type %s not found.", $project_id));

            $sponsoredProject->removeSponsorshipType($projectSponsorshipType);
        });
    }

    /**
     * @param int $project_id
     * @param int $sponsorship_id
     * @param int $company_id
     * @param array $payload
     * @return SupportingCompany
     * @throws \Exception
     */
    public function addCompanyToProjectSponsorshipType(int $project_id, int $sponsorship_id, array $payload): SupportingCompany
    {
        return $this->tx_service->transaction(function() use ($project_id, $sponsorship_id, $payload){

            $sponsoredProject = $this->repository->getById($project_id);

            if(is_null($sponsoredProject) || !$sponsoredProject instanceof SponsoredProject)
                throw new EntityNotFoundException(sprintf("sponsored project %s not found.", $project_id));

            $projectSponsorshipType = $sponsoredProject->getSponsorshipTypeById($sponsorship_id);
            if(is_null($projectSponsorshipType) || !$projectSponsorshipType instanceof ProjectSponsorshipType)
                throw new EntityNotFoundException(sprintf("sponsorship type %s not found.", $project_id));


            $company = $this->company_repository->getById(intval($payload['company_id']));

            if(is_null($company) || !$company instanceof Company)
                throw new EntityNotFoundException(sprintf("company %s not found.", $payload['company_id']));

            $oldSupportingCompany = $projectSponsorshipType->getSupportingCompanyByCompany($company);
            if(!is_null($oldSupportingCompany)){
                throw new ValidationException(sprintf("Company %s already is a supporting company.", $payload['company_id']));
            }

            $supportingCompany = $projectSponsorshipType->addSupportingCompany($company);

            if (isset($payload['order']) && intval($payload['order']) != $supportingCompany->getOrder()) {
                // request to update order
                $projectSponsorshipType->recalculateSupportingCompanyOrder($supportingCompany, intval($payload['order']));
            }

            return $supportingCompany;
        });
    }

    /**
     * @inheritDoc
     */
    public function updateCompanyToProjectSponsorshipType(int $project_id, int $sponsorship_id, int $company_id, array $payload): SupportingCompany
    {
        return $this->tx_service->transaction(function() use ($project_id, $sponsorship_id,$company_id, $payload){

            $sponsoredProject = $this->repository->getById($project_id);

            if(is_null($sponsoredProject) || !$sponsoredProject instanceof SponsoredProject)
                throw new EntityNotFoundException(sprintf("sponsored project %s not found.", $project_id));

            $projectSponsorshipType = $sponsoredProject->getSponsorshipTypeById($sponsorship_id);
            if(is_null($projectSponsorshipType) || !$projectSponsorshipType instanceof ProjectSponsorshipType)
                throw new EntityNotFoundException(sprintf("sponsorship type %s not found.", $project_id));

            $company = $this->company_repository->getById(intval($payload['company_id']));

            if(is_null($company) || !$company instanceof Company)
                throw new EntityNotFoundException(sprintf("company %s not found.", $payload['company_id']));

            $supportingCompany = $projectSponsorshipType->getSupportingCompanyById($company_id);

            if(is_null($supportingCompany))
                throw new ValidationException(sprintf("Supporting company %s not found.", $company_id));

            if (isset($payload['order']) && intval($payload['order']) != $supportingCompany->getOrder()) {
                // request to update order
                $projectSponsorshipType->recalculateSupportingCompanyOrder($supportingCompany, intval($payload['order']));
            }

            return $supportingCompany;
        });
    }

    /**
     * @inheritDoc
     */
    public function removeCompanyToProjectSponsorshipType(int $project_id, int $sponsorship_id, int $company_id): void
    {
        $this->tx_service->transaction(function() use ($project_id, $sponsorship_id, $company_id){
            $sponsoredProject = $this->repository->getById($project_id);

            if(is_null($sponsoredProject) || !$sponsoredProject instanceof SponsoredProject)
                throw new EntityNotFoundException(sprintf("sponsored project %s not found.", $project_id));

            $projectSponsorshipType = $sponsoredProject->getSponsorshipTypeById($sponsorship_id);
            if(is_null($projectSponsorshipType) || !$projectSponsorshipType instanceof ProjectSponsorshipType)
                throw new EntityNotFoundException(sprintf("sponsorship type %s not found.", $project_id));

            $company = $this->company_repository->getById($company_id);

            if(is_null($company) || !$company instanceof Company)
                throw new EntityNotFoundException(sprintf("company %s not found.", $company_id));

            $projectSponsorshipType->removeSupportingCompany($company);
        });
    }

}