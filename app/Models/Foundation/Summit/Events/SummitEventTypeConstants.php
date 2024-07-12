<?php namespace App\Models\Foundation\Summit\Events;
/**
 * Copyright 2018 OpenStack Foundation
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
use models\summit\PresentationType;
use models\summit\SummitEventType;
/**
 * Class SummitEventTypeConstants
 * @package App\Models\Foundation\Summit\Events
 */
final class SummitEventTypeConstants {
  public static $valid_class_names = [SummitEventType::ClassName, PresentationType::ClassName];

  const BLACKOUT_TIME_FINAL = "Final";
  const BLACKOUT_TIME_PROPOSED = "Proposed";
  const BLACKOUT_TIME_ALL = "All";
  const BLACKOUT_TIME_NONE = "None";

  public static $valid_blackout_times = [
    self::BLACKOUT_TIME_FINAL,
    self::BLACKOUT_TIME_PROPOSED,
    self::BLACKOUT_TIME_ALL,
    self::BLACKOUT_TIME_NONE,
  ];
}
