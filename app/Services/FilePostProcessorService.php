<?php namespace App\Services;
/**
 * Copyright 2026 OpenStack Foundation
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

use App\Services\Model\FileInfoDTO;
use App\Services\Model\ICompanyService;
use App\Services\Model\IFilePostProcessorForChildEntity;
use App\Services\Model\IFilePostProcessorService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use models\main\Company;
use models\utils\IEntity;

final class FilePostProcessorService implements IFilePostProcessorService
{

    /**
     * @param string $className
     * @return IFilePostProcessorForChildEntity|null
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    private function locateService(string $className): ?IFilePostProcessorForChildEntity{
        switch($className){
            case Company::class:
                return App::make(ICompanyService::class);
        }
        return null;
    }

    public function postProcessFileFromFileApi(FileInfoDTO $file_info_dto): IEntity
    {
       Log::debug(sprintf("FilePostProcessorService::postProcessFileFromFileApi entity_class=%s member=%s", $file_info_dto->owner_entity_class, $file_info_dto->owner_member_name));
       $service = $this->locateService($file_info_dto->owner_entity_class);
       if(is_null($service)){
           Log::warning(sprintf("FilePostProcessorService: no handler registered for entity class '%s'", $file_info_dto->owner_entity_class));
           throw new \InvalidArgumentException(sprintf("No file post-processor registered for entity class '%s'.", $file_info_dto->owner_entity_class));
       }
       return $service->processFileForChildEntity($file_info_dto);
    }
}
