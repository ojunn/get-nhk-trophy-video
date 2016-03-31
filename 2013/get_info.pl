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

say $data->{'0'}->{'title'} ;
say $data->{'0'}->{'category'} ;
say $data->{'0'}->{'segment'} ;
say $data->{'0'}->{'player_name'} ;
say $data->{'0'}->{'cliptype'} ;
say $data->{'0'}->{'c_playerid'} ;
say $data->{'0'}->{'i_playerid'} ;
say $data->{'0'}->{'thumbnail_S'} ;
say $data->{'0'}->{'thumbnail_M'} ;
say $data->{'0'}->{'thumbnail_L'} ;
say $data->{'0'}->{'thumbnail_LL'} ;
say $data->{'0'}->{'thumbnail_SP'} ;

#print Dumper  $data->{'0'}->{'detail'}->[0] ;

foreach my $detail (@{$data->{'0'}->{'detail'}}){
	say $detail->{title};
	say $detail->{thumbnail};
}

