#!/bin/bash

#run codesniffer code review report
#
#

parent_path=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )

cd "$parent_path"

chmod -R 777 ../public/codereview 

phpcs -n -v --report-summary=../public/codereview/codereview.txt --exclude=PEAR.NamingConventions.ValidVariableName,Generic.Commenting.DocComment,PEAR.Commenting.FileComment,PEAR.Commenting.FunctionComment,PEAR.Commenting.ClassComment ../module/

phpcs -n  --exclude=PEAR.NamingConventions.ValidVariableName,Generic.Commenting.DocComment,PEAR.Commenting.FileComment,PEAR.Commenting.FunctionComment,PEAR.Commenting.ClassComment ../module/