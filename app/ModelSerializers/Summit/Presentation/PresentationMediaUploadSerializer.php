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
use App\Services\Filesystem\FileDownloadStrategyFactory;
use Illuminate\Support\Facades\Log;
use Libs\ModelSerializers\AbstractSerializer;
use models\summit\PresentationMediaUpload;
/**
 * Class PresentationMediaUploadSerializer
 * @package ModelSerializers
 */
class PresentationMediaUploadSerializer extends PresentationMaterialSerializer {
  protected static $array_mappings = [
    "Filename" => "filename:json_text",
    "MediaUploadTypeId" => "media_upload_type_id:json_int",
  ];

  protected static $allowed_fields = ["filename", "media_upload_type_id", "public_url"];

  /**
   * @param null $expand
   * @param array $fields
   * @param array $relations
   * @param array $params
   * @return array
   */
  public function serialize(
    $expand = null,
    array $fields = [],
    array $relations = [],
    array $params = [],
  ) {
    $values = parent::serialize($expand, $fields, $relations, $params);
    $mediaUpload = $this->object;
    if (!$mediaUpload instanceof PresentationMediaUpload) {
      return [];
    }
    // these values are calculated
    unset($values["name"]);
    unset($values["description"]);
    unset($values["featured"]);

    $mediaUploadType = $mediaUpload->getMediaUploadType();
    if (!is_null($mediaUploadType)) {
      try {
        if (in_array("name", $fields)) {
          $values["name"] = $mediaUploadType->getName();
        }
        if (in_array("description", $fields)) {
          $values["description"] = $mediaUploadType->getDescription();
        }
        if (in_array("public_url", $fields)) {
          $strategy = FileDownloadStrategyFactory::build($mediaUploadType->getPublicStorageType());
          if (!is_null($strategy)) {
            $values["public_url"] = $strategy->getUrl(
              $mediaUpload->getRelativePath(),
              $mediaUploadType->isUseTemporaryLinksOnPublicStorage(),
              $mediaUploadType->getTemporaryLinksPublicStorageTtl() * 60, // convert to seconds
            );
          }
        }
      } catch (\Exception $ex) {
        Log::warning($ex);
      }
    }

    if (!empty($expand)) {
      foreach (explode(",", $expand) as $relation) {
        $relation = trim($relation);
        switch ($relation) {
          case "media_upload_type":
            unset($values["media_upload_type_id"]);
            $type = $mediaUpload->getMediaUploadType();
            if (!is_null($type)) {
              $values["media_upload_type"] = SerializerRegistry::getInstance()
                ->getSerializer($type)
                ->serialize(
                  AbstractSerializer::filterExpandByPrefix($expand, $relation),
                  AbstractSerializer::filterFieldsByPrefix($fields, $relation),
                  AbstractSerializer::filterFieldsByPrefix($relations, $relation),
                );
            }
            break;
        }
      }
    }

    return $values;
  }
}
