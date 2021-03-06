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
use Illuminate\Http\UploadedFile;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\Company;
use models\main\File;
/**
 * Interface ICompanyService
 * @package App\Services\Model
 */
interface ICompanyService
{
    /**
     * @param array $payload
     * @throws ValidationException
     * @return Company
     */
    public function addCompany(array $payload):Company;

    /**
     * @param int $company_id
     * @param array $payload
     * @throws ValidationException
     * @throws EntityNotFoundException
     * @return Company
     */
    public function updateCompany(int $company_id, array $payload):Company;

    /**
     * @param int $company_id
     * @throws ValidationException
     * @throws EntityNotFoundException
     */
    public function deleteCompany(int $company_id):void;

    /**
     * @param int $company_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @return File
     */
    public function addCompanyLogo(int $company_id,  UploadedFile $file,  $max_file_size = 10485760):File;

    /**
     * @throws EntityNotFoundException
     * @param int $company_id
     */
    public function deleteCompanyLogo(int $company_id):void;

    /**
     * @param int $company_id
     * @param UploadedFile $file
     * @param int $max_file_size
     * @throws EntityNotFoundException
     * @throws ValidationException
     * @return File
     */
    public function addCompanyBigLogo(int $company_id,  UploadedFile $file,  $max_file_size = 10485760):File;

    /**
     * @throws EntityNotFoundException
     * @param int $company_id
     */
    public function deleteCompanyBigLogo(int $company_id):void;
}