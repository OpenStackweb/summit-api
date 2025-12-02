<?php

return [
    'entities' => [
        \models\main\SummitMemberSchedule::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitMemberScheduleAuditLogFormatter::class,
        ],
        \models\summit\SummitTrackChair::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\SummitTrackChairAuditLogFormatter::class,
        ],
        \App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairRatingType::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\PresentationTrackChairRatingTypeAuditLogFormatter::class,
        ],
        \App\Models\Foundation\Summit\Events\Presentations\TrackChairs\PresentationTrackChairScoreType::class => [
            'enabled' => true,
            'strategy' => \App\Audit\ConcreteFormatters\PresentationTrackChairScoreTypeAuditLogFormatter::class,
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
    ]
];
