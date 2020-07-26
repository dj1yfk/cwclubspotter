DROP TABLE IF EXISTS `rbn_activity`;
CREATE TABLE `rbn_activity` (
  `callsign` varchar(24) NOT NULL,
  `data` blob NOT NULL,
  `hours` int(11) NOT NULL DEFAULT 0,
  `beacon` tinyint(4) NOT NULL DEFAULT 0,
  `dxcc` varchar(4) NOT NULL DEFAULT '',
  `wl` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`callsign`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `rbn_rank_beacon`;
CREATE TABLE `rbn_rank_beacon` (
  `rank` bigint(21) DEFAULT NULL,
  `hours` int(11) NOT NULL DEFAULT 0,
  `dxcc` varchar(4) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `callsign` varchar(24) CHARACTER SET latin1 NOT NULL,
  `beacon` tinyint(4) NOT NULL DEFAULT 0,
  `wl` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `rbn_rank_nobeacon`;
CREATE TABLE `rbn_rank_nobeacon` (
  `rank` bigint(21) DEFAULT NULL,
  `hours` int(11) NOT NULL DEFAULT 0,
  `dxcc` varchar(4) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `callsign` varchar(24) CHARACTER SET latin1 NOT NULL,
  `beacon` tinyint(4) NOT NULL DEFAULT 0,
  `wl` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

