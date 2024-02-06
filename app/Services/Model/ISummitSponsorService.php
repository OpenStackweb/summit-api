<?php namespace services\model;
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

use App\Models\Foundation\Main\IFileConstants;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType;
use Illuminate\Http\UploadedFile;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\File;
use models\summit\Sponsor;
use models\summit\SponsorAd;
use models\summit\SponsorMaterial;
use models\summit\SponsorSocialNetwork;
use models\summit\Summit;
/**
 * Interface ISummitSponsorService
 * @package services\model
 */
interface ISummitSponsorService
{
    /**
     * @param Summit $summit
     * @param array $payload
     * @return Sponsor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSponsor(Summit $summit, array $payload):Sponsor;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param array $payload
     * @return Sponsor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateSponsor(Summit $summit, int $sponsor_id, array $payload):Sponsor;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteSponsor(Summit $summit, int $sponsor_idd):void;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $member_id
     * @return Sponsor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSponsorUser(Summit $summit, int $sponsor_id, int $member_id):Sponsor;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $member_id
     * @return Sponsor
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function removeSponsorUser(Summit $summit, int $sponsor_id, int $member_id):Sponsor;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return File
     */
    public function addSponsorSideImage(Summit $summit, int $sponsor_id,  UploadedFile $file,  $max_file_size =  IFileConstants::MaxImageSizeInBytes):File;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     */
    public function deleteSponsorSideImage(Summit $summit, int $sponsor_id):void;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return File
     */
    public function addSponsorHeaderImage(Summit $summit, int $sponsor_id,  UploadedFile $file,  $max_file_size = IFileConstants::MaxImageSizeInBytes):File;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     */
    public function deleteSponsorHeaderImage(Summit $summit, int $sponsor_id):void;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return File
     */
    public function addSponsorHeaderImageMobile(Summit $summit, int $sponsor_id,  UploadedFile $file,  $max_file_size = IFileConstants::MaxImageSizeInBytes):File;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     */
    public function deleteSponsorHeaderImageMobile(Summit $summit, int $sponsor_id):void;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @return File
     */
    public function addSponsorCarouselAdvertiseImage(Summit $summit, int $sponsor_id,  UploadedFile $file,  $max_file_size = IFileConstants::MaxImageSizeInBytes):File;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     */
    public function deleteSponsorCarouselAdvertiseImage(Summit $summit, int $sponsor_id):void;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param array $payload
     * @return SponsorAd
     */
    public function addSponsorAd(Summit $summit, int $sponsor_id, array $payload):SponsorAd;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $ad_id
     * @param $
     * @param array $payload
     * @return SponsorAd
     */
    public function updateSponsorAd(Summit $summit, int $sponsor_id, int $ad_id, array $payload):SponsorAd;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $ad_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteSponsorAd(Summit $summit, int $sponsor_id, int $ad_id):void;

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
    public function addSponsorAdImage(Summit $summit, int $sponsor_id, int $ad_id, UploadedFile $file, $max_file_size = IFileConstants::MaxImageSizeInBytes):File;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $ad_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteSponsorAdImage(Summit $summit, int $sponsor_id, int $ad_id):void;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param array $payload
     * @return SponsorMaterial
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSponsorMaterial(Summit $summit, int $sponsor_id, array $payload):SponsorMaterial;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $material_id
     * @param array $payload
     * @return SponsorMaterial
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateSponsorMaterial(Summit $summit, int $sponsor_id, int $material_id, array $payload):SponsorMaterial;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $material_id
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteSponsorMaterial(Summit $summit, int $sponsor_id, int $material_id):void;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param array $payload
     * @return SponsorSocialNetwork
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSponsorSocialNetwork(Summit $summit, int $sponsor_id, array $payload):SponsorSocialNetwork;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $social_network_id
     * @param array $payload
     * @return SponsorSocialNetwork
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateSponsorSocialNetwork(Summit $summit, int $sponsor_id, int $social_network_id, array $payload):SponsorSocialNetwork;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $social_network_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteSponsorSocialNetwork(Summit $summit, int $sponsor_id, int $social_network_id):void;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param array $payload
     * @return SummitSponsorExtraQuestionType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function addSponsorExtraQuestion(Summit $summit, int $sponsor_id, array $payload):SummitSponsorExtraQuestionType;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $extra_question_id
     * @param array $payload
     * @return SummitSponsorExtraQuestionType
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function updateSponsorExtraQuestion(Summit $summit, int $sponsor_id, int $extra_question_id, array $payload):SummitSponsorExtraQuestionType;

    /**
     * @param Summit $summit
     * @param int $sponsor_id
     * @param int $extra_question_id
     * @return void
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function deleteSponsorExtraQuestion(Summit $summit, int $sponsor_id, int $extra_question_id):void;
}