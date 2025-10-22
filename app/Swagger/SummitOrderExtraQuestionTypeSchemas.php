<?php

namespace App\Swagger\Summit\Registration;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SummitOrderExtraQuestionType",
    description: "Summit Order Extra Question Type",
    type: "object"
)]
class SummitOrderExtraQuestionTypeSchemas
{
    #[OA\Property(property: "id", type: "integer", format: "int64")]
    public int $id;

    #[OA\Property(property: "name", type: "string")]
    public string $name;

    #[OA\Property(property: "label", type: "string")]
    public string $label;

    #[OA\Property(property: "type", type: "string", enum: ["Text", "TextArea", "DropDown", "CheckBox", "RadioButtonList", "CheckBoxList"])]
    public string $type;

    #[OA\Property(property: "class", type: "string", enum: ["Main", "SubQuestion"])]
    public string $class;

    #[OA\Property(property: "usage", type: "string", enum: ["BothQuestionUsage", "TicketQuestionUsage", "OrderQuestionUsage"])]
    public string $usage;

    #[OA\Property(property: "printable", type: "boolean")]
    public bool $printable;

    #[OA\Property(property: "order", type: "integer", format: "int32")]
    public int $order;

    #[OA\Property(property: "mandatory", type: "boolean")]
    public bool $mandatory;

    #[OA\Property(property: "is_deletable", type: "boolean")]
    public bool $is_deletable;

    #[OA\Property(property: "summit_id", type: "integer", format: "int64")]
    public int $summit_id;

    #[OA\Property(
        property: "values",
        type: "array",
        items: new OA\Items(ref: "#/components/schemas/ExtraQuestionTypeValue")
    )]
    public array $values;

    #[OA\Property(
        property: "sub_question_rules",
        type: "array",
        items: new OA\Items(ref: "#/components/schemas/SubQuestionRule")
    )]
    public array $sub_question_rules;

    #[OA\Property(
        property: "allowed_ticket_types",
        type: "array",
        items: new OA\Items(type: "integer", format: "int64")
    )]
    public array $allowed_ticket_types;

    #[OA\Property(
        property: "allowed_badge_features_types",
        type: "array",
        items: new OA\Items(type: "integer", format: "int64")
    )]
    public array $allowed_badge_features_types;
}