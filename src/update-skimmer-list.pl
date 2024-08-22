#!/usr/bin/perl

my $list = `links2 -dump -width 255 "https://www.reversebeacon.net/cont_includes/status.php?t=skt"`;
# my $list = "  DJ5CW  asd  JN58SE ";

my @a = split(/\n/, $list);



system("cp /tmp/skimmer.sql /tmp/skimmer.sql.old");
open OUT, ">/tmp/skimmer.sql";
print OUT "delete from skimmers;\n";
foreach my $line (sort @a) {
    $line =~ s/-//g;
    $line =~ s/\///g;
    if ($line =~ /\s+(\w+)\s+(.*\s+)?([A-X]{2}[0-9]{2}[A-X]{2})/) {
        my $call = $1;
        my @ll = loc_to_latlon($3);
        #        print OUT "-- original line: $line\n";
        print OUT "insert into skimmers (`callsign`, `lat`, `lng`) values ('$call', $ll[0], $ll[1]);\n";
    }
}
close OUT;


system("diff -u /tmp/skimmer.sql.old /tmp/skimmer.sql");
system("mysql -uspotfilter -pspotfilter spotfilter < /tmp/skimmer.sql");

sub loc_to_latlon {
    my @l = qw/0 0/;
	my $loc = shift;
    $l[0] =
    (ord(substr($loc, 1, 1))-65) * 10 - 90 +
    (ord(substr($loc, 3, 1))-48) +
    (ord(substr($loc, 5, 1))-65) / 24 + 1/48;
    $l[1] =
    (ord(substr($loc, 0, 1))-65) * 20 - 180 +
    (ord(substr($loc, 2, 1))-48) * 2 +
    (ord(substr($loc, 4, 1))-65) / 12 + 1/24;
    return @l;
}




