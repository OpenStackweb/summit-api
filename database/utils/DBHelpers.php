<?php namespace Database\Utils;
/*
 * Copyright 2021 OpenStack Foundation
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
use Illuminate\Support\Facades\DB;

/**
 * Class DBHelpers
 * @package Database\Utils
 */
final class DBHelpers
{
    /**
     * @param string $db
     * @param string $table
     * @param string $fk
     * @return bool
     */
    public static function existsFK(string $db, string $table, string $fk):bool{
        $sql = <<<SQL
SELECT 1
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = '{$db}'
AND TABLE_NAME = '{$table}'
AND CONSTRAINT_NAME = '{$fk}';
SQL;
        $res = DB::select($sql);
        if($res && count($res)){
            return true;
        }
        return false;
    }
}