<?php

namespace App\Swagger\schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SponsorBase',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'created', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'last_edited', type: 'integer', example: 1, format: "time_epoch"),
        new OA\Property(property: 'order', type: 'integer'),
        new OA\Property(property: 'summit_id', type: 'integer'),
        new OA\Property(property: 'company_id', type: 'integer'),
        new OA\Property(property: 'is_published', type: 'boolean'),
        new OA\Property(property: 'side_image', type: 'string', format: 'url'),
        new OA\Property(property: 'header_image', type: 'string', format: 'url'),
        new OA\Property(property: 'header_image_mobile', type: 'string', format: 'url'),
        new OA\Property(property: 'carousel_advertise_image', type: 'string', format: 'url'),
        new OA\Property(property: 'marquee', type: 'string'),
        new OA\Property(property: 'intro', type: 'string'),
        new OA\Property(property: 'external_link', type: 'string'),
        new OA\Property(property: 'video_link', type: 'string'),
        new OA\Property(property: 'chat_link', type: 'string'),
        new OA\Property(property: 'featured_event_id', type: 'integer'),
        new OA\Property(property: 'header_image_alt_text', type: 'string'),
        new OA\Property(property: 'side_image_alt_text', type: 'string'),
        new OA\Property(property: 'header_image_mobile_alt_text', type: 'string'),
        new OA\Property(property: 'carousel_advertise_image_alt_text', type: 'string'),
        new OA\Property(property: 'show_logo_in_event_page', type: 'boolean'),
        new OA\Property(property: 'lead_report_setting_id', type: 'integer'),
        new OA\Property(property: 'extra_questions', type: 'array', items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: '#/components/schemas/SummitSponsorExtraQuestionType'),
                new OA\Schema(type: 'integer')
            ]
        ), description: 'SummitSponsorExtraQuestionType Ids when included in the relations, and full objects when expanded'),
        new OA\Property(property: 'members', type: 'array', items: new OA\Items(
            oneOf: [
                new OA\Schema(ref: '#/components/schemas/Member'),
                new OA\Schema(type: 'integer')
            ]
        ), description: 'Member Ids when included in the relations, and full objects when expanded'),
        new OA\Property(property: 'summit', ref: '#/components/schemas/Summit', description: 'Summit object (Public) when included in expand'),
        new OA\Property(property: 'company', ref: '#/components/schemas/Company', description: 'Company object when included in expand'),
        new OA\Property(property: 'featured_event', ref: '#/components/schemas/SummitEvent', description: 'SummitEvent object when included in expand'),
        new OA\Property(property: 'lead_report_setting', ref: '#/components/schemas/SummitLeadReportSetting', description: 'SummitLeadReportSetting object when included in expand'),
    ]
)]
class SponsorBaseSchema
{
}
