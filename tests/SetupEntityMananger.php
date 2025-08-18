<?php namespace Tests;
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

use LaravelDoctrine\ORM\Facades\EntityManager;
use LaravelDoctrine\ORM\Facades\Registry;
use models\utils\SilverstripeBaseModel;
use Illuminate\Support\Facades\DB;

/**
 * Trait InsertMemberTestData
 */
trait SetupEntityMananger
{
    /**
     * @var EntityManager
     */
    static $em;


    /**
     * SetupEntityManager constructor.
     */
    protected static function setupEntityManager()
    {
        DB::setDefaultConnection("model");

        self::$em = Registry::getManager(SilverstripeBaseModel::EntityManager);
        if (!self::$em->isOpen()) {
            self::$em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
        }
    }

    protected static function tearDownEntityManager()
    {
        try {
            if (!self::$em->isOpen()) {
                self::$em = Registry::resetManager(SilverstripeBaseModel::EntityManager);
            }

            self::$em->flush();

        } catch (\Exception $ex) {

        }
    }
}