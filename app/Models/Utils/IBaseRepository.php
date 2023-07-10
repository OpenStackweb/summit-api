<?php namespace models\utils;
/**
 * Copyright 2017 OpenStack Foundation
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
use utils\Filter;
use utils\Order;
use utils\PagingInfo;
use utils\PagingResponse;
/**
 * Interface IBaseRepository
 */
interface IBaseRepository
{
    /**
     * @param int $id
     * @return IEntity
     */
    public function getById($id);

    /**
     * @param int $id
     * @return IEntity
     */
    public function getByIdRefreshed($id);

    /**
     * @param int $id
     * @param bool $refresh
     * @return IEntity
     */
    public function getByIdExclusiveLock($id, bool $refresh = false);

    /**
     * @param IEntity $entity
     * @param bool $sync
     * @return void
     */
    public function add($entity, $sync = false);

    /**
     * @param IEntity $entity
     * @return void
     */
    public function delete($entity);

    /**
     * @return IEntity[]
     */
    public function getAll();

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return PagingResponse
     */
    public function getAllByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null);

    /**
     * @param PagingInfo $paging_info
     * @param Filter|null $filter
     * @param Order|null $order
     * @return array
     */
    public function getAllIdsByPage(PagingInfo $paging_info, Filter $filter = null, Order $order = null):array;

}