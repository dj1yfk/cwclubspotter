-- see http://fkurz.net/ham/stuff.html?rbnbandmap
drop table spots;
CREATE TABLE `spots` (
        `call` varchar(16) NOT NULL default '',
        `freq` decimal(6,1)  NOT NULL default 0,
        `dxcall` varchar(16) NOT NULL default '',
        `fromcont` varchar(2) NOT NULL default '',
        `memberof` varchar(255) NOT NULL default '',
        `comment` varchar(64) NOT NULL default '',
        `snr` int NOT NULL default '0',
        `wpm` int NOT NULL default '0',
        `time` datetime NOT NULL default '1970-01-01',
        `band` int NOT NULL default '0',
        KEY `dxcall` (`dxcall`)
) ENGINE=MEMORY;
