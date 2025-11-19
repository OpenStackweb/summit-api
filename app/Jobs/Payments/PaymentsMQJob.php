<?php namespace App\Jobs\Payments;
/**
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

use Illuminate\Support\Facades\Log;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob as BaseJob;

class PaymentsMQJob extends BaseJob
{
    public int $tries = 1;

    /**
     * Get the decoded body of the job.
     *
     * @return array
     */
    public function payload(): array
    {
        $routing_key = $this->getRabbitMQMessage()->getRoutingKey();
        Log::debug("PaymentsMQJob::payload processing job", ['routing_key' => $routing_key]);
        switch ($routing_key) {
            case EventTypes::PAYMENT_PROFILE_CREATED:
                $job = 'App\Jobs\Payments\CreatePaymentProfileMQJob@handle';
                break;
            case EventTypes::PAYMENT_PROFILE_UPDATED:
                $job = 'App\Jobs\Payments\UpdatePaymentProfileMQJob@handle';
                break;
            case EventTypes::PAYMENT_PROFILE_DELETED:
                $job = 'App\Jobs\Payments\DeletePaymentProfileMQJob@handle';
                break;
            default:
                Log::warning('Received an unknown routing key', ['routing_key' => $routing_key, 'message' => $this->getRawBody()]);
                return [];
        }
        return [
            'job' => $job,
            'data' => json_decode($this->getRawBody(), true)
        ];
    }
}
