<?php namespace App\Services\Apis\PaymentGateways;
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

use App\Services\Apis\CartAlreadyPaidException;
use App\Services\Apis\IPaymentGatewayAPI;
use Illuminate\Http\Request as LaravelRequest;
use models\exceptions\ValidationException;
use Stripe\Charge;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Event;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\WebhookSignature;
use Stripe\WebhookEndpoint;
use Illuminate\Support\Facades\Log;

/**
 * Class StripesApi
 * @package App\Services\Apis\PaymentGateways
 */
final class StripeApi implements IPaymentGatewayAPI
{
    const Version = '2019-12-03';

    /**
     * @var string
     */
    private $secret_key;

    /**
     * @var string
     */
    private $webhook_secret_key;

    /**
     * @var bool
     */
    private $send_email_receipt;

    /**
     * StripeApi constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->secret_key = $config['secret_key'] ?? null;
        $this->webhook_secret_key = $config['webhook_secret_key'] ?? null;
        $this->send_email_receipt = false;
        if(isset($config['send_email_receipt']))
            $this->send_email_receipt = boolval($config['send_email_receipt']);

        Log::debug
        (
            sprintf
            (
                "StripeApi::__construct secret_key %s  webhook_secret_key %s",
                $this->secret_key,
                $this->webhook_secret_key
            )
        );
    }

    /**
     * @param string $webhook_secret_key
     */
    public function setWebHookSecretKey(string $webhook_secret_key):void{
        $this->webhook_secret_key = $webhook_secret_key;
    }

    /**
     * @param array $payload
     * @return array
     */
    public function generatePayment(array $payload): array
    {
        if (empty($this->secret_key))
            throw new \InvalidArgumentException();

        Stripe::setApiKey($this->secret_key);
        Stripe::setApiVersion(self::Version);

        $amount = $payload['amount'];
        $currency = $payload['currency'];

        if (!self::isZeroDecimalCurrency($currency)) {
            /**
             * All API requests expect amounts to be provided in a currency’s smallest unit. For example,
             * to charge $10 USD, provide an amount value of 1000 (i.e, 1000 cents).
             * For zero-decimal currencies, still provide amounts as an integer but without multiplying by 100.
             * For example, to charge ¥500, simply provide an amount value of 500.
             */
            $amount = $amount * 100;
        }

        $request = [
            'amount' => intval($amount),
            'currency' => $currency,
            'payment_method_types' => ['card'],
        ];

        // check setting to send stripe invoice
        // @see https://stripe.com/docs/receipts
        if (isset($payload['receipt_email']) && $this->send_email_receipt) {
            Log::debug(sprintf("StripeApi::generatePayment setting receipt_email to %s", $payload['receipt_email']));
            $request['receipt_email'] = trim($payload['receipt_email']);
        }

        if (isset($payload['metadata'])) {
            $request['metadata'] = $payload['metadata'];
        }

        Log::debug(sprintf("StripeApi::generatePayment creating payment intent %s", json_encode($request)));

        $intent = PaymentIntent::create($request);
        Log::debug(sprintf("StripeApi::generatePayment intent id %s", $intent->id));
        return [
            'client_token' => $intent->client_secret,
            'cart_id' => $intent->id,
        ];
    }

    /**
     * @param string $currency
     * @return bool
     * @see https://stripe.com/docs/currencies#zero-decimal
     */
    private static function isZeroDecimalCurrency(string $currency): bool
    {
        $zeroDecimalCurrencies = [
            'JPY',
            'BIF',
            'CLP',
            'DJF',
            'GNF',
            'KMF',
            'KRW',
            'MGA',
            'PYG',
            'RWF',
            'UGX',
            'VND',
            'VUV',
            'XAF',
            'XOF',
            'XPF',
        ];
        return in_array($currency, $zeroDecimalCurrencies);
    }

    /**
     * @param LaravelRequest $request
     * @return array
     * @throws SignatureVerificationException
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    public function processCallback(LaravelRequest $request): array
    {
        try {

            $signature = $request->header('Stripe-Signature');
            $requestContent = $request->getContent();
            Log::debug
            (
                sprintf
                (
                    "StripeApi::processCallback Stripe-Signature %s requestContent %s webHook Secret %s",
                    $signature,
                    $requestContent,
                    $this->webhook_secret_key
                )
            );

            WebhookSignature::verifyHeader(
                $requestContent,
                $signature,
                $this->webhook_secret_key
            );

            $event = Event::constructFrom(json_decode($requestContent, true));
            if (!in_array($event->type, ["payment_intent.succeeded", "payment_intent.payment_failed"]))
                throw new \InvalidArgumentException();

            $intent = $event->data->object;
            if ($event->type == "payment_intent.succeeded") {
                Log::debug("StripeApi::processCallback: payment_intent.succeeded");
                return [
                    "event_type" => $event->type,
                    "cart_id" => $intent->id,
                ];
            }

            if ($event->type == "payment_intent.payment_failed") {
                Log::debug("StripeApi::processCallback: payment_intent.payment_failed");
                $intent = $event->data->object;
                return [
                    "event_type" => $event->type,
                    "cart_id" => $intent->id,
                    "error" => [
                        "last_payment_error" => $intent->last_payment_error,
                        "message" => $intent->last_payment_error->message
                    ]
                ];
            }

            throw new ValidationException(sprintf("event type %s not handled!", $event->type));
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            throw $e;
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param array $payload
     * @return bool
     */
    public function isSuccessFullPayment(array $payload): bool
    {
        if (isset($payload['event_type']) && $payload['event_type'] == "payment_intent.succeeded") return true;
        Log::debug("StripeApi::isSuccessFullPayment false");
        return false;
    }

    /**
     * @param array $payload
     * @return string
     */
    public function getPaymentError(array $payload): ?string
    {
        if (isset($payload['event_type']) && $payload['event_type'] == "payment_intent.payment_failed") {
            if (isset($payload['error'])) {
                $error = $payload['error'];
                if (isset($error["message"])) {
                    return $error["message"];
                }
            }
        }
        return null;
    }

    /**
     * @param string $cart_id
     * @param float $amount
     * @param string $currency
     * @throws \InvalidArgumentException
     */
    public function refundPayment(string $cart_id, float $amount, string $currency): void
    {
        try {

            Log::debug(sprintf("StripeApi::refundPayment calling cart_id %s amount %s currency %s", $cart_id, $amount, $currency));

            if (empty($this->secret_key))
                throw new \InvalidArgumentException();

            Stripe::setApiKey($this->secret_key);
            Stripe::setApiVersion(self::Version);

            $intent = PaymentIntent::retrieve($cart_id);

            if (is_null($intent))
                throw new \InvalidArgumentException();
            if (count($intent->charges->data) == 0)
                throw new \InvalidArgumentException("this intent payment has no charges");
            $charge = $intent->charges->data[0];
            if (!$charge instanceof Charge)
                throw new \InvalidArgumentException();
            $params = [
                'charge' => $charge->id,
                'reason' => 'requested_by_customer'
            ];
            if ($amount > 0) {
                if (!self::isZeroDecimalCurrency($currency)) {
                    /**
                     * All API requests expect amounts to be provided in a currency’s smallest unit. For example,
                     * to charge $10 USD, provide an amount value of 1000 (i.e, 1000 cents).
                     * For zero-decimal currencies, still provide amounts as an integer but without multiplying by 100.
                     * For example, to charge ¥500, simply provide an amount value of 500.
                     */
                    $amount = $amount * 100;
                }
                $params['amount'] = intval($amount);
            }

            // $charge->refund($params);
            // @see https://github.com/stripe/stripe-php/wiki/Migration-guide-for-v7
            $refund = Refund::create($params);

            Log::debug(sprintf("StripeApi::refundPayment refund requested for cart_id %s amount %s response %s", $cart_id, $amount, $refund->toJSON()));
        }
        catch (\Exception $ex){
            Log::error($ex);
            throw $ex;
        }
    }

    /**
     * @param string $cart_id
     * @return mixed|void
     * @throws CartAlreadyPaidException
     */
    public function abandonCart(string $cart_id)
    {
        if (empty($this->secret_key))
            throw new \InvalidArgumentException();

        Stripe::setApiKey($this->secret_key);
        Stripe::setApiVersion(self::Version);

        $intent = PaymentIntent::retrieve($cart_id);

        if (is_null($intent))
            throw new \InvalidArgumentException();

        if (!in_array($intent->status, [
            PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD,
            PaymentIntent::STATUS_REQUIRES_CAPTURE,
            PaymentIntent::STATUS_REQUIRES_CONFIRMATION,
            PaymentIntent::STATUS_REQUIRES_ACTION,
            "requires_source",
            "requires_source_action",
        ]))
            throw new CartAlreadyPaidException(sprintf("cart id %s has status %s", $cart_id, $intent->status));

        $intent->cancel();
    }

    /**
     * @param string $status
     * @return bool
     */
    public function canAbandon(string $status): bool
    {
        return in_array($status, [
            PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD,
            PaymentIntent::STATUS_REQUIRES_CAPTURE,
            PaymentIntent::STATUS_REQUIRES_CONFIRMATION,
            PaymentIntent::STATUS_REQUIRES_ACTION,
            "requires_source",
            "requires_source_action",
        ]);
    }

    /**
     * @param string $status
     * @return bool
     */
    public function isSucceeded(string $status): bool
    {
        return $status == PaymentIntent::STATUS_SUCCEEDED;
    }

    /**
     * @param string $cart_id
     * @return string|null
     */
    public function getCartStatus(string $cart_id): ?string
    {

        if (empty($this->secret_key))
            throw new \InvalidArgumentException();

        Stripe::setApiKey($this->secret_key);
        Stripe::setApiVersion(self::Version);

        try {
            $intent = PaymentIntent::retrieve($cart_id);

            if (is_null($intent))
                throw new \InvalidArgumentException();

            return $intent->status;
        }
        catch(ApiErrorException $ex){
            Log::warning(sprintf("StripeApi::getCartStatus cart_id %s code %s message %s", $cart_id, $ex->getCode(), $ex->getMessage()));
            return null;
        }
    }

    /**
     * @param string $webhook_endpoint_url
     * @return array
     */
    public function createWebHook(string $webhook_endpoint_url): array
    {
        if (empty($this->secret_key))
            throw new \InvalidArgumentException();

        Stripe::setApiKey($this->secret_key);
        Stripe::setApiVersion(self::Version);

        $res = WebhookEndpoint::create([
            'url' => $webhook_endpoint_url,
            'enabled_events' => [
                'payment_intent.succeeded',
                'payment_intent.payment_failed',
            ],
        ]);

        return [
            'id' => $res->id,
            'secret' => $res->secret,
            'livemode' => $res->livemode
        ];
    }

    /**
     * @param string $id
     * @return WebhookEndpoint
     */
    public function getWebHookById(string $id){

        try {
            if (empty($this->secret_key))
                throw new \InvalidArgumentException();

            Stripe::setApiKey($this->secret_key);
            Stripe::setApiVersion(self::Version);

            return WebhookEndpoint::retrieve($id);
        }
        catch (\Exception $ex){
            Log::error($ex);
            throw $ex;
        }
    }

    /**
     * @param string $id
     * @return void
     */
    public function deleteWebHookById(string $id):void{
        try {
            $webhook = $this->getWebHookById($id);
            if (!$webhook) return;
            $webhook->delete();
        }
        catch (\Exception $ex){
            Log::error($ex);
            throw $ex;
        }
    }
}