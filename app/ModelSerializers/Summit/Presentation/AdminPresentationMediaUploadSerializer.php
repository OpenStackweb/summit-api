<?php namespace ModelSerializers;
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

use App\Models\Utils\IStorageTypesConstants;
use App\Services\Filesystem\FileDownloadStrategyFactory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use models\summit\PresentationMediaUpload;
/**
 * Class AdminPresentationMediaUploadSerializer
 * @package ModelSerializers
 */
final class AdminPresentationMediaUploadSerializer extends PresentationMediaUploadSerializer
{
  /**
     * @param null $expand
     * @param array $fields
     * @param array $relations
     * @param array $params
     * @return array
     */
    public function serialize($expand = null, array $fields = array(), array $relations = array(), array $params = array() )
    {
        $values = parent::serialize($expand, $fields, $relations, $params);
        $mediaUpload  = $this->object;
        if(!$mediaUpload instanceof PresentationMediaUpload) return [];

        $mediaUploadType = $mediaUpload->getMediaUploadType();
        if(!is_null($mediaUploadType)) {
            try{
                $strategy = FileDownloadStrategyFactory::build($mediaUploadType->getPrivateStorageType());
                if (!is_null($strategy)) {

                    $values['private_url'] = $strategy->getUrl($mediaUpload->getRelativePath(IStorageTypesConstants::PrivateType));
                }
            }
            catch (\Exception $ex){
                Log::warning($ex);
            }
        }

        return $values;
    }
}