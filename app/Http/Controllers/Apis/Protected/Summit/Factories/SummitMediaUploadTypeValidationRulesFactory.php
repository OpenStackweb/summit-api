<?php namespace App\Http\Controllers;
/*
 * Copyright 2023 OpenStack Foundation
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

use App\Http\ValidationRulesFactories\AbstractValidationRulesFactory;
use App\Models\Utils\IStorageTypesConstants;

/**
 * Class SummitMediaUploadTypeValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SummitMediaUploadTypeValidationRulesFactory extends AbstractValidationRulesFactory {
  public static function buildForAdd(array $payload = []): array {
    return [
      "name" => "required|string|max:255",
      "description" => "sometimes|string|max:5120",
      "is_mandatory" => "required|boolean",
      "use_temporary_links_on_public_storage" => "sometimes|boolean",
      "temporary_links_public_storage_ttl" =>
        "sometimes|int|required_with:use_temporary_links_on_public_storage",
      // in KB
      "max_size" => "required|int|megabyte_aligned",
      "private_storage_type" =>
        "required|string|in:" . implode(",", IStorageTypesConstants::ValidPrivateTypes),
      "public_storage_type" =>
        "required|string|in:" . implode(",", IStorageTypesConstants::ValidPublicTypes),
      "type_id" => "required|int",
      "presentation_types" => "sometimes|int_array",
      "min_uploads_qty" => "sometimes|integer|min:0",
      "max_uploads_qty" => "sometimes|integer|min:0",
      "is_editable" => "required|boolean",
    ];
  }

  public static function buildForUpdate(array $payload = []): array {
    return [
      "name" => "sometimes|string|max:255",
      "description" => "sometimes|string|max:5120",
      "is_mandatory" => "sometimes|boolean",
      "use_temporary_links_on_public_storage" => "sometimes|boolean",
      "temporary_links_public_storage_ttl" =>
        "sometimes|int|required_with:use_temporary_links_on_public_storage",
      // KB
      "max_size" => "sometimes|int|megabyte_aligned",
      "private_storage_type" =>
        "sometimes|string|in:" . implode(",", IStorageTypesConstants::ValidPrivateTypes),
      "public_storage_type" =>
        "sometimes|string|in:" . implode(",", IStorageTypesConstants::ValidPublicTypes),
      "type_id" => "sometimes|int",
      "presentation_types" => "sometimes|int_array",
      "min_uploads_qty" => "sometimes|integer|min:0",
      "max_uploads_qty" => "sometimes|integer|min:0",
      "is_editable" => "sometimes|boolean",
    ];
  }
}
