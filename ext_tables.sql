CREATE TABLE tx_academicpersons_domain_model_profile (
    gender varchar(50) DEFAULT '' NOT NULL,
    title varchar(50) DEFAULT '' NOT NULL,
    first_name varchar(80) DEFAULT '' NOT NULL,
    first_name_alpha char(1) DEFAULT '' NOT NULL,
    middle_name varchar(80) DEFAULT '' NOT NULL,
    last_name varchar(80) DEFAULT '' NOT NULL,
    last_name_alpha char(1) DEFAULT '' NOT NULL,
    image int(11) unsigned DEFAULT '0' NOT NULL,
    slug varchar(255) DEFAULT '' NOT NULL,
    contracts int(11) unsigned DEFAULT '0' NOT NULL,
    website varchar(255) DEFAULT '' NOT NULL,
    teaching_area text,
    core_competences text,
    memberships text,
    supervised_thesis text,
    supervised_doctoral_thesis text,
    vita text,
    publications text,
    publications_link varchar(255) DEFAULT '' NOT NULL,
    miscellaneous text,
);

CREATE TABLE tx_academicpersons_domain_model_address (
    employee_type int(11) unsigned DEFAULT '0' NOT NULL,
    organisational_level_1 int(11) unsigned DEFAULT '0' NOT NULL,
    organisational_level_2 int(11) unsigned DEFAULT '0' NOT NULL,
    organisational_level_3 int(11) unsigned DEFAULT '0' NOT NULL,
    street varchar(120) DEFAULT '' NOT NULL,
    street_number varchar(6) DEFAULT '' NOT NULL,
    additional varchar(120) DEFAULT '' NOT NULL,
    zip varchar(10) DEFAULT '' NOT NULL,
    city varchar(100) DEFAULT '' NOT NULL,
    state varchar(60) DEFAULT '' NOT NULL,
    country varchar(100) DEFAULT '' NOT NULL,
    type varchar(100) DEFAULT '' NOT NULL,
    contract int(11) unsigned DEFAULT '0' NOT NULL,
    sorting int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_email (
    email varchar(255) DEFAULT '' NOT NULL,
    type varchar(100) DEFAULT '' NOT NULL,
    contract int(11) unsigned DEFAULT '0' NOT NULL,
    sorting int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_phone_number (
    phone_number varchar(60) DEFAULT '' NOT NULL,
    type varchar(100) DEFAULT '' NOT NULL,
    contract int(11) unsigned DEFAULT '0' NOT NULL,
    sorting int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_contract (
    employee_type int(11) unsigned DEFAULT '0' NOT NULL,
    organisational_level_1 int(11) unsigned DEFAULT '0' NOT NULL,
    organisational_level_2 int(11) unsigned DEFAULT '0' NOT NULL,
    organisational_level_3 int(11) unsigned DEFAULT '0' NOT NULL,
    physical_addresses_from_organisation int(11) unsigned DEFAULT '0' NOT NULL,
    physical_addresses int(11) unsigned DEFAULT '0' NOT NULL,
    position varchar(100) DEFAULT '' NOT NULL,
    location varchar(255) DEFAULT '' NOT NULL,
    room varchar(100) DEFAULT '' NOT NULL,
    phone_numbers int(11) unsigned DEFAULT '0' NOT NULL,
    email_addresses int(11) unsigned DEFAULT '0' NOT NULL,
    office_hours text,
    publish tinyint(4) unsigned DEFAULT '0' NOT NULL,
    profile int(11) unsigned DEFAULT '0' NOT NULL,
    sorting int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_academicpersons_contract_address_mm (
    fieldname varchar(255) DEFAULT '' NOT NULL,
);
