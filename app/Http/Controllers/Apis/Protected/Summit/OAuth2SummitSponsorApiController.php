<?php namespace App\Http\Controllers;
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
use App\Models\Foundation\Summit\Repositories\ISponsorRepository;
use Illuminate\Support\Facades\Log;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\oauth2\IResourceServerContext;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\utils\IEntity;
use ModelSerializers\SerializerRegistry;
use services\model\ISummitSponsorService;
/**
 * Class OAuth2SummitSponsorApiController
 * @package App\Http\Controllers
 */
final class OAuth2SummitSponsorApiController extends OAuth2ProtectedController
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitSponsorService
     */
    private $service;

    /**
     * OAuth2SummitSponsorApiController constructor.
     * @param ISponsorRepository $repository
     * @param ISummitRepository $summit_repository
     * @param ISummitSponsorService $service
     * @param IResourceServerContext $resource_server_context
     */
    public function __construct
    (
        ISponsorRepository $repository,
        ISummitRepository $summit_repository,
        ISummitSponsorService $service,
        IResourceServerContext $resource_server_context
    )
    {
        parent::__construct($resource_server_context);
        $this->summit_repository = $summit_repository;
        $this->service = $service;
        $this->repository = $repository;
    }


    /**
     * @return array
     */
    protected function getFilterRules():array{
        return [
            'company_name'      => ['==', '=@'],
            'sponsorship_name'  => ['==', '=@'],
            'sponsorship_size'  => ['==', '=@'],
            'badge_scans_count' => ['==', '<','>','<=','>=','<>'],
        ];
    }

    /**
     * @return array
     */
    protected function getFilterValidatorRules():array{
        return [
            'company_name'      => 'sometimes|required|string',
            'sponsorship_name'  => 'sometimes|required|string',
            'sponsorship_size'  => 'sometimes|required|string',
            'badge_scans_count' => 'sometimes|required|integer',
        ];
    }
    /**
     * @return array
     */
    protected function getOrderRules():array{
        return [
            'id',
            'order',
        ];
    }

    use GetAllBySummit;

    use GetSummitChildElementById;

    use AddSummitChildElement;

    use UpdateSummitChildElement;

    use DeleteSummitChildElement;

    /**
     * @param array $payload
     * @return array
     */
    function getAddValidationRules(array $payload): array
    {
        return SponsorValidationRulesFactory::build($payload);
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return IEntity
     */
    protected function addChild(Summit $summit, array $payload): IEntity
    {
        return $this->service->addSponsor($summit, $payload);
    }

    /**
     * @return ISummitRepository
     */
    protected function getSummitRepository(): ISummitRepository
    {
        return $this->summit_repository;
    }

    /**
     * @param Summit $summit
     * @param $child_id
     * @return void
     */
    protected function deleteChild(Summit $summit, $child_id): void
    {
        $this->service->deleteSponsor($summit, $child_id);
    }

    protected function getChildFromSummit(Summit $summit, $child_id): ?IEntity
    {
       return $summit->getSummitSponsorById($child_id);
    }

    /**
     * @param array $payload
     * @return array
     */
    function getUpdateValidationRules(array $payload): array
    {
        return SponsorValidationRulesFactory::build($payload, true);
    }

    /**
     * @param Summit $summit
     * @param int $child_id
     * @param array $payload
     * @return IEntity
     */
    protected function updateChild(Summit $summit, int $child_id, array $payload): IEntity
    {
        return $this->service->updateSponsor($summit, $child_id, $payload);
    }

    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $member_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function addSponsorUser($summit_id, $sponsor_id, $member_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $sponsor = $this->service->addSponsorUser($summit, $sponsor_id, $member_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($sponsor)->serialize());
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
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }

    /**
     * @param $summit_id
     * @param $sponsor_id
     * @param $member_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function removeSponsorUser($summit_id, $sponsor_id, $member_id){
        try {
            $summit = SummitFinderStrategyFactory::build($this->getSummitRepository(), $this->getResourceServerContext())->find($summit_id);
            if (is_null($summit)) return $this->error404();

            $sponsor = $this->service->removeSponsorUser($summit, $sponsor_id, $member_id);
            return $this->updated(SerializerRegistry::getInstance()->getSerializer($sponsor)->serialize());
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
        catch (\Exception $ex) {
            Log::error($ex);
            return $this->error500($ex);
        }
    }
}