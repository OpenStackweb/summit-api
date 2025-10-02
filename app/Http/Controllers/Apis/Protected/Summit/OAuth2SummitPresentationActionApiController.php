<?php namespace App\Http\Controllers;
/**
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
use App\Security\SummitScopes;
use App\Services\Model\ISummitPresentationActionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use OpenApi\Attributes as OA;
use Exception;
/**
 * Class OAuth2SummitPresentationActionApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitPresentationActionApiController
    extends OAuth2ProtectedController
{

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitPresentationActionService
     */
    private $service;

    /**
     * OAuth2SummitPresentationActionApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitPresentationActionService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ISummitPresentationActionService $service,
        IResourceServerContext $resource_server_context
    )
    {
        $this->summit_repository = $summit_repository;
        $this->service = $service;
        parent::__construct($resource_server_context);
    }

    // OpenAPI Documentation

    #[OA\Put(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}/actions/{action_type_id}/complete',
        summary: 'Mark a presentation action as completed',
        description: 'Marks a specific action for a presentation as completed by a track chair. Track chairs use presentation actions to manage the review process (e.g., "Review Video", "Check Speakers", "Verify Content"). Only track chairs and track chair admins can perform this action.',
        security: [['oauth2_security_scope' => [SummitScopes::WriteSummitData]]],
        tags: ['Presentation Actions'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'presentation_id',
                in: 'path',
                required: true,
                description: 'Presentation ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'action_type_id',
                in: 'path',
                required: true,
                description: 'Action Type ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Expand relationships. Available: presentation, type, created_by, updated_by',
                schema: new OA\Schema(type: 'string', example: 'type,created_by')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Presentation action marked as completed successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PresentationAction')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/400'),
            new OA\Response(response: 401, ref: '#/components/responses/401'),
            new OA\Response(response: 403, ref: '#/components/responses/403', description: 'Forbidden - User must be a track chair or track chair admin'),
            new OA\Response(response: 404, ref: '#/components/responses/404'),
            new OA\Response(response: 412, ref: '#/components/responses/412'),
            new OA\Response(response: 500, ref: '#/components/responses/500'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @param $action_type_id
     */
    public function complete($summit_id, $selection_plan_id, $presentation_id, $action_type_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $member = $this->resource_server_context->getCurrentUser();

            if (is_null($member))
                return $this->error403();

            $authz = $summit->isTrackChair($member) || $summit->isTrackChairAdmin($member);

            if (!$authz)
                return $this->error403();

            $action = $this->service->updateAction($summit, intval($selection_plan_id), intval($presentation_id), intval($action_type_id), true );
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($action)->serialize(Request::input('expand', '')));

        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    #[OA\Delete(
        path: '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}/actions/{action_type_id}/incomplete',
        summary: 'Mark a presentation action as incomplete',
        description: 'Unmarks a completed presentation action, setting it back to incomplete status. This allows track chairs to revert an action they previously marked as done. Only track chairs and track chair admins can perform this action.',
        security: [['oauth2_security_scope' => [SummitScopes::WriteSummitData]]],
        tags: ['Presentation Actions'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Summit ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'selection_plan_id',
                in: 'path',
                required: true,
                description: 'Selection Plan ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'presentation_id',
                in: 'path',
                required: true,
                description: 'Presentation ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'action_type_id',
                in: 'path',
                required: true,
                description: 'Action Type ID',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Expand relationships. Available: presentation, type, created_by, updated_by',
                schema: new OA\Schema(type: 'string', example: 'type,created_by')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Presentation action marked as incomplete successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/PresentationAction')
            ),
            new OA\Response(response: 400, ref: '#/components/responses/400'),
            new OA\Response(response: 401, ref: '#/components/responses/401'),
            new OA\Response(response: 403, ref: '#/components/responses/403', description: 'Forbidden - User must be a track chair or track chair admin'),
            new OA\Response(response: 404, ref: '#/components/responses/404'),
            new OA\Response(response: 412, ref: '#/components/responses/412'),
            new OA\Response(response: 500, ref: '#/components/responses/500'),
        ]
    )]

    /**
     * @param $summit_id
     * @param $selection_plan_id
     * @param $presentation_id
     * @param $action_type_id
     */
    public function uncomplete($summit_id, $selection_plan_id, $presentation_id, $action_type_id){
        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $member = $this->resource_server_context->getCurrentUser();

            if (is_null($member))
                return $this->error403();

            $authz = $summit->isTrackChair($member) || $summit->isTrackChairAdmin($member);

            if (!$authz)
                return $this->error403();

            $action = $this->service->updateAction($summit, intval($selection_plan_id), intval($presentation_id), intval($action_type_id), false );

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($action)->serialize(Request::input('expand', '')));
        } catch (ValidationException $ex) {
            Log::warning($ex);
            return $this->error412($ex->getMessages());
        } catch (EntityNotFoundException $ex) {
            Log::warning($ex);
            return $this->error404($ex->getMessage());
        } catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}
