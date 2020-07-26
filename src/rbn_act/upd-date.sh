#!/bin/sh

cd /home/fabian/sites/foc.dj1yfk.de/rbn-raw

dldate=$1

wget http://reversebeacon.net/raw_data/dl.php?f=$dldate -O $dldate".zip"

unzip $dldate".zip"

perl /home/fabian/sites/foc.dj1yfk.de/rbn-raw/act.pl $dldate".csv"

rm -f $dldate".csv"

