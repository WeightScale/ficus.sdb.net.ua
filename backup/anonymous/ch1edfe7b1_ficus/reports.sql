create table reports
(
    id       int auto_increment
        primary key,
    user     int                                not null,
    name     varchar(64)                        not null,
    value    json                               not null,
    `update` datetime default CURRENT_TIMESTAMP not null,
    constraint reports_users_id_fk
        foreign key (user) references users (id)
);

create index user__index
    on reports (user);

