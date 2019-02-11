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
awk -f cwops.awk <temp2 >cwopsmembers.new

echo Importing special calls
cat cwops.spc >> cwopsmembers.new

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
   all)
      cwops
      fists
      foc
      hsc
      xhsc
      skcc
      ;;
   *)
      echo $"Usage: $0 {cwops|fists|foc|hsc|ehsc|shsc|vhsc|xhsc|skcc|all}"
      ;;
esac
