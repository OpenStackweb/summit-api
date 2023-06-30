<?php namespace App\Http\Utils\Filters;
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

use Illuminate\Support\Facades\Request;
use utils\Filter;

/**
 * Class FiltersParams
 * @package App\Http\Utils\Filters
 */
final class FiltersParams
{
    const FilterRequestParamName = 'filter';
    const FilterMainOpRequestParamName = 'filter_op';
    public static function hasFilterParam():bool{
        return Request::has(self::FilterRequestParamName);
    }

    public static function getFilterParam(){
        return Request::input(self::FilterRequestParamName);
    }

    public static function getFilterMainOpParam(){
        return Request::input(self::FilterMainOpRequestParamName, Filter::MainOperatorAnd);
    }
}