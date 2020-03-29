#!/usr/bin/perl

use strict;
use warnings;

my $a = "";
while (my $line = <>) {
    $a .= $line;
}


my @a = split(/<\/tr>/, $a); 

foreach my $l (@a) {

    $l =~ s/Ø/0/g;
    $l =~ s/<(\/)?t[rh][^>]*>//g;
    $l =~ s/<(\/)?div[^>]*>//g;
    $l =~ s/<\/td[^>]*>//g;
    $l =~ s/;//g;
    $l =~ s/<td[^>]*>/;/g;
    my @r = split(/;/, $l);
    if ($r[3]) {
        my $call = uc($r[3]);
        $call =~ s/^ //g;

        if ($call =~ / \/ /) {  # multiple calls e.g. DL1ABC / DL2XYZ
            my @t = split(/ \/ /, $call);
            foreach (@t) {
                print $_."\n" if ($call =~ /[A-Z]+[0-9]+/);
            }
        }
        else {
            print $call."\n" if ($call =~ /[A-Z]+[0-9]+/); 
        }
    }

}
    
