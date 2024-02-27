<?php namespace App\ModelSerializers\Summit\Registration\Refunds;
/*
 * Copyright 2024 OpenStack Foundation
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

use Libs\ModelSerializers\One2ManyExpandSerializer;
use ModelSerializers\SilverStripeSerializer;
/**
 * Class SummitTaxRefundSerializer
 * @package App\ModelSerializers\Summit\Registration\Refunds
 */
final class SummitTaxRefundSerializer extends SilverStripeSerializer
{
    protected static $array_mappings = [
        'TaxId' => 'tax_id:json_int',
        'RefundRequestId' => 'refund_request_id:json_int',
        'RefundedAmount' => 'refunded_amount:json_float',
    ];

    protected static $expand_mappings = [
        'refund_request' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'refund_request_id',
            'getter' => 'getRefundRequest',
            'has' => 'hasRefundRequest'
        ],
        'tax' => [
            'type' => One2ManyExpandSerializer::class,
            'original_attribute' => 'tax_id',
            'getter' => 'getTax',
            'has' => 'hasTax'
        ],
    ];
}