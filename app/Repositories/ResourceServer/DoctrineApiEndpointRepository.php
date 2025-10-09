<?php namespace repositories\resource_server;
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

use App\Models\ResourceServer\ApiEndpoint;
use App\Models\ResourceServer\IApiEndpoint;
use App\Models\ResourceServer\IApiEndpointRepository;
use App\Repositories\ConfigDoctrineRepository;
use App\Repositories\DoctrineRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Support\Facades\Log;

/**
 * Class DoctrineApiEndpointRepository
 * @package repositories\resource_server
 */
final class DoctrineApiEndpointRepository
    extends ConfigDoctrineRepository
    implements IApiEndpointRepository
{

    /**
     * @param string $url
     * @param string $http_method
     * @return IApiEndpoint
     */
    public function getApiEndpointByUrlAndMethod($url, $http_method)
    {
        try {
            $em = $this->getEntityManager();

            $qb = $em->createQueryBuilder();
            $qb->select('e')
                ->from(ApiEndpoint::class, 'e')
                ->leftJoin('e.scopes', 's', 'WITH', 's.active = true')
                ->addSelect('s')
                ->where('e.route = :route')
                ->andWhere('e.http_method = :method')
                ->setParameter('route', trim($url))
                ->setParameter('method', strtoupper(trim($http_method)));

            $q = $qb->getQuery();
            $q->setCacheable(false);

            return $q->getOneOrNullResult();
        }
        catch(\Exception $ex){
            Log::error($ex);
            return null;
        }
    }

    /**
     * @return string
     */
    protected function getBaseEntity()
    {
        return ApiEndpoint::class;
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

}
