# RBN / DX Cluster Bandmap view with PHP and JavaScript / AJAX
# Description: http://fkurz.net/ham/stuff.html?rbnbandmap
#
# This Perl script connects to a RBN telnet server, filters
# spots for calls in "*members.txt", and writes them to a SQL
# database.
#
# It adds the continent of the DX station with some code from
# "dxcc" (http://fkurz.net/ham/dxcc.html) which is slapped to
# the end of the file. It's not very pretty...
#
# Fabian Kurz, DJ1YFK <fabian@fkurz.net>
# 2012-12-22
#
# Frank R. Oppedijk, PA4N <pa4n@xs4all.nl>
# 2013-04-26
#
# This code is in the public domain.

use warnings;
use strict;
use DBI;
use Date::Format qw( );
use POSIX qw( strftime );
use Net::Telnet ();
use Redis;

$| =  1;

my %callhash;
my %callcount;

my %conts;

my $line;


my $r = Redis->new();

################
# for dxcc stuff
my %prefixes;            # hash of arrays  main prefix -> (all, prefixes,..)
my %dxcc;                # hash of arrays  main prefix -> (CQZ, ITUZ, ...)
my $mainprefix;
my @dxcc;
&read_cty();
##################

# additionally, the lowest bit in bm_conts is used as an indicator of a
# "verified" spot. rules for verified spots: at least 3 spots near this
# freq and max one spot per call on this freq in 5 minutes.
my %bm_conts = ( 'OC' => 0x04, 'AF' => 0x08, 'SA' => 0x10, 'AS' => 0x20, 'NA' => 0x40, 'EU' => 0x80 );

my %bm_bands = ( '160' => 0x0001, '80' => 0x0002, '60' => 0x0004, '40' => 0x0008, '30' => 0x0010, '20' => 0x0020, '17' => 0x0040, '15' => 0x0080, '12' => 0x0100, '10' => 0x0200, '6' => 0x0400, '4' => 0x0800, '2' => 0x1000);

my @clubs = qw/CWOPS FISTS FOC HSC VHSC SHSC EHSC SKCC AGCW NAQCC BUG RCWC LIDS NRR QRPARCI CWJF TORCW SOC UFT ECWARC LICW EACW MF A1C NTC MORSE 4SQRP 30CW SPCWC HTC UQRQC GPCW MCARI SMHSC OECWG CFO CFT/;
my %bm = ();
# create bit masks from @clubs array
my $i = 0;
my %membersize = ();
foreach my $club (@clubs) {
    $bm{$club} = 1 << $i;
    $membersize{$club} = 0;
    $i++;
}

my $dbh = DBI->connect("DBI:mysql:spotfilter;host=localhost",'spotfilter','spotfilter') or die "Could not connect to MySQL database: " .  DBI->errstr;

open SPOTDUMP, ">/tmp/spots.txt";

$| =  1;
my @lines;
print "rbn: trying to connect....\n";
my $t = new Net::Telnet (Timeout => 10, Port => 7300, Prompt => '/./');
$t->open("foc.dj1yfk.de");
$t->print("DJ5CW-9\n");
$t->print("set/raw\n");
print "rbn: connected... \n";

my $db_keepalive = time;

loadcalls();
while (1) {
        $line = $t->getline(Timeout => 60);
        if (!$line) {
            print "RBN feed died!\n";
            exit;
         }
        chop($line);
        $line = $line."\r\n";
        next unless ($line =~ /^DX/);

        next if ($line =~ /DK1DU/);    # opt-out
        next if ($line =~ /DK2DO/);    # opt-out
        next if ($line =~ /R6AF/);    # opt-out
        next if ($line =~ /HA2ZB/);    # opt-out

        my %spot = &parse_line($line);

        if ($spot{mode} ne "CW") {
            next;
        }
        next if ($line =~ /BEACON/);
        next if ($line =~ /NCDXF/);

        if (time - $db_keepalive > 30) {
            $dbh->do("select 1") or die "MySQL server died... ".DBI->errstr;
            $db_keepalive = time;
            loadcalls();
        }

        # remove portable indicators for checking
        # against member databases. e.g. PA/DJ1YFK/P => DJ1YFK
        my $stripcall = $spot{dxcall};
        if ($stripcall =~ /^(\w{1,3}\/)?(\w{3,99})(\/\w{1,3})?$/) {
                $stripcall = $2;
        }

        # more striptease (to recognize e.g. GW4XYZ as G4XYZ)
        &strip_ukcd_calls($stripcall);

        if ($callhash{$stripcall}) {
            $spot{member} = $callhash{$stripcall};    # save bitmask for club membership in spot hahs
            &save_spot(\%spot);                        # save to SQL database
        }
        else {
            $spot{member} = 0;
            &save_spot(\%spot);
        }

}

sub parse_line {
    my $line = shift;
    $line =~ s/[\r\n]+$//;

    my @a = split(/\s+/, $line, 6);

    my %spot = ();
    $spot{line} = $line; # save original line
    $spot{call} = $a[2];
    $spot{call} =~ s/[^a-z0-9\/]//gi;
    $spot{freq} = $a[3];
    $spot{dxcall} = $a[4];
    $spot{dxcall} =~ s/[^a-z0-9\/]//gi;
    $spot{comment} = $a[5];
    $spot{snr} = substr($spot{comment}, 6, 2);
    $spot{wpm} = substr($spot{comment}, 13, 2);
    $spot{utc} = substr($spot{comment}, -5, 4);
    $spot{mode} = substr($spot{comment}, 0, 2);
    $spot{band} = &freq2band($spot{freq});

    # cache continent informations (less CPU time)
    if (defined($conts{$spot{call}})) {
        $spot{cont} = $conts{$spot{call}};
    }
    else {
        $spot{cont} = (&dxcc($spot{call}))[3];
        $conts{$spot{call}} = $spot{cont};
    }

    return %spot;
}

sub loadcalls {
    my $any_change = 0;
    foreach my $club (@clubs) {
        my $filename = "members/".lc($club)."members.txt";
        if ($membersize{$club} != -s $filename) {
            $membersize{$club} = -s $filename;
            $any_change = 1;
        }
    }

    return if ($any_change == 0);

    %callhash = ();

    # at least one club had a change, so we need
    # to completely reload $callhash (because some
    # members may be deleted)
    foreach my $club (@clubs) {
        my $filename = "members/".lc($club)."members.txt";
        open CALLS, $filename;
        while (my $a = <CALLS>) {
            chomp($a);
            $a =~ s/\s//g;
            $a =~ s/\r//g;
            strip_ukcd_calls($a);
            print "Reading $club member [".$a."]\n";
            $callhash{$a} |= $bm{$club};
        }
        close CALLS;
    }
}

# strip regional locators from UK&CD calls.
# i.e. GM3HGE => G3HGE, 2U0ARE = 20ARE
# these stripped calls are only used to
# compare spotted calls against the member
# lists in which they are also stripped.
# so it doesn't matter that 2-callsigns
# are mangled...
sub strip_ukcd_calls {
    if ($_[0] =~ /^([GM2])[A-Z](\d.*)/) {
        $_[0] = $1.$2;
    }
}


# Insert into database...
sub save_spot {
    my %spot = %{$_[0]};

	# RBN stats guys calling off band
	if ($spot{dxcall} eq "WR5U" or $spot{dxcall} eq "DJ6ZM") {
		if (($spot{band} == 160 and $spot{freq} > 1840) or
			($spot{band} == 80 and $spot{freq} > 3569.5) or
			($spot{band} == 60 and $spot{freq} > 5356) or
			($spot{band} == 40 and $spot{freq} > 7040) or
			($spot{band} == 30 and $spot{freq} > 10129) or
			($spot{band} == 20 and $spot{freq} > 14070) or
			($spot{band} == 17 and $spot{freq} > 18090) or
			($spot{band} == 15 and $spot{freq} > 21061) or
			($spot{band} == 12 and $spot{freq} > 24920) or
			($spot{band} == 10 and $spot{freq} > 28070)) {
			return;
		}
	}

    $spot{memberof} = "";
    foreach my $club (@clubs) {
        $spot{memberof}  .= "($club) " if ($spot{member} & $bm{$club});
    }

    $spot{comment} =~ s/\s+$//g;
    $spot{comment} = $dbh->quote($spot{comment});

    my ($minute, $hour, $day, $month, $year) = (gmtime(time))[1,2,3,4,5];
    $minute= sprintf("%02d", $minute);
    $hour = sprintf("%02d", $hour);
    $month = sprintf("%02d", $month+1);
    $day = sprintf("%02d", $day);
    $year += 1900;
    # this should use strftime, or just use NOW() for the SQL statement,
    # but whatever... :)
    my $time = "$year-$month-$day $hour:$minute:00";  # always use gmtime, ignore spot time

    # delete any old spots on the same band from this one
    my $dbhret = $dbh->do("delete from spots where `call`='$spot{call}' and band='$spot{band}' and dxcall='$spot{dxcall}'");
    $dbhret = $dbh->do("delete from spots where band='$spot{band}' and dxcall='$spot{dxcall}' and abs(freq - $spot{freq}) > 0.4");

    $spot{'memberof'} = substr($spot{'memberof'}, 0, 255);

    $dbh->do("INSERT INTO spots (`call`, `freq`, `dxcall`, `memberof`, `comment`, `snr`, `wpm`, `time`, `band`, `fromcont`, `member`) VALUES ('$spot{call}', '$spot{freq}', '$spot{dxcall}', '$spot{memberof}', $spot{comment}, '$spot{snr}', $spot{wpm}, '$time', '$spot{band}', '$spot{cont}', $spot{member});");

    # fix freq to average
    $dbhret = $dbh->prepare("select round(avg(freq),1) as newfreq from spots where dxcall='$spot{dxcall}' and band=$spot{band};");
    $dbhret->execute();
    
    my $nf = 0;
    $dbhret->bind_columns(\$nf);
    if ($dbhret->fetch() && $nf != 0) {
        $dbh->do("update spots set freq = $nf where dxcall='$spot{dxcall}' and band = $spot{band}");
    }       

    my $line2=sprintf("%s %-24.24s %2.2s %02X %s", substr($line, 0, 39), $spot{memberof}, $spot{cont}, $spot{member}, substr($line, 70)); 
    $| = 1;
    print STDOUT $line2;

    # for Redis, we publish:
    # 1 byte continent info + lowest bit = "verified" spot 
    # 8 byte member info
    # 1 byte speed
    # 2 byte band info
    # original line

    my $bm_speed = 0;
    if    ($spot{wpm} <= 10) { $bm_speed = 0x01; }
    elsif ($spot{wpm} <= 14) { $bm_speed = 0x02; }  
    elsif ($spot{wpm} <= 19) { $bm_speed = 0x04; }  
    elsif ($spot{wpm} <= 24) { $bm_speed = 0x08; }  
    elsif ($spot{wpm} <= 29) { $bm_speed = 0x10; }  
    elsif ($spot{wpm} <= 34) { $bm_speed = 0x20; }  
    elsif ($spot{wpm} <= 39) { $bm_speed = 0x40; }  
    elsif ($spot{wpm} >  39) { $bm_speed = 0x80; }  

    my $line3 = pack("C", $bm_conts{$spot{cont}} | &is_verified(%spot));
    $line3 .= pack("Q", $spot{member});
    $line3 .= pack("C", $bm_speed);
    $line3 .= pack("v", $bm_bands{$spot{band}});
    $line3 .= $spot{line};

    $r->publish('rbn', $line3."\r\n");
}

# filter for "verified" spots. a "verified" spot means:
# - at least 3 spots in this frequency (+/-)
# - max one spot per freq (+/-) every 5 minutes
sub is_verified {
    #   my %spot = %{$_[0]};
    return 0;
}

sub freq2band {
                my $freq = shift;

                if (($freq >= 135) && ($freq <= 138)) { $freq = "2190"; }
                elsif (($freq >= 1800) && ($freq <= 2000)) { $freq = "160"; }
                elsif (($freq >= 3500) && ($freq <= 4000)) { $freq = "80"; }
                elsif (($freq >= 5000) && ($freq <= 5999)) { $freq = "60"; }
                elsif (($freq >= 7000) && ($freq <= 7300)) { $freq = "40"; }
                elsif (($freq >=10100) && ($freq <=10150)) { $freq = "30"; }
                elsif (($freq >=14000) && ($freq <=14350)) { $freq = "20"; }
                elsif (($freq >=18068) && ($freq <=18168)) { $freq = "17"; }
                elsif (($freq >=21000) && ($freq <=21450)) { $freq = "15"; }
                elsif (($freq >=24890) && ($freq <=24990)) { $freq = "12"; }
                elsif (($freq >=28000) && ($freq <=29700)) { $freq = "10"; }
                elsif (($freq >=50000) && ($freq <=54000)) { $freq = "6"; }
                elsif (($freq >=144000) && ($freq <=148000)) { $freq = "2"; }
                elsif (($freq >=430000) && ($freq <=460000)) { $freq = "0.7"; }
                elsif (($freq >=1200000) && ($freq <=1300000)) { $freq = "0.23"; }
                else {
                        $freq = 0;
                }
                return $freq;
}

# the following code is from my dxcc Perl tool...


###############################################################################
#
# &wpx derives the Prefix following WPX rules from a call. These can be found
# at: http://www.cq-amateur-radio.com/wpxrules.html
#  e.g. DJ1YFK/TF3  can be counted as both DJ1 or TF3, but this sub does 
# not ask for that, always TF3 (= the attached prefix) is returned. If that is 
# not want the OP wanted, it can still be modified manually.
#
###############################################################################
 
sub wpx {
  my ($prefix,$a,$b,$c);
  
  # First check if the call is in the proper format, A/B/C where A and C
  # are optional (prefix of guest country and P, MM, AM etc) and B is the
  # callsign. Only letters, figures and "/" is accepted, no further check if the
  # callsign "makes sense".
  # 23.Apr.06: Added another "/X" to the regex, for calls like RV0AL/0/P
  # as used by RDA-DXpeditions....
    
if ($_[0] =~ 
    /^((\d|[A-Z])+\/)?((\d|[A-Z]){3,})(\/(\d|[A-Z])+)?(\/(\d|[A-Z])+)?$/) {
   
    # Now $1 holds A (incl /), $3 holds the callsign B and $5 has C
    # We save them to $a, $b and $c respectively to ensure they won't get 
    # lost in further Regex evaluations.
   
    ($a, $b, $c) = ($1, $3, $5);
    if ($a) { chop $a };            # Remove the / at the end 
    if ($c) { $c = substr($c,1,)};  # Remove the / at the beginning
    
    # In some cases when there is no part A but B and C, and C is longer than 2
    # letters, it happens that $a and $b get the values that $b and $c should
    # have. This often happens with liddish callsign-additions like /QRP and
    # /LGT, but also with calls like DJ1YFK/KP5. ~/.yfklog has a line called    
    # "lidadditions", which has QRP and LGT as defaults. This sorts out half of
    # the problem, but not calls like DJ1YFK/KH5. This is tested in a second
    # try: $a looks like a call (.\d[A-Z]) and $b doesn't (.\d), they are
    # swapped. This still does not properly handle calls like DJ1YFK/KH7K where
    # only the OP's experience says that it's DJ1YFK on KH7K.

if (!$c && $a && $b) {                  # $a and $b exist, no $c
        if (0) {    # check if $b is a lid-addition
            $b = $a; $a = undef;        # $a goes to $b, delete lid-add
        }
        elsif (($a =~ /\d[A-Z]+$/) && ($b =~ /\d$/)) {   # check for call in $a
        }
}    

    # *** Added later ***  The check didn't make sure that the callsign
    # contains a letter. there are letter-only callsigns like RAEM, but not
    # figure-only calls. 

    if ($b =~ /^[0-9]+$/) {            # Callsign only consists of numbers. Bad!
            return undef;            # exit, undef
    }

    # Depending on these values we have to determine the prefix.
    # Following cases are possible:
    #
    # 1.    $a and $c undef --> only callsign, subcases
    # 1.1   $b contains a number -> everything from start to number
    # 1.2   $b contains no number -> first two letters plus 0 
    # 2.    $a undef, subcases:
    # 2.1   $c is only a number -> $a with changed number
    # 2.2   $c is /P,/M,/MM,/AM -> 1. 
    # 2.3   $c is something else and will be interpreted as a Prefix
    # 3.    $a is defined, will be taken as PFX, regardless of $c 

    if ((not defined $a) && (not defined $c)) {  # Case 1
            if ($b =~ /\d/) {                    # Case 1.1, contains number
                $b =~ /(.+\d)[A-Z]*/;            # Prefix is all but the last
                $prefix = $1;                    # Letters
            }
            else {                               # Case 1.2, no number 
                $prefix = substr($b,0,2) . "0";  # first two + 0
            }
    }        
    elsif ((not defined $a) && (defined $c)) {   # Case 2, CALL/X
           if ($c =~ /^(\d)$/) {              # Case 2.1, number
                $b =~ /(.+\d)[A-Z]*/;            # regular Prefix in $1
                # Here we need to find out how many digits there are in the
                # prefix, because for example A45XR/0 is A40. If there are 2
                # numbers, the first is not deleted. If course in exotic cases
                # like N66A/7 -> N7 this brings the wrong result of N67, but I
                # think that's rather irrelevant cos such calls rarely appear
                # and if they do, it's very unlikely for them to have a number
                # attached.   You can still edit it by hand anyway..  
                if ($1 =~ /^([A-Z]\d)\d$/) {        # e.g. A45   $c = 0
                                $prefix = $1 . $c;  # ->   A40
                }
                else {                         # Otherwise cut all numbers
                $1 =~ /(.*[A-Z])\d+/;          # Prefix w/o number in $1
                $prefix = $1 . $c;}            # Add attached number    
            } 
            elsif (0) {
                $b =~ /(.+\d)[A-Z]*/;       # Known attachment -> like Case 1.1
                $prefix = $1;
            }
            elsif ($c =~ /^\d\d+$/) {        # more than 2 numbers -> ignore
                $b =~ /(.+\d)[A-Z]*/;       # see above
                $prefix = $1;
            }
            else {                          # Must be a Prefix!
                    if ($c =~ /\d$/) {      # ends in number -> good prefix
                            $prefix = $c;
                    }
                    else {                  # Add Zero at the end
                            $prefix = $c . "0";
                    }
            }
    }
    elsif (defined $a) {                    # $a contains the prefix we want
            if ($a =~ /\d$/) {              # ends in number -> good prefix
                    $prefix = $a
            }
            else {                          # add zero if no number
                    $prefix = $a . "0";
            }
    }

# In very rare cases (right now I can only think of KH5K and KH7K and FRxG/T
# etc), the prefix is wrong, for example KH5K/DJ1YFK would be KH5K0. In this
# case, the superfluous part will be cropped. Since this, however, changes the
# DXCC of the prefix, this will NOT happen when invoked from with an
# extra parameter $_[1]; this will happen when invoking it from &dxcc.
    
if (($prefix =~ /(\w+\d)[A-Z]+\d/) && (not defined $_[1])) {
        $prefix = $1;                
}
    
return $prefix;
}
else { return ''; }    # no proper callsign received.
} # wpx ends here


##############################################################################
#
# &dxcc determines the DXCC country of a given callsign using the cty.dat file
# provided by K1EA at http://www.k1ea.com/cty/cty.dat .
# An example entry of the file looks like this:
#
# Portugal:                 14:  37:  EU:   38.70:     9.20:     0.0:  CT:
#     CQ,CR,CR5A,CR5EBD,CR6EDX,CR7A,CR8A,CR8BWW,CS,CS98,CT,CT98;
#
# The first line contains the name of the country, WAZ, ITU zones, continent, 
# latitude, longitude, UTC difference and main Prefix, the second line contains 
# possible Prefixes and/or whole callsigns that fit for the country, sometimes 
# followed by zones in brackets (WAZ in (), ITU in []).
#
# This sub checks the callsign against this list and the DXCC in which 
# the best match (most matching characters) appear. This is needed because for 
# example the CTY file specifies only "D" for Germany, "D4" for Cape Verde.
# Also some "unusual" callsigns which appear to be in wrong DXCCs will be 
# assigned properly this way, for example Antarctic-Callsigns.
# 
# Then the callsign (or what appears to be the part determining the DXCC if
# there is a "/" in the callsign) will be checked against the list of prefixes
# and the best matching one will be taken as DXCC.
#
# The return-value will be an array ("Country Name", "WAZ", "ITU", "Continent",
# "latitude", "longitude", "UTC difference", "DXCC").   
#
###############################################################################

sub dxcc {
    my $testcall = shift;
    my $matchchars=0;
    my $matchprefix='';
    my $test;
    my $zones = '';                 # annoying zone exceptions
    my $goodzone;
    my $letter='';


if ($testcall =~ /(^OH\/)|(\/OH[1-9]?$)/) {    # non-Aland prefix!
    $testcall = "OH";                      # make callsign OH = finland
}
elsif ($testcall =~ /(^3D2R)|(^3D2.+\/R)/) { # seems to be from Rotuma
    $testcall = "3D2RR";                 # will match with Rotuma
}
elsif ($testcall =~ /^3D2C/) {               # seems to be from Conway Reef
    $testcall = "3D2CR";                 # will match with Conway
}
elsif ($testcall =~ /\w\/\w/) {             # check if the callsign has a "/"
    $testcall = &wpx($testcall,1)."AA";        # use the wpx prefix instead, which may
                                         # intentionally be wrong, see &wpx!
}

$letter = substr($testcall, 0,1);

foreach $mainprefix (keys %prefixes) {

    foreach $test (@{$prefixes{$mainprefix}}) {
        my $len = length($test);

        if ($letter ne substr($test,0,1)) {            # gains 20% speed
            next;
        }

        $zones = '';

        if (($len > 5) && ((index($test, '(') > -1)            # extra zones
                        || (index($test, '[') > -1))) {
                $test =~ /^([A-Z0-9\/]+)([\[\(].+)/;
                $zones .= $2 if defined $2;
                $len = length($1);
        }

        if ((substr($testcall, 0, $len) eq substr($test,0,$len)) &&
                                ($matchchars <= $len))    {
            $matchchars = $len;
            $matchprefix = $mainprefix;
            $goodzone = $zones;
        }
    }
}

my @mydxcc;                                        # save typing work

if (defined($dxcc{$matchprefix})) {
    @mydxcc = @{$dxcc{$matchprefix}};
}
else {
    @mydxcc = qw/Unknown 0 0 0 0 0 0 ?/;
}

# Different zones?

if ($goodzone) {
    if ($goodzone =~ /\((\d+)\)/) {                # CQ-Zone in ()
        $mydxcc[1] = $1;
    }
    if ($goodzone =~ /\[(\d+)\]/) {                # ITU-Zone in []
        $mydxcc[2] = $1;
    }
}

# cty.dat has special entries for WAE countries which are not separate DXCC
# countries. Those start with a "*", for example *TA1. Those have to be changed
# to the proper DXCC. Since there are opnly a few of them, it is hardcoded in
# here.

if ($mydxcc[7] =~ /^\*/) {                            # WAE country!
    if ($mydxcc[7] eq '*TA1') { $mydxcc[7] = "TA" }        # Turkey
    if ($mydxcc[7] eq '*4U1V') { $mydxcc[7] = "OE" }    # 4U1VIC is in OE..
    if ($mydxcc[7] eq '*GM/s') { $mydxcc[7] = "GM" }    # Shetlands
    if ($mydxcc[7] eq '*IG9') { $mydxcc[7] = "I" }        # African Italy
    if ($mydxcc[7] eq '*IT9') { $mydxcc[7] = "I" }        # Sicily
    if ($mydxcc[7] eq '*JW/b') { $mydxcc[7] = "JW" }    # Bear Island

}

# CTY.dat uses "/" in some DXCC names, but I prefer to remove them, for example
# VP8/s ==> VP8s etc.

$mydxcc[7] =~ s/\///g;

return @mydxcc; 

} # dxcc ends here 


sub read_cty {
    # Read cty.dat from AD1C, or this program itself (contains cty.dat)
    my $self=0;
    my $filename;

    if (-e "/usr/share/dxcc/cty.dat") {
        $filename = "/usr/share/dxcc/cty.dat";
    }
    elsif (-e "/usr/local/share/dxcc/cty.dat") {
        $filename = "/usr/local/share/dxcc/cty.dat";
    }
    else {
        $filename = $0;
        $self = 1;
    }

    open CTY, $filename;

    while (my $line = <CTY>) {
        # When opening itself, skip all lines before "CTY".
        if ($self) {
            if ($line =~ /^#CTY/) {
                $self = 0
            }
            next;
        }

        # In case we're reading this file, remove #s
        if (substr($line, 0, 1) eq '#') {
            substr($line, 0, 1) = '';
        }

        if (substr($line, 0, 1) ne ' ') {            # New DXCC
            $line =~ /\s+([*A-Za-z0-9\/]+):\s+$/;
            $mainprefix = $1;
            $line =~ s/\s{2,}//g;
            @{$dxcc{$mainprefix}} = split(/:/, $line);
        }
        else {                                        # prefix-line
            $line =~ s/\s+//g;
            unless (defined($prefixes{$mainprefix}[0])) {
                @{$prefixes{$mainprefix}} = split(/,|;/, $line);
            }
            else {
                push(@{$prefixes{$mainprefix}}, split(/,|;/, $line));
            }
        }
    }
    close CTY;

} # read_cty


exit;
#CTY
#Sov Mil Order of Malta:   15:  28:  EU:   41.90:   -12.40:    -1.0:  1A:
#    1A;
#Spratly Is.:              26:  50:  AS:    8.80:  -111.90:    -8.0:  1S:
#    1S,9M0,BV9S,9M2/PG5M,9M4SDX,DU0K,DX0JP,DX0K;
#Monaco:                   14:  27:  EU:   43.70:    -7.40:    -1.0:  3A:
#    3A;
#Agalega & St. Brandon:    39:  53:  AF:  -10.40:   -56.60:    -4.0:  3B6:
#    3B6,3B7;
#Mauritius:                39:  53:  AF:  -20.30:   -57.50:    -4.0:  3B8:
#    3B8;
#Rodriguez I.:             39:  53:  AF:  -19.70:   -63.40:    -4.0:  3B9:
#    3B9;
#Equatorial Guinea:        36:  47:  AF:    1.80:    -9.80:    -1.0:  3C:
#    3C;
#Annobon:                  36:  52:  AF:   -1.50:    -5.60:     0.0:  3C0:
#    3C0;
#Fiji:                     32:  56:  OC:  -18.10:  -178.40:   -12.0:  3D2:
#    3D2;
#Conway Reef:              32:  56:  OC:  -21.40:  -174.40:   -13.0:  3D2/c:
#    3D2CI,3D2CY;
#Rotuma:                   32:  56:  OC:  -12.30:  -177.70:   -12.0:  3D2/r:
#    3D2AG/P,3D2RR,3D2RX;
#Swaziland:                38:  57:  AF:  -26.30:   -31.10:    -2.0:  3DA:
#    3DA;
#Tunisia:                  33:  37:  AF:   36.80:   -10.20:    -1.0:  3V:
#    3V,TS;
#Vietnam:                  26:  49:  AS:   10.80:  -106.70:    -7.0:  3W:
#    3W,XV;
#Guinea:                   35:  46:  AF:    9.50:    13.70:     0.0:  3X:
#    3X;
#Bouvet:                   38:  67:  AF:  -54.50:    -3.40:     0.0:  3Y/b:
#    3Y;
#Peter I I.:               12:  72:  SA:  -68.80:    90.60:     6.0:  3Y/p:
#    3Y0PI,3Y0X,3Y1EE;
#Azerbaijan:               21:  29:  AS:   40.40:   -49.90:    -4.0:  4J:
#    4J,4K;
#Georgia:                  21:  29:  AS:   41.70:   -44.80:    -4.0:  4L:
#    4L;
#Montenegro:               15:  28:  EU:   42.50:   -19.30:    -1.0:  4O:
#    4O;
#Sri Lanka:                22:  41:  AS:    7.00:   -79.90:    -5.5:  4S:
#    4P,4Q,4R,4S;
#ITU HQ Geneva:            14:  28:  EU:   46.20:    -6.20:    -1.0:  4U1I:
#    4U1ITU,4U0ITU,4U1WRC,4U2ITU,4U3ITU,4U4ITU,4U5ITU,4U6ITU,4U7ITU,4U8ITU,
#    4U9ITU;
#United Nations HQ:        05:  08:  NA:   40.80:    74.00:     5.0:  4U1U:
#    4U0UN,4U1UN,4U2UN,4U3UN,4U4UN,4U50SPACE,4U5UN,4U6UN;
#Vienna Intl Ctr:          15:  28:  EU:   48.20:   -16.30:    -1.0:  *4U1V:
#    4U1VIC;
#Timor-Leste:              28:  54:  OC:   -8.60:  -125.50:    -8.0:  4W:
#    4W;
#Israel:                   20:  39:  AS:   31.80:   -35.20:    -2.0:  4X:
#    4X,4Z;
#Libya:                    34:  38:  AF:   32.50:   -12.50:    -2.0:  5A:
#    5A;
#Cyprus:                   20:  39:  AS:   35.20:   -33.40:    -2.0:  5B:
#    5B,C4,EURO,H2,P3;
#Tanzania:                 37:  53:  AF:   -7.00:   -39.50:    -3.0:  5H:
#    5H,5I;
#Nigeria:                  35:  46:  AF:    6.50:    -3.40:    -1.0:  5N:
#    5N,5O;
#Madagascar:               39:  53:  AF:  -18.90:   -47.50:    -3.0:  5R:
#    5R,5S,6X;
#Mauritania:               35:  46:  AF:   18.10:    16.00:     0.0:  5T:
#    5T;
#Niger:                    35:  46:  AF:   13.50:    -2.00:    -1.0:  5U:
#    5U;
#Togo:                     35:  46:  AF:    6.20:    -1.40:     0.0:  5V:
#    5V;
#Samoa:                    32:  62:  OC:  -13.50:   171.80:    11.0:  5W:
#    5W;
#Uganda:                   37:  48:  AF:    0.30:   -32.50:    -3.0:  5X:
#    5X;
#Kenya:                    37:  48:  AF:   -1.30:   -37.50:    -3.0:  5Z:
#    5Y,5Z;
#Senegal:                  35:  46:  AF:   14.70:    17.50:     0.0:  6W:
#    6V,6W;
#Jamaica:                  08:  11:  NA:   18.00:    76.80:     5.0:  6Y:
#    6Y;
#Yemen:                    21:  39:  AS:   12.80:   -45.00:    -3.0:  7O:
#    7O;
#Lesotho:                  38:  57:  AF:  -29.30:   -27.50:    -2.0:  7P:
#    7P;
#Malawi:                   37:  53:  AF:  -14.90:   -34.40:    -2.0:  7Q:
#    7Q;
#Algeria:                  33:  37:  AF:   36.70:    -3.00:    -1.0:  7X:
#    7R,7T,7U,7V,7W,7X,7Y;
#Barbados:                 08:  11:  NA:   13.10:    59.60:     4.0:  8P:
#    8P;
#Maldives:                 22:  41:  AS:    4.40:   -73.40:    -5.0:  8Q:
#    8Q;
#Guyana:                   09:  12:  SA:    6.80:    58.20:     4.0:  8R:
#    8R;
#Croatia:                  15:  28:  EU:   45.50:   -15.60:    -1.0:  9A:
#    9A;
#Ghana:                    35:  46:  AF:    5.50:     0.20:     0.0:  9G:
#    9G;
#Malta:                    15:  28:  EU:   36.00:   -14.40:    -1.0:  9H:
#    9H;
#Zambia:                   36:  53:  AF:  -15.40:   -28.30:    -2.0:  9J:
#    9I,9J;
#Kuwait:                   21:  39:  AS:   29.50:   -47.80:    -3.0:  9K:
#    9K;
#Sierra Leone:             35:  46:  AF:    8.50:    13.20:     0.0:  9L:
#    9L;
#West Malaysia:            28:  54:  AS:    3.20:  -101.60:    -7.5:  9M2:
#    9M2,9M4,9M50,9W2,9W4;
#East Malaysia:            28:  54:  OC:    5.80:  -118.10:    -7.5:  9M6:
#    9M6,9M8,9W6,9W8,9M2/PG5M/6,9M50MS;
#Nepal:                    22:  42:  AS:   27.70:   -85.30:   -5.75:  9N:
#    9N;
#Rep. of Congo:            36:  52:  AF:   -4.30:   -15.30:    -1.0:  9Q:
#    9O,9P,9Q,9R,9S,9T;
#Burundi:                  36:  52:  AF:   -3.30:   -29.30:    -2.0:  9U:
#    9U;
#Singapore:                28:  54:  AS:    1.30:  -103.80:    -8.0:  9V:
#    9V,S6;
#Rwanda:                   36:  52:  AF:   -2.00:   -30.10:    -2.0:  9X:
#    9X;
#Trinidad & Tobago:        09:  11:  SA:   10.50:    61.30:     4.0:  9Y:
#    9Y,9Z;
#Botswana:                 38:  57:  AF:  -24.80:   -25.90:    -2.0:  A2:
#    8O,A2;
#Tonga:                    32:  62:  OC:  -21.10:   175.20:   -13.0:  A3:
#    A3;
#Oman:                     21:  39:  AS:   23.60:   -58.60:    -4.0:  A4:
#    A4;
#Bhutan:                   22:  41:  AS:   27.30:   -89.40:    -6.5:  A5:
#    A5;
#United Arab Emirates:     21:  39:  AS:   24.50:   -54.20:    -4.0:  A6:
#    A6;
#Qatar:                    21:  39:  AS:   25.30:   -51.50:    -3.0:  A7:
#    A7;
#Bahrain:                  21:  39:  AS:   26.20:   -50.60:    -3.0:  A9:
#    A9;
#Pakistan:                 21:  41:  AS:   24.90:   -67.10:    -5.0:  AP:
#    6P,6Q,6R,6S,AP,AQ,AR,AS;
#Scarborough Reef:         27:  50:  AS:   15.10:  -117.50:    -8.0:  BS7:
#    BS7;
#Taiwan:                   24:  44:  AS:   25.10:  -121.50:    -8.0:  BV:
#    BM,BN,BO,BP,BQ,BU,BV,BW,BX;
#Pratas Island:            24:  44:  AS:   20.40:  -116.40:    -8.0:  BV9P:
#    BM9P,BN9P,BO9P,BP9P,BQ9P,BU9P,BV9P,BW9P,BX9P;
#China:                    24:  44:  AS:   40.00:  -116.40:    -8.0:  BY:
#    3H,3I,3J,3K,3L,3M,3N,3O,3P,3Q,3R,3S,3T,3U,B1,B2,B3,B3G(23)[33],B3H(23)[33],
#    B3I(23)[33],B3J(23)[33],B3K(23)[33],B3L(23)[33],B4,B5,B6,B7,B8,B9,B9M(24)[33],
#    B9N(24)[33],B9O(24)[33],B9P(24)[33],B9Q(24)[33],B9R(24)[33],B9S(24)[33],BA,
#    BA3G(23)[33],BA3H(23)[33],BA3I(23)[33],BA3J(23)[33],BA3K(23)[33],
#    BA3L(23)[33],BA9M(24)[33],BA9N(24)[33],BA9O(24)[33],BA9P(24)[33],BA9Q(24)[33],
#    BA9R(24)[33],BA9S(24)[33],BD,BD3G(23)[33],BD3H(23)[33],BD3I(23)[33],
#    BD3J(23)[33],BD3K(23)[33],BD3L(23)[33],BD9M(24)[33],BD9N(24)[33],
#    BD9O(24)[33],BD9P(24)[33],BD9Q(24)[33],BD9R(24)[33],BD9S(24)[33],BG,
#    BG3G(23)[33],BG3H(23)[33],BG3I(23)[33],BG3J(23)[33],BG3K(23)[33],
#    BG3L(23)[33],BG9M(24)[33],BG9N(24)[33],BG9O(24)[33],BG9P(24)[33],BG9Q(24)[33],
#    BG9R(24)[33],BG9S(24)[33],BH,BH3G(23)[33],BH3H(23)[33],BH3I(23)[33],
#    BH3J(23)[33],BH3K(23)[33],BH3L(23)[33],BH9M(24)[33],BH9N(24)[33],
#    BH9O(24)[33],BH9P(24)[33],BH9Q(24)[33],BH9R(24)[33],BH9S(24)[33],BI,BL,
#    BL3G(23)[33],BL3H(23)[33],BL3I(23)[33],BL3J(23)[33],BL3K(23)[33],
#    BL3L(23)[33],BL9M(24)[33],BL9N(24)[33],BL9O(24)[33],BL9P(24)[33],BL9Q(24)[33],
#    BL9R(24)[33],BL9S(24)[33],BT,BT3G(23)[33],BT3H(23)[33],BT3I(23)[33],
#    BT3J(23)[33],BT3K(23)[33],BT3L(23)[33],BT9M(24)[33],BT9N(24)[33],
#    BT9O(24)[33],BT9P(24)[33],BT9Q(24)[33],BT9R(24)[33],BT9S(24)[33],BY,
#    BY3G(23)[33],BY3H(23)[33],BY3I(23)[33],BY3J(23)[33],BY3K(23)[33],
#    BY3L(23)[33],BY9M(24)[33],BY9N(24)[33],BY9O(24)[33],BY9P(24)[33],BY9Q(24)[33],
#    BY9R(24)[33],BY9S(24)[33],BZ,BZ3G(23)[33],BZ3H(23)[33],BZ3I(23)[33],
#    BZ3J(23)[33],BZ3K(23)[33],BZ3L(23)[33],BZ9M(24)[33],BZ9N(24)[33],
#    BZ9O(24)[33],BZ9P(24)[33],BZ9Q(24)[33],BZ9R(24)[33],BZ9S(24)[33],XS;
#Nauru:                    31:  65:  OC:   -0.50:  -166.90:   -11.5:  C2:
#    C2;
#Andorra:                  14:  27:  EU:   42.50:    -1.50:    -1.0:  C3:
#    C3;
#Gambia:                   35:  46:  AF:   13.50:    16.70:     0.0:  C5:
#    C5;
#Bahamas:                  08:  11:  NA:   25.10:    77.40:     5.0:  C6:
#    C6;
#Mozambique:               37:  53:  AF:  -26.00:   -32.60:    -2.0:  C9:
#    C8,C9;
#Chile:                    12:  14:  SA:  -33.50:    70.80:     4.0:  CE:
#    3G,CA,CB,CC,CD,CE,XQ,XR;
#San Felix I.:             12:  14:  SA:  -26.30:    80.10:     6.0:  CE0X:
#    3G0X,CA0X,CB0X,CC0X,CD0X,CE0X,XQ0X,XR0X;
#Easter Island:            12:  63:  SA:  -27.10:   109.40:     6.0:  CE0Y:
#    3G0,CA0,CB0,CC0,CD0,CE0,XQ0,XR0;
#Juan Fernandez Is.:       12:  14:  SA:  -33.60:    78.80:     4.0:  CE0Z:
#    3G0Z,CA0Z,CB0Z,CC0Z,CD0Z,CE0I,CE0Z,XQ0Z,XR0Z;
#Antarctica:               13:  74:  SA:  -65.00:    64.00:    -4.0:  CE9:
#    ANT,AX0,FT0Y(30)[70],FT2Y(30)[70],FT4Y(30)[70],FT5Y(30)[70],FT8Y(30)[70],
#    LU1Z[73],R1AN,VH0(39)[69],VI0(39)[69],VJ0(39)[69],VK0(39)[69],VL0(39)[69],
#    VM0(39)[69],VN0(39)[69],VZ0(39)[69],ZL0(30)[71],ZL5(30)[71],ZM5(30)[71],
#    ZS7(38)[67],8J1RF(39)[67],8J1RL(39)[67],DP0GVN(38)[67],KC4/K2ARB(30)[71],
#    KC4AAA(39),KC4AAC[73],KC4USB(12)[72],KC4USV(30)[71],LU4ZS[73],OJ1ABOA(38),
#    VP8DJB[73],VP8DKF(30)[71],VP8PJ[73],VP8ROT[73];
#Cuba:                     08:  11:  NA:   23.10:    82.40:     5.0:  CM:
#    CL,CM,CO,T4;
#Morocco:                  33:  37:  AF:   33.60:     7.50:     0.0:  CN:
#    5C,5D,5E,5F,5G,CN;
#Bolivia:                  10:  12:  SA:  -16.50:    68.40:     4.0:  CP:
#    CP;
#Portugal:                 14:  37:  EU:   38.70:     9.20:     0.0:  CT:
#    CQ,CR,CS,CT;
#Madeira Is.:              33:  36:  AF:   32.60:    16.90:     0.0:  CT3:
#    CQ3,CQ9,CR3,CR9,CS3,CS9,CT3,CT9,XX;
#Azores:                   14:  36:  EU:   37.70:    25.70:     1.0:  CU:
#    CU;
#Uruguay:                  13:  14:  SA:  -34.90:    56.20:     3.0:  CX:
#    CV,CW,CX;
#Sable I.:                 05:  09:  NA:   43.80:    60.00:     4.0:  CY0:
#    CY0;
#St. Paul I.:              05:  09:  NA:   47.20:    60.10:     4.0:  CY9:
#    CY9;
#Angola:                   36:  52:  AF:   -8.80:   -13.20:    -1.0:  D2:
#    D2,D3;
#Cape Verde:               35:  46:  AF:   14.90:    23.50:     1.0:  D4:
#    D4;
#Comoros:                  39:  53:  AF:  -11.80:   -43.70:    -3.0:  D6:
#    D6;
#Germany:                  14:  28:  EU:   51.00:   -10.00:    -1.0:  DL:
#    DA,DB,DC,DD,DE,DF,DG,DH,DI,DJ,DK,DL,DM,DN,DO,DP,DQ,DR;
#Philippines:              27:  50:  OC:   14.60:  -121.00:    -8.0:  DU:
#    4D,4E,4F,4G,4H,4I,DU,DV,DW,DX,DY,DZ;
#Eritrea:                  37:  48:  AF:   15.30:   -38.90:    -3.0:  E3:
#    E3;
#Palestine:                20:  39:  AS:   31.40:   -35.10:    -2.0:  E4:
#    E4;
#North Cook Is.:           32:  62:  OC:  -10.40:   161.00:    10.0:  E5/n:
#    E51WL;
#South Cook Is.:           32:  62:  OC:  -21.20:   159.80:    10.0:  E5/s:
#    E5;
#Bosnia-Herzegovina:       15:  28:  EU:   43.50:   -18.30:    -1.0:  E7:
#    E7,T9;
#Spain:                    14:  37:  EU:   40.40:     3.70:    -1.0:  EA:
#    AM,AN,AO,EA,EB,EC,ED,EE,EF,EG,EH;
#Balearic Is.:             14:  37:  EU:   39.50:    -2.60:    -1.0:  EA6:
#    AM6,AN6,AO6,EA6,EB6,EC6,ED6,EE6,EF6,EG6,EH6,ED5ON/6;
#Canary Is.:               33:  36:  AF:   28.40:    15.30:     0.0:  EA8:
#    AM8,AN8,AO8,EA8,EB8,EC8,ED8,EE8,EF8,EG8,EH8;
#Ceuta and Melilla:        33:  37:  AF:   35.60:     3.00:    -1.0:  EA9:
#    AM9,AN9,AO9,EA9,EB9,EC9,ED9,EE9,EF9,EG9,EH9;
#Ireland:                  14:  27:  EU:   53.30:     6.30:     0.0:  EI:
#    EI,EJ;
#Armenia:                  21:  29:  AS:   40.30:   -44.50:    -4.0:  EK:
#    EK;
#Liberia:                  35:  46:  AF:    6.30:    10.80:     0.0:  EL:
#    5L,5M,6Z,A8,D5,EL;
#Iran:                     21:  40:  AS:   35.80:   -51.80:    -3.5:  EP:
#    9B,9C,9D,EP,EQ;
#Moldova:                  16:  29:  EU:   47.00:   -28.80:    -2.0:  ER:
#    ER;
#Estonia:                  15:  29:  EU:   59.40:   -24.80:    -2.0:  ES:
#    ES;
#Ethiopia:                 37:  48:  AF:    9.00:   -38.70:    -3.0:  ET:
#    9E,9F,ET;
#Belarus:                  16:  29:  EU:   53.90:   -27.60:    -2.0:  EU:
#    EU,EV,EW;
#Kyrgyzstan:               17:  31:  AS:   42.90:   -74.60:    -6.0:  EX:
#    EX;
#Tajikistan:               17:  30:  AS:   39.70:   -66.80:    -5.0:  EY:
#    EY;
#Turkmenistan:             17:  30:  AS:   38.00:   -58.40:    -5.0:  EZ:
#    EZ;
#France:                   14:  27:  EU:   48.80:    -2.30:    -1.0:  F:
#    F,HW,HX,HY,TH,TM,TP,TQ,TV,TW;
#Guadeloupe:               08:  11:  NA:   16.00:    61.70:     4.0:  FG:
#    FG,TO1T,TO1USB,TO2ANT,TO2FG,TO2OOO,TO4T,TO5BG,TO5C,TO5G,TO5GI,TO5ROM,TO5S,
#    TO6T,TO7ACR,TO7AES,TO7DSR,TO7GAS,TO7T,TO8CW,TO8RR,TO9T;
#Mayotte:                  39:  53:  AF:  -13.00:   -45.30:    -3.0:  FH:
#    FH,TO8MZ,TX0P,TX5M,TX5NK,TX5T,TX6A;
#Saint Barthelemy:         08:  11:  NA:   17.90:    62.90:     4.0:  FJ:
#    FJ,TO5FJ;
#New Caledonia:            32:  56:  OC:  -22.30:  -166.50:   -11.0:  FK:
#    FK,TX8,TX1A,TX3SAM,TX5CW;
#Chesterfield Is.:         30:  56:  OC:  -19.90:  -158.30:   -11.0:  FK/c:
#    TX0AT,TX0C,TX0DX,TX9;
#Martinique:               08:  11:  NA:   14.60:    61.00:     4.0:  FM:
#    FM,TO0O,TO0P,TO1A,TO1YR,TO2DX,TO3M,TO3T,TO3W,TO4A,TO5A,TO5AA,TO5J,TO5MM,
#    TO5T,TO5X,TO6M,TO7HAM,TO7X,TO8B,TO9A,TX4B;
#French Polynesia:         32:  63:  OC:  -17.60:   149.50:    10.0:  FO:
#    FO;
#Austral Is.:              32:  63:  OC:  -22.50:   152.00:    10.0:  FO/a:
#    FO/DL1AWI,FO/DL5XU,FO/DL9AWI;
#Clipperton I.:            07:  10:  NA:   10.30:   109.20:     7.0:  FO/c:
#    FO0/F8UFT,FO0AAA,FO0CI,TX5C;
#Marquesas Is.:            31:  63:  OC:   -9.00:   139.50:    10.0:  FO/m:
#    FO/HA9G,FO/OH1RX;
#St. Pierre & Miquelon:    05:  09:  NA:   46.70:    56.00:     3.0:  FP:
#    FP;
#Reunion:                  39:  53:  AF:  -21.10:   -55.60:    -4.0:  FR:
#    FR;
#Glorioso:                 39:  53:  AF:  -11.50:   -47.30:    -4.0:  FR/g:
#    TO4G;
#Juan de Nova & Europa:    39:  53:  AF:  -19.60:   -41.60:    -3.0:  FR/j:
#    TO4E;
#Tromelin:                 39:  53:  AF:  -15.90:   -54.40:    -4.0:  FR/t:
#    FR5ZU/T;
#French St. Martin:        08:  11:  NA:   18.10:    63.10:     4.0:  FS:
#    FS,TO5D;
#Crozet:                   39:  68:  AF:  -46.00:   -52.00:    -4.0:  FT5W:
#    FT0W,FT2W,FT4W,FT5W,FT8W;
#Kerguelen:                39:  68:  AF:  -49.30:   -69.20:    -5.0:  FT5X:
#    FT0X,FT2X,FT4X,FT5X,FT8X;
#Amsterdam & St. Paul:     39:  68:  AF:  -37.70:   -77.60:    -5.0:  FT5Z:
#    FT0Z,FT2Z,FT4Z,FT5Z,FT8Z;
#Wallis & Futuna Is.:      32:  62:  OC:  -13.30:   176.30:   -12.0:  FW:
#    FW;
#French Guiana:            09:  12:  SA:    4.90:    52.30:     3.0:  FY:
#    FY,TO7C,TO7IR,TO7R,TX0A;
#England:                  14:  27:  EU:   51.50:     0.10:     0.0:  G:
#    2E,G,M;
#Isle of Man:              14:  27:  EU:   54.30:     4.50:     0.0:  GD:
#    2D,2T,GD,GT,MD,MT,GB0MST,GB0WCY,GB100MER,GB100TT,GB125SR,GB2IOM,GB2MAD,
#    GB2WB,GB3GD,GB4IOM,GB4MNH,GB4WXM/P,GB50UN,GB5MOB,GB6SPC;
#Northern Ireland:         14:  27:  EU:   54.60:     5.90:     0.0:  GI:
#    2I,2N,GI,GN,MI,MN,GB0BTC,GB0BVC,GB0CI,GB0CSC,GB0DDF,GB0GPF,GB0MFD,GB0PSM,
#    GB0REL,GB0SHC,GB0SIC,GB0SPD,GB0TCH,GB0WOA,GB1SPD,GB2IL,GB2LL,GB2MGY,
#    GB2MRI,GB2NIC,GB2NTU,GB2TCA,GB3MNI,GB4CSC,GB4ES,GB4SPD,GB50AAD,GB5BIG,
#    GB5BL,GB5SPD,GB90SOM;
#Jersey:                   14:  27:  EU:   49.30:     2.20:     0.0:  GJ:
#    2H,2J,GH,GJ,MH,MJ,GB0CLR,GB0GUD,GB0JSA,GB0SHL,GB2BYL,GB2JSA,GB4BHF,
#    GB50JSA;
#Scotland:                 14:  27:  EU:   55.80:     4.30:     0.0:  GM:
#    2A,2M,2S,GM,GS,MM,MS,GB0AC,GB0BNC,GB0BWT,GB0DGL,GB0FFS,GB0FLA,GB0GDS,
#    GB0GEI,GB0GHD,GB0GKR,GB0GNE,GB0HHW,GB0KGS,GB0KTC,GB0LCS,GB0MLM,GB0MOL,
#    GB0NHL,GB0OS,GB0OYT,GB0PPE,GB0QWM,GB0RBS,GB0SHP,GB0SK,GB0SKY,GB0SS,GB0SSF,
#    GB100MAS,GB125BRC,GB150NRL,GB1EPC,GB1FVT,GB2AGG,GB2AST,GB2AYR,GB2CHG,
#    GB2DHS,GB2FBM,GB2FIO,GB2FSM,GB2GNL,GB2GTM,GB2HI,GB2HRH,GB2HST,GB2HSW,
#    GB2IAS,GB2IGB,GB2IGS,GB2IOC,GB2IOG,GB2IOT,GB2JUNO,GB2KDS,GB2KHL,GB2LAY,
#    GB2LBN,GB2LCL,GB2LCP,GB2LGB,GB2LHI,GB2LMG,GB2LNM,GB2LO,GB2LP,GB2LS,GB2LSS,
#    GB2LT,GB2LTN,GB2MAS,GB2MOD,GB2MOF,GB2MSL,GB2MUL,GB2NAG,GB2NBC,GB2NCL,
#    GB2NEF,GB2NL,GB2NTS,GB2OWM,GB2OYC,GB2PBF,GB2PS,GB2RB,GB2RRL,GB2SKG,GB2SLH,
#    GB2SPD,GB2SSF,GB2STB,GB2TDS,GB2TI,GB2WBB,GB3GM,GB400CA,GB4AAS,GB4CGW,
#    GB4DAS,GB4GM,GB4LNM,GB4NFE,GB4PMS,GB4RAF,GB4SLH,GB4TSR,GB4ZBS,GB50ATC,
#    GB50JS,GB50SWL,GB5AST,GB5BBS,GB5CO,GB5FHC,GB5OL,GB5RO,GB5SI,GB5TI,GB60BBC,
#    GB60CRB,GB60NTS,GB6MI,GB6SA,GB6SM,GB6TAA,GB6WW,GB700BSB,GB75GD,GB75SCP,
#    GB75STT,GB8AYR,GB8CA,GB8CF,GB8CI,GB8CM,GB8CN,GB8CO,GB8CSL,GB8CY,GB8FF,
#    GB8OO,GB8RU,GB93AM;
#Shetland:                 14:  27:  EU:   60.40:     1.50:     0.0:  *GM/s:
#    GZ,MZ,2M0ZET,GB2ELH,GM0AVR,GM0CXQ,GM0CYJ,GM0DJI,GM0EKM,GM0ILB,GM0ULK,
#    GM1ZNR,GM3KLA,GM3WHT,GM3ZET,GM3ZNM,GM4GPP,GM4GQM,GM4IPK,GM4LBE,GM4LER,
#    GM4SLV,GM4SSA,GM4SWU,GM4WXQ,GM4ZHL,GM7AFE,GM7GWW,GM8LNH,GM8MMA,GM8YEC,
#    MM0LSM,MM0XAU,MM0ZAL,MM1FJM,MM3VQO,MM5PSL,MS0ZCG;
#Guernsey:                 14:  27:  EU:   49.50:     2.70:     0.0:  GU:
#    2P,2U,GP,GU,MP,MU,GB0GUC,GB0JAG,GB0ON,GB0U,GB2ECG,GB2GU,GB50LIB;
#Wales:                    14:  27:  EU:   51.50:     3.20:     0.0:  GW:
#    2C,2W,2X,2Y,GC,GW,MC,MW,GB0CCE,GB0CLC,GB0CVA,GB0GCR,GB0GIW,GB0GLV,GB0HEL,
#    GB0HMT,GB0ML,GB0MPA,GB0MWL,GB0NEW,GB0PSG,GB0RPO,GB0RSC,GB0SDD,GB0SH,
#    GB0SOA,GB0SPS,GB0SRH,GB0TD,GB0TTT,GB0WRC,GB100BD,GB100FI,GB100LP,GB1CCC,
#    GB1LSG,GB1SL,GB1SSL,GB1TDS,GB2000SET,GB200A,GB200HNT,GB2ANG,GB2CPC,GB2GGM,
#    GB2GLS,GB2GOL,GB2GSG,GB2GSS,GB2HDG,GB2IMD,GB2LNP,GB2LSA,GB2MIL,GB2MLM,
#    GB2MOP,GB2RFS,GB2RSC,GB2RTB,GB2SDD,GB2SIP,GB2TD,GB2TTA,GB2VK,GB2WDS,
#    GB2WFF,GB2WHO,GB2WSF,GB4BPL,GB4CI,GB4DPS,GB4HMD,GB4HMM,GB4LSG,GB4MD,
#    GB4MDI,GB4NDG,GB4SA,GB4SMM,GB4SNF,GB4XXX,GB5BS/J,GB5FI,GB5SIP,GB60VLY,
#    GB6AR,GB6GW,GB6OQA,GB750CC,GB8OQE;
#Solomon Islands:          28:  51:  OC:   -9.40:  -160.00:   -11.0:  H4:
#    H4;
#Temotu:                   32:  51:  OC:  -10.70:  -165.80:   -11.0:  H40:
#    H40;
#Hungary:                  15:  28:  EU:   47.50:   -19.10:    -1.0:  HA:
#    HA,HG;
#Switzerland:              14:  28:  EU:   47.00:    -7.50:    -1.0:  HB:
#    HB,HE;
#Liechtenstein:            14:  28:  EU:   47.20:    -9.60:    -1.0:  HB0:
#    HB0,HE0;
#Ecuador:                  10:  12:  SA:   -0.20:    78.00:     5.0:  HC:
#    HC,HD;
#Galapagos Is.:            10:  12:  SA:   -0.50:    90.50:     6.0:  HC8:
#    HC8,HD8;
#Haiti:                    08:  11:  NA:   18.50:    72.30:     5.0:  HH:
#    4V,HH;
#Dominican Republic:       08:  11:  NA:   18.50:    70.00:     4.0:  HI:
#    HI;
#Colombia:                 09:  12:  SA:    4.60:    74.10:     5.0:  HK:
#    5J,5K,HJ,HK;
#San Andres/Providencia:   07:  11:  NA:   12.50:    81.70:     5.0:  HK0/a:
#    5J0,5K0,HJ0,HK0;
#Malpelo I.:               09:  12:  SA:    4.00:    81.10:     5.0:  HK0/m:
#    5J0M,5K0M,HJ0M,HK0M,HK0TU;
#South Korea:              25:  44:  AS:   37.50:  -127.00:    -9.0:  HL:
#    6K,6L,6M,6N,D7,D8,D9,DS,DT,HL;
#North Korea:              25:  44:  AS:   39.00:  -126.00:    -9.0:  HM:
#    HM,P5,P6,P7,P8,P9;
#Panama:                   07:  11:  NA:    9.00:    79.50:     5.0:  HP:
#    3E,3F,H3,H8,H9,HO,HP;
#Honduras:                 07:  11:  NA:   14.10:    87.20:     6.0:  HR:
#    HQ,HR;
#Thailand:                 26:  49:  AS:   13.80:  -100.50:    -7.0:  HS:
#    E2,HS;
#Vatican City:             15:  28:  EU:   41.90:   -12.50:    -1.0:  HV:
#    HV;
#Saudi Arabia:             21:  39:  AS:   26.30:   -50.00:    -3.0:  HZ:
#    7Z,8Z,HZ;
#Italy:                    15:  28:  EU:   41.90:   -12.50:    -1.0:  I:
#    I;
#Italy (Africa):           33:  37:  AF:   35.40:   -12.50:    -1.0:  *IG9:
#    IG9,IH9;
#Sardinia:                 15:  28:  EU:   39.20:    -9.10:    -1.0:  IS:
#    IM0,IS,IW0U,IW0V,IW0W,IW0X,IW0Y,IW0Z,IQ0AG,IQ0AH,IQ0AI,IQ0AK,IQ0AL,IQ0AM,
#    IQ0EH,IQ0HO,IQ0QP,IQ0SS;
#Sicily:                   15:  28:  EU:   37.50:   -14.00:    -1.0:  *IT9:
#    IB9,ID9,IE9,IF9,II9,IJ9,IO9,IQ9,IR9,IT,IU9,IW9,IZ9;
#Djibouti:                 37:  48:  AF:   11.60:   -43.20:    -3.0:  J2:
#    J2;
#Grenada:                  08:  11:  NA:   12.00:    61.80:     4.0:  J3:
#    J3;
#Guinea-Bissau:            35:  46:  AF:   11.90:    15.60:     0.0:  J5:
#    J5;
#St. Lucia:                08:  11:  NA:   13.90:    61.00:     4.0:  J6:
#    J6;
#Dominica:                 08:  11:  NA:   15.40:    61.30:     4.0:  J7:
#    J7;
#St. Vincent:              08:  11:  NA:   13.30:    61.30:     4.0:  J8:
#    J8;
#Japan:                    25:  45:  AS:   35.70:  -139.80:    -9.0:  JA:
#    7J,7K,7L,7M,7N,8J,8K,8L,8M,8N,JA,JB,JC,JE,JF,JG,JH,JI,JJ,JK,JL,JM,JN,JO,
#    JP,JQ,JR,JS;
#Minami Torishima:         27:  90:  OC:   24.30:  -154.00:   -10.0:  JD/m:
#    JA6GXK/JD1,JD1/JI7BCD,JD1BME,JD1BMM,JD1YAA,JD1YBJ;
#Ogasawara:                27:  45:  AS:   27.50:  -141.00:   -10.0:  JD/o:
#    JD1;
#Mongolia:                 23:  32:  AS:   47.90:  -106.90:    -8.0:  JT:
#    JT,JU,JV;
#Svalbard:                 40:  18:  EU:   78.80:   -16.00:    -1.0:  JW:
#    JW;
#Bear I.:                  40:  18:  EU:   74.50:   -19.00:    -1.0:  *JW/b:
#    JW2FL,JW5RIA,JW7FD;
#Jan Mayen:                40:  18:  EU:   71.00:     8.30:     1.0:  JX:
#    JX;
#Jordan:                   20:  39:  AS:   32.00:   -35.90:    -2.0:  JY:
#    JY;
#United States:            05:  08:  NA:   43.00:    87.90:     5.0:  K:
#    4U1WB,AA,AB,AC,AD,AE,AF,AG,AI,AJ,AK,K,N,W,AA0CY(5)[8],AA3VA(4),AB4EJ(4),
#    AB4GG(4),AC4PY(4),AD4EB(4),AD8J(5),AE9F(3)[6],AG3V(4)[7],AG4W(4),
#    AH0AH(5)[8],AH2AK(5)[8],AH6HJ(5)[8],AH6RI(3)[6],AH8M(5)[8],AL0F(3)[6],
#    AL1VE(5)[8],AL4T(5)[8],AL7C(4)[8],AL7KT(5)[8],AL7LV(5)[8],AL7NS(5)[8],
#    AL7O(4)[7],AL7QQ(4)[7],AL7W(3)[6],K0COP(5)[8],K0JJ(3)[6],K0JJM(4)[7],
#    K0JJR(4)[7],K0LUZ(5)[8],K0TV(5)[8],K0TVD(4)[7],K1GU(4),K1GUG(5),
#    K1LKR(3)[6],K1LT(4),K1NG(4),K1NT(4)[7],K1NTR(5)[8],K1TN(4),K1TU(4)[7],
#    K2AAW(4),K2BA(4)[7],K2HT(4)[7],K2HTO(5)[8],K2RD(3)[6],K2VCO(3)[6],
#    K2VV(4)[7],K3CQ(4),K3GP(4),K3IE(4),K3PA(4)[7],K3WT(4)[7],K4AMC(4),
#    K4BEV(4),K4BP(4),K4BX(4),K4BXC(5),K4EJQ(4),K4FXN(4),K4HAL(4),K4IE(4),
#    K4IU(4)[7],K4JA(4),K4JNY(4),K4LTA(4),K4NO(4),K4OAQ(4),K4RO(4),K4SAC(4),
#    K4TD(4),K4VU(3)[6],K4VUD(5)[8],K4WI(4),K4WW(4),K4WX(4),K4XG(4),K4XU(3)[6],
#    K4ZGB(4),K5KG(5)[8],K5MA(5)[8],K5RC(3)[6],K5RR(3)[6],K5ZD(5)[8],
#    K5ZDG(4)[7],K6EID(5)[8],K6XT(4)[7],K7ABV(4)[6],K7BG(4)[6],K7CMZ(5)[8],
#    K7CS(5)[8],K7GM(5)[8],K7GMF(3)[6],K7IA(4)[7],K7RE(4)[7],K7REL(3)[6],
#    K7SV(5)[8],K7TD(4)[7],K7UP(4)[7],K7VU(4)[7],K8AC(5),K8IA(3)[6],K8JQ(5),
#    K8OQL(5),K8OSF(5),K8XS(5),K8YC(5),K9AW(5),K9ES(5),K9FY(5),K9HUY(5),
#    K9JF(3)[6],K9OM(5),K9VV(5),KA2EYH(4),KA8Q(5),KB7Q(4)[6],KC3MR(4),
#    KC7UP(4)[6],KD5M(5)[8],KD5MDO(4)[7],KE4MBP(4),KE4OAR(4),KE7NO(4)[6],
#    KH2D(5)[8],KH6DX(3)[6],KH6GJV(3)[6],KH6HHS(5)[8],KH6ILR(5)[8],KH6OE(4)[8],
#    KH6QAI(3)[6],KH6QAJ(3)[6],KH6RW(3)[6],KI6DY(4)[7],KK9A(5),KL0ET(4)[8],
#    KL0LN(4)[8],KL1IF(4)[8],KL7FDQ(3)[6],KL7WP(3)[6],KL7XX(4)[8],KM4FO(4),
#    KM6JD(5)[8],KN4Q(4),KN4QS(4)[7],KN5H(3)[6],KN6RO(5)[8],KN8J(5),KO7X(4)[7],
#    KP2N(5)[8],KS7T(4)[6],KU1CW(4)[7],KU8E(5),KY1V(4),KY4AA(4),KY4Z(4)[7],
#    N0AX(3)[6],N1LN(4)[7],N1SZ(4)[7],N1WI(4),N1ZP(4),N2BJ(4),N2BJL(5),
#    N2IC(4)[7],N2LA(4)[7],N2NB(3)[6],N2WN(4),N3AIU(4)[7],N3BB(4)[7],
#    N3ZZ(3)[6],N4CVO(4),N4DD(4),N4DW(4),N4GK(4),N4GN(4),N4IR(4),N4IRR(5),
#    N4JF(4),N4KG(4),N4KZ(4),N4NO(4),N4OGW(4)[7],N4QS(4),N4SL(3)[6],N4TN(4),
#    N4TZ(4),N4UW(4),N4VV(4),N4XM(4),N4ZZ(4),N6AR(5)[8],N6MW(5)[8],N6MWA(3)[6],
#    N6RFM(5)[8],N6ZO(5)[8],N6ZZ(4)[7],N7DC(5)[8],N7DF(4)[7],N7FLT(4)[6],
#    N7IV(4)[7],N7NG(5)[8],N7VMR(4)[6],N8FF(5),N8II(5),N8NA(5),N8PR(5),N8RA(5),
#    N8WXQ(5),N9ADG(3)[6],NA4K(4),NA4M(4)[7],NA4MA(5)[8],ND2T(3)[6],ND9M(5),
#    NH7C(5)[8],NJ4I(4),NL7AU(5)[8],NL7CO(4)[7],NL7XM(5)[8],NP3D(5)[8],NQ4U(4),
#    NU4B(4),NU4BP(5),NW7MT(4)[6],NW8U(5),NX9T(5),NY4N(4),NY6DX(5)[8],
#    W0RLI(3)[6],W0UCE(5)[8],W0YK(3)[6],W0YR(5)[8],W0YRN(4)[7],W0ZZ(3)[6],
#    W0ZZQ(4)[7],W1AA/MSC(5)[8],W1DY(4)[7],W1DYH(5)[8],W1DYJ(5)[8],W1MVY(3)[6],
#    W1RH(3)[6],W1SRD(3)[6],W2OO(4),W2VJN(3)[6],W3CP(3)[6],W3HDH(4),W4BCG(4),
#    W4CID(4),W4DAN(4),W4DHE(4),W4DVG(4),W4EEH(4),W4EF(3)[6],W4FMS(4),W4GKM(4),
#    W4HZD(4),W4JSI(4),W4KW(4),W4LC(4),W4LIA(4),W4NBS(4),W4NI(4),W4NTI(4),
#    W4NZ(4),W4PA(4),W4RYW(4),W4TDB(4),W4TYU(4),W4YOK(4)[7],W5KI(5)[8],
#    W5REA(5)[8],W6AAN(5)[8],W6DSQ(4)[8],W6FC(5)[8],W6IHG(5)[8],W6JV(5)[8],
#    W6LFB(4)[7],W6NWS(5)[8],W6TER(4)[7],W6UB(4)[8],W6XR(5)[8],W6YJ(4)[7],
#    W7FG(4)[7],W7LPF(5)[8],W7LR(4)[6],W7LRD(3)[6],W7QF(5)[8],W7SE(4)[7],
#    W8AEF(3)[6],W8FJ(5),W8HGH(5),W8TN(5),W8WEJ(5),W8ZA(5),W9GE(5),W9GEN(4),
#    W9IGJ(5),W9MAK(3)[6],W9NGA(3)[6],WA0KDS(3)[6],WA1FCN(4),WA1MKE(4),
#    WA1UJU(4),WA2MNO(4)[7],WA4GLH(4),WA4JA(4),WA4OSD(4),WA5VGI(3)[6],WA8WV(5),
#    WB2ORD(4),WB4YDL(4),WB4ZBI(4),WB6BWZ(5)[8],WB8YQJ(3)[6],WB8YYY(5),WD4K(4),
#    WD4OHD(4),WG7Y(4)[7],WH0AI(4)[8],WH6ASW/M(3)[6],WJ9B(5),WL7BPY(4)[7],
#    WL7K(3)[6],WN4M(4),WO4O(4),WO5D(5)[8],WP4JBG(4)[8],WS4Y(4)[7],WT5L(5)[8],
#    WX4TM(4);
#Guantanamo Bay:           08:  11:  NA:   19.90:    75.20:     5.0:  KG4:
#    KG4,KG44;
#Mariana Is.:              27:  64:  OC:   15.20:  -145.80:   -10.0:  KH0:
#    AH0,KH0,NH0,WH0,KG6SL;
#Baker & Howland Is.:      31:  61:  OC:    0.50:   176.00:    11.0:  KH1:
#    AH1,KH1,NH1,WH1;
#Guam:                     27:  64:  OC:   13.50:  -144.80:   -10.0:  KH2:
#    AH2,KH2,NH2,WH2,KG6ASO,KG6DX;
#Johnston I.:              31:  61:  OC:   16.80:   169.50:    10.0:  KH3:
#    AH3,KH3,NH3,WH3,KJ6BZ;
#Midway I.:                31:  61:  OC:   28.20:   177.40:    11.0:  KH4:
#    AH4,KH4,NH4,WH4;
#Palmyra & Jarvis Is.:     31:  61:  OC:    5.90:   162.10:    10.0:  KH5:
#    AH5,KH5,NH5,WH5;
#Kingman Reef:             31:  61:  OC:    7.50:   162.80:    10.0:  KH5K:
#    AH5K,KH5K,NH5K,WH5K;
#Hawaii:                   31:  61:  OC:   21.30:   157.90:    10.0:  KH6:
#    AH6,AH7,KH6,KH7,N6KB,NH6,NH7,WH6,WH7;
#Kure I.:                  31:  61:  OC:   28.40:   178.40:    11.0:  KH7K:
#    AH7K,KH7K,NH7K,WH7K;
#American Samoa:           32:  62:  OC:  -14.30:   170.80:    11.0:  KH8:
#    AH8,KH8,NH8,WH8;
#Swains Island:            32:  62:  OC:  -11.05:   171.25:    11.0:  KH8/s:
#    KH8SI;
#Wake I.:                  31:  65:  OC:   19.30:  -166.60:   -12.0:  KH9:
#    AH9,KH9,NH9,WH9;
#Alaska:                   01:  01:  NA:   61.20:   150.00:     9.0:  KL:
#    AL,KL,NL,WL,KW1W;
#Navassa I.:               08:  11:  NA:   18.40:    75.00:     5.0:  KP1:
#    KP1,NP1,WP1;
#Virgin Is.:               08:  11:  NA:   18.30:    64.90:     5.0:  KP2:
#    KP2,NP2,WP2,KV4FZ;
#Puerto Rico:              08:  11:  NA:   18.50:    66.20:     5.0:  KP4:
#    KP3,KP4,NP3,NP4,WP3,WP4;
#Desecheo I.:              08:  11:  NA:   18.30:    67.50:     5.0:  KP5:
#    KP5,NP5,WP5;
#Norway:                   14:  18:  EU:   60.00:   -10.70:    -1.0:  LA:
#    LA,LB,LC,LD,LE,LF,LG,LH,LI,LJ,LK,LL,LM,LN;
#Argentina:                13:  14:  SA:  -34.60:    58.40:     3.0:  LU:
#    AY,AZ,L2,L3,L4,L5,L6,L7,L8,L84VI/D,L9,LO,LP,LQ,LR,LS,LT,LU,LV,LW,AY0N/X,
#    AY3DR/D,AY4EJ/D,AY5E/D,AY7DSY/D,DJ4SN/LU/X,L20ARC/D,L21ESC/LH,L25E/D,
#    L30EY/D,L30EY/V,L40E/D,L44D/D,L80AA/D,L8D/X,LO0D/D,LO7E/D,LU/DH4PB/R,
#    LU/DH4PB/S,LU1AEE/D,LU1AF/D,LU1CDP/D,LU1DK/D,LU1DMA/E,LU1DZ/E,LU1DZ/P,
#    LU1DZ/Q,LU1DZ/R,LU1DZ/S,LU1DZ/X,LU1EJ/W,LU1EQ/D,LU1EYW/D,LU1OFN/I,
#    LU1VOF/D,LU1VZ/V,LU1XAW/X,LU1XY/X,LU1YU/D,LU1YY/Y,LU2CRM/XA,LU2DT/D,
#    LU2DT/LH,LU2DVI/H,LU2EE/D,LU2EE/E,LU2EJB/X,LU2VC/D,LU2WV/O,LU2XX/X,
#    LU3CQ/D,LU3DJI/D,LU3DJI/W,LU3DOC/D,LU3DR/D,LU3DR/V,LU3DXG/D,LU3ES/D,
#    LU3ES/W,LU4AAO/D,LU4DA/D,LU4DQ/D,LU4DRC/Y,LU4DRH/D,LU4DRH/E,LU4EJ/D,
#    LU4ETN/D,LU4WG/W,LU5BE/D,LU5BOJ/O,LU5DEM/D,LU5DEM/V,LU5DIT/D,LU5DIT/V,
#    LU5DRV/D,LU5DRV/V,LU5DT/D,LU5DV/D,LU5DWS/D,LU5EAO/D,LU5EFX/Y,LU5EWO/D,
#    LU5FZ/D,LU5XC/X,LU6DBL/D,LU6DKT/D,LU6DRD/D,LU6DRD/E,LU6DRN/D,LU6DRR/D,
#    LU6EC/W,LU6EJJ/D,LU6EPR/D,LU6EPR/E,LU6EYK/X,LU6JJ/D,LU6UO/D,LU6UO/P,
#    LU6UO/Q,LU6UO/R,LU6UO/S,LU6UO/X,LU6XAH/X,LU7AC/D,LU7BTO/D,LU7DID/V,
#    LU7DID/Y,LU7DIR/D,LU7DJJ/W,LU7DP/D,LU7DR/D,LU7DSY/D,LU7DSY/V,LU7DSY/W,
#    LU7DW/D,LU7DZL/D,LU7DZL/E,LU7EGH/V,LU7EGY/D,LU7EO/D,LU7EPC/D,LU7EPC/W,
#    LU7VCH/D,LU7WFM/W,LU7WW/W,LU8ADX/D,LU8DCH/D,LU8DCH/Q,LU8DRH/D,LU8DWR/D,
#    LU8DWR/V,LU8EBJ/D,LU8EBJ/E,LU8EBK/D,LU8EBK/E,LU8ECF/D,LU8ECF/E,LU8EEM/D,
#    LU8EGS/D,LU8EHQ/D,LU8EHQ/E,LU8EHQ/W,LU8EKB/W,LU8EKC/D,LU8EOT/X,LU8EOT/Y,
#    LU8ERH/D,LU8EXJ/D,LU8EXN/D,LU8FOZ/V,LU8VCC/D,LU8XC/X,LU8XW/X,LU9ARB/D,
#    LU9AUC/D,LU9DBK/X,LU9DKX/X,LU9DPD/XA,LU9EI/F,LU9EJS/E,LU9ESD/D,LU9ESD/V,
#    LU9ESD/Y,LU9EV/LH,LU9JMG/J,LW1DAL/D,LW1EXU/D,LW1EXU/Y,LW2DX/E,LW2DX/P,
#    LW2DX/Q,LW2DX/R,LW2DX/S,LW2DX/Y,LW2ENB/D,LW3DKC/D,LW3DKC/E,LW3DKO/D,
#    LW3DKO/E,LW3HAQ/D,LW4DRH/D,LW4DRH/E,LW4DRV/D,LW4EM/E,LW4EM/LH,LW5DR/LH,
#    LW5DWX/D,LW5EE/D,LW5EE/V,LW5EOL/D,LW6DTM/D,LW7DAF/D,LW7DAF/W,LW7DLY/D,
#    LW7DNS/E,LW8DMK/D,LW8ECQ/D,LW8EU/D,LW8EXF/D,LW9DCF/Y,LW9EAG/D,LW9EAG/V,
#    LW9EVA/D,LW9EVA/E;
#Luxembourg:               14:  27:  EU:   49.60:    -6.20:    -1.0:  LX:
#    LX;
#Lithuania:                15:  29:  EU:   54.50:   -25.50:    -2.0:  LY:
#    LY;
#Bulgaria:                 20:  28:  EU:   42.70:   -23.30:    -2.0:  LZ:
#    LZ;
#Peru:                     10:  12:  SA:  -12.10:    77.10:     5.0:  OA:
#    4T,OA,OB,OC;
#Lebanon:                  20:  39:  AS:   33.90:   -35.50:    -2.0:  OD:
#    OD;
#Austria:                  15:  28:  EU:   48.20:   -16.30:    -1.0:  OE:
#    OE,SH75,4U1VIC;
#Finland:                  15:  18:  EU:   60.20:   -25.00:    -2.0:  OH:
#    OF,OG,OH,OI,OJ;
#Aland Is.:                15:  18:  EU:   60.20:   -20.00:    -2.0:  OH0:
#    OF0,OG0,OH0,OI0;
#Market Reef:              15:  18:  EU:   60.30:   -19.00:    -2.0:  OJ0:
#    OJ0;
#Czech Republic:           15:  28:  EU:   50.10:   -14.40:    -1.0:  OK:
#    OK,OL;
#Slovakia:                 15:  28:  EU:   48.10:   -17.10:    -1.0:  OM:
#    OM;
#Belgium:                  14:  27:  EU:   50.90:    -4.40:    -1.0:  ON:
#    ON,OO,OP,OQ,OR,OS,OT;
#Greenland:                40:  05:  NA:   62.50:    45.00:     3.0:  OX:
#    OX,XP;
#Faroe Is.:                14:  18:  EU:   62.00:     6.80:     0.0:  OY:
#    OW,OY;
#Denmark:                  14:  18:  EU:   55.70:   -12.60:    -1.0:  OZ:
#    5P,5Q,OU,OV,OZ;
#Papua New Guinea:         28:  51:  OC:   -9.40:  -147.10:   -10.0:  P2:
#    P2;
#Aruba:                    09:  11:  SA:   12.50:    70.00:     4.0:  P4:
#    P4;
#Netherlands:              14:  27:  EU:   52.40:    -4.90:    -1.0:  PA:
#    PA,PB,PC,PD,PE,PF,PG,PH,PI;
#Netherlands Antilles:     09:  11:  SA:   12.10:    69.00:     4.0:  PJ2:
#    PJ0,PJ1,PJ2,PJ3,PJ4,PJ9;
#Sint Maarten:             08:  11:  NA:   17.70:    63.20:     4.0:  PJ7:
#    PJ5,PJ6,PJ7,PJ8;
#Brazil:                   11:  15:  SA:  -23.00:    43.20:     3.0:  PY:
#    PP,PQ,PR,PS,PT,PU,PV,PW,PX,PY,ZV,ZW,ZX,ZY,ZZ;
#Fernando de Noronha:      11:  13:  SA:   -3.90:    32.40:     2.0:  PY0F:
#    PP0F,PP0ZF,PQ0F,PQ0ZF,PR0F,PR0ZF,PS0F,PS0ZF,PT0F,PT0ZF,PU0F,PU0ZF,PV0F,
#    PV0ZF,PW0F,PW0ZF,PX0F,PX0ZF,PY0F,PY0ZF,ZV0F,ZV0ZF,ZW0F,ZW0ZF,ZX0F,ZX0ZF,
#    ZY0F,ZY0ZF,ZZ0F,ZZ0ZF;
#St. Peter & St. Paul:     11:  13:  SA:    1.00:    29.40:     2.0:  PY0S:
#    PP0S,PP0ZS,PQ0S,PQ0ZS,PR0S,PR0ZS,PS0S,PS0ZS,PT0S,PT0ZS,PU0S,PU0ZS,PV0S,
#    PV0ZS,PW0S,PW0ZS,PX0S,PX0ZS,PY0S,PY0ZS,ZV0S,ZV0ZS,ZW0S,ZW0ZS,ZX0S,ZX0ZS,
#    ZY0S,ZY0ZS,ZZ0S,ZZ0ZS;
#Trindade & Martim Vaz:    11:  15:  SA:  -20.50:    29.30:     2.0:  PY0T:
#    PP0T,PP0ZT,PQ0T,PQ0ZT,PR0T,PR0ZT,PS0T,PS0ZT,PT0T,PT0ZT,PU0T,PU0ZT,PV0T,
#    PV0ZT,PW0T,PW0ZT,PX0T,PX0ZT,PY0T,PY0ZT,ZV0T,ZV0ZT,ZW0T,ZW0ZT,ZX0T,ZX0ZT,
#    ZY0T,ZY0ZT,ZZ0T,ZZ0ZT;
#Suriname:                 09:  12:  SA:    5.80:    55.20:     3.0:  PZ:
#    PZ;
#Franz Josef Land:         40:  75:  EU:   80.00:   -53.00:    -3.0:  R1FJ:
#    FJL,R1FJ,UA1PBN/1;
#Malyj Vysotskij:          16:  29:  EU:   60.40:   -28.40:    -3.0:  R1MV:
#    MVI,R1MV;
#Western Sahara:           33:  46:  AF:   22.00:    15.00:     0.0:  S0:
#    S0;
#Bangladesh:               22:  41:  AS:   23.70:   -90.40:    -6.0:  S2:
#    S2,S3;
#Slovenia:                 15:  28:  EU:   46.00:   -14.50:    -1.0:  S5:
#    S5;
#Seychelles:               39:  53:  AF:   -4.60:   -55.50:    -4.0:  S7:
#    S7;
#Sao Tome & Principe:      36:  47:  AF:    0.30:    -6.70:     0.0:  S9:
#    S9;
#Sweden:                   14:  18:  EU:   59.30:   -18.10:    -1.0:  SM:
#    7S,8S,SA,SB,SC,SD,SE,SF,SG,SH,SI,SJ,SK,SL,SM;
#Poland:                   15:  28:  EU:   52.20:   -21.00:    -1.0:  SP:
#    3Z,HF,SN,SO,SP,SQ,SR;
#Sudan:                    34:  48:  AF:   15.60:   -32.50:    -2.0:  ST:
#    6T,6U,ST;
#Egypt:                    34:  38:  AF:   30.00:   -31.40:    -2.0:  SU:
#    6A,6B,SS,SU;
#Greece:                   20:  28:  EU:   38.00:   -23.70:    -2.0:  SV:
#    J4,SV,SW,SX,SY,SZ;
#Mount Athos:              20:  28:  EU:   40.20:   -24.30:    -2.0:  SV/a:
#    SV2ASP/A;
#Dodecanese:               20:  28:  EU:   36.40:   -28.20:    -2.0:  SV5:
#    J45,SV5,SW5,SX5,SY5,SZ5;
#Crete:                    20:  28:  EU:   35.40:   -25.20:    -2.0:  SV9:
#    J49,SV9,SW9,SX9,SY9,SZ9,SV0XAZ;
#Tuvalu:                   31:  65:  OC:   -8.70:  -179.20:   -12.0:  T2:
#    T2;
#Western Kiribati:         31:  65:  OC:   -1.40:  -173.20:   -12.0:  T30:
#    T30;
#Central Kiribati:         31:  62:  OC:   -2.80:   171.70:    11.0:  T31:
#    T31;
#Eastern Kiribati:         31:  61:  OC:    1.90:   157.40:    10.0:  T32:
#    T32;
#Banaba:                   31:  65:  OC:   -0.50:  -169.40:   -11.0:  T33:
#    T33;
#Somalia:                  37:  48:  AF:    2.10:   -45.40:    -3.0:  T5:
#    6O,T5;
#San Marino:               15:  28:  EU:   43.90:   -12.30:    -1.0:  T7:
#    T7;
#Palau:                    27:  64:  OC:    9.50:  -138.20:   -10.0:  T8:
#    T8;
#Turkey:                   20:  39:  AS:   40.00:   -33.00:    -2.0:  TA:
#    TA,TB,TC,YM;
#Turkey (Europe):          20:  39:  EU:   41.20:   -29.00:    -2.0:  *TA1:
#    TA1,TB1,TC1,YM1;
#Iceland:                  40:  17:  EU:   64.10:    22.00:     0.0:  TF:
#    TF;
#Guatemala:                07:  11:  NA:   14.60:    90.50:     6.0:  TG:
#    TD,TG;
#Costa Rica:               07:  11:  NA:    9.90:    84.00:     6.0:  TI:
#    TE,TI;
#Cocos I.:                 07:  11:  NA:    5.60:    87.00:     6.0:  TI9:
#    TE9,TI9;
#Cameroon:                 36:  47:  AF:    3.90:   -11.50:    -1.0:  TJ:
#    TJ;
#Corsica:                  15:  28:  EU:   42.00:    -9.00:    -1.0:  TK:
#    TK;
#Central African Rep:      36:  47:  AF:    4.40:   -18.60:    -1.0:  TL:
#    TL;
#Congo:                    36:  52:  AF:   -4.30:   -15.30:    -1.0:  TN:
#    TN;
#Gabon:                    36:  52:  AF:    0.40:    -9.50:    -1.0:  TR:
#    TR;
#Chad:                     36:  47:  AF:   12.10:   -15.00:    -1.0:  TT:
#    TT;
#Cote d'Ivoire:            35:  46:  AF:    5.30:     4.00:     0.0:  TU:
#    TU;
#Benin:                    35:  46:  AF:    6.50:    -2.60:    -1.0:  TY:
#    TY;
#Mali:                     35:  46:  AF:   12.70:     8.00:     0.0:  TZ:
#    TZ;
#European Russia:          16:  29:  EU:   55.80:   -37.60:    -3.0:  UA:
#    R,RD4W[30],RK4W[30],RM4W[30],RN4W[30],RU4W[30],RV4W[30],RW4W[30],U,
#    UA4W[30],R245GS,R7C,R7C/1,R7C/3,R7C/4;
#Kaliningrad:              15:  29:  EU:   55.00:   -20.50:    -2.0:  UA2:
#    R2,RA2,RB2,RC2,RD2,RE2,RF2,RG2,RH2,RI2,RJ2,RK2,RL2,RM2,RN2,RO2,RP2,RQ2,
#    RR2,RS2,RT2,RU2,RV2,RW2,RX2,RY2,RZ2,U2,UA2,UB2,UC2,UD2,UE2,UF2,UG2,UH2,
#    UI2,R5K/2,UA1AAE/2;
#Asiatic Russia:           17:  30:  AS:   55.00:   -83.00:    -7.0:  UA9:
#    R0,R450W,R7,R8,R8T(18)[32],R8V(18)[33],R9,R9I(18)[31],R9M(17),R9S(16),
#    R9T(16),R9W(16),RA0,RA7,RA8,RA8T(18)[32],RA8V(18)[33],RA9,RA9I(18)[31],
#    RA9M(17),RA9S(16),RA9T(16),RA9W(16),RB0,RB7,RB8,RB8T(18)[32],RB8V(18)[33],RB9,
#    RB9I(18)[31],RB9M(17),RB9S(16),RB9T(16),RB9W(16),RC0,RC7,RC8,RC8T(18)[32],
#    RC8V(18)[33],RC9,RC9I(18)[31],RC9M(17),RC9S(16),RC9T(16),RC9W(16),RD0,RD7,RD8,
#    RD8T(18)[32],RD8V(18)[33],RD9,RD9I(18)[31],RD9M(17),RD9S(16),RD9T(16),
#    RD9W(16),RE0,RE7,RE8,RE8T(18)[32],RE8V(18)[33],RE9,RE9I(18)[31],RE9M(17),
#    RE9S(16),RE9T(16),RE9W(16),RF0,RF7,RF8,RF8T(18)[32],RF8V(18)[33],RF9,
#    RF9I(18)[31],RF9M(17),RF9S(16),RF9T(16),RF9W(16),RG0,RG7,RG8,RG8T(18)[32],
#    RG8V(18)[33],RG9,RG9I(18)[31],RG9M(17),RG9S(16),RG9T(16),RG9W(16),RH0,RH7,RH8,
#    RH8T(18)[32],RH8V(18)[33],RH9,RH9I(18)[31],RH9M(17),RH9S(16),RH9T(16),
#    RH9W(16),RI0,RI7,RI8,RI8T(18)[32],RI8V(18)[33],RI9,RI9I(18)[31],RI9M(17),
#    RI9S(16),RI9T(16),RI9W(16),RJ0,RJ7,RJ8,RJ8T(18)[32],RJ8V(18)[33],RJ9,
#    RJ9I(18)[31],RJ9M(17),RJ9S(16),RJ9T(16),RJ9W(16),RK0,RK7,RK8,RK8T(18)[32],
#    RK8V(18)[33],RK9,RK9I(18)[31],RK9M(17),RK9S(16),RK9T(16),RK9W(16),RL0,RL7,RL8,
#    RL8T(18)[32],RL8V(18)[33],RL9,RL9I(18)[31],RL9M(17),RL9S(16),RL9T(16),
#    RL9W(16),RM0,RM7,RM8,RM8T(18)[32],RM8V(18)[33],RM9,RM9I(18)[31],RM9M(17),
#    RM9S(16),RM9T(16),RM9W(16),RN0,RN7,RN8,RN8T(18)[32],RN8V(18)[33],RN9,
#    RN9I(18)[31],RN9M(17),RN9S(16),RN9T(16),RN9W(16),RO0,RO7,RO8,RO8T(18)[32],
#    RO8V(18)[33],RO9,RO9I(18)[31],RO9M(17),RO9S(16),RO9T(16),RO9W(16),RP0,RP7,RP8,
#    RP8T(18)[32],RP8V(18)[33],RP9,RP9I(18)[31],RP9M(17),RP9S(16),RP9T(16),
#    RP9W(16),RQ0,RQ7,RQ8,RQ8T(18)[32],RQ8V(18)[33],RQ9,RQ9I(18)[31],RQ9M(17),
#    RQ9S(16),RQ9T(16),RQ9W(16),RR0,RR7,RR8,RR8T(18)[32],RR8V(18)[33],RR9,
#    RR9I(18)[31],RR9M(17),RR9S(16),RR9T(16),RR9W(16),RS0,RS7,RS8,RS8T(18)[32],
#    RS8V(18)[33],RS9,RS9I(18)[31],RS9M(17),RS9S(16),RS9T(16),RS9W(16),RT0,RT7,RT8,
#    RT8T(18)[32],RT8V(18)[33],RT9,RT9I(18)[31],RT9M(17),RT9S(16),RT9T(16),
#    RT9W(16),RU0,RU7,RU8,RU8T(18)[32],RU8V(18)[33],RU9,RU9I(18)[31],RU9M(17),
#    RU9S(16),RU9T(16),RU9W(16),RV0,RV7,RV8,RV8T(18)[32],RV8V(18)[33],RV9,
#    RV9I(18)[31],RV9M(17),RV9S(16),RV9T(16),RV9W(16),RW0,RW7,RW8,RW8T(18)[32],
#    RW8V(18)[33],RW9,RW9I(18)[31],RW9M(17),RW9S(16),RW9T(16),RW9W(16),RX0,RX7,RX8,
#    RX8T(18)[32],RX8V(18)[33],RX9,RX9I(18)[31],RX9M(17),RX9S(16),RX9T(16),
#    RX9W(16),RY0,RY7,RY8,RY8T(18)[32],RY8V(18)[33],RY9,RY9I(18)[31],RY9M(17),
#    RY9S(16),RY9T(16),RY9W(16),RZ0,RZ7,RZ8,RZ8T(18)[32],RZ8V(18)[33],RZ9,
#    RZ9I(18)[31],RZ9M(17),RZ9S(16),RZ9T(16),RZ9W(16),U0,U7,U8,U8T(18)[32],
#    U8V(18)[33],U9,U9I(18)[31],U9M(17),U9S(16),U9T(16),U9W(16),UA0,UA7,UA8,
#    UA8T(18)[32],UA8V(18)[33],UA9,UA9I(18)[31],UA9M(17),UA9S(16),UA9T(16),
#    UA9W(16),UB0,UB7,UB8,UB8T(18)[32],UB8V(18)[33],UB9,UB9I(18)[31],UB9M(17),
#    UB9S(16),UB9T(16),UB9W(16),UC0,UC7,UC8,UC8T(18)[32],UC8V(18)[33],UC9,
#    UC9I(18)[31],UC9M(17),UC9S(16),UC9T(16),UC9W(16),UD0,UD7,UD8,UD8T(18)[32],
#    UD8V(18)[33],UD9,UD9I(18)[31],UD9M(17),UD9S(16),UD9T(16),UD9W(16),UE0,UE7,UE8,
#    UE8T(18)[32],UE8V(18)[33],UE9,UE9I(18)[31],UE9M(17),UE9S(16),UE9T(16),
#    UE9W(16),UF0,UF7,UF8,UF8T(18)[32],UF8V(18)[33],UF9,UF9I(18)[31],UF9M(17),
#    UF9S(16),UF9T(16),UF9W(16),UG0,UG7,UG8,UG8T(18)[32],UG8V(18)[33],UG9,
#    UG9I(18)[31],UG9M(17),UG9S(16),UG9T(16),UG9W(16),UH0,UH7,UH8,UH8T(18)[32],
#    UH8V(18)[33],UH9,UH9I(18)[31],UH9M(17),UH9S(16),UH9T(16),UH9W(16),UI0,UI7,UI8,
#    UI8T(18)[32],UI8V(18)[33],UI9,UI9I(18)[31],UI9M(17),UI9S(16),UI9T(16),
#    UI9W(16),R30ZF,R35NP,R3F/9,R9HQ(17)[30],UE60SWA;
#Uzbekistan:               17:  30:  AS:   41.20:   -69.30:    -5.0:  UK:
#    UJ,UK,UL,UM;
#Kazakhstan:               17:  30:  AS:   43.30:   -76.90:    -5.0:  UN:
#    UN,UO,UP,UQ;
#Ukraine:                  16:  29:  EU:   50.40:   -30.50:    -2.0:  UR:
#    EM,EN,EO,U5,UR,US,UT,UU,UV,UW,UX,UY,UZ;
#Antigua & Barbuda:        08:  11:  NA:   17.10:    61.80:     4.0:  V2:
#    V2;
#Belize:                   07:  11:  NA:   17.30:    88.80:     6.0:  V3:
#    V3;
#St. Kitts & Nevis:        08:  11:  NA:   17.30:    62.60:     4.0:  V4:
#    V4;
#Namibia:                  38:  57:  AF:  -22.60:   -17.10:    -1.0:  V5:
#    V5;
#Micronesia:               27:  65:  OC:    6.90:  -158.30:   -10.0:  V6:
#    V6;
#Marshall Is.:             31:  65:  OC:    9.10:  -167.30:   -12.0:  V7:
#    V7;
#Brunei:                   28:  54:  OC:    4.90:  -114.90:    -8.0:  V8:
#    V8;
#Canada:                   05:  09:  NA:   45.00:    80.00:     4.0:  VE:
#    CF,CG,CH1(5)[9],CH2(2)[9],CI0(2)[4],CI1(1)[2],CI2(5)[9],CJ,CK,CY1(5)[9],
#    CY2(2)[9],CZ0(2)[4],CZ1(1)[2],CZ2(5)[9],VA,VB,VC,VD1(5)[9],VD2(2)[9],VE,
#    VF0(2)[4],VF1(1)[2],VF2(5)[9],VG,VO1(5)[9],VO2(2)[9],VX,VY0(2)[4],
#    VY1(1)[2],VY2(5)[9],XJ1(5)[9],XJ2(2)[9],XK0(2)[4],XK1(1)[2],XK2(5)[9],XL,
#    XM,XN1(5)[9],XN2(2)[9],XO0(2)[4],XO1(1)[2],XO2(5)[9],K3FMQ/VE2(2),
#    KD3RF/VE2(2),KD3TB/VE2(2),VA2BY(2),VA2CT(2),VA2DO(2),VA2DXE(2),VA2KCE(2),
#    VA2RHJ(2),VA2UA(2),VA2VFT(2),VA2ZM(2),VA3NA/2(2),VB2C(2),VB2R(2),VB2V(2),
#    VC2C(2),VE2/K3FMQ(2),VE2ACP(2),VE2AE(2),VE2AG(2),VE2AOF(2),VE2AQS(2),
#    VE2AS(2),VE2BQB(2),VE2CSI(2),VE2CVI(2),VE2DMG(2),VE2DS(2),VE2DWU(2),
#    VE2DXY(2),VE2DYW(2),VE2DYX(2),VE2EAK(2),VE2EDL(2),VE2EDX(2),VE2ELL(2),
#    VE2ENB(2),VE2END(2),VE2ENR(2),VE2ERU(2),VE2FCV(2),VE2GSA(2),VE2GSO(2),
#    VE2III(2),VE2IM(2),VE2KK(2),VE2MTA(2),VE2MTB(2),VE2NN(2),VE2NRK(2),
#    VE2PR(2),VE2QRZ(2),VE2RB(2),VE2TVU(2),VE2UA(2),VE2VH(2),VE2WDX(2),
#    VE2WT(2),VE2XAA/2(2),VE2XY(2),VE2YM(2),VE2Z(2),VE2ZC(5),VE2ZM(5),VE2ZV(5),
#    VE3EY/2(2),VE3NE/2(2),VE3RHJ/2(2),VE8AJ(2),VE8PW(2),VE8RCS(2),VER20080212,
#    VY0AA(4)[3],VY0PW(4)[3],VY2MGY/3(4)[4];
#Australia:                30:  59:  OC:  -22.00:  -135.00:   -10.0:  VK:
#    AX,VH,VI,VJ,VK,VL,VM,VN,VZ;
#Heard I.:                 39:  68:  AF:  -53.00:   -73.40:    -5.0:  VK0H:
#    VK0HI,VK0IR;
#Macquarie I.:             30:  60:  OC:  -54.70:  -158.80:   -11.0:  VK0M:
#    AX0M,VH0M,VI0M,VJ0M,VK0M,VL0M,VM0M,VN0M,VZ0M;
#Cocos-Keeling:            29:  54:  OC:  -12.20:   -96.80:    -6.5:  VK9C:
#    AX9C,AX9Y,VH9C,VH9Y,VI9C,VI9Y,VJ9C,VJ9Y,VK9C,VK9FC,VK9KC,VK9KY,VK9Y,VL9C,
#    VL9Y,VM9C,VM9Y,VN9C,VN9Y,VZ9C,VZ9Y,VK9AA;
#Lord Howe I.:             30:  60:  OC:  -31.60:  -159.10:   -10.5:  VK9L:
#    AX9L,VH9L,VI9L,VJ9L,VK9CL,VK9FL,VK9GL,VK9KL,VK9L,VL9L,VM9L,VN9L,VZ9L;
#Mellish Reef:             30:  56:  OC:  -17.60:  -155.80:   -10.0:  VK9M:
#    AX9M,VH9M,VI9M,VJ9M,VK9FM,VK9KM,VK9M,VL9M,VM9M,VN9M,VZ9M;
#Norfolk I.:               32:  60:  OC:  -29.00:  -168.00:   -11.5:  VK9N:
#    AX9,VH9,VI9,VJ9,VK9,VK9CN,VL9,VM9,VN9,VZ9;
#Willis I.:                30:  55:  OC:  -16.30:  -149.50:   -10.0:  VK9W:
#    AX9W,VH9W,VI9W,VJ9W,VK9FW,VK9KW,VK9W,VL9W,VM9W,VN9W,VZ9W,VK9DWX;
#Christmas I.:             29:  54:  OC:  -10.50:  -105.70:    -7.0:  VK9X:
#    AX9X,VH9X,VI9X,VJ9X,VK9FX,VK9KX,VK9X,VL9X,VM9X,VN9X,VZ9X;
#Anguilla:                 08:  11:  NA:   18.30:    63.00:     4.0:  VP2E:
#    VP2E;
#Montserrat:               08:  11:  NA:   16.80:    62.20:     4.0:  VP2M:
#    VP2M;
#British Virgin Is.:       08:  11:  NA:   18.40:    64.60:     4.0:  VP2V:
#    VP2V;
#Turks & Caicos:           08:  11:  NA:   21.80:    72.40:     5.0:  VP5:
#    VP5,VQ5;
#Pitcairn I.:              32:  63:  OC:  -25.10:   130.10:     8.5:  VP6:
#    VP6;
#Ducie I.:                 32:  63:  OC:  -24.67:   124.79:     8.5:  VP6/d:
#    VP6DI,VP6DX;
#Falkland Is.:             13:  16:  SA:  -51.70:    57.90:     4.0:  VP8:
#    VP8;
#South Georgia:            13:  73:  SA:  -54.30:    36.80:     2.0:  VP8/g:
#    VP8DKX,VP8SGK;
#South Shetland:           13:  73:  SA:  -62.00:    58.30:     4.0:  VP8/h:
#    DT8A,ED3RKL,HF0POL,HL8KSJ,LU/R1ANF,LU1ZC,LZ0A,R1ANF,VP8/LZ1UQ,VP8DJK;
#South Orkney:             13:  73:  SA:  -60.00:    45.50:     3.0:  VP8/o:
#    AY1ZA,LU1ZA,LU2ERA/Z;
#South Sandwich:           13:  73:  SA:  -57.00:    26.70:     2.0:  VP8/s:
#    VP8SSI,VP8THU;
#Bermuda:                  05:  11:  NA:   32.30:    64.70:     4.0:  VP9:
#    VP9;
#Chagos Is.:               39:  41:  AF:   -7.30:   -72.40:    -5.0:  VQ9:
#    VQ9;
#Hong Kong:                24:  44:  AS:   22.30:  -114.30:    -8.0:  VR:
#    VR;
#India:                    22:  41:  AS:   22.00:   -80.00:    -5.5:  VU:
#    8T,8U,8V,8W,8X,8Y,AT,AU,AV,AW,VT,VU,VV,VW;
#Andaman & Nicobar:        26:  49:  AS:   11.70:   -92.80:    -5.5:  VU4:
#    VU4,VU3VPX,VU3VPY;
#Laccadive Is.:            22:  41:  AS:   10.00:   -73.00:    -5.5:  VU7:
#    VU7;
#Mexico:                   06:  10:  NA:   19.40:    99.10:     6.0:  XE:
#    4A,4B,4C,6D,6E,6F,6G,6H,6I,6J,XA,XB,XC,XD,XE,XF,XG,XH,XI;
#Revilla Gigedo:           06:  10:  NA:   19.00:   111.50:     7.0:  XF4:
#    4A4,4B4,4C4,6D4,6E4,6F4,6G4,6H4,6I4,6J4,XA4,XB4,XC4,XD4,XE4,XF4,XG4,XH4,
#    XI4;
#Burkina Faso:             35:  46:  AF:   12.40:     1.60:     0.0:  XT:
#    XT;
#Kampuchea:                26:  49:  AS:   11.70:  -104.80:    -7.0:  XU:
#    XU;
#Laos:                     26:  49:  AS:   18.00:  -102.60:    -7.0:  XW:
#    XW;
#Macau:                    24:  44:  AS:   22.20:  -113.60:    -8.0:  XX9:
#    XX9;
#Myanmar:                  26:  49:  AS:   16.80:   -96.00:    -6.5:  XZ:
#    1Z,XY,XZ;
#Afghanistan:              21:  40:  AS:   34.40:   -69.20:    -4.5:  YA:
#    T6,YA;
#Indonesia:                28:  54:  OC:   -6.20:  -106.80:    -7.0:  YB:
#    7A,7B,7C,7D,7E,7F,7G,7H,7I,8A,8B,8C,8D,8E,8F,8G,8H,8I,JZ,PK,PL,PM,PN,PO,
#    YB,YC,YD,YE,YF,YG,YH;
#Iraq:                     21:  39:  AS:   33.00:   -44.50:    -3.0:  YI:
#    HN,YI;
#Vanuatu:                  32:  56:  OC:  -17.70:  -168.30:   -11.0:  YJ:
#    YJ;
#Syria:                    20:  39:  AS:   33.50:   -36.30:    -2.0:  YK:
#    6C,YK;
#Latvia:                   15:  29:  EU:   57.00:   -24.10:    -2.0:  YL:
#    YL;
#Nicaragua:                07:  11:  NA:   12.00:    86.00:     6.0:  YN:
#    H6,H7,HT,YN;
#Romania:                  20:  28:  EU:   44.40:   -26.10:    -2.0:  YO:
#    YO,YP,YQ,YR;
#El Salvador:              07:  11:  NA:   13.70:    89.20:     6.0:  YS:
#    HU,YS;
#Serbia:                   15:  28:  EU:   44.90:   -20.50:    -1.0:  YU:
#    4N,YT,YU,YZ;
#Venezuela:                09:  12:  SA:   10.50:    67.00:     4.5:  YV:
#    4M,YV,YW,YX,YY;
#Aves I.:                  08:  11:  NA:   15.70:    63.70:     4.0:  YV0:
#    4M0,YV0,YW0,YX0,YY0;
#Zimbabwe:                 38:  53:  AF:  -17.80:   -31.00:    -2.0:  Z2:
#    Z2;
#Macedonia:                15:  28:  EU:   41.80:   -21.40:    -1.0:  Z3:
#    Z3;
#Albania:                  15:  28:  EU:   41.30:   -19.80:    -1.0:  ZA:
#    ZA;
#Gibraltar:                14:  37:  EU:   36.10:     5.40:    -1.0:  ZB:
#    ZB,ZG;
#UK Bases on Cyprus:       20:  39:  AS:   34.60:   -33.00:    -2.0:  ZC4:
#    ZC4;
#Saint Helena:             36:  66:  AF:  -16.00:     5.90:     0.0:  ZD7:
#    ZD7;
#Ascension I.:             36:  66:  AF:   -8.00:    14.40:     0.0:  ZD8:
#    ZD8;
#Tristan da Cunha:         38:  66:  AF:  -37.10:    12.30:     0.0:  ZD9:
#    ZD9;
#Cayman Is.:               08:  11:  NA:   19.50:    81.20:     5.0:  ZF:
#    ZF;
#Niue:                     32:  62:  OC:  -19.00:   169.90:    11.0:  ZK2:
#    ZK2;
#Tokelau:                  31:  62:  OC:   -8.40:   172.70:    11.0:  ZK3:
#    ZK3;
#New Zealand:              32:  60:  OC:  -36.90:  -174.80:   -12.0:  ZL:
#    ZK,ZL,ZM,ZL75;
#Chatham Is.:              32:  60:  OC:  -44.00:   176.50:  -12.75:  ZL7:
#    ZL7,ZM7;
#Kermadec Is.:             32:  60:  OC:  -30.00:   177.90:   -12.0:  ZL8:
#    ZL1GO/8,ZL8,ZM8;
#Auckland & Campbell:      32:  60:  OC:  -50.70:  -166.50:   -12.0:  ZL9:
#    ZL9,ZM9;
#Paraguay:                 11:  14:  SA:  -25.30:    57.70:     4.0:  ZP:
#    ZP;
#South Africa:             38:  57:  AF:  -26.20:   -28.10:    -2.0:  ZS:
#    H5,S4,S8,V9,ZR,ZS,ZT,ZU;
#Marion I.:                38:  57:  AF:  -46.80:   -37.80:    -3.0:  ZS8:
#    ZR8,ZS8,ZT8,ZU8;
