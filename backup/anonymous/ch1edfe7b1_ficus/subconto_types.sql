create table subconto_types
(
    id     int auto_increment
        primary key,
    name   char(64)             null,
    remove tinyint(1) default 0 null,
    constraint name
        unique (name)
)
    comment 'Субконто';

