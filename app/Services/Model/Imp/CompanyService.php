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
use App\Http\Utils\IFileUploader;
use App\Models\Foundation\Main\Factories\CompanyFactory;
use App\Services\Model\AbstractService;
use App\Services\Model\ICompanyService;
use Illuminate\Http\UploadedFile;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Company;
use models\main\File;
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
     * @var IFileUploader
     */
    private $file_uploader;

    /**
     * CompanyService constructor.
     * @param ICompanyRepository $repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ICompanyRepository $repository,
        IFileUploader $file_uploader,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->repository = $repository;
        $this->file_uploader = $file_uploader;
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
               throw new ValidationException(sprintf("Company %s already exists.", $company_name));
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
        return $this->tx_service->transaction(function() use($company_id, $payload){
            $company = $this->repository->getById($company_id);
            if(is_null($company) || !$company instanceof Company)
                throw new EntityNotFoundException(sprintf("company %s not found.", $company_id));

            if(isset($payload['name'])){
                $former_company = $this->repository->getByName(trim($payload['name']));
                if(!is_null($former_company) && $company_id !== $former_company->getId()){
                    throw new ValidationException(sprintf("company %s already exists", $payload['name']));
                }
            }

            return CompanyFactory::populate($company, $payload);
        });
    }

    /**
     * @param int $company_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteCompany(int $company_id): void
    {
        $this->tx_service->transaction(function() use($company_id){
            $company = $this->repository->getById($company_id);
            if(is_null($company))
                throw new EntityNotFoundException(sprintf("company %s not found.", $company_id));

            $this->repository->delete($company);
        });
    }

    /**
     * @inheritDoc
     */
    public function addCompanyLogo(int $company_id, UploadedFile $file, $max_file_size = 10485760): File
    {
        return $this->tx_service->transaction(function () use ($company_id, $file, $max_file_size) {

            $allowed_extensions = ['png', 'jpg', 'jpeg', 'svg'];

            $company = $this->repository->getById($company_id);

            if (is_null($company) || !$company instanceof Company) {
                throw new EntityNotFoundException('company not found!');
            }

            if (!in_array($file->extension(), $allowed_extensions)) {
                throw new ValidationException("file does not has a valid extension ('png', 'jpg', 'jpeg', 'svg').");
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("file exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $logo = $this->file_uploader->build($file, sprintf('companies/%s/logos', $company->getId()), true);
            $company->setLogo($logo);
            return $logo;
        });
    }

    /**
     * @inheritDoc
     */
    public function deleteCompanyLogo(int $company_id): void
    {
        $this->tx_service->transaction(function () use ($company_id) {

            $company = $this->repository->getById($company_id);

            if (is_null($company) || !$company instanceof Company) {
                throw new EntityNotFoundException('company not found!');
            }

            $company->clearLogo();

        });
    }

    /**
     * @inheritDoc
     */
    public function addCompanyBigLogo(int $company_id, UploadedFile $file, $max_file_size = 10485760): File
    {
        return $this->tx_service->transaction(function () use ($company_id, $file, $max_file_size) {

            $allowed_extensions = ['png', 'jpg', 'jpeg', 'svg'];

            $company = $this->repository->getById($company_id);

            if (is_null($company) || !$company instanceof Company) {
                throw new EntityNotFoundException('company not found!');
            }

            if (!in_array($file->extension(), $allowed_extensions)) {
                throw new ValidationException("file does not has a valid extension ('png', 'jpg', 'jpeg', 'svg').");
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("file exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $logo = $this->file_uploader->build($file, sprintf('companies/%s/logos', $company->getId()), true);
            $company->setBigLogo($logo);
            return $logo;
        });
    }

    /**
     * @inheritDoc
     */
    public function deleteCompanyBigLogo(int $company_id): void
    {
        $this->tx_service->transaction(function () use ($company_id) {

            $company = $this->repository->getById($company_id);

            if (is_null($company) || !$company instanceof Company) {
                throw new EntityNotFoundException('company not found!');
            }

            $company->clearBigLogo();

        });
    }
}