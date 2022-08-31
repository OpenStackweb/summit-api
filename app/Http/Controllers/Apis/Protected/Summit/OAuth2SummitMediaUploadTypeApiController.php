<?php namespace App\Http\Controllers;
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
use App\Http\Exceptions\HTTP403ForbiddenException;
use App\Models\Foundation\Summit\Repositories\ISummitMediaUploadTypeRepository;
use App\Models\Utils\IStorageTypesConstants;
use App\Services\Model\ISummitMediaUploadTypeService;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use Exception;
/**
 * Class OAuth2SummitMediaUploadTypeApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitMediaUploadTypeApiController extends OAuth2ProtectedController
{
    use GetAllBySummit;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

    use GetSummitChildElementById;

    /**
     * @var ISummitMediaUploadTypeService
     */
    private $service;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;


    public function __construct
    (
        ISummitMediaUploadTypeRepository $repository,
        ISummitRepository $summit_repository,
        ISummitMediaUploadTypeService  $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->service = $service;
        $this->summit_repository = $summit_repository;
        $this->repository = $repository;
    }

    /**
     * @return array
     */
    protected function getFilterRules():array
    {
        return [
            'name' => ['=@', '=='],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'name' => 'sometimes|required|string',
        ];
    }
    /**
     * @return array
     */
    protected function getOrderRules():array{
        return [
            'id',
            'name',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->add($summit, $payload);
    }

    /**
     * @inheritDoc
     */
    function getAddValidationRules(array $payload): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'sometimes|string|max:255',
            'is_mandatory' => 'required|boolean',
            'use_temporary_links_on_public_storage' => 'sometimes|boolean',
            'temporary_links_public_storage_ttl' => 'sometimes|int|required_with:use_temporary_links_on_public_storage',
            // in KB
            'max_size' => 'required|int|megabyte_aligned',
            'private_storage_type' => 'required|string|in:'.implode(",", IStorageTypesConstants::ValidPrivateTypes),
            'public_storage_type' => 'required|string|in:'.implode(",", IStorageTypesConstants::ValidPublicTypes),
            'type_id' => 'required|int',
            'presentation_types' => 'sometimes|int_array',
            'min_uploads_qty' => 'sometimes|integer|min:0',
            'max_uploads_qty' => 'sometimes|int',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @inheritDoc
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->delete($summit, $child_id);
    }

    /**
     * @inheritDoc
     */
    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
       return $summit->getMediaUploadTypeById($child_id);
    }

    /**
     * @inheritDoc
     */
    function getUpdateValidationRules(array $payload): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:255',
            'is_mandatory' => 'sometimes|boolean',
            'use_temporary_links_on_public_storage' => 'sometimes|boolean',
            'temporary_links_public_storage_ttl' => 'sometimes|int|required_with:use_temporary_links_on_public_storage',
            // KB
            'max_size' => 'sometimes|int|megabyte_aligned',
            'private_storage_type' => 'sometimes|string|in:'.implode(",", IStorageTypesConstants::ValidPrivateTypes),
            'public_storage_type' => 'sometimes|string|in:'.implode(",", IStorageTypesConstants::ValidPublicTypes),
            'type_id' => 'sometimes|int',
            'presentation_types' => 'sometimes|int_array',
            'min_uploads_qty' => 'sometimes|integer|min:0',
            'max_uploads_qty' => 'sometimes|int',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->update($summit, $child_id, $payload);
    }

    /**
     * @param $summit_id
     * @param $media_upload_type_id
     * @param $presentation_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addToPresentationType($summit_id, $media_upload_type_id, $presentation_type_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $presentation_type = $this->service->addToPresentationType($summit, intval($media_upload_type_id), intval($presentation_type_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer
            (
                $presentation_type
            )->serialize());
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412(array($ex->getMessage()));
        }
        catch(EntityNotFoundException $ex)
        {
            Log::warning($ex);
            return $this->error404(array('message'=> $ex->getMessage()));
        }
        catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        }
        catch (HTTP403ForbiddenException $ex) {
            Log::warning($ex);
            return $this->error403();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $media_upload_type_id
     * @param $presentation_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function deleteFromPresentationType($summit_id, $media_upload_type_id, $presentation_type_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $presentation_type = $this->service->deleteFromPresentationType($summit, intval($media_upload_type_id), intval($presentation_type_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer
            (
                $presentation_type
            )->serialize());
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412(array($ex->getMessage()));
        }
        catch(EntityNotFoundException $ex)
        {
            Log::warning($ex);
            return $this->error404(array('message'=> $ex->getMessage()));
        }
        catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        }
        catch (HTTP403ForbiddenException $ex) {
            Log::warning($ex);
            return $this->error403();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $to_summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function cloneMediaUploadTypes($summit_id, $to_summit_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $to_summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($to_summit_id);
            if (is_null($to_summit)) return $this->error404();

            $to_summit = $this->service->cloneMediaUploadTypes($summit, $to_summit);

            return $this->created(
                SerializerRegistry::getInstance()->getSerializer
                (
                    $to_summit
                )->serialize()
            );
        }
        catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412(array($ex->getMessage()));
        }
        catch(EntityNotFoundException $ex)
        {
            Log::warning($ex);
            return $this->error404(array('message'=> $ex->getMessage()));
        }
        catch (\HTTP401UnauthorizedException $ex) {
            Log::warning($ex);
            return $this->error401();
        }
        catch (HTTP403ForbiddenException $ex) {
            Log::warning($ex);
            return $this->error403();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }


}