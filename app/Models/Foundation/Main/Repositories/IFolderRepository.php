<?php namespace models\main;
/**
 * Copyright 2017 OpenStack Foundation
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
use models\utils\IBaseRepository;
/**
 * Interface IFolderRepository
 * @package models\main
 */
interface IFolderRepository extends IBaseRepository
{
    /**
     * @param string $folder_name
     * @return File
     */
    public function getFolderByName($folder_name);

    /**
     * @param string $name
     * @return bool
     */
    public function existByName(string $name):bool;

    /**
     * @param string $file_name
     * @return File
     */
    public function getFolderByFileName($file_name);

    /**
     * @param string $folder_name
     * @param File $parent
     * @return File
     */
    public function getFolderByNameAndParent($folder_name, File $parent);

}