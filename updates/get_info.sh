#!/bin/bash
#dbQuery="               select '\"1\"' as 'col1' ,'\"requests\"' as 'col2','\"' ||count(*)|| '\"' as 'col3', '\"x\"' as 'col4'   from requests"
#dbQuery="$dbQuery UNION select '\"2\"','\"registered\"'        ,'\"' ||count(*)|| '\"'            ,'\"x\"'           from requests where status ='registered'"
#dbQuery="$dbQuery UNION select '\"3\"','\"accepted\"'          ,'\"' ||count(*)|| '\"'            ,'\"x\"'           from requests where status ='accepted'"
#dbQuery="$dbQuery UNION select '\"4\"','\"blocked\"'           ,'\"' ||count(*)|| '\"'            ,'\"x\"'           from requests where status ='blocked'"
#dbQuery="$dbQuery UNION select '\"5\"','\"unregistered\"'      ,'\"' ||count(*)|| '\"'            ,'\"x\"'           from unregistered"
#dbQuery="$dbQuery UNION select '\"6\"','\"versions\"'          ,'\"' ||count(*)|| '\"'            ,'\"x\"'           from versions"
#dbQuery="$dbQuery UNION select '\"7\"','\"downloads\"'         ,'\"' ||count(*)|| '\"'            ,'\"x\"'           from downloads"
#dbQuery="$dbQuery UNION select '\"8\"', '\"' ||appname|| '\"'  ,'\"' ||notes|| '\"' , '\"' ||limit_mode|| '\"' from versions order by 1,2,3"
#sqlite3 registrations.db ".mode columns" "$dbQuery"
#cd /mnt/Share0/updatesbackups
#ls -p -l --block-size=K *.zip *.tar 2>/dev/null | grep -v / | awk '{print "\"9\"", "\""$9"\"", "\""$5"\"", "\"x\""}' | sort -r 


echo "0|<b> --- Raspberry Pi:</b>| | "
echo "0|Model|" $(cat /proc/cpuinfo | grep Model | cut -d":" -f 2) "|"
echo "0|"$(grep -i memtotal /proc/meminfo | sed 's/:/|/')"|"

echo "0|<b> --- Database info:</b>| | "
echo "10|Database Size|" $(du -h registrations.db | cut -d"r" -f 1) "|"

#dbQuery="SELECT '1' AS col1, 'Registered' AS col2, COUNT(*) AS col3, 'Count' AS col4 FROM requests WHERE status ='registered'"
dbQuery="SELECT '1' AS col1, COUNT(*) || ' Requests:' AS col2, ' ' AS col3, ' ' AS col4 FROM requests"
dbQuery="$dbQuery UNION SELECT '2', 'Registered', COUNT(*) , 'Count' FROM requests WHERE status ='registered'"
dbQuery="$dbQuery UNION SELECT '3', 'Accepted', COUNT(*) , 'Count' FROM requests WHERE status ='accepted'"
dbQuery="$dbQuery UNION SELECT '4', 'Blocked', COUNT(*) , 'Count' FROM requests WHERE status ='blocked'"
#dbQuery="$dbQuery UNION SELECT '4', 'Requests', COUNT(*) , 'Total Requests' FROM requests"
dbQuery="$dbQuery UNION SELECT '5', '<i><u>Unregistered Requests</u></i>', COUNT(*) , 'Count' FROM unregistered"
#dbQuery="$dbQuery UNION SELECT '5',  '<i><u>+ ' || COUNT(*) || ' Unregistered Requests</u></i>' , ' ' , ' ' FROM unregistered"
dbQuery="$dbQuery UNION SELECT '6', 'Downloads', COUNT(*) , 'Count' FROM downloads"
#dbQuery="$dbQuery UNION SELECT '7', ' --- Versions', COUNT(*) , 'Details below' FROM versions"
dbQuery="$dbQuery UNION SELECT '7',  COUNT(*) || ' Versions:' , ' ' , ' ' FROM versions"
dbQuery="$dbQuery UNION SELECT '8', appname , notes , limit_mode FROM versions ORDER BY 1,2,3"
sqlite3 registrations.db -separator '|' -list "$dbQuery"

echo "9|<b> --- Last 5 backups:</b>| | "
cd /mnt/Share0/updatesbackups
ls -p -l --block-size=K *.zip *.tar 2>/dev/null | grep -v / | awk -v OFS='|' '{print "9", $9, $5, " "}' | sort -r | head -n 5

echo "10|<b> --- Software info:</b>| | "
echo "10|linux|" $(lsb_release -a 2>/dev/null| grep Description) "|"
echo "10|apache|" $(apache2 -version| grep version | cut -d" " -f 3-) "|"
echo "10|apache php|" $(a2query -m | grep php) "|"
echo "10|sqlite|" $(sqlite3 --version | cut -d" " -f 1) "|"
echo "10|python|" $(python --version | cut -d" " -f 2) "|"

echo "10|<b> --- Utilisation:</b>| | "
df -h | grep '^/' | awk -v OFS='|' '{print "10", $1" mounted on "$6, $3" of "$2" used ("$5")", "Available: "$4}'
echo "10| 1 minute load|" $(cat /proc/loadavg | cut -d" " -f 1) "|"
echo "10| 5 minute load|" $(cat /proc/loadavg | cut -d" " -f 2) "|"
echo "10|15 minute load|" $(cat /proc/loadavg | cut -d" " -f 3) "|"


