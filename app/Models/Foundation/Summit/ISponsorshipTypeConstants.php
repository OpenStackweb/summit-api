<?php namespace models\summit;
/**
 * Copyright 2019 OpenStack Foundation
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
 * Interface ISponsorshipTypeConstants
 * @package models\summit
 */
interface ISponsorshipTypeConstants
{

    const SmallSize  = 'Small';
    const MediumSize = 'Medium';
    const LargeSize  = 'Large';
    const BigSize    = 'Big';

    const AllowedSizes = [
        self::SmallSize,
        self::MediumSize,
        self::LargeSize,
        self::BigSize
    ];
}