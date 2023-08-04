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

use App\Models\Utils\Traits\FinancialTrait;
use App\Services\Apis\CartAlreadyPaidException;
use App\Services\Apis\IPaymentGatewayAPI;
use Illuminate\Http\Request as LaravelRequest;
use models\exceptions\ValidationException;
use models\summit\IPaymentConstants;
use models\summit\SummitOrder;
use Stripe\Charge;
use Exception;
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
    use FinancialTrait;

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

    public function getCreditCardInfo(array $payload): array
    {
        if (!isset($payload['charges']) || !isset($payload['charges']['data'])) return [];

        $charges = $payload['charges']['data'];

        if (count($charges) == 0) return [];

        $charge = $charges[0];
        if (!isset($charge['payment_method_details'])) return [];

        $payment_method_details = $charge['payment_method_details'];
        if (!is_null($payment_method_details) && isset($payment_method_details['card'])) {
            $card = $payment_method_details['card'];
            return [
                "order_credit_card_type"     => $card['brand'],
                "order_credit_card_4numbers" => $card['last4'],
            ];
        }
        return [];
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
            $amount = self::convertToCents($amount);

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

        try {
            $intent = PaymentIntent::create($request);
        }
        catch (ApiErrorException $ex){
            Log::warning($ex);
            throw new ValidationException($ex->getMessage());
        }

        Log::debug(sprintf("StripeApi::generatePayment intent id %s", $intent->id));
        return [
            'client_token' => $intent->client_secret,
            'cart_id' => $intent->id,
        ];
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

            $creditcard_info = $this->getCreditCardInfo($intent->toArray());

            if ($event->type == "payment_intent.succeeded") {
                Log::debug("StripeApi::processCallback: payment_intent.succeeded");
                return array_merge([
                    "event_type" => $event->type,
                    "cart_id" => $intent->id
                ], $creditcard_info);
            }

            if ($event->type == "payment_intent.payment_failed") {
                Log::debug("StripeApi::processCallback: payment_intent.payment_failed");
                $intent = $event->data->object;
                return array_merge([
                    "event_type" => $event->type,
                    "cart_id" => $intent->id,
                    "error" => [
                        "last_payment_error" => $intent->last_payment_error,
                        "message" => $intent->last_payment_error->message
                    ]
                ], $creditcard_info);
            }

            throw new ValidationException(sprintf("event type %s not handled!", $event->type));
        } catch (\UnexpectedValueException $e) {
            Log::warning($e);
            // Invalid payload
            throw $e;
        } catch (SignatureVerificationException $e) {
            Log::warning($e);
            // Invalid signature
            throw $e;
        } catch (\Exception $e) {
            Log::warning($e);
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
     * @param string $reason
     * @return string|null
     * @throws \Exception
     */
    public function refundPayment(string $cart_id, float $amount, string $currency, string $reason = 'requested_by_customer'): ?string
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
                'reason' => $reason
            ];

            if ($amount > 0) {
                if (!self::isZeroDecimalCurrency($currency)) {
                    /**
                     * All API requests expect amounts to be provided in a currency’s smallest unit. For example,
                     * to charge $10 USD, provide an amount value of 1000 (i.e, 1000 cents).
                     * For zero-decimal currencies, still provide amounts as an integer but without multiplying by 100.
                     * For example, to charge ¥500, simply provide an amount value of 500.
                     */

                    $amount = self::convertToCents($amount);
                }

                $params['amount'] = intval($amount);
            }

            // $charge->refund($params);
            // @see https://github.com/stripe/stripe-php/wiki/Migration-guide-for-v7
            $refund = Refund::create($params);
            $res = $refund->toJSON();
            Log::debug(sprintf("StripeApi::refundPayment refund requested for cart_id %s amount %s response %s", $cart_id, $amount, $res));
            return $res;
        }
        catch (Exception $ex){
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
        catch(Exception $ex){
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
        Log::debug(sprintf("StripeApi::createWebHook webhook_endpoint_url %s", $webhook_endpoint_url));

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

        Log::debug(sprintf("StripeApi::createWebHook webhook_endpoint_url %s res %s", $webhook_endpoint_url, $res->toJSON()));

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

        Log::debug(sprintf("StripeApi::getWebHookById id %s", $id));

        try {
            if (empty($this->secret_key))
                throw new \InvalidArgumentException();

            Stripe::setApiKey($this->secret_key);
            Stripe::setApiVersion(self::Version);

            $res =  WebhookEndpoint::retrieve($id);
            if(!is_null($res))
                Log::debug(sprintf("StripeApi::getWebHookById id %s res %s", $id, $res->toJSON()));

            return $res;
        }
        catch (Exception $ex){
            Log::error($ex);
            throw $ex;
        }
    }

    /**
     * @param string $id
     * @return void
     */
    public function deleteWebHookById(string $id):void{
        Log::debug(sprintf("StripeApi::deleteWebHookById id %s", $id));

        try {
            $webhook = $this->getWebHookById($id);
            if (!$webhook) return;

            $webhook->delete();
            Log::debug(sprintf("StripeApi::deleteWebHookById deleted webhook id %s", $id));

        }
        catch (Exception $ex){
            Log::error($ex);
            throw $ex;
        }
    }

    /**
     * @param SummitOrder $order
     * @return SummitOrder
     * @throws ValidationException
     */
    public function preProcessOrder(SummitOrder $order): SummitOrder
    {
        $summit_id = $order->getSummitId();
        $result = $this->generatePayment(
            [
                "amount" => $order->getFinalAmount(),
                "currency" => $order->getCurrency(),
                "receipt_email" => $order->getOwnerEmail(),
                "metadata" => [
                    "type" => IPaymentConstants::ApplicationTypeRegistration,
                    "summit_id" => $summit_id,
                ]
            ]
        );

        if (!isset($result['cart_id']))
            throw new ValidationException("payment gateway error");

        if (!isset($result['client_token']))
            throw new ValidationException("payment gateway error");

        $order->setPaymentGatewayCartId($result['cart_id']);
        $order->setPaymentGatewayClientToken($result['client_token']);
        return $order;
    }

    /**
     * @param SummitOrder $order
     * @param array $payload
     * @throws ValidationException
     * @return SummitOrder
     */
    public function postProcessOrder(SummitOrder $order, array $payload = []):SummitOrder
    {
        $order->setConfirmed();
        return $order;
    }

    public function clearWebHooks(): void
    {
        // TODO: Implement clearWebHooks() method.
    }
}