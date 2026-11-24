<?php namespace App\Models\Foundation\Summit\Repositories;
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


use models\summit\PresentationMediaUpload;
use models\utils\IBaseRepository;

interface IPresentationMediaUploadRepository extends IBaseRepository
{
    /**
     * Reads the row from the database even when the entity is already managed, so a row another
     * process deleted or changed is seen as it is now. Null when the row is gone.
     * @param int $id
     * @param bool $exclusive_lock lock the row FOR UPDATE; needs an open transaction
     * @return PresentationMediaUpload|null
     */
    public function getByIdFresh(int $id, bool $exclusive_lock = false): ?PresentationMediaUpload;

}