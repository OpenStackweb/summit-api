<?php namespace App\Repositories;
/**
 * Copyright 2016 OpenStack Foundation
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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Support\Facades\App;
use models\utils\SilverstripeBaseModel;
use LaravelDoctrine\ORM\Facades\Registry;
/**
 * Class SilverStripeDoctrineRepository
 * @package App\Repositories
 */
abstract class SilverStripeDoctrineRepository extends DoctrineRepository
{

    /**
     * Initializes a new <tt>EntityRepository</tt>.
     *
     * @param EntityManager         $em    The EntityManager to use.
     * @param ClassMetadata $class The class descriptor.
     */
    public function __construct($em, ClassMetadata $class)
    {
        $this->manager_name = SilverstripeBaseModel::EntityManager;
        parent::__construct(Registry::getManager(SilverstripeBaseModel::EntityManager), $class);
    }

    /**
     * @return array
     */
    protected function getFilterMappings()
    {
        return [];
    }

    /**
     * @return array
     */
    protected function getOrderMappings()
    {
        return [];
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraFilters(QueryBuilder $query){
        return $query;
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function applyExtraJoins(QueryBuilder $query){
        return $query;
    }

    /**
     * @param string $group_code
     * @return bool
     */
    protected static function isCurrentMemberOnGroup($group_code){
        $resource_server_ctx = App::make(\models\oauth2\IResourceServerContext::class);
        $member              = $resource_server_ctx->getCurrentUser();
        if(is_null($member)) return false;
        return $member->isOnGroup($group_code);
    }

    public function deleteAll():void{
        foreach ($this->getAll() as $entity){
            $this->delete($entity);
        }
    }
}