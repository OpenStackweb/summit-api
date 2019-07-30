<?php namespace App\Services\Model;
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
use libs\utils\ITransactionService;
use models\exceptions\ValidationException;
use models\main\ITagRepository;
use models\main\Tag;
/**
 * Class TagService
 * @package App\Services\Model
 */
final class TagService
    extends AbstractService
    implements ITagService
{
    /**
     * @var ITagRepository
     */
    private $tag_repository;

    /**
     * TagService constructor.
     * @param ITagRepository $tag_repository
     * @param ITransactionService $rx_service
     */
    public function __construct(ITagRepository $tag_repository, ITransactionService $rx_service)
    {
        parent::__construct($rx_service);
        $this->tag_repository = $tag_repository;
    }

    /**
     * @param array $payload
     * @return Tag
     */
    public function addTag(array $payload): Tag
    {
       return $this->tx_service->transaction(function () use ($payload){
          $former_tag = $this->tag_repository->getByTag(trim($payload['tag']));
          if(!is_null($former_tag)){
              throw new ValidationException(sprintf("tag %s already exists!", $payload['tag']));
          }

          $tag = new Tag(trim($payload['tag']));

          $this->tag_repository->add($tag);

          return $tag;
       });
    }
}