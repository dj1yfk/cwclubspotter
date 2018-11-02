-- see http://fkurz.net/ham/stuff.html?rbnbandmap
CREATE TABLE `spots` (
        `ID` bigint(5) NOT NULL auto_increment,
        `call` varchar(16) NOT NULL default '',
        `freq` decimal(6,1)  NOT NULL default 0,
        `dxcall` varchar(16) NOT NULL default '',
        `fromcont` varchar(2) NOT NULL default '',
        `memberof` varchar(64) NOT NULL default '',
        `comment` varchar(64) NOT NULL default '',
        `snr` int NOT NULL default '0',
        `wpm` int NOT NULL default '0',
        `time` datetime NOT NULL default '1970-01-01',
        `band` int NOT NULL default '0',
        PRIMARY KEY (`ID`),
        KEY `dxcall` (`dxcall`)
) AUTO_INCREMENT = 1;
