<?php

namespace App\Swagger\Summit\Registration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SummitOrderExtraQuestionType",
    description: "Summit Order Extra Question Type",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", format: "int64", example: 1),
        new OA\Property(property: "name", type: "string", example: "extra_question_1"),
        new OA\Property(property: "label", type: "string", example: "Extra Question 1"),
        new OA\Property(property: "type", type: "string", enum: ["Text", "TextArea", "DropDown", "CheckBox", "RadioButtonList", "CheckBoxList"], example: "Text"),
        new OA\Property(property: "class", type: "string", enum: ["Main", "SubQuestion"], example: "Main"),
        new OA\Property(property: "usage", type: "string", enum: ["BothQuestionUsage", "TicketQuestionUsage", "OrderQuestionUsage"], example: "OrderQuestionUsage"),
        new OA\Property(property: "printable", type: "boolean", example: true),
        new OA\Property(property: "order", type: "integer", format: "int32", example: 1),
        new OA\Property(property: "mandatory", type: "boolean", example: false),
        new OA\Property(property: "is_deletable", type: "boolean", example: true),
        new OA\Property(property: "summit_id", type: "integer", format: "int64", example: 10),
        new OA\Property(
            property: "values",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/ExtraQuestionTypeValue")
        ),
        new OA\Property(
            property: "sub_question_rules",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/SubQuestionRule")
        ),
        new OA\Property(
            property: "allowed_ticket_types",
            type: "array",
            items: new OA\Items(type: "integer", format: "int64")
        ),
        new OA\Property(
            property: "allowed_badge_features_types",
            type: "array",
            items: new OA\Items(type: "integer", format: "int64")
        ),
    ]
)]
class SummitOrderExtraQuestionTypeSchemas
{
}

#[OA\Schema(
    schema: "PaginatedSummitOrderExtraQuestionTypesResponse",
    description: "Paginated list of summit order extra question types",
    allOf: [
        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SummitOrderExtraQuestionType")
                )
            ]
        )
    ]
)]
class PaginatedSummitOrderExtraQuestionTypesResponseSchema {}

#[OA\Schema(
    schema: "PaginatedSubQuestionRulesResponse",
    description: "Paginated list of sub question rules",
    allOf: [
        new OA\Schema(ref: "#/components/schemas/PaginateDataSchemaResponse"),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SubQuestionRule")
                )
            ]
        )
    ]
)]
class PaginatedSubQuestionRulesResponseSchema {}
