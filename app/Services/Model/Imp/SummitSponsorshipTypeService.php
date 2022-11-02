<?php namespace App\Services\Model\Imp;
/*
 * Copyright 2022 OpenStack Foundation
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
use App\Models\Foundation\Main\IFileConstants;
use App\Models\Foundation\Summit\Factories\SummitSponsorshipTypeFactory;
use App\Models\Foundation\Summit\Repositories\ISponsorshipTypeRepository;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitSponsorshipTypeService;
use Illuminate\Http\UploadedFile;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\File;
use models\summit\SponsorshipType;
use models\summit\Summit;
use models\summit\SummitSponsorshipType;

/**
 * Class SummitSponsorshipTypeService
 * @package App\Services\Model\Imp
 */
final class SummitSponsorshipTypeService
extends AbstractService
implements ISummitSponsorshipTypeService
{

    /**
     * @var IFileUploader
     */
    private $file_uploader;

    /**
     * @var ISponsorshipTypeRepository
     */
    private $sponsorship_type_repository;

    public function __construct
    (
        ISponsorshipTypeRepository $sponsorship_type_repository,
        IFileUploader              $file_uploader,
        ITransactionService        $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->file_uploader = $file_uploader;
        $this->sponsorship_type_repository = $sponsorship_type_repository;
    }
    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitSponsorshipType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function add(Summit $summit, array $payload): SummitSponsorshipType
    {
       return $this->tx_service->transaction(function() use($summit, $payload){

           if(!isset($payload['type_id']))
               throw new ValidationException("type_id is mandatory.");

           $type = $this->sponsorship_type_repository->getById(intval($payload['type_id']));
           if(!$type instanceof SponsorshipType)
               throw new EntityNotFoundException("Type not found.");

           $former_sponsorship_type = $summit->getSummitSponsorshipTypeByType($type);
           if(!is_null($former_sponsorship_type)){
               throw new ValidationException
               (
                   sprintf
                   (
                       "There is already a Sponsorship of type %s for summit %s.",
                       $type->getId(),
                       $summit->getId()
                   )
               );
           }

           $sponsorship_type = SummitSponsorshipTypeFactory::build($payload);

           $sponsorship_type->setType($type);

           $summit->addSponsorshipType($sponsorship_type);

           return $sponsorship_type;
       });
    }

    /**
     * @param Summit $summit
     * @param int $sponsorship_id
     * @param array $payload
     * @return SummitSponsorshipType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function update(Summit $summit, int $sponsorship_id, array $payload): SummitSponsorshipType
    {
        return $this->tx_service->transaction(function() use($summit, $sponsorship_id, $payload){

            $sponsorship_type = $summit->getSummitSponsorshipTypeById($sponsorship_id);
            if(is_null($sponsorship_type))
                throw new EntityNotFoundException('Summit Sponsorship not found.');

            if(isset($payload['type_id'])) {
                $type = $this->sponsorship_type_repository->getById(intval($payload['type_id']));
                if (!$type instanceof SponsorshipType)
                    throw new EntityNotFoundException("Type not found.");

                $former_sponsorship_type = $summit->getSummitSponsorshipTypeByType($type);
                if(!is_null($former_sponsorship_type) && $former_sponsorship_type->getId() !== $sponsorship_type->getId()){
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "There is already a Sponsorship of type %s for summit %s.",
                            $type->getId(),
                            $summit->getId()
                        )
                    );
                }

                $sponsorship_type->setType($type);
            }

            $sponsorship_type = SummitSponsorshipTypeFactory::populate($sponsorship_type, $payload);

            if (isset($payload['order']) && intval($payload['order']) != $sponsorship_type->getOrder()) {
                // request to update order
                $summit->recalculateSponsorShipTypeOrder($sponsorship_type, intval($payload['order']));
            }

            return $sponsorship_type;
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsorship_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function delete(Summit $summit, int $sponsorship_id): void
    {
        // TODO: Implement delete() method.
    }

    /**
     * @param Summit $summit
     * @param int $type_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return File
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addBadgeImage(Summit $summit, int $sponsorship_id, UploadedFile $file, $max_file_size = IFileConstants::MaxImageSizeInBytes): File
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsorship_id, $file, $max_file_size) {
            $summit_sponsorship = $summit->getSummitSponsorshipTypeById($sponsorship_id);
            if (is_null($summit_sponsorship))
                throw new EntityNotFoundException("Summit Sponsorship Type not found.");

            if (!in_array($file->extension(), IFileConstants::ValidImageExtensions)) {
                throw new ValidationException(sprintf("File does not has a valid extension (%s).", implode(",", IFileConstants::ValidImageExtensions)));
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("File exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $photo = $this->file_uploader->build($file, sprintf('summits/%s/summit_sponsorship_types/%s', $summit->getId(), $summit_sponsorship->getId()), true);
            $summit_sponsorship->setBadgeImage($photo);

            return $photo;
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsorship_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteBadgeImage(Summit $summit, int $sponsorship_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $sponsorship_id) {

            $summit_sponsorship = $summit->getSummitSponsorshipTypeById($sponsorship_id);
            if (is_null($summit_sponsorship))
                throw new EntityNotFoundException("Summit Sponsorship Type not found.");

            $summit_sponsorship->ClearBadgeImage();
        });
    }
}