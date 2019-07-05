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
use App\Services\Apis\IPaymentGatewayAPI;
use Illuminate\Http\Request as LaravelRequest;
use models\exceptions\ValidationException;
use models\summit\SummitRoomReservation;
use Stripe\Charge;
use Stripe\Error\SignatureVerification;
use Stripe\Event;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\WebhookSignature;
/**
 * Class StripesApi
 * @package App\Services\Apis\PaymentGateways
 */
final class StripeApi implements IPaymentGatewayAPI
{
    /**
     * @var string
     */
    private $api_key;

    /**
     * @var string
     */
    private $webhook_secret;

    /**
     * StripeApi constructor.
     * @param string $api_key
     * @param string $webhook_secret
     */
    public function __construct(string $api_key, string $webhook_secret)
    {
        $this->api_key = $api_key;
        $this->webhook_secret = $webhook_secret;
    }

    /**
     * @param SummitRoomReservation $reservation
     * @return array
     */
    public function generatePayment(SummitRoomReservation $reservation):array
    {
        if(empty($this->api_key))
            throw new \InvalidArgumentException();

        Stripe::setApiKey($this->api_key);

        $intent = PaymentIntent::create([
            'amount'        => $reservation->getAmount(),
            'currency'      => $reservation->getCurrency(),
            'receipt_email' => $reservation->getOwner()->getEmail()
        ]);

        return [
            'client_token'  => $intent->client_secret,
            'cart_id'       => $intent->id,
        ];
    }

    /**
     * @param LaravelRequest $request
     * @return array
     * @throws SignatureVerification
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    public function processCallback(LaravelRequest $request): array
    {
        try {

            WebhookSignature::verifyHeader(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                $this->webhook_secret
            );

            $event = Event::constructFrom(json_decode($request->getContent(), true));
            if(!in_array($event->type, ["payment_intent.succeeded", "payment_intent.payment_failed"]))
                throw new \InvalidArgumentException();

            $intent = $event->data->object;
            if ($event->type == "payment_intent.succeeded") {
                return [
                    "event_type" => $event->type,
                    "cart_id"  => $intent->id,
                ];
            }

            if ($event->type == "payment_intent.payment_failed") {
                $intent = $event->data->object;
                return [
                    "event_type" => $event->type,
                    "cart_id"    => $intent->id,
                    "error" => [
                        "last_payment_error" => $intent->last_payment_error,
                        "message" =>  $intent->last_payment_error->message
                    ]
                ];
            }

            throw new ValidationException(sprintf("event type %s not handled!", $event->type));
        }
        catch(\UnexpectedValueException $e) {
            // Invalid payload
           throw $e;
        }
        catch(SignatureVerification $e) {
            // Invalid signature
            throw $e;
        }
        catch (\Exception $e){
            throw $e;
        }
    }

    /**
     * @param array $payload
     * @return bool
     */
    public function isSuccessFullPayment(array $payload): bool
    {
        if(isset($payload['type']) && $payload['type'] == "payment_intent.succeeded") return true;
        return false;
    }

    /**
     * @param array $payload
     * @return string
     */
    public function getPaymentError(array $payload): ?string
    {
        if(isset($payload['type']) && $payload['type'] == "payment_intent.payment_failed"){
            $error_message = $payload['error']["message"];
            return $error_message;
        }
        return null;
    }

    /**
     * @param string $cart_id
     * @param int $amount
     * @throws \InvalidArgumentException
     */
    public function refundPayment(string $cart_id, int $amount = 0): void
    {
        if(empty($this->api_key))
            throw new \InvalidArgumentException();

        Stripe::setApiKey($this->api_key);
        $intent = PaymentIntent::retrieve($cart_id);

        if(is_null($intent))
            throw new \InvalidArgumentException();
        if(count($intent->charges->data) == 0)
            throw new \InvalidArgumentException("this intent payment has no charges");
        $charge = $intent->charges->data[0];
        if(!$charge instanceof Charge)
            throw new \InvalidArgumentException();
        $params = [];
        if($amount > 0 ){
            $params['amount'] = $amount;
        }
        $charge->refund($params);
    }
}