<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SpeakersSummitRegistrationPromoCode",
    description: "Speakers Summit Registration Promo Code",
    required: ["id", "code", "type"]
)]
class SpeakersSummitRegistrationPromoCodeSchemas
{
    #[OA\Property(description: "Promo Code ID", type: "integer", format: "int64")]
    public int $id;

    #[OA\Property(description: "Promo Code Value", type: "string")]
    public string $code;

    #[OA\Property(description: "Code Type (speakers)", type: "string", enum: ["speakers"])]
    public string $type;

    #[OA\Property(description: "Description", type: "string", nullable: true)]
    public ?string $description;

    #[OA\Property(description: "Discount Amount", type: "number", format: "float")]
    public float $discount;

    #[OA\Property(description: "Number of Uses", type: "integer")]
    public int $number_of_uses;

    #[OA\Property(description: "Remaining Uses", type: "integer")]
    public int $remaining_quantity;

    #[OA\Property(description: "Creation Date", type: "string", format: "date-time")]
    public string $created;

    #[OA\Property(description: "Last Update Date", type: "string", format: "date-time")]
    public string $updated;

    #[OA\Property(description: "Valid From Date", type: "string", format: "date-time", nullable: true)]
    public ?string $valid_from_date;

    #[OA\Property(description: "Valid Until Date", type: "string", format: "date-time", nullable: true)]
    public ?string $valid_until_date;

    #[OA\Property(description: "Is Active", type: "boolean")]
    public bool $is_active;

    #[OA\Property(description: "Owner Speaker IDs", type: "array", items: new OA\Items(type: "integer"))]
    public array $owners;

    #[OA\Property(description: "Ticket Type Rules", type: "array", items: new OA\Items(type: "object"))]
    public array $ticket_types_rules;
}