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

use App\Services\Apis\CartAlreadyPaidException;
use App\Services\Apis\IPaymentGatewayAPI;
use Illuminate\Http\Request as LaravelRequest;
use Illuminate\Support\Facades\Log;
use ChargeIO;
use ChargeIO_Credentials;
use ChargeIO_Charge;
use ChargeIO_PaymentMethodReference;
use ChargeIO_Merchant;
use ChargeIO_InvalidRequestError;
use Exception;
use models\exceptions\ValidationException;
use models\summit\IPaymentConstants;
use models\summit\SummitOrder;

/**
 * Class LawPayApi
 * @package App\Services\Apis\PaymentGateways
 */
final class LawPayApi implements IPaymentGatewayAPI
{

    /**
     * @var string
     */
    private $secret_key;

    /**
     * @var string
     */
    private $public_key;

    /**
     * @var string
     */
    private $account_id;

    /**
     * @var bool
     */
    private $test_mode_enabled;

    /**
     * StripeApi constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->secret_key = $config['secret_key'] ?? null;
        $this->public_key = $config['public_key'] ?? null;
        $this->account_id = $config['account_id'] ?? null;
        $this->test_mode_enabled = $config['test_mode_enabled'] ?? true;

        Log::debug
        (
            sprintf
            (
                "LawPayApi::__construct config %s",
                json_encode($config)
            )
        );
    }

    /**
     * @param SummitOrder $order
     * @return SummitOrder
     */
    public function preProcessOrder(SummitOrder $order): SummitOrder
    {
        return $order;
    }

    /**
     * @param SummitOrder $order
     * @param array $payload
     * @return SummitOrder
     * @throws ChargeIO_InvalidRequestError
     * @throws ValidationException
     */
    public function postProcessOrder(SummitOrder $order, array $payload = []):SummitOrder
    {
        Log::debug(sprintf("LawPayApi::postProcessOrder order %s payload %s", $order->getId(), json_encode($payload)));

        $summit_id = $order->getSummitId();
        $token_id = $payload['payment_method_id'] ?? null;
        if(empty($token_id))
            throw new ValidationException("payment_method_id is required.");

        $result = $this->generatePayment(
            [
                "amount" => $order->getFinalAmount(),
                "token_id" => $token_id,
                "metadata" => [
                    "type" => IPaymentConstants::ApplicationTypeRegistration,
                    "summit_id" => $summit_id,
                ]
            ]
        );

        if (!isset($result['cart_id']))
            throw new ValidationException("payment gateway error.");

        $order->setPaymentGatewayCartId($result['cart_id']);

        return $order;
    }

    /**
     * @param ChargeIO_InvalidRequestError $ex
     * @throws ChargeIO_InvalidRequestError
     * @throws ValidationException
     */
    static private function handleChargeIOError(ChargeIO_InvalidRequestError $ex):void{
        Log::warning($ex->getJson());
        Log::error($ex);

        $code = $ex->getCode();
        if(LawPayCardValidationMessages::isCardValidationError($code))
            throw new ValidationException($ex->errors[0]);

        if(LawPayCardProcessingMessages::isCardProcessingError($code))
            throw new ValidationException($ex->errors[0]);

        if(LawPayGeneralMessages::isGeneralError($code))
            throw new ValidationException($ex->errors[0]);

        throw $ex;
    }

    /**
     * @param Exception $ex
     * @throws Exception
     */
    static private function handleGenericException(Exception $ex):void{
        Log::error($ex);
        throw $ex;
    }

    /**
     * @param array $payload
     * @return array
     * @throws ChargeIO_InvalidRequestError
     * @throws ValidationException
     */
    public function generatePayment(array $payload): array
    {
        if (empty($this->secret_key))
            throw new \InvalidArgumentException();

        if (empty($this->public_key))
            throw new \InvalidArgumentException();

        Log::debug(sprintf("LawPayApi::generatePayment payload %s ",json_encode($payload)));
        try {
            ChargeIO::setCredentials(new ChargeIO_Credentials(
                $this->public_key,
                $this->secret_key
            ));
            // convert to cents
            // The total payment amount in cents. For example: $500.00 = 50000.
            // @see https://developers.affinipay.com/charge/create-charge.html
            $amount = intval(floatval($payload['amount']) * 100);
            $token_id = $payload['token_id'];
            if (empty($token_id))
                throw new \InvalidArgumentException();

            $params = [
            ];

            if (!empty($this->account_id)) {
                Log::debug(sprintf("LawPayApi::generatePayment setting merchant account id %s.",$this->account_id));
                $params['account_id'] = $this->account_id;
            }
            //ChargeIO::setDebug(true);

            $charge = ChargeIO_Charge::create
            (
                new ChargeIO_PaymentMethodReference(['id' => $token_id]),
                $amount,
                $params
            );

            Log::debug(sprintf("LawPayApi::generatePayment charge id %s.", $charge->id));
            return [
                'cart_id' => $charge->id,
            ];
        }
        catch (ChargeIO_InvalidRequestError $ex){
            self::handleChargeIOError($ex);
        }
        catch (Exception $ex){
          self::handleGenericException($ex);
        }
    }

    /**
     * @param LaravelRequest $request
     * @return array
     * @throws ChargeIO_InvalidRequestError
     * @throws ValidationException
     */
    public function processCallback(LaravelRequest $request): array
    {
        try {
            $requestContent = $request->getContent();
            Log::debug
            (
                sprintf
                (
                    "LawPayApi::processCallback requestContent %s",
                    $requestContent,
                )
            );

            $event = json_decode($requestContent, false);
            $transaction = $event->data;
            if ($event->type == ILawPayApiEventType::TransactionCompleted) {
                Log::debug("LawPayApi::processCallback: transaction.completed");
                return [
                    "event_type" => $event->type,
                    "cart_id" => $transaction->id,
                ];
            }

            if ($event->type == ILawPayApiEventType::TransactionFailed) {
                Log::debug("LawPayApi::processCallback: transaction.failed");
                $transaction = $event->data;
                return [
                    "event_type" => $event->type,
                    "cart_id" => $transaction->id,
                    "error" => [
                        "last_payment_error" => $transaction->status,
                        "message" => $transaction->failure_code
                    ]
                ];
            }

            throw new ValidationException(sprintf("event type %s not handled!", $event->type));
        }
        catch (ChargeIO_InvalidRequestError $ex){
            self::handleChargeIOError($ex);
        }
        catch (Exception $ex){
            self::handleGenericException($ex);
        }
    }

    /**
     * @param array $payload
     * @return bool
     */
    public function isSuccessFullPayment(array $payload): bool
    {
        if (isset($payload['type']) &&
            in_array($payload['type'],
                [ILawPayApiEventType::TransactionAuthorized, ILawPayApiEventType::TransactionCompleted]))
            return true;
        Log::debug("LawPayApi::isSuccessFullPayment false");
        return false;
    }

    /**
     * @param array $payload
     * @return string
     */
    public function getPaymentError(array $payload): ?string
    {
        if (isset($payload['type']) && $payload['type'] == ILawPayApiEventType::TransactionFailed) {
            if (isset($payload['data'])) {
                $data = $payload['data'];
                if (isset($data["failure_code"])) {
                    return $data["failure_code"];
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
     * @throws ChargeIO_InvalidRequestError
     * @throws ValidationException
     */
    public function refundPayment(string $cart_id, float $amount, string $currency, string $reason = 'requested_by_customer'): ?string
    {
        if (empty($this->secret_key))
            throw new \InvalidArgumentException();

        if (empty($this->public_key))
            throw new \InvalidArgumentException();

        Log::debug
        (
            sprintf
            (
                "LawPayApi::refundPayment cart_id %s amount %s currency %s reason %s.",
                $cart_id,
                $amount,
                $currency,
                $reason
            )
        );

        try {
            ChargeIO::setCredentials(new ChargeIO_Credentials(
                $this->public_key,
                $this->secret_key
            ));

            $charge = ChargeIO_Charge::findById($cart_id);
            if (is_null($charge))
                throw new \InvalidArgumentException();

            if($charge->status !== ILawPayApiChargeStatus::Completed){
                throw new ValidationException(sprintf("charge can not be refunded."));
            }

            if ($amount > 0) {
                $amount = $amount * 100;
            }

            $res = $charge->refund($amount);
            $res = json_encode($res->attributes);
            Log::debug(sprintf("LawPayApi::refundPayment refund requested for cart_id %s amount %s response %s", $cart_id, $amount, $res));
            return $res;
        }
        catch (ChargeIO_InvalidRequestError $ex){
            self::handleChargeIOError($ex);
        }
        catch(Exception $ex){
            self::handleGenericException($ex);
        }
    }

    /**
     * @param string $cart_id
     * @return mixed|void
     * @throws CartAlreadyPaidException
     * @throws ChargeIO_InvalidRequestError
     * @throws ValidationException
     */
    public function abandonCart(string $cart_id)
    {
        if (empty($this->secret_key))
            throw new \InvalidArgumentException();

        if (empty($this->public_key))
            throw new \InvalidArgumentException();

        try {
            Log::debug(sprintf("LawPayApi::abandonCart %s", $cart_id));

            ChargeIO::setCredentials(new ChargeIO_Credentials(
                $this->public_key,
                $this->secret_key
            ));

            $charge = ChargeIO_Charge::findById($cart_id);
            if (is_null($charge))
                throw new \InvalidArgumentException();

            if(!$this->canAbandon($charge->status))
                throw new CartAlreadyPaidException(sprintf("cart id %s has status %s", $cart_id, $charge->status));

            $charge->void();
        }
        catch (ChargeIO_InvalidRequestError $ex){
            self::handleChargeIOError($ex);
        }
        catch(Exception $ex){
            Log::warning(sprintf("LawPayApi::abandonCart cart_id %s code %s message %s", $cart_id, $ex->getCode(), $ex->getMessage()));
            throw $ex;
        }
    }

    /**
     * @param string $status
     * @return bool
     */
    public function canAbandon(string $status): bool
    {
        return in_array($status, [
            ILawPayApiChargeStatus::Pending
        ]);
    }

    /**
     * @param string $cart_id
     * @return string|null
     * @throws ChargeIO_InvalidRequestError
     * @throws ValidationException
     */
    public function getCartStatus(string $cart_id): ?string
    {
        if (empty($this->secret_key))
            throw new \InvalidArgumentException();

        if (empty($this->public_key))
            throw new \InvalidArgumentException();

        try {
            Log::debug(sprintf("LawPayApi::getCartStatus %s", $cart_id));

            ChargeIO::setCredentials(new ChargeIO_Credentials(
                $this->public_key,
                $this->secret_key
            ));

            $charge = ChargeIO_Charge::findById($cart_id);
            if (is_null($charge))
                throw new \InvalidArgumentException();
            return $charge->status;
        }
        catch (ChargeIO_InvalidRequestError $ex){
            self::handleChargeIOError($ex);
        }
        catch(Exception $ex){
            Log::warning(sprintf("LawPayApi::getCartStatus cart_id %s code %s message %s", $cart_id, $ex->getCode(), $ex->getMessage()));
            return null;
        }
    }

    /**
     * @param string $status
     * @return bool
     */
    public function isSucceeded(string $status): bool
    {
        return in_array($status,[ILawPayApiChargeStatus::Authorized, ILawPayApiChargeStatus::Completed]);
    }

    /**
     * @param string $webhook_endpoint_url
     * @return array
     * @throws ChargeIO_InvalidRequestError
     * @throws ValidationException
     */
    public function createWebHook(string $webhook_endpoint_url): array
    {
        if (empty($this->secret_key))
            throw new \InvalidArgumentException();

        if (empty($this->public_key))
            throw new \InvalidArgumentException();

        try {
            Log::debug(sprintf("LawPayApi::createWebHook %s", $webhook_endpoint_url));

            ChargeIO::setCredentials(new ChargeIO_Credentials(
                $this->public_key,
                $this->secret_key
            ));

            $current_merchant = ChargeIO_Merchant::findCurrent();
            if (is_null($current_merchant))
                throw new \InvalidArgumentException();

            Log::debug(sprintf("LawPayApi::createWebHook found merchant %s", json_encode($current_merchant->attributes)));

            $attributes = [];
            if($this->test_mode_enabled){
                $attributes['test_events_urls'] = $webhook_endpoint_url;
            }
            else{
                $attributes['live_events_urls'] = $webhook_endpoint_url;
            }
            $current_merchant->update($attributes);

            return [];
        }
        catch (ChargeIO_InvalidRequestError $ex){
            self::handleChargeIOError($ex);
        }
        catch(Exception $ex){
            Log::warning(sprintf("LawPayApi::createWebHook code %s message %s", $ex->getCode(), $ex->getMessage()));
            return [];
        }
    }

    /**
     * @throws ChargeIO_InvalidRequestError
     * @throws ValidationException
     */
    public function clearWebHooks():void{
        if (empty($this->secret_key))
            throw new \InvalidArgumentException();

        if (empty($this->public_key))
            throw new \InvalidArgumentException();

        try {

            Log::debug("LawPayApi::clearWebHooks");

            ChargeIO::setCredentials(new ChargeIO_Credentials(
                $this->public_key,
                $this->secret_key
            ));

            $current_merchant = ChargeIO_Merchant::findCurrent();
            if (is_null($current_merchant))
                throw new \InvalidArgumentException();

            $attributes = ['test_events_urls' => '', 'live_events_urls' => ''];
            $current_merchant->update($attributes);
        }
        catch (ChargeIO_InvalidRequestError $ex){
            self::handleChargeIOError($ex);
        }
        catch(Exception $ex){
            Log::warning(sprintf("LawPayApi::createWebHook code %s message %s", $ex->getCode(), $ex->getMessage()));
        }
    }
}