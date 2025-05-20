#!/usr/bin/perl

# Parse raw RBN data into a custom file format to measure activity 
# of a certain station:
#
# Each callsign's data is saved in a file which contains an array 
# of 32 bit values, one for each hour slot of each day, resulting
# in 24 * 365 (or 366) 32 bit values. The bits are interpreted as
# follows:
#
#   MSB
#   0
#   1
#   2
#   3
#   4
#   5
#   6
#   7
#   8
#   9       2200m   # extended July 2020
#   10      630m
#   11      6m
#   12      4m
#   13      2m
#   14      70cm
#   15      23cm
#   16		EU      # old format MSB / 0
#   17      NA      # old format bit 1
#   18		AS
#   19		SA
#   20		AF
#   21		OC
#   22		10m
#   23		12m
#   24		15m
#   25		17m
#   26		20m
#   27		30m
#   28		40m
#   29		60m
#   30		80m
#   31		160m 

# This data is temporarily saved for each callsign in a hash where
# the callsign is the key and the data is the value.
#
# The continent counter is saved in a separate hash.

use warnings;
use strict;
use Date::Parse;
use Digest::MD5 qw(md5 md5_hex);
use DBI;

use Compress::Zlib qw(memGzip memGunzip);

my $start_time = time();

# start time of the first year
#my $t0 = str2time("2015-01-01 00:00:00", 'GMT');
my $t0 = str2time("2009-01-01 00:00:00", 'GMT');

# main data hash of arrays
my %d = ();

my %bm_bands = ( '160m' => 0x0001, '80m' => 0x0002, '60m' => 0x0004,
				'40m' => 0x0008, '30m' => 0x0010, '20m' => 0x0020,
				'17m' => 0x0040, '15m' => 0x0080, '12m' => 0x0100,
				'10m' => 0x0200,
                '136kHz' => 0x00400000, '472kHz' => 0x00200000,, '6m' => 0x00100000,
                '4m' => 0x00080000, '2m' => 0x00040000, '70cm' => 0x00020000,
                '23cm' => 0x00010000
            );

my %bm_conts = ( 'OC' => 0x0400, 'AF' => 0x0800, 'SA' => 0x1000,
				'AS' => 0x2000, 'NA' => 0x4000, 'EU' => 0x8000 );


my $uniques = 0;

my $first_hour = -1;

while (my $line = <>) {

	next if (substr($line, 0, 1) eq "(");# last line
	next if (substr($line, 0, 1) eq "c");# first line

	my (undef, undef, $s_cont, undef, $band, $call, undef, undef, undef,
		undef, $date, undef, undef) = split(/,/, $line);

    # find out the hour (counting from 2009-01-01)
	# which starts the current day
	if ($first_hour < 0) {
		if (substr($date, 11,2) ne "00") {	# should be 00:xx
			print "Unexpected date/time....\n";
			exit;
		}
		$first_hour = int((str2time($date, 'GMT') - $t0)/3600);
	}

	# sanitize call, GW0ETF skimmer produced calls like "DJ1YFK    C";
	$call =~ s/\s+.$//g;

	unless (defined($d{$call})) {
		for (0..23)  {
			$d{$call}[$_] = 0;
		}
#		print "Now ".(++$uniques)." unique calls\n";
	}

	# Find the hour from the date
	my $h = substr($date, 11, 2);

	$h += 0;

	# Get the bit mask for the current spot
	my $bm = 0x00000000 | $bm_bands{$band} | $bm_conts{$s_cont};

	# Set the bit mask at the proper position in the hash
	$d{$call}[$h] |= $bm;
}

my $updated = 0;
my $new = 0;
my $total = 0;
my $outstr = '';
my $data_blob = '';

my $dbh = DBI->connect("DBI:mysql:rbnactivity;host=localhost",'rbnactivity','rbnactivity') or die "Could not connect to MySQL database: " .  DBI->errstr;

my $uncomp = '';
my $exists;
my $dat;
my @a;
my $s;
my $beacon;
my $dxcc;
my $wl;

my $tpl = "\x00"x596064;		# completely empty template 

foreach my $k (sort keys %d) {

		$total++;

		# check if data was already there for the callsign
		my $q = $dbh->prepare("select callsign, beacon, wl, dxcc, data from rbn_activity where callsign='$k'");
		$q->execute();
		($exists, $beacon, $wl, $dxcc, $dat) = $q->fetchrow_array();

		if ($exists) {
			$updated++;

			$uncomp = Compress::Zlib::memGunzip($dat); 
			my $uncomp_len = length($uncomp);

			# remove old data
			$dbh->do("delete from rbn_activity where callsign='$k';");

		}
		else {
			$new++;
			print "New $k...\n";
			$uncomp = $tpl;
			$beacon = 0;
			$wl = 1; 
            $dxcc = '';
		}

		# now insert current day into $uncomp...
		my $today = pack("L24", @{$d{$k}});

		substr($uncomp, $first_hour*4, 24*4) = $today;

		# count activity in last year, i.e. non-null byte pairs
		# starting at 2*(($first_hour + 24) - 365*24)
		my $one_year_ago = 4*(($first_hour + 24) - 365*24);
		my $year_hours = 0;
		for (my $i = $one_year_ago; $i < 4*($first_hour+24); $i = $i + 4) {
			if (substr($uncomp, $i, 4) ne "\x00\x00\x00\x00") {
				$year_hours++;
			}
		}

	    $data_blob = Compress::Zlib::memGzip($uncomp);
		$s = $dbh->prepare("insert into rbn_activity (`callsign`, `hours`, `beacon`, `wl`, `dxcc`, `data`) VALUES (?, ?, ?, ?, ?, ?);");
		$s->bind_param(1, $k);
		$s->bind_param(2, $year_hours);
		$s->bind_param(3, $beacon);
		$s->bind_param(4, $wl);
		$s->bind_param(5, $dxcc);
		$s->bind_param(6, $data_blob);
		$s->execute();
}	

print "Done. Total: $total, New: $new, Updated: $updated\n";
print "Time: ".(time - $start_time)."\n";


