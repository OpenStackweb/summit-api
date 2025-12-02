<?php 
namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaymentGatewayWebhookRequest',
    type: 'object',
    description: 'Generic payment gateway webhook payload. The structure depends on the payment provider (e.g., Stripe, etc.)',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'string',
            example: 'evt_1234567890',
            description: 'Webhook event ID from payment provider'
        ),
        new OA\Property(
            property: 'type',
            type: 'string',
            example: 'charge.succeeded',
            description: 'Event type from payment provider'
        ),
        new OA\Property(
            property: 'cart_id',
            type: 'string',
            example: '1234567890',
            description: 'Cart or order identifier'
        ),
        new OA\Property(
            property: 'data',
            type: 'object',
            description: 'Event data payload from payment provider'
        ),
    ]
)]
class PaymentGatewayWebhookRequestSchema {}

#[OA\Schema(
    schema: 'PaymentProcessingResponse',
    type: 'string',
    example: 'ok',
    description: 'Confirmation message that payment was processed successfully'
)]
class PaymentProcessingResponseSchema {}