<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

/**
 * Base Promo Code Request Schema - Common properties for all promo code types
 */
#[OA\Schema(
    schema: 'PromoCodeBaseRequest',
    title: 'Promo Code Base Request',
    description: 'Base request schema for promo codes with common properties',
    required: ['class_name', 'code'],
    properties: [
        new OA\Property(
            property: 'class_name',
            type: 'string',
            description: 'The type of promo code to create',
            enum: [
                'SUMMIT_PROMO_CODE',
                'SUMMIT_DISCOUNT_CODE',
                'SPEAKER_PROMO_CODE',
                'SPEAKERS_PROMO_CODE',
                'SPONSOR_PROMO_CODE',
                'MEMBER_PROMO_CODE',
                'MEMBER_DISCOUNT_CODE',
                'SPEAKER_DISCOUNT_CODE',
                'SPEAKERS_DISCOUNT_CODE',
                'SPONSOR_DISCOUNT_CODE',
                'PRE_PAID_PROMO_CODE',
                'PRE_PAID_DISCOUNT_CODE'
            ]
        ),
        new OA\Property(property: 'code', type: 'string', maxLength: 255, description: 'The promo code string'),
        new OA\Property(property: 'description', type: 'string', maxLength: 1024, description: 'Description of the promo code'),
        new OA\Property(property: 'notes', type: 'string', maxLength: 2048, description: 'Internal notes about the promo code'),
        new OA\Property(property: 'quantity_available', type: 'integer', minimum: 0, description: 'Number of times this code can be used'),
        new OA\Property(property: 'valid_since_date', type: 'integer', format: 'int64', description: 'Start validity date (epoch timestamp)'),
        new OA\Property(property: 'valid_until_date', type: 'integer', format: 'int64', description: 'End validity date (epoch timestamp)'),
        new OA\Property(property: 'allowed_ticket_types', type: 'array', items: new OA\Items(type: 'integer'), description: 'Array of allowed ticket type IDs'),
        new OA\Property(property: 'badge_features', type: 'array', items: new OA\Items(type: 'integer'), description: 'Array of badge feature IDs to apply'),
        new OA\Property(property: 'allows_to_delegate', type: 'boolean', description: 'Whether the code holder can delegate to others'),
        new OA\Property(property: 'allows_to_reassign', type: 'boolean', description: 'Whether the code can be reassigned'),
    ]
)]
class PromoCodeBaseRequestSchema {}

/**
 * Add Promo Code Request Schema
 */
#[OA\Schema(
    schema: 'PromoCodeAddRequest',
    title: 'Add Promo Code Request',
    description: 'Request schema for creating a new promo code. Additional properties depend on class_name.',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PromoCodeBaseRequest'),
        new OA\Schema(
            properties: [
                new OA\Property(property: 'type', type: 'string', description: 'Promo code type (VIP, ATC, MEDIA ANALYST for Member codes; ACCEPTED, ALTERNATE for Speaker codes)'),
                new OA\Property(property: 'first_name', type: 'string', description: 'Owner first name (for Member codes, required without owner_id)'),
                new OA\Property(property: 'last_name', type: 'string', description: 'Owner last name (for Member codes, required without owner_id)'),
                new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 254, description: 'Owner email (for Member codes, required without owner_id)'),
                new OA\Property(property: 'owner_id', type: 'integer', description: 'Owner member ID (for Member codes, required without first_name/last_name/email)'),
                new OA\Property(property: 'speaker_id', type: 'integer', description: 'Speaker ID (for Speaker codes)'),
                new OA\Property(property: 'sponsor_id', type: 'integer', description: 'Sponsor ID (for Sponsor codes, required)'),
                new OA\Property(property: 'contact_email', type: 'string', format: 'email', maxLength: 254, description: 'Contact email (for Sponsor codes, required)'),
                new OA\Property(property: 'amount', type: 'number', description: 'Discount amount (for discount codes, required without rate)'),
                new OA\Property(property: 'rate', type: 'number', description: 'Discount rate percentage (for discount codes, required without amount)'),
            ]
        )
    ]
)]
class PromoCodeAddRequestSchema {}

/**
 * Update Promo Code Request Schema
 */
#[OA\Schema(
    schema: 'PromoCodeUpdateRequest',
    title: 'Update Promo Code Request',
    description: 'Request schema for updating an existing promo code. All properties are optional.',
    required: ['class_name'],
    properties: [
        new OA\Property(
            property: 'class_name',
            type: 'string',
            description: 'The type of promo code',
            enum: [
                'SUMMIT_PROMO_CODE',
                'SUMMIT_DISCOUNT_CODE',
                'SPEAKER_PROMO_CODE',
                'SPEAKERS_PROMO_CODE',
                'SPONSOR_PROMO_CODE',
                'MEMBER_PROMO_CODE',
                'MEMBER_DISCOUNT_CODE',
                'SPEAKER_DISCOUNT_CODE',
                'SPEAKERS_DISCOUNT_CODE',
                'SPONSOR_DISCOUNT_CODE',
                'PRE_PAID_PROMO_CODE',
                'PRE_PAID_DISCOUNT_CODE'
            ]
        ),
        new OA\Property(property: 'code', type: 'string', maxLength: 255, description: 'The promo code string'),
        new OA\Property(property: 'description', type: 'string', maxLength: 1024, description: 'Description of the promo code'),
        new OA\Property(property: 'notes', type: 'string', maxLength: 2048, description: 'Internal notes about the promo code'),
        new OA\Property(property: 'quantity_available', type: 'integer', minimum: 0, description: 'Number of times this code can be used'),
        new OA\Property(property: 'valid_since_date', type: 'integer', format: 'int64', description: 'Start validity date (epoch timestamp)'),
        new OA\Property(property: 'valid_until_date', type: 'integer', format: 'int64', description: 'End validity date (epoch timestamp)'),
        new OA\Property(property: 'allowed_ticket_types', type: 'array', items: new OA\Items(type: 'integer'), description: 'Array of allowed ticket type IDs'),
        new OA\Property(property: 'badge_features', type: 'array', items: new OA\Items(type: 'integer'), description: 'Array of badge feature IDs to apply'),
        new OA\Property(property: 'badge_features_apply_to_all_tix_retroactively', type: 'boolean', description: 'Apply badge features retroactively to all tickets'),
        new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string'), description: 'Array of tag strings'),
        new OA\Property(property: 'allows_to_delegate', type: 'boolean', description: 'Whether the code holder can delegate to others'),
        new OA\Property(property: 'allows_to_reassign', type: 'boolean', description: 'Whether the code can be reassigned'),
        new OA\Property(property: 'type', type: 'string', description: 'Promo code type'),
        new OA\Property(property: 'first_name', type: 'string', description: 'Owner first name (for Member codes)'),
        new OA\Property(property: 'last_name', type: 'string', description: 'Owner last name (for Member codes)'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 254, description: 'Owner email (for Member codes)'),
        new OA\Property(property: 'owner_id', type: 'integer', description: 'Owner member ID (for Member codes)'),
        new OA\Property(property: 'speaker_id', type: 'integer', description: 'Speaker ID (for Speaker codes)'),
        new OA\Property(property: 'sponsor_id', type: 'integer', description: 'Sponsor ID (for Sponsor codes)'),
        new OA\Property(property: 'contact_email', type: 'string', format: 'email', maxLength: 254, description: 'Contact email (for Sponsor codes)'),
        new OA\Property(property: 'amount', type: 'number', description: 'Discount amount (for discount codes)'),
        new OA\Property(property: 'rate', type: 'number', description: 'Discount rate percentage (for discount codes)'),
    ]
)]
class PromoCodeUpdateRequestSchema {}

/**
 * Promo Code Ticket Type Rule Request Schema
 */
#[OA\Schema(
    schema: 'PromoCodeTicketTypeRuleRequest',
    title: 'Promo Code Ticket Type Rule Request',
    description: 'Request schema for adding a ticket type rule to a promo code (discount codes only)',
    properties: [
        new OA\Property(property: 'amount', type: 'number', minimum: 0, description: 'Fixed discount amount (required without rate)'),
        new OA\Property(property: 'rate', type: 'number', minimum: 0, description: 'Discount rate percentage (required without amount)'),
    ]
)]
class PromoCodeTicketTypeRuleRequestSchema {}

/**
 * Send Sponsor Promo Codes Email Request Schema
 */
#[OA\Schema(
    schema: 'SendSponsorPromoCodesRequest',
    title: 'Send Sponsor Promo Codes Email Request',
    description: 'Request schema for sending sponsor promo codes via email',
    required: ['email_flow_event'],
    properties: [
        new OA\Property(property: 'email_flow_event', type: 'string', description: 'Email flow event slug', enum: ['SUMMIT_REGISTRATION_SPONSOR_PROMO_CODE']),
        new OA\Property(property: 'promo_code_ids', type: 'array', items: new OA\Items(type: 'integer'), description: 'Specific promo code IDs to send'),
        new OA\Property(property: 'excluded_promo_code_ids', type: 'array', items: new OA\Items(type: 'integer'), description: 'Promo code IDs to exclude'),
        new OA\Property(property: 'test_email_recipient', type: 'string', format: 'email', description: 'Test email recipient'),
        new OA\Property(property: 'outcome_email_recipient', type: 'string', format: 'email', description: 'Email recipient for outcome notification'),
    ]
)]
class SendSponsorPromoCodesRequestSchema {}
