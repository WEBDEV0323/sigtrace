
#!/bin/bash
#Get Schema Information
/usr/bin/php /var/www/html/current/pvtrace/public/index.php getSchemaInfo > /var/www/html/current/pvtrace/ReportExport/db.conf

source /var/www/html/current/pvtrace/ReportExport/db.conf
echo "select id,reportQuery,userEmail,fileName from exportreport where status='Queued'" > /var/www/html/current/pvtrace/ReportExport/reportQuery.sql

mysql -h ${HOST} --user ${USER} -p${USER_PASSWORD} ${SCHEMA} < /var/www/html/current/pvtrace/ReportExport/reportQuery.sql | tr '\t' '~'> /var/www/html/current/pvtrace/ReportExport/reportQuery.csv

INPUT=/var/www/html/current/pvtrace/ReportExport/reportQuery.csv
while IFS='~' read -r id reportQuery userEmail fileName
do
    mysql -h${HOST} -u${USER} -p${USER_PASSWORD} ${SCHEMA} -e "UPDATE exportreport set status='Processing' where id=$id"

    mysql -h${HOST} -u${USER} -p${USER_PASSWORD} ${SCHEMA} -e "$reportQuery" | tr '\r' ' ' | sed 's/\t/","/g;s/^/"/;s/$/"/;s/\n//g'  > /var/www/html/current/pvtrace/ReportExport/$fileName.csv

    uuid=$(uuidgen)
    zip -j -P $uuid /var/www/html/current/pvtrace/ReportExport/$fileName.zip /var/www/html/current/pvtrace/ReportExport/$fileName.csv
    mv /var/www/html/current/pvtrace/ReportExport/*.zip /var/www/html/current/pvtrace/public/reports/

    /usr/bin/php /var/www/html/current/pvtrace/public/index.php report_move_to_s3 $fileName
    /usr/bin/php /var/www/html/current/pvtrace/public/index.php report_in_mail $id $uuid $userEmail $fileName
    

    mysql -h${HOST} -u${USER} -p${USER_PASSWORD} ${SCHEMA} -e "UPDATE exportreport set status='Sent' where id=$id"

done<"$INPUT"

rm /var/www/html/current/pvtrace/ReportExport/*.csv
find /var/www/html/current/pvtrace/public/reports/*.zip -mtime +30 -exec rm {} \;

