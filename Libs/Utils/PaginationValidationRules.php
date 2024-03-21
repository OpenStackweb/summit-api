<?php namespace libs\utils;
/*
 * Copyright 2022 OpenStack Foundation
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
 * Class PaginationValidationRules
 * @package libs\utils
 */
final class PaginationValidationRules
{

    const PageParam = 'page';
    const PerPageParam = 'per_page';

    const PerPageMin = 5;
    const PerPageMax = 500;

    public static function get():array{
        return [
            self::PageParam => 'integer|min:1',
            self::PerPageParam => sprintf('required_with:page|integer|min:%s|max:%s',self::PerPageMin, self::PerPageMax)
        ];
    }
}