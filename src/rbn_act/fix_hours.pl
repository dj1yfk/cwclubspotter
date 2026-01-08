#!/usr/bin/perl

# add year activity hours to each* callsign   (* see query below, with threshold)
# for last 365 days.
# this is needed because otherwise stations who are not active any more will
# just sit on their last hour value and never sink down, despite inactive

use warnings;
use strict;
use Date::Parse;
use Digest::MD5 qw(md5 md5_hex);
use DBI;

use Compress::Zlib qw(memGzip memGunzip);

my $start_time = time();

# start time of the first year
my $t0 = str2time("2009-01-01 00:00:00", 'GMT');

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

my $tpl = "\x00"x876000;		# completely empty template

my $today = `date -d yesterday +%Y-%m-%d`;
chomp($today);
print "Today: $today\n";
my $first_hour = int((str2time($today, 'GMT') - $t0)/3600);

# fetch all calls
my %d = ();
my $q = $dbh->prepare("select callsign from rbn_activity where hours > 10;");
$q->execute();
my $call;
my $count = 0;
while ($call = $q->fetchrow_array()) {
	$d{$call} = 1;
    $count++;
}

print "Number of calls to update: $count\n";

foreach my $k (sort keys %d) {
		$total++;

		# fetch data
		my $q = $dbh->prepare("select callsign, data from rbn_activity where callsign='$k'");
		$q->execute();
		($exists, $dat) = $q->fetchrow_array();

		$updated++;

		$uncomp = Compress::Zlib::memGunzip($dat); 
		my $uncomp_len = length($uncomp);

		# count activity in last year, i.e. non-null byte pairs
		# starting at 4*(($first_hour + 24) - 365*24)
		my $one_year_ago = 4*(($first_hour + 24) - 365*24);
		my $year_hours = 0;
		for (my $i = $one_year_ago; $i < 4*($first_hour+24); $i = $i + 4) {
			if (length($uncomp) >= $i+4 and substr($uncomp, $i, 4) ne "\x00\x00\x00\x00") {
				$year_hours++;
			}
		}

		# only update activity hours
		$s = $dbh->prepare("update rbn_activity set hours = $year_hours where callsign='$k';");
		$s->execute();
		if ($total % 500 == 0) {
				print "$total - $k\n";
		}
}	

print "Done. Total: $total, New: $new, Updated: $updated\n";
print "Time: ".(time - $start_time)."\n";


