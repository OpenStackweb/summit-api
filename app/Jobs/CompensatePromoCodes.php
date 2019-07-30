<?php namespace App\Jobs;
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
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\ValidationException;
use models\summit\ISummitRegistrationPromoCodeRepository;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\summit\SummitRegistrationPromoCode;
/**
 * Class CompensatePromoCodes
 * @package App\Jobs
 */
class CompensatePromoCodes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    /**
     * @var string
     */
    private $code;

    /**
     * @var int
     */
    private $qty_to_return;

    /**
     * @var int
     */
    private $summit_id;

    /**
     * CompensatePromoCodes constructor.
     * @param Summit $summit
     * @param string $code
     * @param int $qty_to_return
     */
    public function __construct(Summit $summit, string $code, int $qty_to_return)
    {
        $this->summit_id     = $summit->getId();
        $this->code          = $code;
        $this->qty_to_return = $qty_to_return;
    }

    /**
     * @param ISummitRepository $summit_repository
     * @param ISummitRegistrationPromoCodeRepository $repository
     * @param ITransactionService $tx_service
     * @throws \Exception
     */
    public function handle
    (
        ISummitRepository $summit_repository,
        ISummitRegistrationPromoCodeRepository $repository,
        ITransactionService $tx_service
    )
    {
        $tx_service->transaction(function() use($summit_repository, $repository){
            $summit = $summit_repository->getById($this->summit_id);
            if(is_null($summit) || ! $summit instanceof Summit) return;
            $promo_code = $repository->getByValueExclusiveLock($summit, $this->code);
            if(is_null($promo_code) || !$promo_code instanceof SummitRegistrationPromoCode) return;
            Log::debug(sprintf("CompensatePromoCodes::handle: compensating promo code %s on %s usages", $this->code,  $this->qty_to_return));
            try {
                $promo_code->removeUsage($this->qty_to_return);
            }
            catch (ValidationException $ex){
                Log::error($ex);
            }
        });
    }
}
