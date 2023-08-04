<?php namespace Tests;
/**
 * Copyright 2023 OpenStack Foundation
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
 * Class PaymentGatewayWebHookTests
 */
class PaymentGatewayWebHookTests extends BrowserKitTestCase
{
    public function testConfirm(){
        $params = [
            'id' => 3648,
            'application_name' => 'Registration',
        ];

        $payload = [
            "id" => "evt_3NVyeLF5nafJ7hGB0HgiTAu0",
            "object" => "event",
            "api_version" => "2019-05-16",
            "created" => 1689866432,
            "data" => [
                "object" => [
                    "id" => "pi_3NVyeLF5nafJ7hGB0WbaxrXw",
                    "object" => "payment_intent",
                    "amount" => 1000,
                    "amount_capturable" => 0,
                    "amount_details" => ["tip" => []],
                    "amount_received" => 1000,
                    "application" => null,
                    "application_fee_amount" => null,
                    "automatic_payment_methods" => null,
                    "canceled_at" => null,
                    "cancellation_reason" => null,
                    "capture_method" => "automatic",
                    "charges" => [
                        "object" => "list",
                        "data" => [
                            [
                                "id" => "ch_3NVyeLF5nafJ7hGB0NHo1I7M",
                                "object" => "charge",
                                "amount" => 1000,
                                "amount_captured" => 1000,
                                "amount_refunded" => 0,
                                "application" => null,
                                "application_fee" => null,
                                "application_fee_amount" => null,
                                "balance_transaction" => "txn_3NVyeLF5nafJ7hGB0wqkHCzN",
                                "billing_details" => [
                                    "address" => [
                                        "city" => "Temperley",
                                        "country" => "AR",
                                        "line1" => "Solis 491",
                                        "line2" => "",
                                        "postal_code" => "1234",
                                        "state" => "BA",
                                    ],
                                    "email" => "smarcet@gmail.com",
                                    "name" => "Sebastian Marcet Gomez",
                                    "phone" => null,
                                ],
                                "calculated_statement_descriptor" => "Stripe",
                                "captured" => true,
                                "created" => 1689866432,
                                "currency" => "usd",
                                "customer" => null,
                                "description" => null,
                                "destination" => null,
                                "dispute" => null,
                                "disputed" => false,
                                "failure_balance_transaction" => null,
                                "failure_code" => null,
                                "failure_message" => null,
                                "fraud_details" => [],
                                "invoice" => null,
                                "livemode" => false,
                                "metadata" => [
                                    "summit_id" => "13",
                                    "type" => "Registration",
                                ],
                                "on_behalf_of" => null,
                                "order" => null,
                                "outcome" => [
                                    "network_status" => "approved_by_network",
                                    "reason" => null,
                                    "risk_level" => "normal",
                                    "risk_score" => 46,
                                    "seller_message" => "Payment complete.",
                                    "type" => "authorized",
                                ],
                                "paid" => true,
                                "payment_intent" => "pi_3NVyeLF5nafJ7hGB0WbaxrXw",
                                "payment_method" => "pm_1NVyeZF5nafJ7hGBflotHYty",
                                "payment_method_details" => [
                                    "card" => [
                                        "brand" => "visa",
                                        "checks" => [
                                            "address_line1_check" => "pass",
                                            "address_postal_code_check" => "pass",
                                            "cvc_check" => "pass",
                                        ],
                                        "country" => "US",
                                        "exp_month" => 12,
                                        "exp_year" => 2033,
                                        "fingerprint" => "Gdsem6nF7SOMHiCq",
                                        "funding" => "credit",
                                        "installments" => null,
                                        "last4" => "4242",
                                        "mandate" => null,
                                        "network" => "visa",
                                        "network_token" => ["used" => false],
                                        "three_d_secure" => null,
                                        "wallet" => null,
                                    ],
                                    "type" => "card",
                                ],
                                "receipt_email" => null,
                                "receipt_number" => null,
                                "receipt_url" => "",
                                "refunded" => false,
                                "refunds" => [
                                    "object" => "list",
                                    "data" => [],
                                    "has_more" => false,
                                    "total_count" => 0,
                                    "url" =>
                                        "/v1/charges/ch_3NVyeLF5nafJ7hGB0NHo1I7M/refunds",
                                ],
                                "review" => null,
                                "shipping" => null,
                                "source" => null,
                                "source_transfer" => null,
                                "statement_descriptor" => null,
                                "statement_descriptor_suffix" => null,
                                "status" => "succeeded",
                                "transfer_data" => null,
                                "transfer_group" => null,
                            ],
                        ],
                        "has_more" => false,
                        "total_count" => 1,
                        "url" =>
                            "/v1/charges?payment_intent=pi_3NVyeLF5nafJ7hGB0WbaxrXw",
                    ],
                    "client_secret" =>
                        "pi_3NVyeLF5nafJ7hGB0WbaxrXw_secret_a7NfMWVvq43vQLCtnm3HlcIF3",
                    "confirmation_method" => "automatic",
                    "created" => 1689866417,
                    "currency" => "usd",
                    "customer" => null,
                    "description" => null,
                    "invoice" => null,
                    "last_payment_error" => null,
                    "latest_charge" => "ch_3NVyeLF5nafJ7hGB0NHo1I7M",
                    "livemode" => false,
                    "metadata" => ["summit_id" => "13", "type" => "Registration"],
                    "next_action" => null,
                    "on_behalf_of" => null,
                    "payment_method" => "pm_1NVyeZF5nafJ7hGBflotHYty",
                    "payment_method_options" => [
                        "card" => [
                            "installments" => null,
                            "mandate_options" => null,
                            "network" => null,
                            "request_three_d_secure" => "automatic",
                        ],
                    ],
                    "payment_method_types" => ["card"],
                    "processing" => null,
                    "receipt_email" => null,
                    "review" => null,
                    "setup_future_usage" => null,
                    "shipping" => null,
                    "source" => null,
                    "statement_descriptor" => null,
                    "statement_descriptor_suffix" => null,
                    "status" => "succeeded",
                    "transfer_data" => null,
                    "transfer_group" => null,
                ],
            ],
            "livemode" => false,
            "pending_webhooks" => 1,
            "request" => [
                "id" => "req_oMhrrogDEgPrHN",
                "idempotency_key" => "562c5e8b-f4f6-482c-aea0-233473d341ca",
            ],
            "type" => "payment_intent.succeeded",
        ];

        $response = $this->action
        (
            "POST",
            "PaymentGatewayWebHookController@confirm",
            $params,
            $payload,
            [],
            [],
            [],
            json_encode($payload)
        );

        $content = $response->getContent();
        $this->assertResponseStatus(201);
    }
}