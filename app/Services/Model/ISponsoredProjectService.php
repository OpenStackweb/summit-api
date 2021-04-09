<?php namespace App\Services\Model;
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
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\ProjectSponsorshipType;
use models\main\SponsoredProject;
use models\main\SupportingCompany;
/**
 * Interface ISponsoredProjectService
 * @package App\Services\Model
 */
interface ISponsoredProjectService
{
    /**
     * @param array $payload
     * @return SponsoredProject
     * @throws ValidationException
     */
    public function add(array $payload):SponsoredProject;

    /**
     * @param int $project_id
     * @param array $payload
     * @return SponsoredProject
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function update(int $project_id, array $payload):SponsoredProject;

    /**
     * @param int $project_id
     * @throws EntityNotFoundException
     */
    public function delete(int $project_id):void;

    /**
     * @param int $project_id
     * @param array $payload
     * @return ProjectSponsorshipType
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addProjectSponsorshipType(int $project_id, array $payload):ProjectSponsorshipType;

    /**
     * @param int $project_id
     * @param int $sponsorship_id
     * @param array $payload
     * @return ProjectSponsorshipType
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateProjectSponsorshipType(int $project_id, int $sponsorship_id, array $payload):ProjectSponsorshipType;

    /**
     * @param int $project_id
     * @param int $sponsorship_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteProjectSponsorshipType(int $project_id, int $sponsorship_id):void;

    /**
     * @param int $project_id
     * @param int $sponsorship_id
     * @param array $payload
     * @return SupportingCompany
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function addCompanyToProjectSponsorshipType(int $project_id, int $sponsorship_id, array $payload):SupportingCompany;

    /**
     * @param int $project_id
     * @param int $sponsorship_id
     * @param int $company_id
     * @param array $payload
     * @return SupportingCompany
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function updateCompanyToProjectSponsorshipType(int $project_id, int $sponsorship_id, int $company_id, array $payload):SupportingCompany;

    /**
     * @param int $project_id
     * @param int $sponsorship_id
     * @param int $company_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function removeCompanyToProjectSponsorshipType(int $project_id, int $sponsorship_id, int $company_id):void;

}