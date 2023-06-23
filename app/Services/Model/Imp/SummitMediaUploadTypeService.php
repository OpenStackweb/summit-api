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
use App\Models\Foundation\Summit\Factories\SummitMediaUploadTypeFactory;
use App\Models\Foundation\Summit\Repositories\ISummitMediaFileTypeRepository;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitMediaUploadTypeService;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\summit\PresentationType;
use models\summit\Summit;
use models\summit\SummitMediaUploadType;
/**
 * Class SummitMediaUploadTypeService
 * @package App\Services\Model\Imp
 */
final class SummitMediaUploadTypeService extends AbstractService
implements ISummitMediaUploadTypeService
{


    /**
     * @var ISummitMediaFileTypeRepository
     */
    private $media_file_type_repository;

    /**
     * SummitMediaUploadTypeService constructor.
     * @param ISummitMediaFileTypeRepository $media_file_type_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        ISummitMediaFileTypeRepository $media_file_type_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->media_file_type_repository = $media_file_type_repository;
    }

    /**
     * @inheritDoc
     */
    public function add(Summit $summit, array $data): SummitMediaUploadType
    {
        return $this->tx_service->transaction(function() use($summit, $data){

            // name should be unique per summit
            if(isset($data['name'])) {
                $media_upload = $summit->getMediaUploadTypeByName($data['name']);
                if(!is_null($media_upload)){
                    throw new ValidationException(sprintf("Media upload name %s already exists at summit %s", $data['name'],  $summit->getId()));
                }
            }

            $type = $this->media_file_type_repository->find(intval($data['type_id']));
            if(is_null($type))
                throw new EntityNotFoundException("Media File Type not found.");

            $media_upload = SummitMediaUploadTypeFactory::build($data);

            if(!$media_upload->hasStorageSet()){
                throw new ValidationException("You must set a Public or Private Storage Type.");
            }

            $media_upload->setType($type);

            $summit->addMediaUploadType($media_upload);

            if(isset($data['presentation_types'])){
                foreach($data['presentation_types'] as $event_type_id){
                    $presentation_type = $summit->getEventType(intval($event_type_id));
                    if(is_null($presentation_type) || !$presentation_type instanceof PresentationType)
                        throw new EntityNotFoundException(sprintf("Presentation Type %s not found", $event_type_id));
                    $media_upload->addPresentationType($presentation_type);
                }
            }

            return $media_upload;
        });
    }

    /**
     * @inheritDoc
     */
    public function update(Summit $summit, int $media_upload_type_id, array $data): SummitMediaUploadType
    {
        return $this->tx_service->transaction(function() use($summit, $media_upload_type_id, $data){

            // name should be unique per summit
            if(isset($data['name'])) {
                $media_upload = $summit->getMediaUploadTypeByName($data['name']);
                if(!is_null($media_upload) && $media_upload->getId() != $media_upload_type_id){
                    throw new ValidationException(sprintf("Media upload name %s already exists at summit %s", $data['name'],  $summit->getId()));
                }
            }

            $media_upload = $summit->getMediaUploadTypeById($media_upload_type_id);
            if(is_null($media_upload))
                throw new EntityNotFoundException(sprintf("Media upload %s not found at summit %s", $media_upload_type_id, $summit->getId()));

            $media_upload = SummitMediaUploadTypeFactory::populate($media_upload, $data);

            if(!$media_upload->hasStorageSet()){
                throw new ValidationException("You must set a Public or Private Storage Type.");
            }

            if(isset($data['type_id'])) {
                $type = $this->media_file_type_repository->find(intval($data['type_id']));
                if (is_null($type))
                    throw new EntityNotFoundException("Media File Type not found.");
                $media_upload->setType($type);
            }

            if(isset($data['presentation_types'])){
                $media_upload->clearPresentationTypes();
                foreach($data['presentation_types'] as $event_type_id){
                    $presentation_type = $summit->getEventType(intval($event_type_id));
                    if(is_null($presentation_type) || !$presentation_type instanceof PresentationType)
                        throw new EntityNotFoundException(sprintf("Presentation Type %s not found", $event_type_id));
                    $media_upload->addPresentationType($presentation_type);
                }
            }

            return $media_upload;
        });
    }

    /**
     * @inheritDoc
     */
    public function delete(Summit $summit, int $media_upload_type_id): void
    {
        $this->tx_service->transaction(function() use($summit, $media_upload_type_id){
            $media_upload = $summit->getMediaUploadTypeById($media_upload_type_id);
            if(is_null($media_upload))
                throw new EntityNotFoundException(sprintf("Media upload %s not found at summit %s", $media_upload_type_id, $summit->getId()));

            $summit->removeMediaUploadType($media_upload);
        });
    }

    /**
     * @inheritDoc
     */
    public function addToPresentationType(Summit $summit, int $media_upload_type_id, int $presentation_type_id): SummitMediaUploadType
    {
        return $this->tx_service->transaction(function() use($summit, $media_upload_type_id, $presentation_type_id){
            $media_upload = $summit->getMediaUploadTypeById($media_upload_type_id);
            if(is_null($media_upload))
                throw new EntityNotFoundException(sprintf("Media upload %s not found at summit %s", $media_upload_type_id, $summit->getId()));

            $presentation_type = $summit->getEventType(intval($presentation_type_id));
            if(is_null($presentation_type) || !$presentation_type instanceof PresentationType)
                throw new EntityNotFoundException(sprintf("Presentation Type %s not found", $presentation_type_id));

            $media_upload->addPresentationType($presentation_type);

           return $media_upload;
        });
    }

    /**
     * @inheritDoc
     */
    public function deleteFromPresentationType(Summit $summit, int $media_upload_type_id, int $presentation_type_id): SummitMediaUploadType
    {
        return $this->tx_service->transaction(function() use($summit, $media_upload_type_id, $presentation_type_id){
            $media_upload = $summit->getMediaUploadTypeById($media_upload_type_id);
            if(is_null($media_upload))
                throw new EntityNotFoundException(sprintf("Media upload %s not found at summit %s", $media_upload_type_id, $summit->getId()));

            $presentation_type = $summit->getEventType(intval($presentation_type_id));
            if(is_null($presentation_type) || !$presentation_type instanceof PresentationType)
                throw new EntityNotFoundException(sprintf("Presentation Type %s not found", $presentation_type_id));

            $media_upload->removePresentationType($presentation_type);

            return $media_upload;
        });
    }

    /**
     * @param Summit $fromSummit
     * @param Summit $toSummit
     * @return Summit
     * @throws \Exception
     */
    public function cloneMediaUploadTypes(Summit $fromSummit, Summit $toSummit): Summit
    {
         return $this->tx_service->transaction(function() use($fromSummit, $toSummit){

            foreach($fromSummit->getMediaUploadTypes() as $mediaUploadType){
                if($toSummit->getMediaUploadTypeByName($mediaUploadType->getName())) continue;

                $newMediaUploadType = new SummitMediaUploadType();
                $newMediaUploadType->setName($mediaUploadType->getName());
                $newMediaUploadType->setDescription($mediaUploadType->getDescription());
                $newMediaUploadType->setType($mediaUploadType->getType());
                $newMediaUploadType->setMaxSize($mediaUploadType->getMaxSize());
                $newMediaUploadType->setPublicStorageType($mediaUploadType->getPublicStorageType());
                $newMediaUploadType->setPrivateStorageType($mediaUploadType->getPrivateStorageType());

                foreach ($mediaUploadType->getPresentationTypes() as $presentationType){
                    $newPresentationType = $toSummit->getEventTypeByType($presentationType->getType());
                    if(is_null($newPresentationType)){
                        $newPresentationType = new PresentationType();
                        $newPresentationType->setType($presentationType->getType());
                        $newPresentationType->setColor($presentationType->getColor());
                        $newPresentationType->setIsPrivate($presentationType->isPrivate());
                        $newPresentationType->setAllowsAttachment($presentationType->isAllowsAttachment());
                        $newPresentationType->setAreSpeakersMandatory($presentationType->isAreSpeakersMandatory());
                        $newPresentationType->setUseSpeakers($presentationType->isUseSpeakers());
                        $newPresentationType->setAreSponsorsMandatory($presentationType->isAreSponsorsMandatory());
                        $newPresentationType->setUseSponsors($presentationType->isUseSponsors());
                        $newPresentationType->setBlackoutTimes($presentationType->getBlackoutTimes());
                        $newPresentationType->setShouldBeAvailableOnCfp($presentationType->isShouldBeAvailableOnCfp());
                        $newPresentationType->setIsModeratorMandatory($presentationType->isModeratorMandatory());
                        $newPresentationType->setUseModerator($presentationType->isUseModerator());
                        $newPresentationType->setMaxModerators($presentationType->getMaxModerators());
                        $newPresentationType->setMaxSpeakers($presentationType->getMaxSpeakers());
                        $newPresentationType->setMinModerators($presentationType->getMinModerators());
                        $newPresentationType->setMinSpeakers($presentationType->getMinSpeakers());
                        $newPresentationType->setModeratorLabel($presentationType->getModeratorLabel());
                        $toSummit->addEventType($newPresentationType);
                    }
                    $newMediaUploadType->addPresentationType($newPresentationType);
                }

                $toSummit->addMediaUploadType($newMediaUploadType);
            }

            return $toSummit;
        });
    }
}