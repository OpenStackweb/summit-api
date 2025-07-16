<?php namespace App\Models\Foundation\Summit\Factories;
/**
 * Copyright 2025 OpenStack Foundation
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

use models\exceptions\ValidationException;
use models\summit\SummitSponsorshipAddOn;

/**
 * Class SummitSponsorshipAddOnFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitSponsorshipAddOnFactory
{
    /**
     * @param array $data
     * @return SummitSponsorshipAddOn
     */
    public static function build(array $data): SummitSponsorshipAddOn {
        return self::populate(new SummitSponsorshipAddOn, $data);
    }

    /**
     * @param SummitSponsorshipAddOn $add_on
     * @param array $data
     * @return SummitSponsorshipAddOn
     * @throws ValidationException
     */
    public static function populate(SummitSponsorshipAddOn $add_on, array $data): SummitSponsorshipAddOn{

        if(isset($data['name']))
            $add_on->setName(trim($data['name']));

        if(isset($data['type']))
            $add_on->setType(trim($data['type']));

        return $add_on;
    }
}