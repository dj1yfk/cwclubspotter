#!/bin/sh

cd /home/fabian/sites/rbn.telegraphy.de/rbn-raw

dldate=$(date +"%Y%m%d" --date="yesterday")

wget http://reversebeacon.net/raw_data/dl.php?f=$dldate -O $dldate".zip"

unzip $dldate".zip"

perl /home/fabian/sites/rbn.telegraphy.de/rbn-raw/act.pl $dldate".csv"
perl /home/fabian/sites/rbn.telegraphy.de/rbn-raw/fixdxcc.pl 
perl /home/fabian/sites/rbn.telegraphy.de/rbn-raw/fix_hours.pl 

rm -f $dldate".csv"


echo "drop table rbn_rank_beacon;" | mysql -urbnactivity -prbnactivity rbnactivity
echo "set @rank=0; create table rbn_rank_beacon select @rank:=@rank+1 as rank, hours, dxcc, callsign, beacon, wl from rbn_activity where hours > 10 and wl=1 order by hours desc;" |  mysql -urbnactivity -prbnactivity rbnactivity

echo "drop table rbn_rank_nobeacon;" | mysql -urbnactivity -prbnactivity rbnactivity
echo "set @rank=0; create table rbn_rank_nobeacon select @rank:=@rank+1 as rank, hours, dxcc, callsign, beacon, wl from rbn_activity where beacon=0 and hours > 10 and wl=1 order by hours desc;" | mysql -urbnactivity -prbnactivity rbnactivity 


