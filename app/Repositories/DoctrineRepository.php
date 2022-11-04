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
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use LaravelDoctrine\ORM\Facades\Registry;
use models\exceptions\ValidationException;
use models\utils\IBaseRepository;
use models\utils\IEntity;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\LazyCriteriaCollection;
/**
 * Class DoctrineRepository
 * @package App\Repositories
 */
abstract class DoctrineRepository extends EntityRepository implements IBaseRepository
{

    /**
     * @var string
     */
    protected $manager_name;

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return Registry::getManager($this->manager_name);
    }

    public function getById($id)
    {
        return $this->find($id);
    }

    /**
     * @param int $id
     * @return IEntity
     */
    public function getByIdRefreshed($id){
        return $this->find($id, null, null, true);
    }

    /**
     * @param int $id
     * @return IEntity|null|object
     */
    public function getByIdExclusiveLock($id){
        return $this->find($id, \Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE);
    }

    /**
     * @param $entity
     * @param bool $sync
     * @return mixed|void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function add($entity, $sync = false)
    {
        $this->getEntityManager()->persist($entity);
        if($sync)
            $this->getEntityManager()->flush($entity);
    }

    /**
     * @param IEntity $entity
     * @return void
     */
    public function delete($entity)
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * @return IEntity[]
     */
    public function getAll()
    {
        return $this->findAll();
    }

    /**
     * @return string
     */
    protected abstract function getBaseEntity();

    /**
     * @return array
     */
    protected abstract function getFilterMappings();

    /**
     * @return array
     */
    protected abstract function getOrderMappings();

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected abstract function applyExtraFilters(QueryBuilder $query);

    /**
     * @param QueryBuilder $query
     * @param Filter|null $filter
     * @return QueryBuilder
     */
    protected abstract function applyExtraJoins(QueryBuilder $query, ?Filter $filter = null);


    /**
     * @param callable $fnQuery
     *  @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @param callable|null $fnDefaultFilter
     * @return PagingResponse
     */
    protected function getParametrizedAllByPage
    (
        callable $fnQuery,
        PagingInfo $paging_info,
        Filter $filter = null,
        Order $order = null,
        callable $fnDefaultFilter = null
    ){

        $query  = call_user_func($fnQuery);

        $query = $this->applyExtraJoins($query, $filter);

        $query = $this->applyExtraFilters($query);

        if(!is_null($filter)){
            $filter->apply2Query($query, $this->getFilterMappings($filter));
        }

        if(!is_null($order)){
            $order->apply2Query($query, $this->getOrderMappings($filter));
        }
        else if(!is_null($fnDefaultFilter)){
            $query = call_user_func($fnDefaultFilter, $query);
        }

        $query = $query
            ->setFirstResult($paging_info->getOffset())
            ->setMaxResults($paging_info->getPerPage());

        $paginator = new Paginator($query, $fetchJoinCollection = true);
        $total     = $paginator->count();
        $data      = [];

        foreach($paginator as $entity)
            array_push($data, $entity);

        return new PagingResponse
        (
            $total,
            $paging_info->getPerPage(),
            $paging_info->getCurrentPage(),
            $paging_info->getLastPage($total),
            $data
        );
    }

    /**
     * @param callable $fnQuery
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @param callable|null $fnDefaultFilter
     * @return array
     */
    public function getParametrizedAllIdsByPage(callable $fnQuery,PagingInfo $paging_info, Filter $filter = null, Order $order = null, callable $fnDefaultFilter = null):array {

        $query  = call_user_func($fnQuery);

        $query = $this->applyExtraJoins($query, $filter);

        $query = $this->applyExtraFilters($query);

        if(!is_null($filter)){
            $filter->apply2Query($query, $this->getFilterMappings($filter));
        }

        if(!is_null($order)){
            $order->apply2Query($query, $this->getOrderMappings($filter));
        }

        else if(!is_null($fnDefaultFilter)){
            $query = call_user_func($fnDefaultFilter, $query);
        }

        $query = $query
            ->setFirstResult($paging_info->getOffset())
            ->setMaxResults($paging_info->getPerPage());

        $res = $query->getQuery()->getArrayResult();
        return array_column($res, 'id');
    }
    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null){

        $query  = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("e")
            ->from($this->getBaseEntity(), "e");

        $query = $this->applyExtraJoins($query, $filter);

        $query = $this->applyExtraFilters($query);

        if(!is_null($filter)){
            $filter->apply2Query($query, $this->getFilterMappings($filter));
        }

        if(!is_null($order)){
            $order->apply2Query($query, $this->getOrderMappings($filter));
        }

        $query= $query
            ->setFirstResult($paging_info->getOffset())
            ->setMaxResults($paging_info->getPerPage());

        $paginator = new Paginator($query, $fetchJoinCollection = true);
        $total     = $paginator->count();
        $data      = [];

        foreach($paginator as $entity)
            array_push($data, $entity);

        return new PagingResponse
        (
            $total,
            $paging_info->getPerPage(),
            $paging_info->getCurrentPage(),
            $paging_info->getLastPage($total),
            $data
        );
    }

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return array
     */
    public function getAllIdsByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null):array {

        $query  = $this->getEntityManager()
            ->createQueryBuilder()
            ->distinct(true)
            ->select("e.id")
            ->from($this->getBaseEntity(), "e");

        $query = $this->applyExtraJoins($query, $filter);

        $query = $this->applyExtraFilters($query);

        if(!is_null($filter)){
            $filter->apply2Query($query, $this->getFilterMappings($filter));
        }

        if(!is_null($order)){
            $order->apply2Query($query, $this->getOrderMappings($filter));
        }

        $query = $query
            ->setFirstResult($paging_info->getOffset())
            ->setMaxResults($paging_info->getPerPage());

        $res = $query->getQuery()->getArrayResult();
        return array_column($res, 'id');
    }

    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param string $alias
     * @param string $indexBy The index for the from.
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias, $indexBy = null)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select($alias)
            ->from($this->_entityName, $alias, $indexBy);
    }

    /**
     * Creates a new result set mapping builder for this entity.
     *
     * The column naming strategy is "INCREMENT".
     *
     * @param string $alias
     *
     * @return ResultSetMappingBuilder
     */
    public function createResultSetMappingBuilder($alias)
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager(), ResultSetMappingBuilder::COLUMN_RENAMING_INCREMENT);
        $rsm->addRootEntityFromClassMetadata($this->_entityName, $alias);

        return $rsm;
    }

    /**
     * Creates a new Query instance based on a predefined metadata named query.
     *
     * @param string $queryName
     *
     * @return Query
     */
    public function createNamedQuery($queryName)
    {
        return $this->getEntityManager()->createQuery($this->_class->getNamedQuery($queryName));
    }

    /**
     * Creates a native SQL query.
     *
     * @param string $queryName
     *
     * @return NativeQuery
     */
    public function createNativeNamedQuery($queryName)
    {
        $queryMapping   = $this->_class->getNamedNativeQuery($queryName);
        $rsm            = new Query\ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addNamedNativeQueryMapping($this->_class, $queryMapping);

        return $this->getEntityManager()->createNativeQuery($queryMapping['query'], $rsm);
    }

    /**
     * Clears the repository, causing all managed entities to become detached.
     *
     * @return void
     */
    public function clear()
    {
        $this->getEntityManager()->clear($this->_class->rootEntityName);
    }

    /**
     * Finds an entity by its primary key / identifier.
     *
     * @param mixed    $id          The identifier.
     * @param int|null $lockMode    One of the \Doctrine\DBAL\LockMode::* constants
     *                              or NULL if no specific lock mode should be used
     *                              during the search.
     * @param int|null $lockVersion The lock version.
     *
     * @return object|null The entity instance or NULL if the entity can not be found.
     */
    public function find($id, $lockMode = null, $lockVersion = null, $refresh = false)
    {
        $res = $this->getEntityManager()->find($this->_entityName, $id, $lockMode, $lockVersion);
        if($refresh)
            $this->getEntityManager()->refresh($res);
        return $res;
    }

    /**
     * Finds entities by a set of criteria.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return array The objects.
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $persister = $this->getEntityManager()->getUnitOfWork()->getEntityPersister($this->_entityName);

        return $persister->loadAll($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Finds a single entity by a set of criteria.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     *
     * @return object|null The entity instance or NULL if the entity can not be found.
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        $persister = $this->getEntityManager()->getUnitOfWork()->getEntityPersister($this->_entityName);

        return $persister->load($criteria, null, null, [], null, 1, $orderBy);
    }

    /**
     * Counts entities by a set of criteria.
     *
     * @todo Add this method to `ObjectRepository` interface in the next major release
     *
     * @param array $criteria
     *
     * @return int The cardinality of the objects that match the given criteria.
     */
    public function count(array $criteria)
    {
        return $this->getEntityManager()->getUnitOfWork()->getEntityPersister($this->_entityName)->count($criteria);
    }

    /**
     * Select all elements from a selectable that match the expression and
     * return a new collection containing these elements.
     *
     * @param \Doctrine\Common\Collections\Criteria $criteria
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function matching(Criteria $criteria)
    {
        $persister = $this->getEntityManager()->getUnitOfWork()->getEntityPersister($this->_entityName);

        return new LazyCriteriaCollection($persister, $criteria);
    }

    /**
     * @param QueryBuilder $query
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @param callable|null $filterMappings
     * @param callable|null $orderMappings
     * @return PagingResponse
     */
    protected function getAllAbstractByPage
    (
        QueryBuilder $query,
        PagingInfo $paging_info,
        Filter $filter = null,
        Order $order = null,
        array $filterMappings = [],
        array $orderMappings = []
    ):PagingResponse{

        if(!is_null($filter) && count($filterMappings) > 0){
            $filter->apply2Query($query, $filterMappings);
        }

        if(!is_null($order) && count($orderMappings) > 0 ){
            $order->apply2Query($query, $orderMappings);
        }

        $query = $query
            ->setFirstResult($paging_info->getOffset())
            ->setMaxResults($paging_info->getPerPage());

        $paginator = new Paginator($query, $fetchJoinCollection = true);
        $total     = $paginator->count();
        $data      = array();

        foreach($paginator as $entity)
            array_push($data, $entity);

        return new PagingResponse
        (
            $total,
            $paging_info->getPerPage(),
            $paging_info->getCurrentPage(),
            $paging_info->getLastPage($total),
            $data
        );
    }
}