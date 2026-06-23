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

use App\Http\Utils\FileUploadInfo;
use App\Http\Utils\IFileUploader;
use App\Jobs\CompanyEventJob;
use App\Jobs\FileProcessingJob;
use App\Jobs\Utils\JobDispatcher;
use App\Models\Foundation\Main\Factories\CompanyFactory;
use App\Services\Model\AbstractService;
use App\Services\Model\FileInfoDTO;
use App\Services\Model\ICompanyService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use libs\utils\FileUtils;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Company;
use models\main\File;
use models\main\ICompanyRepository;
use models\utils\IEntity;

/**
 * Class CompanyService
 * @package App\Services\Model\Imp
 */
final class CompanyService
    extends AbstractService
    implements ICompanyService
{
    use FileUtils;

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

       $company = $this->tx_service->transaction(function() use($payload){
           $company_name = trim($payload['name']);
           $former_company = $this->repository->getByName($company_name);
           if(!is_null($former_company)){
               throw new ValidationException(sprintf("Company %s already exists.", $company_name));
           }
           $company = CompanyFactory::build($payload);
           $this->repository->add($company);
           return $company;
       });

        if (isset($payload['logo']) && is_array($payload['logo']))         $this->dispatchLogoJob($company, 'logo',     $payload['logo']);
        if (isset($payload['big_logo']) && is_array($payload['big_logo'])) $this->dispatchLogoJob($company, 'big_logo', $payload['big_logo']);

       return $company;
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
        $company = $this->tx_service->transaction(function() use($company_id, $payload){
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
        CompanyEventJob::dispatch($company, "UPDATE");
        return $company;
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
            CompanyEventJob::dispatch($company, "DELETE");
            $this->repository->delete($company);
        });
    }

    /**
     * @inheritDoc
     */
    public function addCompanyLogo(int $company_id, UploadedFile $file, $max_file_size = 10485760): File
    {
        return $this->tx_service->transaction(function () use ($company_id, $file, $max_file_size) {

            $company = $this->repository->getById($company_id);

            if (is_null($company) || !$company instanceof Company) {
                throw new EntityNotFoundException('company not found!');
            }

            if (!in_array($file->extension(), Company::LogoAllowedExtensions)) {
                throw new ValidationException(sprintf("file does not has a valid extension (%s).", implode(', ', Company::LogoAllowedExtensions)));
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

            $company = $this->repository->getById($company_id);

            if (is_null($company) || !$company instanceof Company) {
                throw new EntityNotFoundException('company not found!');
            }

            if (!in_array($file->extension(), Company::LogoAllowedExtensions)) {
                throw new ValidationException(sprintf("file does not has a valid extension (%s).", implode(', ', Company::LogoAllowedExtensions)));
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

    /**
     * @param FileInfoDTO $file_info_dto
     * @return IEntity
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function processFileForChildEntity(FileInfoDTO $file_info_dto): IEntity {
        Log::debug(sprintf("CompanyService::processFileForChildEntity file_info_dto %s", $file_info_dto));
        switch ($file_info_dto->owner_member_name) {
            case 'big_logo':
                return $this->processLogoFile($file_info_dto, [$this, 'addCompanyBigLogo']);
            case 'logo':
                return $this->processLogoFile($file_info_dto, [$this, 'addCompanyLogo']);
            default:
                Log::warning(sprintf("CompanyService::processFileForChildEntity unknown member name '%s'", $file_info_dto->owner_member_name));
                throw new \InvalidArgumentException(sprintf("Unknown owner_member_name '%s' for entity class '%s'.", $file_info_dto->owner_member_name, $file_info_dto->owner_entity_class));
        }
    }

    /**
     * Downloads a file from remote storage to a local temp path, verifies its MD5 (when provided),
     * invokes $uploader to persist it, then cleans up. On failure the remote file is preserved
     * so queue retries can re-download it. Cleanup errors after a successful upload are logged
     * but not re-thrown - upload success determines job success, not storage housekeeping.
     */
    private function processLogoFile(FileInfoDTO $file_info_dto, callable $uploader): IEntity
    {
        $localPath = self::getFileFromRemoteStorageOnTempStorage(
            $file_info_dto->filename,
            $file_info_dto->filepath
        );
        $succeeded = false;
        try {
            if (!is_null($file_info_dto->md5)) {
                $localHash = md5_file($localPath);
                if ($localHash === false)
                    throw new ValidationException("File integrity check failed: unable to read local temp file.");
                if ($localHash !== strtolower($file_info_dto->md5))
                    throw new ValidationException("File integrity check failed: MD5 mismatch.");
            }
            $file = new UploadedFile(
                path: $localPath,
                originalName: $file_info_dto->filename,
                mimeType: $file_info_dto->mime_type,
                error: null,
                test: true,
            );
            $logo = $uploader($file_info_dto->owner_entity_id, $file);
            $succeeded = true;
        } finally {
            if ($succeeded) {
                try {
                    self::cleanLocalAndRemoteFile($localPath, $file_info_dto->filepath);
                } catch (\Throwable $e) {
                    // Upload succeeded; cleanup failure is non-fatal. Log and continue so the
                    // job does not retry and create duplicate File records.
                    Log::warning(sprintf(
                        "CompanyService::processLogoFile cleanup failed after successful upload (filepath=%s): %s",
                        $file_info_dto->filepath,
                        $e->getMessage()
                    ));
                }
            } else {
                self::cleanLocalFile($localPath);
            }
        }
        return $logo;
    }

    private function dispatchLogoJob(Company $company, string $memberName, array $payload): void
    {
        $file_upload_info = FileUploadInfo::buildFromPayload($payload);
        if (is_null($file_upload_info)) return;

        if (!in_array($file_upload_info->getFileExt(), Company::LogoAllowedExtensions))
            throw new ValidationException(sprintf(
                "%s file does not have a valid extension (%s).",
                ucwords(str_replace('_', ' ', $memberName)),
                implode(',', Company::LogoAllowedExtensions)
            ));

        JobDispatcher::withDbFallback(job: new FileProcessingJob(new FileInfoDTO(
            owner_entity_id:    $company->getId(),
            owner_entity_class: Company::class,
            owner_member_name:  $memberName,
            filepath:           $file_upload_info->getFilePath(),
            filename:           $file_upload_info->getFileName(),
            size:               $file_upload_info->getSize(),
            md5:                $file_upload_info->getMd5(),
            mime_type:          $file_upload_info->getMimeType(),
            source_bucket:      $file_upload_info->getSourceBucket()
        )));
    }
}
