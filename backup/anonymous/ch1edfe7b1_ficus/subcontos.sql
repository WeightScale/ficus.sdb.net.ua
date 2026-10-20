create table subcontos
(
    id            int auto_increment
        primary key,
    subconto_type int                  not null,
    name          varchar(64)          not null,
    remove        tinyint(1) default 0 null,
    constraint directory_subcontos_id_fk
        foreign key (subconto_type) references subconto_types (id)
)
    comment 'Справочник';

