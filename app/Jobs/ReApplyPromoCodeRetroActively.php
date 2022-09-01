<?php namespace App\Jobs;
/*
 * Copyright 2022 OpenStack Foundation
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
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;
use services\model\ISummitPromoCodeService;

/**
 * Class ReApplyPromoCodeRetroActively
 * @package App\Jobs
 */
final class ReApplyPromoCodeRetroActively implements ShouldQueue
{
    public $tries = 1;

    public $timeout = 0;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    private $promo_code_id;

    /**
     * @param int $promo_code_id
     */
    public function __construct(int $promo_code_id)
    {
        $this->promo_code_id = $promo_code_id;
    }

    /**
     * @param ISummitPromoCodeService $service
     * @throws \models\exceptions\EntityNotFoundException
     */
    public function handle(ISummitPromoCodeService $service){
        Log::error(sprintf( "ReApplyPromoCodeRetroActively::handle promo code id %s", $this->promo_code_id));
        $service->reApplyPromoCode($this->promo_code_id);
    }

    public function failed(Exception $exception)
    {
        Log::error($exception);
    }
}