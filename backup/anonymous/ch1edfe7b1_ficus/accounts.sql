create table accounts
(
    id             int auto_increment
        primary key,
    number         int                    not null,
    name           varchar(64) default '' not null,
    subconto_type1 int                    null,
    subconto_type2 int                    null,
    count          tinyint(1)  default 0  null,
    remove         tinyint(1)  default 0  null,
    constraint accounts_subcontos_id_fk
        foreign key (subconto_type1) references subconto_types (id),
    constraint accounts_subcontos_id_fk_2
        foreign key (subconto_type2) references subconto_types (id)
)
    comment 'Счета';

