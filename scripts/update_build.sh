#!/bin/bash
parent_path=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )
cd "$parent_path" 
sudo rm ../config/autoload/global.php
sudo rm ../conf/logstash/siglog.conf
sudo cp ../config/autoload/config_update.php ../config/autoload/global.php
sudo cp ../conf/logstash/siglog_dev.conf ../conf/logstash/siglog.conf
#
# Replace file in global_dev.php
#
sed -i -e "s/{{RDSENDPOINT}}/$RDSENDPOINTBUILD/g" ../config/autoload/global.php
sed -i -e "s/{{RDSDBNAME}}/$RDSDBNAMEBUILD/g" ../config/autoload/global.php
sed -i -e "s/{{RDSUSERNAME}}/$RDSUSERNAMEBUILD/g" ../config/autoload/global.php
sed -i -e "s/{{RDSSECRET}}/$RDSSECRETBUILD/g" ../config/autoload/global.php
sed -i -e "s/{{REGION}}/$REGION/g" ../config/autoload/global.php
sed -i -e "s/{{KIBANAURL}}/$KIBANAURL/g" ../config/autoload/global.php
sed -i -e "s/{{BUCKET_NAME}}/$BUCKET_NAME/g" ../config/autoload/global.php
sed -i -e "s/{{ES_HOST}}/$ES_HOST/g" ../config/autoload/global.php
sed -i -e "s/{{APPURL}}/$APPURL/g" ../config/autoload/global.php
sed -i -e "s/{{HTTP_PROTOCOL}}/$HTTP_PROTOCOL/g" ../config/autoload/global.php
sed -i -e "s/{{DEFAULT_DB}}/$DEFAULT_DB/g" ../config/autoload/global.php
sed -i -e "s/{{APP_PATH}}/$APP_PATH/g" ../config/autoload/global.php
sed -i -e "s/{{AWS_ACCESS_KEY_ID}}/$AWS_ACCESS_KEY_ID/g" ../config/autoload/global.php
sed -i -e "s/{{ACCESS_KEY}}/$ACCESS_KEY/g" ../config/autoload/global.php
sed -i -e "s/{{GROUP_ID}}/$GROUP_ID/g" ../config/autoload/global.php
sed -i -e "s/{{LOGO}}/$LOGO/g" ../config/autoload/global.php
#
# Edit File for sigLog.conf
#
sed -i -e "s/{{ES_HOST}}/$ES_HOST/g" ../conf/logstash/siglog.conf
sed -i -e "s/{{AWS_ACCESS_KEY_ID}}/$AWS_ACCESS_KEY_ID/g" ../conf/logstash/siglog.conf
sed -i -e "s/{{ACCESS_KEY}}/$ACCESS_KEY/g" ../conf/logstash/siglog.conf
sed -i -e "s/{{REGION}}/$REGION/g" ../conf/logstash/siglog.conf
#
#Change Build Number in UnitTest XSL file
#
sed -i -e "s/{{BUILDNUMBER}}/$BUILD_BUILDNUMBER/g" ../public/unittestxml/unit_testreport_to_html.xsl
