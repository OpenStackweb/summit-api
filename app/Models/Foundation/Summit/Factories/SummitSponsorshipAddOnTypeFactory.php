<?php namespace App\Models\Foundation\Summit\Factories;
/*
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

use models\summit\SummitSponsorshipAddOnType;

/**
 * Class SummitSponsorshipAddOnTypeFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitSponsorshipAddOnTypeFactory
{
    /**
     * @param array $data
     * @return SummitSponsorshipAddOnType
     */
    public static function build(array $data): SummitSponsorshipAddOnType
    {
        return self::populate(new SummitSponsorshipAddOnType(), $data);
    }

    /**
     * @param SummitSponsorshipAddOnType $type
     * @param array $data
     * @return SummitSponsorshipAddOnType
     */
    public static function populate(SummitSponsorshipAddOnType $type, array $data): SummitSponsorshipAddOnType
    {
        if (isset($data['name']))
            $type->setName(trim($data['name']));

        return $type;
    }
}
