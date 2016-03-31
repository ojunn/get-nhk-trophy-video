#!/bin/sh

ITEM_ID=$1
JSON_FILENAME=$2
MP4_FILENAME=$3

SAVE_FILENAME=json/$ITEM_ID.json

if [ ! -f $SAVE_FILENAME ] ; then
	curl -s "http://www1.nhk.or.jp/figure/common/data/"$JSON_FILENAME".jsonp" \
	| sed -e "s/^callback_mp4(//g" | sed -e "s/);\$//g" > $SAVE_FILENAME
fi


