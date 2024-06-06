create table DoctrineMigration
(
    version     varchar(14) not null
        primary key,
    executed_at datetime    not null comment '(DC2Type:datetime_immutable)'
)
    collate = utf8mb3_unicode_ci;

create table apis
(
    id          bigint unsigned auto_increment
        primary key,
    name        varchar(255)                         not null,
    description longtext                             null,
    active      tinyint(1) default 1                 not null,
    created_at  datetime   default CURRENT_TIMESTAMP not null,
    updated_at  datetime   default CURRENT_TIMESTAMP not null,
    constraint UNIQ_8B1CD7425E237E06
        unique (name)
)
    collate = utf8mb3_unicode_ci;

create table api_endpoints
(
    id                bigint unsigned auto_increment
        primary key,
    api_id            bigint unsigned                           not null,
    active            tinyint(1)      default 1                 not null,
    allow_cors        tinyint(1)      default 1                 not null,
    allow_credentials tinyint(1)      default 1                 not null,
    description       longtext                                  null,
    name              varchar(255)                              not null,
    created_at        datetime        default CURRENT_TIMESTAMP not null,
    updated_at        datetime        default CURRENT_TIMESTAMP not null,
    route             longtext                                  not null,
    http_method       longtext                                  not null comment '(DC2Type:array)',
    rate_limit        bigint unsigned default '0'               not null,
    rate_limit_decay  bigint unsigned default '0'               not null,
    constraint UNIQ_A1C980CB5E237E06
        unique (name),
    constraint FK_A1C980CB54963938
        foreign key (api_id) references apis (id)
)
    collate = utf8mb3_unicode_ci;

create index IDX_A1C980CB54963938
    on api_endpoints (api_id);

create table api_scopes
(
    id                bigint unsigned auto_increment
        primary key,
    api_id            bigint unsigned                    null,
    name              varchar(512)                       not null,
    short_description varchar(512)                       not null,
    description       longtext                           not null,
    active            tinyint(1)                         not null,
    `default`         tinyint(1)                         null,
    `system`          tinyint(1)                         null,
    created_at        datetime default CURRENT_TIMESTAMP not null,
    updated_at        datetime default CURRENT_TIMESTAMP not null,
    constraint FK_8223A4B054963938
        foreign key (api_id) references apis (id)
)
    collate = utf8mb3_unicode_ci;

create index IDX_8223A4B054963938
    on api_scopes (api_id);

create table endpoint_api_authz_groups
(
    id              bigint unsigned auto_increment
        primary key,
    api_endpoint_id bigint unsigned                    not null,
    created_at      datetime default CURRENT_TIMESTAMP not null,
    updated_at      datetime default CURRENT_TIMESTAMP not null,
    group_slug      varchar(512)                       not null,
    constraint UNIQ_B388DE9C4BD8F4B8B1C7C012
        unique (api_endpoint_id, group_slug),
    constraint FK_B388DE9C4BD8F4B8
        foreign key (api_endpoint_id) references api_endpoints (id)
)
    collate = utf8mb3_unicode_ci;

create index IDX_B388DE9C4BD8F4B8
    on endpoint_api_authz_groups (api_endpoint_id);

create table endpoint_api_scopes
(
    id              bigint unsigned auto_increment
        primary key,
    api_endpoint_id bigint unsigned                    not null,
    scope_id        bigint unsigned                    not null,
    created_at      datetime default CURRENT_TIMESTAMP not null,
    updated_at      datetime default CURRENT_TIMESTAMP not null,
    constraint FK_C3E8B8BE4BD8F4B8
        foreign key (api_endpoint_id) references api_endpoints (id),
    constraint FK_C3E8B8BE682B5931
        foreign key (scope_id) references api_scopes (id)
)
    collate = utf8mb3_unicode_ci;

create index IDX_C3E8B8BE4BD8F4B8
    on endpoint_api_scopes (api_endpoint_id);

create index IDX_C3E8B8BE682B5931
    on endpoint_api_scopes (scope_id);

create table ip_rate_limit_routes
(
    id               bigint unsigned auto_increment
        primary key,
    ip               varchar(255)                              not null,
    route            longtext                                  not null,
    active           tinyint(1)      default 1                 not null,
    http_method      longtext                                  not null comment '(DC2Type:array)',
    rate_limit       bigint unsigned default '0'               not null,
    rate_limit_decay bigint unsigned default '0'               not null,
    created_at       datetime        default CURRENT_TIMESTAMP not null,
    updated_at       datetime        default CURRENT_TIMESTAMP not null
)
    collate = utf8mb3_unicode_ci;

create table queue_failed_jobs
(
    id         bigint auto_increment
        primary key,
    connection longtext                           not null,
    queue      longtext                           not null,
    payload    longtext                           not null,
    exception  longtext                           not null,
    failed_at  datetime default CURRENT_TIMESTAMP not null
)
    collate = utf8mb3_unicode_ci;

create table queue_jobs
(
    id           bigint auto_increment
        primary key,
    queue        varchar(255)      not null,
    payload      longtext          not null,
    attempts     smallint unsigned not null,
    reserved_at  int unsigned      null,
    available_at int unsigned      not null,
    created_at   int unsigned      not null
)
    collate = utf8mb3_unicode_ci;

create index queue
    on queue_jobs (queue);
