#!/usr/bin/perl

# this file belongs to the CW Club RBN Spotter rbn.telegraphy.de
#
# fetch all user's geofilter polygons from Redis and re-calculate the skimmers
# that intersect with them.

use strict;
use warnings;
use Redis;
use JSON::PP;
use REST::Client;

my $r = Redis->new();

my %p = $r->hgetall("rbnpolygons");
my %s = $r->hgetall("rbnskimmers");

foreach my $c (keys %p) {
    next unless ($c);
	#    print "\n";
	#    print "Updating Skimmers for for: >$c<\n";
    my @oldlist = sort split(/\s+/, $s{$c});
    my $old = join(" ", @oldlist);
	#    print "Polygons: $p{$c}\n\n";
    # break up the JSON array of arrays without decoding :)
    my @a = split(/\],\[/, $p{$c});
    my @skimmerlist;
    foreach (@a) {
        my $poly = $_;
        $poly =~ s/^\[\[/[/g;
        $poly =~ s/\]\]$/]/g;
        if ($poly =~ /^\{/) { $poly = "[".$poly; }
        if ($poly =~ /\}$/) { $poly = $poly."]"; }

        my $client = REST::Client->new();
        $client->POST('https://rbn.telegraphy.de/api?action=skimmers_in_polygon', $poly);
        if ($client->responseCode() eq '200') {
            my $l = decode_json($client->responseContent());
            if ($l) {
                push(@skimmerlist, @{$l});
            }
            else {
                print "Warning: No Skimmers in polygon for $c\n");
            }
        }
   }
   @skimmerlist = sort @skimmerlist;
   my $new = join(" ", @skimmerlist);
   #   print "old: $old\n";
   #   print "new: $new\n";

   if ($old ne $new) {
       print "=> Updating list for $c in Redis\n";
       $r->hset("rbnskimmers", $c, $new);
   }


}



