<?php namespace App\Http\Controllers;
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
use App\Http\Utils\PagingConstants;
use App\Services\Model\IMemberService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use Illuminate\Support\Facades\Validator;
use ModelSerializers\SerializerRegistry;
use utils\Filter;
use utils\FilterParser;
use utils\FilterParserException;
use utils\OrderParser;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use utils\PagingInfo;
use utils\PagingResponse;
use Exception;
/**
 * Class OAuth2MembersApiController
 * @package App\Http\Controllers
 */
final class OAuth2MembersApiController extends OAuth2ProtectedController
{
    /**
     * @var IMemberService
     */
    private $member_service;

    /**
     * OAuth2MembersApiController constructor.
     * @param IMemberRepository $member_repository
     * @param IMemberService $member_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        IMemberRepository $member_repository,
        IMemberService $member_service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->repository     = $member_repository;
        $this->member_service = $member_service;
    }

    /**
     * @return mixed
     */
    public function getAll(){

        $values = Request::all();

        $rules = [
            'page'     => 'integer|min:1',
            'per_page' => sprintf('required_with:page|integer|min:%s|max:%s', PagingConstants::MinPageSize, PagingConstants::MaxPageSize),
        ];

        try {

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page     = 1;
            $per_page = PagingConstants::DefaultPageSize;;

            if (Request::has('page')) {
                $page     = intval(Request::input('page'));
                $per_page = intval(Request::input('per_page'));
            }

            $filter = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'),  [

                    'irc'            => ['=@', '==', '@@'],
                    'twitter'        => ['=@', '==', '@@'],
                    'first_name'     => ['=@', '==', '@@'],
                    'last_name'      => ['=@', '==', '@@'],
                    'email'          => ['=@', '==', '@@'],
                    'group_slug'     => ['=@', '==', '@@'],
                    'group_id'       => ['=='],
                    'email_verified' => ['=='],
                    'active'         => ['=='],
                    'github_user'    => ['=@', '==', '@@'],
                    'full_name'      => ['=@', '==', '@@'],
                    'created'        => ['>', '<', '<=', '>=', '=='],
                    'last_edited'    => ['>', '<', '<=', '>=', '=='],
                ]);
            }

            if(is_null($filter)) $filter = new Filter();

            $filter->validate([
                'irc'            => 'sometimes|required|string',
                'twitter'        => 'sometimes|required|string',
                'first_name'     => 'sometimes|required|string',
                'last_name'      => 'sometimes|required|string',
                'email'          => 'sometimes|required|string',
                'group_slug'     => 'sometimes|required|string',
                'group_id'       => 'sometimes|required|integer',
                'email_verified' => 'sometimes|required|boolean',
                'active'         => 'sometimes|required|boolean',
                'github_user'    => 'sometimes|required|string',
                'full_name'      => 'sometimes|required|string',
                'created'        => 'sometimes|required|date_format:U',
                'last_edited'    => 'sometimes|required|date_format:U',
            ]);

            $order = null;

            if (Request::has('order'))
            {
                $order = OrderParser::parse(Request::input('order'), [
                    'first_name',
                    'last_name',
                    'id',
                    'created',
                    'last_edited',
                ]);
            }

            $data      = $this->repository->getAllByPage(new PagingInfo($page, $per_page), $filter, $order);
            $fields    = Request::input('fields', '');
            $fields    = !empty($fields) ? explode(',', $fields) : [];
            $relations = Request::input('relations', '');
            $relations = !empty($relations) ? explode(',', $relations) : [];
            $current_member = $this->resource_server_context->getCurrentUser();
            $serializer_type = SerializerRegistry::SerializerType_Public;

            if(!is_null($current_member) && ($current_member->isAdmin() || $current_member->isSummitAdmin() ||  $current_member->isTrackChairAdmin())){
                $serializer_type = SerializerRegistry::SerializerType_Admin;
            }

            return $this->ok
            (
                $data->toArray
                (
                    Request::input('expand', ''),
                    $fields,
                    $relations,
                    [],
                    $serializer_type
                )
            );
        }
        catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        }
        catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412($ex2->getMessages());
        }
        catch(FilterParserException $ex3){
            Log::warning($ex3);
            return $this->error412($ex3->getMessages());
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $member_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function getById($member_id){
        try{
            $member = $this->repository->getById(intval($member_id));
            if(is_null($member))
                throw new EntityNotFoundException();

            $fields    = Request::input('fields', '');
            $fields    = !empty($fields) ? explode(',', $fields) : [];
            $relations = Request::input('relations', '');
            $relations = !empty($relations) ? explode(',', $relations) : [];
            $current_member = $this->resource_server_context->getCurrentUser();
            $serializer_type = SerializerRegistry::SerializerType_Public;

            if(!is_null($current_member) && ($current_member->isAdmin() || $current_member->isSummitAdmin() ||  $current_member->isTrackChairAdmin())){
                $serializer_type = SerializerRegistry::SerializerType_Admin;
            }

            return $this->ok
            (
                SerializerRegistry::getInstance()
                    ->getSerializer($member, $serializer_type)
                    ->serialize
                    (
                        Request::input('expand', ''),
                        $fields,
                        $relations,
                        [],
                    )
            );


        }
        catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        }
        catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412($ex2->getMessages());
        }
        catch(FilterParserException $ex3){
            Log::warning($ex3);
            return $this->error412($ex3->getMessages());
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return mixed
     */
    public function getMyMember(){

        $current_member = $this->resource_server_context->getCurrentUser();
        if (is_null($current_member)) return $this->error404();

        $fields    = Request::input('fields', null);
        $relations = Request::input('relations', null);

        return $this->ok
        (
            SerializerRegistry::getInstance()->getSerializer($current_member, SerializerRegistry::SerializerType_Private)
                ->serialize
                (
                    Request::input('expand', ''),
                    is_null($fields) ? [] : explode(',', $fields),
                    is_null($relations) ? [] : explode(',', $relations)
                )
        );
    }


    /**
     * @return mixed
     */
    public function getMyMemberAffiliations(){
        return $this->getMemberAffiliations('me');
    }

    /**
     * @param $member_id
     * @return mixed
     */
    public function getMemberAffiliations($member_id){
        try {

            $member = (strtolower($member_id) == 'me') ?
                $this->resource_server_context->getCurrentUser() :
                $this->repository->getById($member_id);

            if(is_null($member)) return $this->error404();
            $affiliations = $member->getAffiliations()->toArray();

            $response    = new PagingResponse
            (
                count($affiliations),
                count($affiliations),
                1,
                1,
                $affiliations
            );

            return $this->ok($response->toArray($expand = Request::input('expand','')));

        }
        catch (EntityNotFoundException $ex1) {
            Log::warning($ex1);
            return $this->error404();
        }
        catch (ValidationException $ex2) {
            Log::warning($ex2);
            return $this->error412($ex2->getMessages());
        }
        catch(FilterParserException $ex3){
            Log::warning($ex3);
            return $this->error412($ex3->getMessages());
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @return mixed
     */
    public function addMyAffiliation(){
        return $this->addAffiliation('me');
    }

    /**
     * @param $member_id
     * @return mixed
     */
    public function addAffiliation($member_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $data = Request::json();

            $member = (strtolower($member_id) == 'me') ?
                $this->resource_server_context->getCurrentUser() :
                $this->repository->getById($member_id);

            if(is_null($member)) return $this->error404();

            $rules = [
                'is_current'        => 'required|boolean',
                'start_date'        => 'required|date_format:U|valid_epoch',
                'end_date'          => 'sometimes|after_or_null_epoch:start_date',
                'organization_id'   => 'sometimes|integer|required_without:organization_name',
                'organization_name' => 'sometimes|string|max:255|required_without:organization_id',
                'job_title'         => 'sometimes|string|max:255'
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $affiliation = $this->member_service->addAffiliation($member, $data->all());

            return $this->created(SerializerRegistry::getInstance()->getSerializer($affiliation)->serialize
            (
                Request::input('expand','')
            ));
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412([$ex1->getMessage()]);
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(['message'=> $ex2->getMessage()]);
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }


    /**
     * @param $affiliation_id
     * @return mixed
     */
    public function updateMyAffiliation($affiliation_id){
        return $this->updateAffiliation('me', $affiliation_id);
    }

    /**
     * @param int $member_id
     * @param int $affiliation_id
     * @return mixed
     */
    public function updateAffiliation($member_id, $affiliation_id){
        try {
            if(!Request::isJson()) return $this->error400();
            $data = Request::json();

            $member = (strtolower($member_id) == 'me') ?
                $this->resource_server_context->getCurrentUser() :
                $this->repository->getById($member_id);

            if(is_null($member)) return $this->error404();

            $rules = [
                'is_current'        => 'sometimes|boolean',
                'start_date'        => 'sometimes|date_format:U|valid_epoch',
                'end_date'          => 'sometimes|after_or_null_epoch:start_date',
                'organization_id'   => 'sometimes|integer',
                'organization_name' => 'sometimes|string|max:255',
                'job_title'         => 'sometimes|string|max:255'
            ];

            // Creates a Validator instance and validates the data.
            $validation = Validator::make($data->all(), $rules);

            if ($validation->fails()) {
                $messages = $validation->messages()->toArray();

                return $this->error412
                (
                    $messages
                );
            }

            $affiliation = $this->member_service->updateAffiliation($member, $affiliation_id, $data->all());

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($affiliation)->serialize(
                Request::input('expand','')
            ));
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }


    public function deleteMyAffiliation($affiliation_id){
        return $this->deleteAffiliation('me', $affiliation_id);
    }

    /**
     * @param $member_id
     * @param $affiliation_id
     * @return mixed
     */
    public function deleteAffiliation($member_id, $affiliation_id){
        try{

            $member = (strtolower($member_id) == 'me') ?
                $this->resource_server_context->getCurrentUser() :
                $this->repository->getById($member_id);

            if(is_null($member)) return $this->error404();

            $this->member_service->deleteAffiliation($member, $affiliation_id);

            return $this->deleted();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $member_id
     * @param $rsvp_id
     * @return mixed
     */
    public function deleteRSVP($member_id, $rsvp_id){
        try{

            $member = $this->repository->getById($member_id);
            if(is_null($member)) return $this->error404();

            $this->member_service->deleteRSVP($member, $rsvp_id);

            return $this->deleted();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $member_id
     * @return mixed
     */
    public function signFoundationMembership(){
        try{

            $member = $this->resource_server_context->getCurrentUser();

            if(is_null($member)) return $this->error404();

            $member = $this->member_service->signFoundationMembership($member);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer
            (
                $member,
                SerializerRegistry::SerializerType_Private
            )->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $member_id
     * @return mixed
     */
    public function signCommunityMembership(){
        try{

            $member = $this->resource_server_context->getCurrentUser();

            if(is_null($member)) return $this->error404();

            $member = $this->member_service->signCommunityMembership($member);

            return $this->updated(SerializerRegistry::getInstance()->getSerializer
            (
                $member,
                SerializerRegistry::SerializerType_Private
            )->serialize());
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $member_id
     * @return mixed
     */
    public function resignMembership(){
        try{

            $member = $this->resource_server_context->getCurrentUser();

            if(is_null($member)) return $this->error404();

            $this->member_service->resignMembership($member);

            return $this->deleted();
        }
        catch (ValidationException $ex1) {
            Log::warning($ex1);
            return $this->error412(array($ex1->getMessage()));
        }
        catch(EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message'=> $ex2->getMessage()));
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

}