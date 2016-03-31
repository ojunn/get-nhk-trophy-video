#!/bin/sh


#DATA=`cat  clipidtojs.json| ./json.sh `
DATA=`cat  clipidtojs.json| ./json.sh |grep -E "[0-9]\]\\s+"|cut -f 2`

for line in $DATA;
do
	ITEM=`echo $line | ./json.sh | grep -E "\[.+\]" | cut -f2 | sed -e "s/\"//g" `

	ITEM_ID=`echo $ITEM | cut -d" " -f1`	
	JSON_FILENAME=`echo $ITEM | cut -d" " -f2`
	MP4=`echo $ITEM | cut -d" " -f3`

	echo -n $ITEM_ID" "	
	./get_cliptype.pl json/$ITEM_ID.json 
	echo -n " "
	./get_title.pl json/$ITEM_ID.json 
	echo 
#	sleep 1	
done

#|head -1|./json.sh |grep -E "\[.+\]"|cut -f2

