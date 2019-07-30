<?php namespace models\summit;
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

/**
 * Interface IOrderConstants
 * @package models\summit
 */
interface IOrderConstants
{
    const ReservedStatus        = 'Reserved';
    const CancelledStatus       = 'Cancelled';
    const RefundRequestedStatus = 'RefundRequested';
    const RefundedStatus        = 'Refunded';
    const ConfirmedStatus       = 'Confirmed';
    const PaidStatus            = 'Paid';
    const ErrorStatus           = 'Error';

    const OnlinePaymentMethod   = 'Online';
    const OfflinePaymentMethod  = 'Offline';

    const ValidStatus = [
        self::ReservedStatus,
        self::CancelledStatus,
        self::RefundRequestedStatus,
        self::RefundedStatus,
        self::ConfirmedStatus,
        self::PaidStatus,
        self::ErrorStatus
    ];
}