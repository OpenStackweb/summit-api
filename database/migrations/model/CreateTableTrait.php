<?php namespace Database\Migrations\Model;
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

use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

/**
 * Trait CreateTableTrait
 * @package Database\Migrations\Model
 */
trait CreateTableTrait
{
    public static function createTable(Schema $schema, string $table_name, callable $add_columns_callback = null): void
    {
        $builder = new Builder($schema);
        if (!$schema->hasTable($table_name)) {
            $builder->create($table_name, function (Table $table) use ($table_name, $add_columns_callback){
                $table->integer("ID", true, false);
                $table->primary("ID");
                $table->string('ClassName')->setDefault($table_name);
                $table->index("ClassName", "ClassName");
                $table->timestamp('Created');
                $table->timestamp('LastEdited');
                if($add_columns_callback)
                    $add_columns_callback($table);
            });
        }
    }
}