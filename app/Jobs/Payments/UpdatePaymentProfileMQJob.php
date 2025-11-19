<?php namespace App\Jobs\Payments;
/*
 * Copyright 2025 OpenStack Foundation
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

use App\Models\Foundation\Summit\Repositories\IPaymentGatewayProfileRepository;
use App\Services\Apis\IPaymentsApi;
use App\Services\Model\Imp\PaymentGatewayProfileService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use models\summit\ISummitRepository;
use models\summit\PaymentGatewayProfile;
use models\summit\Summit;

class UpdatePaymentProfileMQJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    private ISummitRepository $summit_repository;

    private IPaymentGatewayProfileRepository $payment_gateway_profile_repository;

    private PaymentGatewayProfileService $service;

    private IPaymentsApi $payments_api;

    /**
     * @param ISummitRepository $summit_repository
     * @param IPaymentGatewayProfileRepository $payment_gateway_profile_repository
     * @param PaymentGatewayProfileService $service
     * @param IPaymentsApi $payments_api
     */
    public function __construct
    (
        ISummitRepository $summit_repository,
        IPaymentGatewayProfileRepository $payment_gateway_profile_repository,
        PaymentGatewayProfileService $service,
        IPaymentsApi $payments_api
    ){
        $this->summit_repository = $summit_repository;
        $this->payment_gateway_profile_repository = $payment_gateway_profile_repository;
        $this->service = $service;
        $this->payments_api = $payments_api;
    }

    public function handle(PaymentsMQJob $job): void{
        try {
            $payload = $job->payload();
            $json = json_encode($payload);
            Log::debug("UpdatePaymentProfileMQJob::handle", ['payload' => $json ]);

            $data = $payload['data'];
            $id = intval($data['id']);
            $summit_id = intval($data['summit_id']);
            $response = $this->payments_api->getPaymentProfile($summit_id, $id);
            Log::debug("UpdatePaymentProfileMQJob::handle", ['response' => $response]);
            $summit = $this->summit_repository->getById($summit_id);
            $local_payment_profile =$this->payment_gateway_profile_repository->getByExternalId($id);
            if($summit instanceof Summit && $local_payment_profile instanceof PaymentGatewayProfile) {
                $local_payment_profile_id = $local_payment_profile->getId();
                // mappings
                $response['external_id'] = $id;
                $response['active'] = $response['is_active'] ?? false;
                Log::debug("UpdatePaymentProfileMQJob::handle updating local profile", ['local_payment_profile_id' => $local_payment_profile_id]);
                $this->service->updatePaymentProfile($summit, $local_payment_profile_id, $response);
            }
            $job->delete();
        } catch (\Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }
}
