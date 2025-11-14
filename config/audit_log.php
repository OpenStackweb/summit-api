<?php

return [
    'entities' => [
        \models\main\SummitMemberSchedule::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitMemberScheduleAuditLogFormatter::class,
        ]
    ]
];
