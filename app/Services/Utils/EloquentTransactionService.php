<?php
/**
 * Copyright 2015 OpenStack Foundation
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

namespace services\utils;

use Closure;
use libs\utils\ITransactionService;
use DB;

/**
 * Class EloquentTransactionService
 * @package services\utils
 */
final class EloquentTransactionService implements ITransactionService {

    /**
     * Execute a Closure within a transaction.
     *
     * @param  Closure $callback
     * @return mixed
     *
     * @throws \Exception
     */
    public function transaction(Closure $callback, int $isolationLevel)
    {
        return DB::transaction($callback);
    }
}