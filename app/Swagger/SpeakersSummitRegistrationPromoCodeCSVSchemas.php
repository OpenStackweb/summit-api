<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SpeakersSummitRegistrationPromoCodeCSV",
    description: "Speakers Summit Registration Promo Code CSV Export",
    required: ["id", "code"]
)]
class SpeakersSummitRegistrationPromoCodeCSVSchemas
{
    #[OA\Property(description: "Promo Code ID", type: "integer")]
    public int $id;

    #[OA\Property(description: "Promo Code Value", type: "string")]
    public string $code;

    #[OA\Property(description: "Description", type: "string", nullable: true)]
    public ?string $description;

    #[OA\Property(description: "Discount Amount", type: "number")]
    public float $discount;

    #[OA\Property(description: "Number of Uses", type: "integer")]
    public int $number_of_uses;

    #[OA\Property(description: "Remaining Quantity", type: "integer")]
    public int $remaining_quantity;

    #[OA\Property(description: "Owner Speaker Names (pipe separated)", type: "string")]
    public string $owner_name;

    #[OA\Property(description: "Owner Speaker Emails (pipe separated)", type: "string")]
    public string $owner_email;

    #[OA\Property(description: "Valid From Date", type: "string", format: "date-time", nullable: true)]
    public ?string $valid_from_date;

    #[OA\Property(description: "Valid Until Date", type: "string", format: "date-time", nullable: true)]
    public ?string $valid_until_date;

    #[OA\Property(description: "Is Active", type: "boolean")]
    public bool $is_active;
}