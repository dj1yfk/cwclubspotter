#!/bin/sh


echo "drop table rbn_rank_beacon;" | mysql -prbnactivity -urbnactivity rbnactivity
echo "set @rank=0; create table rbn_rank_beacon select @rank:=@rank+1 as rank, hours, dxcc, callsign, beacon, wl from rbn_activity where wl=1 and hours > 10 order by hours desc;" | mysql -prbnactivity -urbnactivity rbnactivity

echo "drop table rbn_rank_nobeacon;" | mysql -prbnactivity -urbnactivity rbnactivity
echo "set @rank=0; create table rbn_rank_nobeacon select @rank:=@rank+1 as rank, hours, dxcc, callsign, beacon, wl from rbn_activity where wl=1 and beacon=0 and hours > 10 order by hours desc;" | mysql -prbnactivity -urbnactivity rbnactivity


