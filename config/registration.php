<?php
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
use models\summit\IPaymentConstants;

return [
    'reservation_lifetime'                         => env('REGISTRATION_LIFETIME', 30),
    'admin_email'                                  => env('REGISTRATION_ADMIN_EMAIL',null),
    'service_client_id'                            => env('REGISTRATION_SERVICE_OAUTH2_CLIENT_ID', null),
    'service_client_secret'                        => env('REGISTRATION_SERVICE_OAUTH2_CLIENT_SECRET', null),
    'service_client_scopes'                        => env('REGISTRATION_SERVICE_OAUTH2_SCOPES', null),
    'from_email'                                   => env('REGISTRATION_FROM_EMAIL', null),
    'invite_attendee_ticket_edition_email_subject' => env('REGISTRATION_INVITE_ATTENDEE_TICKET_EDITION_EMAIL_SUBJECT', null),
    'registered_member_order_paid_mail_subject'    => env('REGISTRATION_REGISTERED_MEMBER_ORDER_PAID_MAIL_SUBJECT', null),
    'unregistered_member_order_paid_email_subject' => env('REGISTRATION_UNREGISTERED_MEMBER_ORDER_PAID_EMAIL_SUBJECT', null),
    'ticket_public_edit_ttl'                       => env('REGISTRATION_TICKET_PUBLIC_EDIT_TTL', 30),//in minutes
    'support_email'                                => env('REGISTRATION_SUPPORT_EMAIL', null),
    'reminder_email_days_interval'                 => env('REGISTRATION_REMINDER_EMAIL_DAYS_INTERVAL', 7),//in days
    'validate_ticket_type_removal'                 => env('REGISTRATION_VALIDATE_TICKET_TYPE_REMOVAL', true),
    'default_payment_provider'                     => env('REGISTRATION_DEFAULT_PAYMENT_PROVIDER',IPaymentConstants::ProviderStripe),
    'default_payment_provider_config'              => [
        IPaymentConstants::ProviderStripe => [
            'is_test_mode'            => env('REGISTRATION_DEFAULT_STRIPE_TEST_MODE', true),
            'live_secret_key'         => env('REGISTRATION_DEFAULT_LIVE_STRIPE_PRIVATE_KEY', null),
            'live_publishable_key'    => env('REGISTRATION_DEFAULT_LIVE_STRIPE_PUBLISHABLE_KEY', null),
            'live_web_hook_secret'    => env('REGISTRATION_DEFAULT_LIVE_WEBHOOK_SECRET', null),
            'test_secret_key'         => env('REGISTRATION_DEFAULT_TEST_STRIPE_PRIVATE_KEY', null),
            'test_publishable_key'    => env('REGISTRATION_DEFAULT_TEST_STRIPE_PUBLISHABLE_KEY', null),
            'test_web_hook_secret'    => env('REGISTRATION_DEFAULT_TEST_WEBHOOK_SECRET', null),
        ]
    ],
    'send_ticket_attachments' => env('REGISTRATION_SEND_TICKET_ATTACHMENTS', false),
    'admin_ticket_edit_url' => env('REGISTRATION_ADMIN_TICKET_EDIT_URL', null),
    'order_public_edit_ttl' => env('REGISTRATION_ORDER_PUBLIC_EDIT_TTL', 10),
];