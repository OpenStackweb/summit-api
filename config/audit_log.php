<?php

return [
    'entities' => [
        \models\main\SummitMemberSchedule::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitMemberScheduleAuditLogFormatter::class,
        ],
        \models\summit\PresentationSpeaker::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\PresentationSpeakerAuditLogFormatter::class,
        ],
        \models\summit\Presentation::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\PresentationSubmissionAuditLogFormatter::class,
        ],
        \App\Models\Foundation\Summit\SelectionPlan::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SelectionPlanAuditLogFormatter::class,
        ],
        \models\summit\SpeakerRegistrationRequest::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SpeakerRegistrationRequestAuditLogFormatter::class,
        ],
        \models\summit\SummitSubmissionInvitation::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SubmissionInvitationAuditLogFormatter::class,
        ],
        \App\Models\Foundation\Summit\Speakers\FeaturedSpeaker::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\FeaturedSpeakerAuditLogFormatter::class,
        ],
        \models\summit\PresentationSpeakerSummitAssistanceConfirmationRequest::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SpeakerAssistanceAuditLogFormatter::class,
        ]
    ]
];
