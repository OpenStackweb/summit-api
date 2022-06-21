<?php namespace App\Services\Apis\PaymentGateways;

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

/**
 * @see https://developers.affinipay.com/reference/api.html#EventTypes180
 *
 */
interface ILawPayApiEventType
{
    /**
     * A transaction.authorized event is generated when a transaction has been accepted by the Gateway. For Charges,
     * this Event is generated after successfully authorizing the payment with the payment processing network
     * (checking for availability of funds, etc). The corresponding Transaction is included in the content of the
     * event's data property.
     */
    const TransactionAuthorized = 'transaction.authorized';

    /**
     * After the Gateway successfully completes the processing on a transaction, a transaction.completed
     * Event is generated. This Event indicates the transaction has been captured by the payment processing
     * network associated with the transaction's Account and funds settlement initiated. The corresponding
     * Transaction is included in the content of the Event's data property.
     */
    const TransactionCompleted = 'transaction.completed';

    /**
     * If a transaction fails for any reason after initial authorization (for example, due to a capture failure), the
     * Gateway generates a transaction.failed event. The corresponding transaction is included in the content of
     * the event's data property.
     */
    const TransactionFailed = 'transaction.failed';
}