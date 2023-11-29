-- drop table calendar;
CREATE TABLE `calendar` (
        `id` bigint(5) NOT NULL AUTO_INCREMENT,
        `day` date NOT NULL default '1970-01-01',
        `hours` varchar(64) NOT NULL default '',
        `name` varchar(64) default '',
        `url` varchar(64) default '',
        PRIMARY KEY (`id`)
);
