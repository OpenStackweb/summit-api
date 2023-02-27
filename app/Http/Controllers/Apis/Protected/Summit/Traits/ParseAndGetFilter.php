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

use Illuminate\Support\Facades\Request;
use utils\Filter;
use utils\FilterParser;

/**
 * Trait ParseAndGetFilter
 * @package App\Http\Controllers
 */
trait ParseAndGetFilter
{
    /**
     * @param callable $getFilterRules
     * @param callable $getFilterValidatorRules
     * @return Filter|null
     * @throws \models\exceptions\ValidationException
     * @throws \utils\FilterParserException
     */
    public static function getFilter(
        callable $getFilterRules,
        callable $getFilterValidatorRules
    ){
        $filter = null;

        if (Request::has('filter')) {
            $filter = FilterParser::parse(Request::get('filter'), call_user_func($getFilterRules));
        }

        if (is_null($filter)) $filter = new Filter();

        $filter_validator_rules = call_user_func($getFilterValidatorRules);
        if (count($filter_validator_rules)) {
            $filter->validate($filter_validator_rules);
        }

        return $filter;
    }
}