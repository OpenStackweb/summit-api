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
use models\exceptions\ValidationException;
use models\main\Member;
use models\summit\ISummitMetricType;
use models\summit\SummitEventAttendanceMetric;
use models\summit\SummitMetric;
use models\summit\SummitSponsorMetric;
/**
 * Class SummitMetricFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitMetricFactory
{
    /**
     * @param Member $member
     * @param array $data
     * @return SummitMetric
     * @throws ValidationException
     */
    public static function build(Member $member, array $data):SummitMetric{
        if(!isset($data['type'])) throw new ValidationException("type param is mandatory");
        $type = trim($data['type']);
        $metric = null;
        switch($type){
            case ISummitMetricType::General:
                $metric = SummitMetric::build($member);
                break;
            case ISummitMetricType::Lobby:
                $metric = SummitMetric::build($member);
                break;
            case ISummitMetricType::Event:
                $metric = SummitEventAttendanceMetric::build($member);
                break;
            case ISummitMetricType::Sponsor:
                $metric = SummitSponsorMetric::build($member);
                break;
        }
        return self::populate($metric, $data);
    }

    /**
     * @param SummitMetric $metric
     * @param array $data
     * @return SummitMetric
     */
    public static function populate(SummitMetric $metric, array $data):SummitMetric{
        if(isset($data['type']))
            $metric->setType($data['type']);
        return $metric;
    }
}