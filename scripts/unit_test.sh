parent_path=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )

cd "$parent_path"

#change permission-------#
sudo chmod -R 777 ../public/unittestxml

#-------Run unit test-------# 
#cd ../module/Application/UnitTest
#sudo phpunit --log-junit ../../../public/unittestxml/applicationtest.xml
#
#cd ../../Data/UnitTest
#sudo phpunit --log-junit ../../../public/unittestxml/datatest.xml
#
#cd ../../Report/UnitTest
#sudo phpunit --log-junit ../../../public/unittestxml/reporttest.xml
#
#cd ../module/Common/Tracker/UnitTest
#sudo phpunit --log-junit ../../../../public/unittestxml/trackertest.xml

cd ../module/Common/BulkAction/test
sudo phpunit --log-junit ../../../../public/unittestxml/bulkactiontest.xml

#cd ../../Calendar/test
#sudo phpunit --log-junit ../../../../public/unittestxml/calendartest.xml

cd ../../Notification/test
sudo phpunit --log-junit ../../../../public/unittestxml/Notificationtest.xml

cd ../../Report/test
sudo phpunit --log-junit ../../../../public/unittestxml/reporttest.xml

cd ../../Settings/test
sudo phpunit --log-junit ../../../../public/unittestxml/settingstest.xml

cd ../../Authentication/test
sudo phpunit --log-junit ../../../../public/unittestxml/AuthenticationControllerTest.xml

#cd ../../Import/test
#sudo phpunit --log-junit ../../../../public/unittestxml/importtest.xml

cd ../../Trigger/test
sudo phpunit --log-junit ../../../../public/unittestxml/triggertest.xml

#cd ../../User/test
#sudo phpunit --log-junit ../../../../public/unittestxml/usertest.xml

cd ../../Workflow/test
sudo phpunit --log-junit ../../../../public/unittestxml/workflowtest.xml

cd ../../../SigTRACE/Casedata/test
sudo phpunit --log-junit ../../../../public/unittestxml/importtest.xml

cd ../../Dashboard/test
sudo phpunit --log-junit ../../../../public/unittestxml/dashboardtest.xml

cd ../../FrequencyAnalysis/test
sudo phpunit --log-junit ../../../../public/unittestxml/frequencyanalysistest.xml

cd ../../Product/test
sudo phpunit --log-junit ../../../../public/unittestxml/producttest.xml

cd ../../Quantitative/test
sudo phpunit --log-junit ../../../../public/unittestxml/quantitativetest.xml


#--------End-------#

#--------change permission-------#
#sudo chmod -R 777 ../../../public/unittestxml/applicationtest.xml
#sudo chmod -R 777 ../../../public/unittestxml/datatest.xml
#sudo chmod -R 777 ../../../public/unittestxml/reporttest.xml
#sudo chmod -R 777 ../../../../public/unittestxml/trackertest.xml

#sudo chmod -R 777 ../../../../public/unittestxml/calendartest.xml
sudo chmod -R 777 ../../../../public/unittestxml/bulkactiontest.xml

sudo chmod -R 777 ../../../../public/unittestxml/Notificationtest.xml


sudo chmod -R 777 ../../../../public/unittestxml/reporttest.xml
sudo chmod -R 777 ../../../../public/unittestxml/settingstest.xml

sudo chmod -R 777 ../../../../public/unittestxml/AuthenticationControllerTest.xml
sudo chmod -R 777 ../../../../public/unittestxml/triggertest.xml
#sudo chmod -R 777 ../../../../public/unittestxml/usertest.xml
sudo chmod -R 777 ../../../../public/unittestxml/workflowtest.xml
sudo chmod -R 777 ../../../../public/unittestxml/importtest.xml
sudo chmod -R 777 ../../../../public/unittestxml/dashboardtest.xml
sudo chmod -R 777 ../../../../public/unittestxml/frequencyanalysistest.xml
sudo chmod -R 777 ../../../../public/unittestxml/producttest.xml
sudo chmod -R 777 ../../../../public/unittestxml/quantitativetest.xml

#--------End-------#

#--------include DLR NO-------#
/usr/bin/php ../../../../public/index.php readUnitTestCSV
#--------End-------#

#--------change directory-------#
cd ../../../../public/unittestxml
#--------End-------#

#--------create html file from xml file-------#
#xsltproc unit_testreport_to_html.xsl applicationtest.xml > applicationtest.html
#xsltproc unit_testreport_to_html.xsl reporttest.xml > reporttest.html
#xsltproc unit_testreport_to_html.xsl datatest.xml > datatest.html
#xsltproc unit_testreport_to_html.xsl trackertest.xml > trackertest.html

#xsltproc unit_testreport_to_html.xsl calendartest.xml > calendartest.html
xsltproc unit_testreport_to_html.xsl bulkactiontest.xml > bulkactiontest.html

xsltproc unit_testreport_to_html.xsl Notificationtest.xml > Notificationtest.html


xsltproc unit_testreport_to_html.xsl reporttest.xml > reporttest.html
xsltproc unit_testreport_to_html.xsl settingstest.xml > settingstest.html

xsltproc unit_testreport_to_html.xsl AuthenticationControllerTest.xml > AuthenticationControllerTest.html
xsltproc unit_testreport_to_html.xsl triggertest.xml > triggertest.html
#xsltproc unit_testreport_to_html.xsl usertest.xml > usertest.html
xsltproc unit_testreport_to_html.xsl workflowtest.xml > workflowtest.html
xsltproc unit_testreport_to_html.xsl importtest.xml > importtest.html
xsltproc unit_testreport_to_html.xsl dashboardtest.xml > dashboardtest.html
xsltproc unit_testreport_to_html.xsl frequencyanalysistest.xml > frequencyanalysistest.html
xsltproc unit_testreport_to_html.xsl producttest.xml > producttest.html
xsltproc unit_testreport_to_html.xsl quantitativetest.xml > quantitativetest.html


#-------Run unit test on console and fail build if any failures-------# 

#cd ../../module/Common/Tracker/UnitTest
#sudo phpunit
#if [ "$?" = "0" ]; then
#	echo "pass Tracker"
#else 
#	echo "Fail Tracker"
#	exit 1
#fi

cd ../../module/Common/BulkAction/test
sudo phpunit
if [ "$?" = "0" ]; then
	echo "pass BulkAction"
else 
	echo "Fail BulkAction"
	exit 1
fi

#cd ../../Calendar/test
#sudo phpunit
#if [ "$?" = "0" ]; then
#	echo "pass Calendar"
#else 
#	echo "Fail Calendar"
#	exit 1
#fi

cd ../../Notification/test
sudo phpunit
if [ "$?" = "0" ]; then
	echo "pass Notification"
else 
	echo "Fail Notification"
	exit 1
fi

cd ../../Report/test
sudo phpunit
if [ "$?" = "0" ]; then
	echo "pass Report"
else 
	echo "Fail Report"
	exit 1
fi

cd ../../Settings/test
sudo phpunit
if [ "$?" = "0" ]; then
	echo "pass Settings"
else 
	echo "Fail Settings"
	exit 1
fi

cd ../../Authentication/test
sudo phpunit
if [ "$?" = "0" ]; then
	echo "pass Authentication"
else 
	echo "Fail Authentication"
	exit 1
fi

cd ../../Trigger/test
sudo phpunit
if [ "$?" = "0" ]; then
	echo "pass Trigger"
else 
	echo "Fail Trigger"
	exit 1
fi

#cd ../../User/test
#sudo phpunit
#if [ "$?" = "0" ]; then
#	echo "pass User"
#else 
#	echo "Fail User"
#	exit 1
#fi

cd ../../Workflow/test
sudo phpunit
if [ "$?" = "0" ]; then
	echo "pass Workflow"
else 
	echo "Fail Workflow"
	exit 1
fi

cd ../../../SigTRACE/Casedata/test
sudo phpunit
if [ "$?" = "0" ]; then
	echo "pass Casedata importtest"
else 
	echo "Fail Casedata importtest"
	exit 1
fi

cd ../../Dashboard/test
sudo phpunit
if [ "$?" = "0" ]; then
	echo "pass Dashboard"
else 
	echo "Fail Dashboard"
	exit 1
fi

cd ../../FrequencyAnalysis/test
sudo phpunit
if [ "$?" = "0" ]; then
	echo "pass FrequencyAnalysis"
else 
	echo "Fail FrequencyAnalysis"
	exit 1
fi

cd ../../Product/test
sudo phpunit
if [ "$?" = "0" ]; then
	echo "pass Product" 
else 
	echo "Fail Product"
	exit 1
fi

cd ../../Quantitative/test
sudo phpunit
if [ "$?" = "0" ]; then
	echo "pass Quantitative"
else 
	echo "Fail Quantitative"
	exit 1
fi
#--------End-------#