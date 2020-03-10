#!/bin/bash

#################
##### CWOPS #####
#################
function cwops() {
echo Processing CWOPS

echo Saving the old members file
cp -p cwopsmembers.txt old/

echo Extracting the Google Docs URL from the first HTML page
#CWOPS_URL=`curl -s http://www.cwops.org/roster.html | awk '/src/ { sub(/.*src="/,""); sub(/"><\/iframe.*/,""); print}' `
CWOPS_URL=`curl -s http://www.cwops.org/old/roster.html | awk '/src/ { sub(/.*src="/,""); sub(/"><\/iframe.*/,""); print}' `

echo Getting the CWOPS member list
curl -s $CWOPS_URL > temp1

echo Removing HTML formatting
awk '/</ {gsub(/<tr[^>]*>/,"\n"); gsub(/<[^>]*>/," "); print } '<temp1 >temp2

echo Extracting member info 
awk -f cwops.awk <temp2 > temp3

echo Importing special calls
cat cwops.spc >> temp3

echo Removing opt-out calls
C=0
cp temp3 temp3-$C
for a in `cat cwops.exc`  ; do
    D=$(( C + 1 ))
    grep -v "$a" temp3-$C > temp3-$D 
    C=$D
done
mv temp3-$D cwopsmembers.new 

echo Old file has `wc -l cwopsmembers.txt|awk '{print $1}'` members
echo New file has `wc -l cwopsmembers.new|awk '{print $1}'` members

echo Copying new members file into place
mv cwopsmembers.new cwopsmembers.txt

echo
}

#################
##### FISTS #####
#################
function fists() {
echo Processing FISTS

echo Saving the old members file
cp -p fistsmembers.txt old/

echo Getting the FISTS member list
curl -s http://fists.co.uk/docs/pa4n/callsigns.txt > temp1

echo Extracting member info 
awk -f fists.awk <temp1 >fistsmembers.new

echo Importing special calls
cat fists.spc >> fistsmembers.new

echo Old file has `wc -l fistsmembers.txt|awk '{print $1}'` members
echo New file has `wc -l fistsmembers.new|awk '{print $1}'` members

echo Copying new members file into place
mv fistsmembers.new fistsmembers.txt

echo
}

#################
##### FOC   #####
#################
function foc() {
return
    echo Processing FOC

echo Saving the old members file
cp -p focmembers.txt old/

echo Getting the FOC member list
#curl -s https://g4foc.org/Resources/Logger/logger32.txt > temp1
curl -s http://foc.dj1yfk.de/members.txt > temp1

echo Extracting member info 
awk -f foc.awk <temp1 >focmembers.new

echo Importing special calls
cat foc.spc >> focmembers.new

echo Old file has `wc -l focmembers.txt|awk '{print $1}'` members
echo New file has `wc -l focmembers.new|awk '{print $1}'` members

echo Copying new members file into place
mv focmembers.new focmembers.txt

echo
}

#################
##### HSC  #####
#################
function hsc() {
echo Processing HSC

echo Saving the old members file
cp -p hscmembers.txt old/

echo Getting the HSC member list
#curl -s http://hsc.lima-city.de/files/hscmember.txt >temp1
curl -s https://hsc.dj1yfk.de/db/list_hsc.php >temp1

echo Extracting member info 
awk -f hsc.awk <temp1 >temp2

echo Making member list unique
sort temp2 | uniq > hscmembers.new

echo Importing special calls
cat hsc.spc >> hscmembers.new

echo Old HSC file has `wc -l hscmembers.txt|awk '{print $1}'` members
echo New HSC file has `wc -l hscmembers.new|awk '{print $1}'` members

echo Copying new members file into place
mv hscmembers.new hscmembers.txt

echo
}

#####################
##### V/S/EHSC  #####
#####################
function xhsc() {
echo Processing xHSC

echo Saving the old members file
cp -p ehscmembers.txt old/

echo Getting the xHSC member list
curl -s http://hsc.dj1yfk.de/db/hsc_list_by_number.csv > temp1

echo Extracting member info for V/S/EHSC
awk -F";" '/^M/{if ($4 > 0) { print $2; if ($8) { split($8,a,", "); for (key in a) {print a[key] }} }}' temp1 | sort | uniq > vhscmembers.new
awk -F";" '/^M/{if ($5 > 0) { print $2; if ($8) { split($8,a,", "); for (key in a) {print a[key] }} }}' temp1 | sort | uniq > shscmembers.new
awk -F";" '/^M/{if ($6 > 0) { print $2; if ($8) { split($8,a,", "); for (key in a) {print a[key] }} }}' temp1 | sort | uniq > ehscmembers.new

echo Importing special calls
cat vhsc.spc >> vhscmembers.new
cat shsc.spc >> shscmembers.new
cat ehsc.spc >> ehscmembers.new

echo Old VHSC file has `wc -l vhscmembers.txt|awk '{print $1}'` members
echo New VHSC file has `wc -l vhscmembers.new|awk '{print $1}'` members
echo
echo Old SHSC file has `wc -l shscmembers.txt|awk '{print $1}'` members
echo New SHSC file has `wc -l shscmembers.new|awk '{print $1}'` members
echo
echo Old EHSC file has `wc -l ehscmembers.txt|awk '{print $1}'` members
echo New EHSC file has `wc -l ehscmembers.new|awk '{print $1}'` members

echo Copying new members file into place
mv vhscmembers.new vhscmembers.txt
mv shscmembers.new shscmembers.txt
mv ehscmembers.new ehscmembers.txt

echo
}





#################
##### EHSC  #####
#################
function ehsc() {
echo Nope, use xhsc!
return
echo Processing EHSC

echo Saving the old members file
cp -p ehscmembers.txt old/

echo Getting the EHSC member list
curl -s https://www.cqrlog.com/members/ehsc.txt >temp1

echo Extracting member info 
awk -f xhsc.awk <temp1 >ehscmembers.new

echo Importing special calls
cat ehsc.spc >> ehscmembers.new

echo Old EHSC file has `wc -l ehscmembers.txt|awk '{print $1}'` members
echo New EHSC file has `wc -l ehscmembers.new|awk '{print $1}'` members

echo Copying new members file into place
mv ehscmembers.new ehscmembers.txt

echo
}

#################
##### SHSC  #####
#################
function shsc() {
echo Nope, use xhsc!
return
echo Processing SHSC

echo Saving the old members file
cp -p shscmembers.txt old/

echo Getting the SHSC member list
curl -s https://www.cqrlog.com/members/shsc.txt >temp1

echo Extracting member info 
awk -f xhsc.awk <temp1 >shscmembers.new

echo Importing special calls
cat shsc.spc >> shscmembers.new

echo Old SHSC file has `wc -l shscmembers.txt|awk '{print $1}'` members
echo New SHSC file has `wc -l shscmembers.new|awk '{print $1}'` members

echo Copying new members file into place
mv shscmembers.new shscmembers.txt

echo
}

#################
##### VHSC  #####
#################
function vhsc() {
echo Nope, use xhsc!
return
echo Processing VHSC

echo Saving the old members file
cp -p vhscmembers.txt old/

echo Getting the VHSC member list
curl -s https://www.cqrlog.com/members/vhsc.txt >temp1

echo Extracting member info 
awk -f xhsc.awk <temp1 >vhscmembers.new

echo Importing special calls
cat vhsc.spc >> vhscmembers.new

echo Old VHSC file has `wc -l vhscmembers.txt|awk '{print $1}'` members
echo New VHSC file has `wc -l vhscmembers.new|awk '{print $1}'` members

echo Copying new members file into place
mv vhscmembers.new vhscmembers.txt

echo
}

#################
##### SKCC  #####
#################
function skcc() {
echo Processing SKCC

echo Saving the old members file
cp -p skccmembers.txt old/

echo Getting the SKCC member list
curl -s https://www.skccgroup.com/membership_data/membership_search.php > temp1

awk '/tr/{split($0, a, "<tr>"); for (line in a) { print a[line]; } }' temp1 | \
    grep -v "\[SK\]" | \
    sed 's/<\/td>/;/g' | sed 's/<td>/;/g' | awk -F";" '{print $4" "$16}' | \
    sed 's/,//g' | fmt -1 | sort | uniq > skccmembers.new

echo Importing special calls
cat skcc.spc >> skccmembers.new

echo Old file has `wc -l skccmembers.txt|awk '{print $1}'` members
echo New file has `wc -l skccmembers.new|awk '{print $1}'` members

echo Copying new members file into place
mv skccmembers.new skccmembers.txt

echo
}

#################
##### NAQCC #####
#################
function naqcc() {
echo Processing NAQCC

echo Saving the old members file
cp -p naqccmembers.txt old/

echo Getting the NAQCC member list
curl -s http://naqcc.info/NAQCClistAlpha.txt > temp1

echo Extracting member info 
awk '{print $1}' temp1 > naqccmembers.new

echo Importing special calls
cat naqcc.spc >> naqccmembers.new

echo Old file has `wc -l naqccmembers.txt|awk '{print $1}'` members
echo New file has `wc -l naqccmembers.new|awk '{print $1}'` members

echo Copying new members file into place
mv naqccmembers.new naqccmembers.txt

echo
}

#################
##### RCWC  #####
#################

function rcwc() {
echo Processing RCWC

echo Saving the old members file
cp -p rcwcmembers.txt old/

echo Getting the RCWC member list
curl -s "http://rcwc.ru/?do=members&getlist=3" > temp1

echo Extracting member info 
awk '//{print $1}' < temp1 > rcwcmembers.new

echo Importing special calls
cat rcwc.spc >> rcwcmembers.new

echo Old file has `wc -l rcwcmembers.txt|awk '{print $1}'` members
echo New file has `wc -l rcwcmembers.new|awk '{print $1}'` members

echo Copying new members file into place
mv rcwcmembers.new rcwcmembers.txt

echo
}


#################
##### LIDS ######
#################

function lids() {
N=lids
echo Processing $N

echo Saving the old members file
cp -p ${N}members.txt old/

echo Getting the $N member list
curl -s "http://lidscw.org/members" > temp1

echo Extracting member info 
perl lids.pl temp1 > ${N}members.new

echo Importing special calls
cat lids.spc >> ${N}members.new

echo Old file has `wc -l ${N}members.txt|awk '{print $1}'` members
echo New file has `wc -l ${N}members.new|awk '{print $1}'` members

echo Copying new members file into place
mv ${N}members.new ${N}members.txt

echo
}



#################
##### Main  #####
#################

case "$1" in
   cwops)
      cwops
      ;;
   fists)
      fists
      ;;
   foc)
      foc
      ;;
   hsc)
      hsc
      ;;
   ehsc)
      ehsc
      ;;
   shsc)
      shsc
      ;;
   vhsc)
      vhsc
      ;;
   xhsc)
      xhsc
      ;;
   skcc)
      skcc
      ;;
   naqcc)
      naqcc 
      ;;
   rcwc)
      rcwc 
      ;;
  lids)
      lids
      ;;
   all)
      cwops
      fists
      foc
      hsc
      xhsc
      skcc
      naqcc 
      rcwc
      lids
      touch /home/fabian/sites/foc.dj1yfk.de/members.txt # :D:D:D
      ;;
   *)
      echo $"Usage: $0 {cwops|fists|foc|hsc|ehsc|shsc|vhsc|xhsc|skcc|naqcc|rcwc|lids|all}"
      ;;
esac
