<?php namespace App\Services\Model;
/**
 * Copyright 2019 OpenStack Foundation
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

use App\Http\Utils\FileSizeUtil;
use App\Http\Utils\FileUploadInfo;
use App\Http\Utils\IFileUploader;
use App\Jobs\FileProcessingJob;
use App\Jobs\Utils\JobDispatcher;
use App\Models\Foundation\Summit\Factories\SummitBadgeFeatureTypeFactory;
use App\Models\Foundation\Summit\Repositories\ISummitBadgeFeatureTypeRepository;
use Illuminate\Support\Facades\Log;
use libs\utils\FileUtils;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\File;
use models\summit\Summit;
use models\summit\SummitBadgeFeatureType;
use models\utils\IEntity;
use Illuminate\Http\UploadedFile;
/**
 * Class SummitBadgeFeatureTypeService
 * @package App\Services\Model
 */
final class SummitBadgeFeatureTypeService extends AbstractService
implements ISummitBadgeFeatureTypeService
{
    use FileUtils;

    /**
     * @var IFileUploader
     */
    private $file_uploader;

    /**
     * @var ISummitBadgeFeatureTypeRepository
     */
    private $repository;

    /**
     * SummitBadgeFeatureTypeService constructor.
     * @param IFileUploader $file_uploader
     * @param ISummitBadgeFeatureTypeRepository $repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IFileUploader $file_uploader,
        ISummitBadgeFeatureTypeRepository $repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->file_uploader = $file_uploader;
        $this->repository = $repository;
    }

    /**
     * @param Summit $summit
     * @param array $data
     * @return SummitBadgeFeatureType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addBadgeFeatureType(Summit $summit, array $data): SummitBadgeFeatureType
    {
        // validated before the transaction so an invalid image means nothing is persisted
        $image_upload_info = $this->buildImageUploadInfo($data);

        $feature = $this->tx_service->transaction(function() use($summit, $data){
            $name = trim($data['name']);
            $former_feature = $summit->getFeatureTypeByName($name);
            if(!is_null($former_feature)){
                throw new ValidationException("feature type name already exists");
            }

            $feature = SummitBadgeFeatureTypeFactory::build($data);

            $summit->addFeatureType($feature);

            return $feature;

        });

        if (!is_null($image_upload_info))
            $this->dispatchImageJob($feature, $image_upload_info);

        return $feature;
    }

    /**
     * @param Summit $summit
     * @param int $feature_id
     * @param array $data
     * @return SummitBadgeFeatureType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateBadgeFeatureType(Summit $summit, int $feature_id, array $data): SummitBadgeFeatureType
    {
        // validated before the transaction so an invalid image means nothing is changed
        $image_upload_info = $this->buildImageUploadInfo($data);

        $feature = $this->tx_service->transaction(function() use($summit, $feature_id, $data){

            $feature = $summit->getFeatureTypeById($feature_id);
            if(is_null($feature))
                throw new EntityNotFoundException('feature not found');

            if(isset($data['name'])) {
                $name = trim($data['name']);
                $former_feature = $summit->getFeatureTypeByName($name);
                if (!is_null($former_feature) && $former_feature->getId() != $feature_id) {
                    throw new ValidationException("feature type name already exists");
                }
            }

            return SummitBadgeFeatureTypeFactory::populate($feature, $data);

        });

        if (!is_null($image_upload_info))
            $this->dispatchImageJob($feature, $image_upload_info);

        return $feature;
    }

    /**
     * @param Summit $summit
     * @param int $feature_id
     * @throws EntityNotFoundException
     */
    public function deleteBadgeFeatureType(Summit $summit, int $feature_id): void
    {
         $this->tx_service->transaction(function() use($summit, $feature_id){

            $feature = $summit->getFeatureTypeById($feature_id);
            if(is_null($feature))
                throw new EntityNotFoundException('feature not found');

            $summit->removeFeatureType($feature);

        });
    }

    /**
     * @param Summit $summit
     * @param int $feature_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return File
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addFeatureImage
    (
        Summit $summit,
        int $feature_id,
        UploadedFile $file,
        int $max_file_size = SummitBadgeFeatureType::ImageMaxFileSize
    ):File
    {
        return $this->tx_service->transaction(function () use ($summit, $feature_id, $file, $max_file_size) {

            $feature = $summit->getFeatureTypeById($feature_id);

            if (is_null($feature) || !$feature instanceof SummitBadgeFeatureType) {
                throw new EntityNotFoundException('feature type not found on summit!');
            }

            if (!in_array($file->extension(), SummitBadgeFeatureType::ImageAllowedExtensions)) {
                throw new ValidationException(sprintf("file does not has a valid extension (%s).", implode(', ', SummitBadgeFeatureType::ImageAllowedExtensions)));
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("file exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $file = $this->file_uploader->build($file, 'summit-event-images', true);
            $feature->setImage($file);

            return $file;
        });
    }

    /**
     * @param Summit $summit
     * @param int $feature_id
     * @throws EntityNotFoundException
     */
    public function removeFeatureImage(Summit $summit, int $feature_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $feature_id) {

            $feature = $summit->getFeatureTypeById($feature_id);

            if (is_null($feature) || !$feature instanceof SummitBadgeFeatureType) {
                throw new EntityNotFoundException('feature type not found on summit!');
            }

            $feature->clearImage();

        });
    }


    /**
     * @param FileInfoDTO $file_info_dto
     * @return IEntity
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function processFileForChildEntity(FileInfoDTO $file_info_dto): IEntity
    {
        Log::debug(sprintf("SummitBadgeFeatureTypeService::processFileForChildEntity file_info_dto %s", $file_info_dto));
        switch ($file_info_dto->owner_member_name) {
            case 'image':
                return self::processFileFromRemoteStorage($file_info_dto, function (int $feature_id, UploadedFile $file) {
                    // the DTO only carries the feature id; addFeatureImage needs its summit
                    $feature = $this->repository->getById($feature_id);
                    if (!$feature instanceof SummitBadgeFeatureType)
                        throw new EntityNotFoundException(sprintf("feature type %s not found.", $feature_id));
                    return $this->addFeatureImage($feature->getSummit(), $feature_id, $file);
                });
            default:
                Log::warning(sprintf("SummitBadgeFeatureTypeService::processFileForChildEntity unknown member name '%s'", $file_info_dto->owner_member_name));
                throw new \InvalidArgumentException(sprintf("Unknown owner_member_name '%s' for entity class '%s'.", $file_info_dto->owner_member_name, $file_info_dto->owner_entity_class));
        }
    }

    /**
     * @param array $data
     * @return FileUploadInfo|null null when no image payload was sent
     * @throws ValidationException
     */
    private function buildImageUploadInfo(array $data): ?FileUploadInfo
    {
        // a form with no file selected can send image as an empty string
        if (!isset($data['image']) || !is_array($data['image'])) return null;

        $file_upload_info = FileUploadInfo::buildFromPayload($data['image']);
        if (is_null($file_upload_info)) return null;

        if (!in_array($file_upload_info->getFileExt(), SummitBadgeFeatureType::ImageAllowedExtensions))
            throw new ValidationException(sprintf(
                "Image file does not have a valid extension (%s).",
                implode(',', SummitBadgeFeatureType::ImageAllowedExtensions)
            ));

        // the job's addFeatureImage enforces the same limit; checking here returns 412 instead of a silent job failure
        if ($file_upload_info->getSize(FileSizeUtil::B) > SummitBadgeFeatureType::ImageMaxFileSize)
            throw new ValidationException(sprintf(
                "Image file exceeds max file size (%s MB).",
                (SummitBadgeFeatureType::ImageMaxFileSize / 1024) / 1024
            ));

        return $file_upload_info;
    }

    private function dispatchImageJob(SummitBadgeFeatureType $feature, FileUploadInfo $file_upload_info): void
    {
        JobDispatcher::withDbFallback(job: new FileProcessingJob(new FileInfoDTO(
            owner_entity_id:    $feature->getId(),
            owner_entity_class: SummitBadgeFeatureType::class,
            owner_member_name:  'image',
            filepath:           $file_upload_info->getFilePath(),
            filename:           $file_upload_info->getFileName(),
            size:               $file_upload_info->getSize(),
            md5:                $file_upload_info->getMd5(),
            mime_type:          $file_upload_info->getMimeType(),
            source_bucket:      $file_upload_info->getSourceBucket()
        )));
    }
}