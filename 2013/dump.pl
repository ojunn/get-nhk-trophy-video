#!/usr/bin/perl
use strict;
use warnings;
use utf8;
use 5.10.0;

use Data::Dumper;
use JSON qw(decode_json);

binmode(STDOUT,":utf8");

my $data = decode_json(<>);
 
print Dumper $data;


