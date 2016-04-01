#!/bin/sh


#DATA=`cat  clipidtojs.json| ./json.sh `
DATA=`cat  clipidtojs.json| ./json.sh |grep -E "[0-9]\]\\s+"|cut -f 2`

echo clip_id json_filename mp4 > clipids

for line in $DATA;
do
	ITEM=`echo $line | ./json.sh | grep -E "\[.+\]" | cut -f2 | sed -e "s/\"//g" `
	echo $ITEM >> clipids

	ITEM_ID=`echo $ITEM | cut -d" " -f1`	
	JSON_FILENAME=`echo $ITEM | cut -d" " -f2`
	MP4=`echo $ITEM | cut -d" " -f3`
	
	./get_json.sh $ITEM_ID $JSON_FILENAME $MP4 &
	
done

#|head -1|./json.sh |grep -E "\[.+\]"|cut -f2

