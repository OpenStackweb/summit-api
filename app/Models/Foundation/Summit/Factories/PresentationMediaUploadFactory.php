<?php namespace App\Models\Foundation\Summit\Factories;
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

use models\summit\Presentation;
use models\summit\PresentationMediaUpload;
use models\summit\SummitMediaUploadType;
/**
 * Class PresentationMediaUploadFactory
 * @package App\Models\Foundation\Summit\Factories
 */
class PresentationMediaUploadFactory {
  /**
   * @param array $data
   * @return PresentationMediaUpload
   */
  public static function build(array $data) {
    return self::populate(new PresentationMediaUpload(), $data);
  }

  /**
   * @param PresentationMediaUpload $media_upload
   * @param array $data
   * @return PresentationMediaUpload
   */
  public static function populate(PresentationMediaUpload $media_upload, array $data) {
    if (isset($data["display_on_site"])) {
      $media_upload->setDisplayOnSite(
        isset($data["display_on_site"]) ? boolval($data["display_on_site"]) : true,
      );
    }

    if (isset($data["file_name"])) {
      $media_upload->setFilename(trim($data["file_name"]));
    }

    if (isset($data["presentation"]) && $data["presentation"] instanceof Presentation) {
      $media_upload->setPresentation($data["presentation"]);
    }

    if (
      isset($data["media_upload_type"]) &&
      $data["media_upload_type"] instanceof SummitMediaUploadType
    ) {
      $media_upload->setMediaUploadType($data["media_upload_type"]);
    }

    return $media_upload;
  }
}
