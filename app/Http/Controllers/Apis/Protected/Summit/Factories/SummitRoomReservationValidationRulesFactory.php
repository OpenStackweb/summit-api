<?php namespace App\Http\Controllers;
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
use models\exceptions\ValidationException;
/**
 * Class SummitRoomReservationValidationRulesFactory
 * @package App\Http\Controllers
 */
final class SummitRoomReservationValidationRulesFactory
{
    /**
     * @param array $data
     * @param bool $update
     * @return array
     * @throws ValidationException
     */
    public static function build(array $data, $update = false){

        return [
            'currency'       => 'required|string|currency_iso',
            'amount'         => 'required|integer|min:0',
            'start_datetime' => 'required|date_format:U',
            'end_datetime'   => 'required|date_format:U|after:start_datetime',
        ];
    }
}