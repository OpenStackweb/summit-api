<?php namespace App\Http\Controllers;
/**
 * Copyright 2018 OpenStack Foundation
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
use App\Models\Foundation\Summit\Repositories\ISpeakerOrganizationalRoleRepository;
use models\oauth2\IResourceServerContext;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use utils\PagingResponse;
use Illuminate\Support\Facades\Request;
use OpenApi\Attributes as OA;
/**
 * Class OAuth2SpeakerOrganizationalRoleApiController
 * @package App\Http\Controllers
 */
final class OAuth2SpeakerOrganizationalRoleApiController extends OAuth2ProtectedController
{

    /**
     * OAuth2SpeakerOrganizationalRoleApiController constructor.
     * @param ISpeakerOrganizationalRoleRepository $repository
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISpeakerOrganizationalRoleRepository $repository,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository = $repository;
    }

    #[OA\Get(
        path: '/api/v1/speakers/organizational-roles',
        summary: 'Get all default speaker organizational roles',
        description: 'Retrieves a list of default organizational roles for speakers. These are predefined role types that speakers can select to describe their position or role within an organization (e.g., "Developer", "Manager", "Architect", "Executive").',
        operationId: 'getAllSpeakerOrganizationalRoles',
        security: [['summit_rsvp_oauth2' => [
            SummitScopes::ReadSummitData,
            SummitScopes::ReadAllSummitData
        ]]],
        tags: ['Speakers'],
        parameters: [
            new OA\Parameter(
                name: 'expand',
                in: 'query',
                required: false,
                description: 'Comma-separated list of related resources to expand',
                schema: new OA\Schema(type: 'string', example: '')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Organizational roles retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SpeakerOrganizationalRolesResponse')
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
            new OA\Response(response: Response::HTTP_PRECONDITION_FAILED, description: "Validation Error"),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error"),
        ]
    )]
    /**
     * @return mixed
     */
    public function getAll()
    {
        try {
            $roles = $this->repository->getDefaultOnes();
            $response = new PagingResponse
            (
                count($roles),
                count($roles),
                1,
                1,
                $roles
            );

            return $this->ok($response->toArray($expand = Request::input('expand', '')));
        } catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        } catch (EntityNotFoundException $ex2) {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        } catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}
