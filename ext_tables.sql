CREATE TABLE tx_academicpersons_domain_model_address (
    contract int(11) unsigned DEFAULT '0' NOT NULL,
    type varchar(100) DEFAULT '' NOT NULL,

    street varchar(120) DEFAULT '' NOT NULL,
    street_number varchar(6) DEFAULT '' NOT NULL,
    additional varchar(120) DEFAULT '' NOT NULL,
    zip varchar(10) DEFAULT '' NOT NULL,
    city varchar(100) DEFAULT '' NOT NULL,
    state varchar(60) DEFAULT '' NOT NULL,
    country varchar(100) DEFAULT '' NOT NULL,

    sorting int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_contract (
    profile int(11) unsigned DEFAULT '0' NOT NULL,

    organisational_unit int(11) unsigned DEFAULT NULL,
    function_type int(11) unsigned DEFAULT NULL,
    valid_from int(11) DEFAULT NULL,
    valid_to int(11) DEFAULT NULL,
    his_id int(11) DEFAULT '0' NOT NULL,

    employee_type int(11) unsigned DEFAULT '0' NOT NULL,
    position varchar(100) DEFAULT '' NOT NULL,
    location int(11) unsigned DEFAULT '0' NOT NULL,

    room varchar(100) DEFAULT '' NOT NULL,
    office_hours text,

    physical_addresses int(11) unsigned DEFAULT '0' NOT NULL,
    phone_numbers int(11) unsigned DEFAULT '0' NOT NULL,
    email_addresses int(11) unsigned DEFAULT '0' NOT NULL,

    publish tinyint(4) unsigned DEFAULT '0' NOT NULL,
    sorting int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_email (
    contract int(11) unsigned DEFAULT '0' NOT NULL,
    type varchar(100) DEFAULT '' NOT NULL,

    email varchar(255) DEFAULT '' NOT NULL,

    sorting int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_function_type (
    function_name varchar(255) DEFAULT '' NOT NULL,
    function_name_male varchar(255) DEFAULT '' NOT NULL,
    function_name_female varchar(255) DEFAULT '' NOT NULL,
    his_id int(11) DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_location (
    title varchar(100) DEFAULT '' NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_organisational_unit (
    parent int(11) unsigned DEFAULT '0' NOT NULL,
    unit_name varchar(255) DEFAULT '' NOT NULL,
    unique_name varchar(255) DEFAULT '' NOT NULL,
    display_text text,
    long_text text,
    valid_from int(11) unsigned DEFAULT '0' NOT NULL,
    valid_to int(11) unsigned DEFAULT '0' NOT NULL,
    his_id int(11) DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_phone_number (
    contract int(11) unsigned DEFAULT '0' NOT NULL,
    type varchar(100) DEFAULT '' NOT NULL,

    phone_number varchar(60) DEFAULT '' NOT NULL,

    sorting int(11) unsigned DEFAULT '0' NOT NULL,
);

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

    publications_link varchar(255) DEFAULT '' NOT NULL,
    publications_link_title varchar(80) DEFAULT '' NOT NULL,
    website varchar(255) DEFAULT '' NOT NULL,
    website_title varchar(80) DEFAULT '' NOT NULL,

    core_competences text,
    miscellaneous text,
    supervised_thesis text,
    supervised_doctoral_thesis text,
    teaching_area text,

    contracts int(11) unsigned DEFAULT '0' NOT NULL,
    cooperation int(11) unsigned DEFAULT '0' NOT NULL,
    lectures int(11) unsigned DEFAULT '0' NOT NULL,
    memberships int(11) unsigned DEFAULT '0' NOT NULL,
    press_media int(11) unsigned DEFAULT '0' NOT NULL,
    publications int(11) unsigned DEFAULT '0' NOT NULL,
    scientific_research int(11) unsigned DEFAULT '0' NOT NULL,
    vita int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_academicpersons_domain_model_profile_information (
    profile int(11) unsigned DEFAULT '0' NOT NULL,

    type varchar(100) DEFAULT '' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    bodytext text,
    link varchar(2048) DEFAULT '' NOT NULL,
    year int(4) DEFAULT '0' NOT NULL,
    year_start int(4) DEFAULT '0' NOT NULL,
    year_end int(4) DEFAULT '0' NOT NULL,

    sorting int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_academicpersons_contract_address_mm (
    fieldname varchar(255) DEFAULT '' NOT NULL,
);
