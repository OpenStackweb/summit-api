<?php namespace App\Models\Utils;
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

/**
 * Interface IStorageTypesConstants
 * @package App\Models\Utils
 */
interface IStorageTypesConstants {
  public const PublicType = "Public";
  public const PrivateType = "Private";

  public const DropBox = "DropBox";
  public const Swift = "Swift";
  public const S3 = "S3";
  public const Local = "Local";
  public const None = "None";

  const ValidPrivateTypes = [self::None, self::DropBox, self::Local];

  const ValidPublicTypes = [self::None, self::Swift, self::S3, self::Local];
}
