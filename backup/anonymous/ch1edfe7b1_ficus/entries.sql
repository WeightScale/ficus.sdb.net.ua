create table entries
(
    id               int auto_increment
        primary key,
    registered       timestamp  default CURRENT_TIMESTAMP not null,
    date             date                                 not null,
    debit            int                                  not null,
    debit_subconto1  int                                  null,
    debit_subconto2  int                                  null,
    credit           int                                  not null,
    credit_subconto1 int                                  null,
    credit_subconto2 int                                  null,
    sum              decimal(10, 2)                       not null,
    currency         varchar(4)                           not null,
    note             text                                 null,
    counts           int        default 0                 null,
    price            decimal(10, 2)                       null,
    user             int                                  not null,
    remove           tinyint(1) default 0                 null,
    child            int                                  null comment 'children row id for uddate operation',
    parent           int                                  null comment 'if delete and update from parent row id',
    changed          timestamp  default CURRENT_TIMESTAMP not null,
    constraint entries_accounts_id_fk
        foreign key (debit) references accounts (id),
    constraint entries_accounts_id_fk_2
        foreign key (credit) references accounts (id),
    constraint entries_fields_id_fk
        foreign key (debit_subconto1) references subcontos (id),
    constraint entries_fields_id_fk_2
        foreign key (debit_subconto2) references subcontos (id),
    constraint entries_fields_id_fk_3
        foreign key (credit_subconto1) references subcontos (id),
    constraint entries_fields_id_fk_4
        foreign key (credit_subconto2) references subcontos (id)
)
    comment 'Проводки';

