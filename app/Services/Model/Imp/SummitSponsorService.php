<?php namespace App\Services;
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

use App\Http\Utils\IFileUploader;
use App\Models\Foundation\ExtraQuestions\ExtraQuestionTypeValue;
use App\Models\Foundation\Main\IFileConstants;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType;
use App\Models\Foundation\Summit\Factories\SponsorAdFactory;
use App\Models\Foundation\Summit\Factories\SponsorExtraQuestionFactory;
use App\Models\Foundation\Summit\Factories\SponsorFactory;
use App\Models\Foundation\Summit\Factories\SponsorMaterialFactory;
use App\Models\Foundation\Summit\Factories\SponsorSocialNetworkFactory;
use App\Models\Foundation\Summit\Repositories\ISponsorExtraQuestionTypeRepository;
use App\Services\Model\Imp\ExtraQuestionTypeService;
use Illuminate\Http\UploadedFile;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Company;
use models\main\File;
use models\main\ICompanyRepository;
use models\main\IMemberRepository;
use models\main\Member;
use models\summit\Sponsor;
use models\summit\SponsorAd;
use models\summit\SponsorMaterial;
use models\summit\SponsorSocialNetwork;
use models\summit\Summit;
use services\model\ISummitSponsorService;

/**
 * Class SummitSponsorService
 * @package App\Services\Model
 */
final class SummitSponsorService
    extends ExtraQuestionTypeService
    implements ISummitSponsorService
{
    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ICompanyRepository
     */
    private $company_repository;

    /**
     * @var IFileUploader
     */
    private $file_uploader;

    /**
     * @param IMemberRepository $member_repository
     * @param ICompanyRepository $company_repository
     * @param ISponsorExtraQuestionTypeRepository $repository
     * @param IFileUploader $file_uploader
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IMemberRepository          $member_repository,
        ICompanyRepository         $company_repository,
        ISponsorExtraQuestionTypeRepository $repository,
        IFileUploader              $file_uploader,
        ITransactionService        $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->member_repository = $member_repository;
        $this->company_repository = $company_repository;
        $this->file_uploader = $file_uploader;
        $this->repository = $repository;
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return Sponsor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSponsor(Summit $summit, array $payload): Sponsor
    {
        return $this->tx_service->transaction(function () use ($summit, $payload) {
            $company_id = intval($payload['company_id']);
            $sponsorship_id = intval($payload['sponsorship_id']);
            $featured_event_id = isset($payload['featured_event_id']) ? intval($payload['featured_event_id']) : 0;

            $company = $this->company_repository->getById($company_id);

            if (!$company instanceof Company)
                throw new EntityNotFoundException("Company not found.");

            $sponsorship_type = $summit->getSummitSponsorshipTypeById($sponsorship_id);
            if (is_null($sponsorship_type))
                throw new EntityNotFoundException("Sponsorship type not found.");

            $former_sponsor = $summit->getSummitSponsorByCompany($company);
            if (!is_null($former_sponsor)) {
                throw new ValidationException("Company already is sponsor on summit.");
            }

            $payload['company'] = $company;
            $payload['sponsorship'] = $sponsorship_type;

            if($featured_event_id && $featured_event_id > 0){

                $featured_event = $summit->getEvent($featured_event_id);
                if(is_null($featured_event))
                    throw new EntityNotFoundException("Featured Event not found.");

                $payload['featured_event'] = $featured_event;
            }

            $sponsor = SponsorFactory::build($payload);

            $summit->addSummitSponsor($sponsor);

            return $sponsor;
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param array $payload
     * @return Sponsor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateSponsor(Summit $summit, int $sponsor_id, array $payload): Sponsor
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $payload) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");
            $company = null;
            $sponsorship_type = null;

            if (isset($payload['company_id'])) {
                $company_id = intval($payload['company_id']);
                $company = $this->company_repository->getById($company_id);
                if (!$company instanceof Company)
                    throw new EntityNotFoundException("Company not found.");
            }

            if (isset($payload['sponsorship_id'])) {
                $sponsorship_id = intval($payload['sponsorship_id']);
                $sponsorship_type = $summit->getSummitSponsorshipTypeById($sponsorship_id);
                if (is_null($sponsorship_type))
                    throw new EntityNotFoundException("Sponsorship type not found.");
            }

            if (!is_null($company)) {
                $former_sponsor = $summit->getSummitSponsorByCompany($company);
                if (!is_null($former_sponsor) && $former_sponsor->getId() != $sponsor_id) {
                    throw new ValidationException("Company already is sponsor on summit.");
                }
            }

            if(isset($payload['featured_event_id'])){

                $summit_sponsor->clearFeaturedEvent();
                $featured_event_id = intval($payload['featured_event_id']);
                if($featured_event_id > 0 ) {

                    $featured_event = $summit->getEvent($featured_event_id);
                    if (is_null($featured_event))
                        throw new EntityNotFoundException("Featured Event not found.");

                    $payload['featured_event'] = $featured_event;
                }
            }

            if (!is_null($company))
                $payload['company'] = $company;

            if (!is_null($sponsorship_type))
                $payload['sponsorship'] = $sponsorship_type;

            $sponsor = SponsorFactory::populate($summit_sponsor, $payload);

            if (isset($payload['order']) && intval($payload['order']) != $sponsor->getOrder()) {
                // request to update order
                $summit->recalculateSummitSponsorOrder($sponsor, $payload['order']);
            }

            return $sponsor;
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteSponsor(Summit $summit, int $sponsor_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $sponsor_id) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $summit->removeSummitSponsor($summit_sponsor);
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $member_id
     * @return Sponsor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSponsorUser(Summit $summit, int $sponsor_id, int $member_id): Sponsor
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $member_id) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);

            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $member = $this->member_repository->getById($member_id);
            $current_summit_begin_date = $summit->getBeginDate();
            $current_summit_end_date = $summit->getEndDate();

            if (is_null($member) || !$member instanceof Member)
                throw new EntityNotFoundException("Member not found.");

            foreach ($member->getSponsorMemberships() as $former_sponsor) {

                $former_summit = $former_sponsor->getSummit();
                $former_summit_begin_date = $former_summit->getBeginDate();
                $former_summit_end_date = $former_summit->getEndDate();

                // check that current summit does not intersect with a former one
                // due a member could be on 2 diff places at same time ...
                // (StartA <= EndB)  and  (EndA >= StartB)

                if ($current_summit_begin_date <= $former_summit_end_date && $current_summit_end_date >= $former_summit_begin_date) {
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "You can not add member %s as sponsor user on summit %s bc its already sponsor user on another concurrent summit (%s).",
                            $member_id,
                            $summit->getId(),
                            $former_summit->getId()
                        )
                    );
                }
            }

            $summit_sponsor->addUser($member);

            return $summit_sponsor;

        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $member_id
     * @return Sponsor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removeSponsorUser(Summit $summit, int $sponsor_id, int $member_id): Sponsor
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $member_id) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $member = $this->member_repository->getById($member_id);

            if (!$member instanceof Member)
                throw new EntityNotFoundException("Member not found.");

            $summit_sponsor->removeUser($member);

            return $summit_sponsor;

        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return File
     */
    public function addSponsorSideImage(Summit $summit, int $sponsor_id, UploadedFile $file, $max_file_size = IFileConstants::MaxImageSizeInBytes): File
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $file, $max_file_size) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            if (!in_array($file->extension(), IFileConstants::ValidImageExtensions)) {
                throw new ValidationException(sprintf("File does not has a valid extension (%s).", implode(",", IFileConstants::ValidImageExtensions)));
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("File exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $photo = $this->file_uploader->build($file, sprintf('summits/%s/sponsors/%s', $summit->getId(), $summit_sponsor->getId()), true);
            $summit_sponsor->setSideImage($photo);

            return $photo;
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     */
    public function deleteSponsorSideImage(Summit $summit, int $sponsor_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $sponsor_id) {

            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $summit_sponsor->clearSideImage();
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return File
     */
    public function addSponsorHeaderImage(Summit $summit, int $sponsor_id, UploadedFile $file, $max_file_size = IFileConstants::MaxImageSizeInBytes): File
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $file, $max_file_size) {

            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            if (!in_array($file->extension(), IFileConstants::ValidImageExtensions)) {
                throw new ValidationException(sprintf("File does not has a valid extension (%s).", implode(",", IFileConstants::ValidImageExtensions)));
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("File exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $photo = $this->file_uploader->build($file, sprintf('summits/%s/sponsors/%s', $summit->getId(), $summit_sponsor->getId()), true);
            $summit_sponsor->setHeaderImage($photo);

            return $photo;
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     */
    public function deleteSponsorHeaderImage(Summit $summit, int $sponsor_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $sponsor_id) {

            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $summit_sponsor->clearHeaderImage();
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return File
     */
    public function addSponsorHeaderImageMobile(Summit $summit, int $sponsor_id, UploadedFile $file, $max_file_size = IFileConstants::MaxImageSizeInBytes): File
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $file, $max_file_size) {

            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            if (!in_array($file->extension(), IFileConstants::ValidImageExtensions)) {
                throw new ValidationException(sprintf("File does not has a valid extension (%s).", implode(",", IFileConstants::ValidImageExtensions)));
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("File exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $photo = $this->file_uploader->build($file, sprintf('summits/%s/sponsors/%s', $summit->getId(), $summit_sponsor->getId()), true);
            $summit_sponsor->setHeaderImageMobile($photo);

            return $photo;
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     */
    public function deleteSponsorHeaderImageMobile(Summit $summit, int $sponsor_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $sponsor_id) {

            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $summit_sponsor->clearHeaderImageMobile();
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return File
     */
    public function addSponsorCarouselAdvertiseImage(Summit $summit, int $sponsor_id, UploadedFile $file, $max_file_size = IFileConstants::MaxImageSizeInBytes): File
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $file, $max_file_size) {

            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            if (!in_array($file->extension(), IFileConstants::ValidImageExtensions)) {
                throw new ValidationException(sprintf("File does not has a valid extension (%s).", implode(",", IFileConstants::ValidImageExtensions)));
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("File exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $photo = $this->file_uploader->build($file, sprintf('summits/%s/sponsors/%s', $summit->getId(), $summit_sponsor->getId()), true);
            $summit_sponsor->setCarouselAdvertiseImage($photo);

            return $photo;
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     */
    public function deleteSponsorCarouselAdvertiseImage(Summit $summit, int $sponsor_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $sponsor_id) {

            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $summit_sponsor->clearCarouselAdvertiseImage();
        });
    }


    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param array $payload
     * @return SponsorAd
     */
    public function addSponsorAd(Summit $summit, int $sponsor_id, array $payload): SponsorAd
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $payload) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $ad = SponsorAdFactory::build($payload);

            $summit_sponsor->addAd($ad);

            return $ad;
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $ad_id
     * @param $
     * @param array $payload
     * @return SponsorAd
     */
    public function updateSponsorAd(Summit $summit, int $sponsor_id, int $ad_id, array $payload): SponsorAd
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $ad_id, $payload) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $ad = $summit_sponsor->getAdById($ad_id);
            if (is_null($ad))
                throw new EntityNotFoundException("Sponsor Ad not found.");

            $ad = SponsorAdFactory::populate($ad, $payload);

            if (isset($payload['order']) && intval($payload['order']) != $ad->getOrder()) {
                // request to update order
                $summit_sponsor->recalculateAdOrder($ad, intval($payload['order']));
            }

            return $ad;
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $ad_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteSponsorAd(Summit $summit, int $sponsor_id, int $ad_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $sponsor_id, $ad_id) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $ad = $summit_sponsor->getAdById($ad_id);
            if (is_null($ad))
                throw new EntityNotFoundException("Sponsor Ad not found.");

            $summit_sponsor->removeAd($ad);
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $ad_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return File
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSponsorAdImage(Summit $summit, int $sponsor_id, int $ad_id, UploadedFile $file, $max_file_size = IFileConstants::MaxImageSizeInBytes): File
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $ad_id, $file, $max_file_size) {

            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $ad = $summit_sponsor->getAdById($ad_id);
            if (is_null($ad))
                throw new EntityNotFoundException("Sponsor Ad not found.");

            if (!in_array($file->extension(), IFileConstants::ValidImageExtensions)) {
                throw new ValidationException(sprintf("File does not has a valid extension (%s).", implode(",", IFileConstants::ValidImageExtensions)));
            }

            if ($file->getSize() > $max_file_size) {
                throw new ValidationException(sprintf("File exceeds max_file_size (%s MB).", ($max_file_size / 1024) / 1024));
            }

            $photo = $this->file_uploader->build($file, sprintf('summits/%s/sponsors/%s/ads/%s', $summit->getId(), $summit_sponsor->getId(), $ad->getId()), true);
            $ad->setImage($photo);
            return $photo;
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $ad_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteSponsorAdImage(Summit $summit, int $sponsor_id, int $ad_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $sponsor_id, $ad_id) {

            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $ad = $summit_sponsor->getAdById($ad_id);
            if (is_null($ad))
                throw new EntityNotFoundException("Sponsor Ad not found.");

            $ad->clearImage();
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param array $payload
     * @return SponsorMaterial
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSponsorMaterial(Summit $summit, int $sponsor_id, array $payload): SponsorMaterial
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $payload) {

            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $former_material = $summit_sponsor->getMaterialByName(trim($payload['name']));

            if(!is_null($former_material))
                throw new ValidationException(sprintf("Material name %s already exists on sponsor %s.", $former_material->getName(), $summit_sponsor->getId()));

            $material = SponsorMaterialFactory::build($payload);

            $summit_sponsor->addMaterial($material);

            return $material;
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $material_id
     * @param array $payload
     * @return SponsorMaterial
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateSponsorMaterial(Summit $summit, int $sponsor_id, int $material_id, array $payload): SponsorMaterial
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $material_id, $payload) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $material = $summit_sponsor->getMaterialById($material_id);
            if (is_null($material))
                throw new EntityNotFoundException("Sponsor Material not found.");

            if(isset($payload['name'])) {
                $former_material = $summit_sponsor->getMaterialByName(trim($payload['name']));

                if (!is_null($former_material) && $material->getId() !== $former_material->getId())
                    throw new ValidationException(sprintf("Material name %s already exists on sponsor %s.", $former_material->getName(), $summit_sponsor->getId()));

            }

            $material = SponsorMaterialFactory::populate($material, $payload);

            if (isset($payload['order']) && intval($payload['order']) != $material->getOrder()) {
                // request to update order
                $summit_sponsor->recalculateMaterialOrder($material, intval($payload['order']));
            }

            return $material;
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $material_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteSponsorMaterial(Summit $summit, int $sponsor_id, int $material_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $sponsor_id, $material_id) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $material = $summit_sponsor->getMaterialById($material_id);
            if (is_null($material))
                throw new EntityNotFoundException("Sponsor Material not found.");

            $summit_sponsor->removeMaterial($material);
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param array $payload
     * @return SponsorSocialNetwork
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSponsorSocialNetwork(Summit $summit, int $sponsor_id, array $payload): SponsorSocialNetwork
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $payload) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $social_network = SponsorSocialNetworkFactory::build($payload);

            $summit_sponsor->addSocialNetwork($social_network);

            return $social_network;
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $social_network_id
     * @param array $payload
     * @return SponsorSocialNetwork
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateSponsorSocialNetwork(Summit $summit, int $sponsor_id, int $social_network_id, array $payload): SponsorSocialNetwork
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $social_network_id, $payload) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $social_network = $summit_sponsor->getSocialNetworkById($social_network_id);

            if(!$social_network instanceof SponsorSocialNetwork)
                throw new EntityNotFoundException("Sponsor Social network not found.");

            $social_network = SponsorSocialNetworkFactory::populate($social_network, $payload);

            return $social_network;
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $social_network_id
     * @return SponsorSocialNetwork
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteSponsorSocialNetwork(Summit $summit, int $sponsor_id, int $social_network_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $sponsor_id, $social_network_id) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $social_network = $summit_sponsor->getSocialNetworkById($social_network_id);

            if(!$social_network instanceof SponsorSocialNetwork)
                throw new EntityNotFoundException("Sponsor Social network not found.");

            $summit_sponsor->removeSocialNetwork($social_network);
        });
    }

    /**
     * @inheritDoc
     */
    public function addSponsorExtraQuestion(Summit $summit, int $sponsor_id, array $payload): SummitSponsorExtraQuestionType
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $payload) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $extra_question = SponsorExtraQuestionFactory::build($payload);

            $summit_sponsor->addExtraQuestion($extra_question);

            return $extra_question;
        });
    }

    /**
     * @inheritDoc
     */
    public function updateSponsorExtraQuestion(Summit $summit, int $sponsor_id, int $extra_question_id, array $payload): SummitSponsorExtraQuestionType
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $extra_question_id, $payload) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $extra_question = $summit_sponsor->getExtraQuestionById($extra_question_id);

            if(!$extra_question instanceof SummitSponsorExtraQuestionType)
                throw new EntityNotFoundException("Sponsor extra question not found.");

            if (isset($payload['order']) && intval($payload['order']) != $extra_question->getOrder()) {
                // request to update order
                $summit_sponsor->recalculateQuestionOrder($extra_question, intval($payload['order']));
            }

            return SponsorExtraQuestionFactory::populate($extra_question, $payload);
        });
    }

    /**
     * @inheritDoc
     */
    public function deleteSponsorExtraQuestion(Summit $summit, int $sponsor_id, int $extra_question_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $sponsor_id, $extra_question_id) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $extra_question = $summit_sponsor->getExtraQuestionById($extra_question_id);

            if(!$extra_question instanceof SummitSponsorExtraQuestionType)
                throw new EntityNotFoundException("Sponsor extra question not found.");

            $summit_sponsor->removeExtraQuestion($extra_question);
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $question_id
     * @param array $payload
     * @return ExtraQuestionTypeValue
     * @throws \Exception
     */
    public function addExtraQuestionValue(Summit $summit, int $sponsor_id, int $question_id, array $payload): ExtraQuestionTypeValue
    {

        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $question_id, $payload) {

            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $extra_question = $summit_sponsor->getExtraQuestionById($question_id);

            if(!$extra_question instanceof SummitSponsorExtraQuestionType)
                throw new EntityNotFoundException("Sponsor extra question not found.");

            return parent::_addExtraQuestionValue($extra_question, $payload);
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $question_id
     * @param int $value_id
     * @param array $payload
     * @return ExtraQuestionTypeValue
     * @throws \Exception
     */
    public function updateExtraQuestionValue(Summit $summit, int $sponsor_id, int $question_id, int $value_id, array $payload): ExtraQuestionTypeValue
    {
        return $this->tx_service->transaction(function () use ($summit, $sponsor_id, $question_id, $value_id, $payload) {

            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $extra_question = $summit_sponsor->getExtraQuestionById($question_id);

            if(!$extra_question instanceof SummitSponsorExtraQuestionType)
                throw new EntityNotFoundException("Sponsor extra question not found.");

            return parent::_updateExtraQuestionValue($extra_question, $value_id,  $payload);
        });
    }

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $question_id
     * @param int $value_id
     * @return void
     * @throws \Exception
     */
    public function deleteExtraQuestionValue(Summit $summit, int $sponsor_id, int $question_id, int $value_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $sponsor_id, $question_id, $value_id) {
            $summit_sponsor = $summit->getSummitSponsorById($sponsor_id);
            if (is_null($summit_sponsor))
                throw new EntityNotFoundException("Sponsor not found.");

            $extra_question = $summit_sponsor->getExtraQuestionById($question_id);

            if(!$extra_question instanceof SummitSponsorExtraQuestionType)
                throw new EntityNotFoundException("Sponsor extra question not found.");


            parent::_deleteExtraQuestionValue($extra_question, $value_id);
        });
    }
}