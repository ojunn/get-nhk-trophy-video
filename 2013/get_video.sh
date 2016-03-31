#!/bin/sh

ITEM_ID=$1
JSON_FILENAME=$2
MP4_FILENAME=$3

SAVE_FILENAME=json/$ITEM_ID.json

if [ ! -f $SAVE_FILENAME ] ; then
	curl -s "http://www1.nhk.or.jp/figure/common/data/"$JSON_FILENAME".jsonp" \
	| sed -e "s/^callback_mp4(//g" | sed -e "s/);\$//g" > $SAVE_FILENAME
fi

TYPE=`./get_cliptype.pl $SAVE_FILENAME`

TITLE=`./get_title.pl $SAVE_FILENAME`

echo $TITLE
DIR="dl/"$TITLE

if [ $TYPE = '1' ]; then
	MASTER="https://nhkmovsr-i.akamaihd.net/i/figure/r/"$MP4_FILENAME"_800.mp4/master.m3u8"
else
	MASTER="https://nhkmovsr-i.akamaihd.net/i/figure/r/"$MP4_FILENAME".mp4/master.m3u8"
fi

mkdir -p "$DIR"
cd "$DIR"

curl -s -o master $MASTER
if [ ! -f master ];then
	echo "failed to download $MASTER"
	exit 0
fi

sleep 1
curl -s -o index  `tail -1 master`
if [ ! -f index ]; then
	echo "failed to download index"
	exit 0
fi

LIST=`grep "http://" index`

for url in $LIST
do
	wget -nc -q $url &
done

if [ -f "index" -a ! -f "crypt.key" ]; then
        KEYURL=`grep "https://" index | sed -e "s/^.\+\(https.\+\)\"\$/\\1/"`
        wget -q -nc -O crypt.key $KEYURL
fi


