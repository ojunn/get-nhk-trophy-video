#!/usr/bin/perl
use strict;
use warnings;
use utf8;
use 5.10.0;

use Data::Dumper;
use JSON qw(decode_json);

binmode(STDOUT,":utf8");

my $data = decode_json(<>);
 
#print Dumper $data;

my $filename = $data->{'0'}->{'thumbnail_LL'};

if(length $filename > 0){
	say "http://www1.nhk.or.jp/figure/r/image/".$filename ;
}

foreach my $detail (@{$data->{'0'}->{'detail'}}){
	say "http://www1.nhk.or.jp".$detail->{thumbnail};
}

