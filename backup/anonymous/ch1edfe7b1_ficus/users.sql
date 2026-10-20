create table users
(
    id          int auto_increment
        primary key,
    registered  timestamp   default CURRENT_TIMESTAMP null,
    email       varchar(32)                           not null,
    double_hash varchar(40) default ''                not null,
    type        json                                  null,
    ip          int(10)     default 0                 null,
    user_hash   varchar(32) default ''                not null,
    subconto    int                                   null,
    constraint email
        unique (email)
);

