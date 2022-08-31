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
use App\Http\Utils\BooleanCellFormatter;
use App\Http\Utils\EpochCellFormatter;
use App\Http\Utils\PagingConstants;
use App\Models\Foundation\Summit\PromoCodes\PromoCodesConstants;
use App\ModelSerializers\SerializerUtils;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use services\model\ISummitPromoCodeService;
use utils\Filter;
use utils\FilterParser;
use utils\OrderParser;
use Illuminate\Support\Facades\Validator;
use utils\PagingInfo;
use Exception;
use Illuminate\Http\Request as LaravelRequest;
/**
 * Class OAuth2SummitPromoCodesApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitPromoCodesApiController extends OAuth2ProtectedController
{

    /**
     * @var ISummitRegistrationPromoCodeRepository
     */
    private $promo_code_repository;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var ISummitPromoCodeService
     */
    private $promo_code_service;

    use RequestProcessor;

    use GetAndValidateJsonPayload;

    use ParametrizedGetAll;

    /**
     * OAuth2SummitPromoCodesApiController constructor.
     * @param ISummitRepository $summit_repository
     * @param ISummitRegistrationPromoCodeRepository $promo_code_repository
     * @param IMemberRepository $member_repository
     * @param ISummitPromoCodeService $promo_code_service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        ISummitRegistrationPromoCodeRepository $promo_code_repository,
        IMemberRepository $member_repository,
        ISummitPromoCodeService $promo_code_service,
        IResourceServerContext $resource_server_context
    ) {
        parent::__construct($resource_server_context);
        $this->promo_code_service    = $promo_code_service;
        $this->promo_code_repository = $promo_code_repository;
        $this->summit_repository     = $summit_repository;
        $this->member_repository     = $member_repository;
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getAllBySummit($summit_id){
        $values = Request::all();
        $rules  = [

            'page'     => 'integer|min:1',
            'per_page' => sprintf('required_with:page|integer|min:%s|max:%s', PagingConstants::MinPageSize, PagingConstants::MaxPageSize),
        ];

        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

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
                $filter = FilterParser::parse(Request::input('filter'), [

                    'code'          => ['=@', '=='],
                    'creator'       => ['=@', '=='],
                    'creator_email' => ['=@', '=='],
                    'owner'         => ['=@', '=='],
                    'owner_email'   => ['=@', '=='],
                    'speaker'       => ['=@', '=='],
                    'speaker_email' => ['=@', '=='],
                    'sponsor'       => ['=@', '=='],
                    'class_name'    => ['=='],
                    'type'          => ['=='],
                ]);
            }

            if(is_null($filter)) $filter = new Filter();

            $filter->validate([
                'class_name'    => sprintf('sometimes|in:%s',implode(',', PromoCodesConstants::$valid_class_names)),
                'code'          => 'sometimes|string',
                'creator'       => 'sometimes|string',
                'creator_email' => 'sometimes|string',
                'owner'         => 'sometimes|string',
                'owner_email'   => 'sometimes|string',
                'speaker'       => 'sometimes|string',
                'speaker_email' => 'sometimes|string',
                'sponsor'       => 'sometimes|string',
                'type'          => sprintf('sometimes|in:%s',implode(',', PromoCodesConstants::getValidTypes())),
            ], [
                'class_name.in' =>  sprintf
                (
                    ":attribute has an invalid value ( valid values are %s )",
                    implode(", ", PromoCodesConstants::$valid_class_names)
                ),
                'type.in' =>  sprintf
                (
                    ":attribute has an invalid value ( valid values are %s )",
                    implode(", ", PromoCodesConstants::getValidTypes())
                )
            ]);

            $order = null;

            if (Request::has('order'))
            {
                $order = OrderParser::parse(Request::input('order'), [

                    'id',
                    'code',
                ]);
            }

            $data      = $this->promo_code_repository->getBySummit($summit, new PagingInfo($page, $per_page), $filter, $order);

            return $this->ok
            (
                $data->toArray
                (
                    Request::input('expand', ''),
                    [],
                    [],
                    [ 'serializer_type' => SerializerRegistry::SerializerType_Private ]
                )
            );
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array( $ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch(\HTTP401UnauthorizedException $ex3)
        {
            Log::warning($ex3);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @return \Illuminate\Http\Response|mixed
     */
    public function getAllBySummitCSV($summit_id){
        $values = Request::all();
        $rules  = [];
        $allowed_columns = [
            "id",
            "created",
            "last_edited",
            "code",
            "redeemed",
            "email_sent",
            "source",
            "summit_id",
            "creator_id",
            "class_name",
            "type",
            "speaker_id",
            "owner_name",
            "owner_email",
            "sponsor_name"
        ];

        try {

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $validation = Validator::make($values, $rules);

            if ($validation->fails()) {
                $ex = new ValidationException();
                throw $ex->setMessages($validation->messages()->toArray());
            }

            // default values
            $page     = 1;
            $per_page = PHP_INT_MAX;
            $filter   = null;

            if (Request::has('filter')) {
                $filter = FilterParser::parse(Request::input('filter'), [
                    'code'          => ['=@', '=='],
                    'creator'       => ['=@', '=='],
                    'creator_email' => ['=@', '=='],
                    'owner'         => ['=@', '=='],
                    'owner_email'   => ['=@', '=='],
                    'speaker'       => ['=@', '=='],
                    'speaker_email' => ['=@', '=='],
                    'sponsor'       => ['=@', '=='],
                    'class_name'    => ['=='],
                    'type'          => ['=='],
                ]);
            }

            if(is_null($filter)) $filter = new Filter();

            $filter->validate([
                'class_name'    => sprintf('sometimes|in:%s',implode(',', PromoCodesConstants::$valid_class_names)),
                'code'          => 'sometimes|string',
                'creator'       => 'sometimes|string',
                'creator_email' => 'sometimes|string',
                'owner'         => 'sometimes|string',
                'owner_email'   => 'sometimes|string',
                'speaker'       => 'sometimes|string',
                'speaker_email' => 'sometimes|string',
                'sponsor'       => 'sometimes|string',
                'type'          => sprintf('sometimes|in:%s',implode(',', PromoCodesConstants::getValidTypes())),
            ], [
                'class_name.in' =>  sprintf
                (
                    ":attribute has an invalid value ( valid values are %s )",
                    implode(", ", PromoCodesConstants::$valid_class_names)
                ),
                'type.in' =>  sprintf
                (
                    ":attribute has an invalid value ( valid values are %s )",
                    implode(", ", PromoCodesConstants::getValidTypes())
                )
            ]);

            $order = null;

            if (Request::has('order'))
            {
                $order = OrderParser::parse(Request::input('order'), [

                    'id',
                    'code',
                ]);
            }
            $columns_param = Request::input("columns", "");
            $columns = [];
            if(!empty($columns_param))
                $columns  = explode(',', $columns_param);
            $diff     = array_diff($columns, $allowed_columns);
            if(count($diff) > 0){
                throw new ValidationException(sprintf("columns %s are not allowed!", implode(",", $diff)));
            }

            $data     = $this->promo_code_repository->getBySummit($summit, new PagingInfo($page, $per_page), $filter, $order);
            $filename = "promocodes-" . date('Ymd');
            $list     = $data->toArray(Request::input("expand", ""));

            return $this->export
            (
                'csv',
                $filename,
                $list['data'],
                [
                    'created'     => new EpochCellFormatter,
                    'last_edited' => new EpochCellFormatter,
                    'redeemed'    => new BooleanCellFormatter,
                    'email_sent'  => new BooleanCellFormatter,
                ],
                $columns
            );
        }
        catch (ValidationException $ex1)
        {
            Log::warning($ex1);
            return $this->error412(array( $ex1->getMessage()));
        }
        catch (EntityNotFoundException $ex2)
        {
            Log::warning($ex2);
            return $this->error404(array('message' => $ex2->getMessage()));
        }
        catch(\HTTP401UnauthorizedException $ex3)
        {
            Log::warning($ex3);
            return $this->error401();
        }
        catch (Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function getMetadata($summit_id){
        $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
        if (is_null($summit)) return $this->error404();

        return $this->ok
        (
            $this->promo_code_repository->getMetadata($summit)
        );
    }

    /**
     * @param $summit_id
     * @return mixed
     */
    public function addPromoCodeBySummit($summit_id){

        return $this->processRequest(function() use($summit_id){

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(PromoCodesValidationRulesFactory::buildForAdd($this->getJsonData()),true);

            $promo_code = $this->promo_code_service->addPromoCode($summit, $payload, $this->resource_server_context->getCurrentUser());

            return $this->created(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $promo_code_id
     * @return mixed
     */
    public function updatePromoCodeBySummit($summit_id, $promo_code_id)
    {
        return $this->processRequest(function() use($summit_id, $promo_code_id){

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(PromoCodesValidationRulesFactory::buildForUpdate($this->getJsonData()),true);

            $promo_code = $this->promo_code_service->updatePromoCode($summit, intval($promo_code_id), $payload, $this->resource_server_context->getCurrentUser());

            return $this->updated(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
            
        });
    }

    /**
     * @param $summit_id
     * @param $promo_code_id
     * @return mixed
     */
    public function deletePromoCodeBySummit($summit_id, $promo_code_id)
    {
        return $this->processRequest(function() use($summit_id, $promo_code_id){

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $this->promo_code_service->deletePromoCode($summit, intval($promo_code_id));

            return $this->deleted();
        });
    }

    /**
     * @param $summit_id
     * @param $promo_code_id
     * @return mixed
     */
    public function sendPromoCodeMail($summit_id, $promo_code_id)
    {
        return $this->processRequest(function() use($summit_id, $promo_code_id){
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $this->promo_code_service->sendPromoCodeMail($summit, intval($promo_code_id));
            return $this->ok();
        });
    }

    /**
     * @param $summit_id
     * @param $promo_code_id
     * @return mixed
     */
    public function getPromoCodeBySummit($summit_id, $promo_code_id)
    {
        return $this->processRequest(function() use($summit_id, $promo_code_id){
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $promo_code = $summit->getPromoCodeById(intval($promo_code_id));
            if(is_null($promo_code))
                return $this->error404();
            return $this->ok(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $promo_code_id
     * @param $badge_feature_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addBadgeFeatureToPromoCode($summit_id, $promo_code_id, $badge_feature_id){

        return $this->processRequest(function() use($summit_id, $promo_code_id, $badge_feature_id){
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $promo_code = $this->promo_code_service->addPromoCodeBadgeFeature($summit, intval($promo_code_id), intval($badge_feature_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }


    /**
     * @param $summit_id
     * @param $promo_code_id
     * @param $badge_feature_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeBadgeFeatureFromPromoCode($summit_id, $promo_code_id, $badge_feature_id){

        return $this->processRequest(function() use($summit_id, $promo_code_id, $badge_feature_id){
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $promo_code = $this->promo_code_service->removePromoCodeBadgeFeature($summit, intval($promo_code_id), intval($badge_feature_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    /**
     * @param $summit_id
     * @param $promo_code_id
     * @param $ticket_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addTicketTypeToPromoCode($summit_id, $promo_code_id, $ticket_type_id){

        return $this->processRequest(function() use($summit_id, $promo_code_id, $ticket_type_id){

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $payload = $this->getJsonPayload(
                [
                    'amount'     => 'sometimes|required_without:rate|numeric|min:0',
                    'rate'       => 'sometimes|required_without:amount|numeric|min:0',
                ]
            );

            $promo_code = $this->promo_code_service->addPromoCodeTicketTypeRule($summit, intval($promo_code_id), intval($ticket_type_id), $payload);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }


    /**
     * @param $summit_id
     * @param $promo_code_id
     * @param $ticket_type_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeTicketTypeFromPromoCode($summit_id, $promo_code_id, $ticket_type_id){

        return $this->processRequest(function() use($summit_id, $promo_code_id, $ticket_type_id){
            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->resource_server_context)->find($summit_id);
            if (is_null($summit)) return $this->error404();
            $promo_code = $this->promo_code_service->removePromoCodeTicketTypeRule($summit, intval($promo_code_id), intval($ticket_type_id));
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($promo_code)->serialize(
                SerializerUtils::getExpand(),
                SerializerUtils::getFields(),
                SerializerUtils::getRelations(),
            ));
        });
    }

    /**
     * @param LaravelRequest $request
     * @param $summit_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function ingestPromoCodes(LaravelRequest $request, $summit_id){
        return $this->processRequest(function() use($summit_id,$request){

            $summit = SummitFinderStrategyFactory::build($this->summit_repository, $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $current_member = $this->resource_server_context->getCurrentUser();
            if (is_null($current_member)) return $this->error403();

            $file = $request->file('file');
            if (is_null($file)) {
                return $this->error412(array('file param not set!'));
            }

            $this->promo_code_service->importPromoCodes($summit, $file, $this->resource_server_context->getCurrentUser());
            return $this->ok();

        });
    }

}