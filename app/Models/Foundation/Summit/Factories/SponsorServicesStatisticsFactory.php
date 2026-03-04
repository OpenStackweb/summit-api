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

use App\Models\Foundation\Summit\SponsorStatistics;

/**
 * Class SponsorServicesStatisticsFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SponsorServicesStatisticsFactory
{
    /**
     * @param array $data
     * @return SponsorStatistics
     */
    public static function build(array $data): SponsorStatistics
    {
        return self::populate(self::getNewEntity(), $data);
    }

    /**
     * @param SponsorStatistics $settings
     * @param array $data
     * @return SponsorStatistics
     */
    public static function populate(SponsorStatistics $statistics, array $data): SponsorStatistics
    {
        if(isset($data['forms_qty'])) {
            $statistics->setFormsQty($data['forms_qty']);
        }
        if(isset($data['purchases_qty'])) {
            $statistics->setPurchasesQty($data['purchases_qty']);
        }
        if(isset($data['pages_qty'])) {
            $statistics->setPagesQty($data['pages_qty']);
        }
        if(isset($data['documents_qty'])) {
            $statistics->setDocumentsQty($data['documents_qty']);
        }
        return $statistics;
    }

    protected static function getNewEntity(): SponsorStatistics
    {
        return new SponsorStatistics;
    }
}
