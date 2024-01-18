<?php

namespace Common\FileHandling\Controller;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Http\Request as HttpRequest ;
use Zend\Console\Request as ConsoleRequest ;
use Aws\Ec2\Ec2Client;
class FileHandlingController extends AbstractActionController
{
    protected $_mailService;
    
    public function getMailService()
    {
        if (!$this->_mailService) {
            $sm = $this->getServiceLocator();
            $this->_mailService = $sm->get('Common\Notification\Controller\Email');
        }
        return $this->_mailService;
    }    
    
    public function connectToAws($access_key_id, $secret_access_key)
    {
        $config = $this->getServiceLocator()->get('Config');
        $credentials = new \Aws\Credentials\Credentials($access_key_id, $secret_access_key);
        // Instantiate the client.
        $s3 = S3Client::factory(
            array(
            'version' => 'latest',
            'region'  => $config['aws']['region'],
            'credentials' => $credentials,
            'http'    => [
                'verify' => false
            ]
            )
        );
        return $s3;
    }
    public function uploadFilesToAwsAction()
    {
        $response = $this->getResponse();
        $keyname = $this->params()->fromRoute('keyname', 0);
        $filepath = $this->params()->fromRoute('filepath', 0);
        $del = $this->params()->fromRoute('del', 0);
        $config = $this->getServiceLocator()->get('Config');
        $secret_access_key=$config['aws']['credentials']['secret_access_key'];
        $access_key_id=$config['aws']['credentials']['access_key_id'];
        try {
            $result = $this->connectToAws($access_key_id, $secret_access_key)->putObject(
                array(
                'Bucket' => $config['aws']['s3']['bucket_name'],
                'Key'    => $keyname,
                'SourceFile'   => $filepath
                )
            );
        } catch (S3Exception $e) {
            echo "Unable to move files to S3"."\n";
            echo $e->getMessage() . "\n";
        }
        if ($del!=0) {
            if (file_exists($filepath)) {
                  unlink($filepath);
            }
        }
        $response->setContent($result);
        return $response;
    }
    public function downloadFilesFromAwsAction()
    {
        $config = $this->getServiceLocator()->get('Config');
        $access_key_id=$config['aws']['credentials']['access_key_id'];
        $secret_access_key=$config['aws']['credentials']['secret_access_key'];
        $bucket = $config['aws']['s3']['bucket_name'];

        $keyname = $this->params()->fromRoute('keyname', 0);
        $filename = $this->params()->fromRoute('filename', 0);
        try {
            $result = $this->connectToAws($access_key_id, $secret_access_key)->getObject(
                array(
                'Bucket' => $bucket,
                'Key'    => base64_decode($keyname)
                )
            );
            header('Content-Description: File Transfer');
            header("Content-Disposition: attachment; filename=\"".base64_decode($filename)."\"");
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header("Content-Type: {$result['ContentType']}");
            echo $result['Body'];
        } catch (S3Exception $e) {
            echo $e->getMessage() . "\n";
        }
        exit;
    }
    
    public function moveToS3Action() 
    {
        $config = $this->getServiceLocator()->get('Config');
        $request = $this->getRequest();
        if ($request instanceof ConsoleRequest) {
            $source = '';
            $fileType = $request->getParam('filetype');
            $todayDate=date("Y-m-d");
            $timeStamp=date('dmYHis');
            $yesterdayDate=date('Y-m-d', strtotime("-1 days"));
            $directory = '';
            $hostName=gethostname();
            $path=$config['appurl']['path'];
            switch ($fileType) {
            case "asrtrace_audit_log":
            case "sigtrace-audit_log":
                $source = $path.'apachelog/src/main/AuditLogs/';
                $filename=$fileType.'-'.$todayDate.'.log';
                $filenameYesterday=$fileType.'-'.$yesterdayDate.'.log';
                $newFilename='';
                if (file_exists($source.$filename)) {
                    $newFilename=$fileType.'-'.$timeStamp.'.log';
                    rename($source.$filename, $source.$newFilename);
                } else if (file_exists($source.$filenameYesterday)) {
                    $newFilename=$fileType.'-'.$timeStamp.'.log';
                    rename($source.$filenameYesterday, $source.$newFilename);
                } else {
                    $newFilename=$filename;
                }
                $filenameS3=$fileType.'-'.$hostName.'_'.$timeStamp.'.log';
                $directory = 'AuditLogs';
                $keyname =  $directory."/".$filenameS3;
                $filepath = $source.$newFilename;
                $this->moveAction($keyname, $filepath);
                break;
            case "error_log":
                $source = $path.'data/logs/';
                $filename=$fileType.'_'.$todayDate.'.log';
                $newFilename=$fileType.'-'.$timeStamp.'.log';
                rename($source.$filename, $source.$newFilename);
                $filenameS3=$fileType.'_'.$hostName.'_'.$timeStamp.'.log';
                $directory = 'ErrorLog';
                break;
            case "error_db":
                $source =  $path.'data/Db/';
                $filename=$fileType.'_'.$todayDate.'.log';
                $newFilename=$fileType.'-'.$timeStamp.'.log';
                rename($source.$filename, $source.$newFilename);
                $filenameS3=$fileType.'_'.$hostName.'_'.$timeStamp.'.log';
                $directory = 'ErrorDb';
                break;
            case "apache2_error":
                $source = '/var/log/apache2/';
                $filename='error.log';
                $newFilename='error'.'-'.$timeStamp.'.log';
                rename($source.$filename, $source.$newFilename);
                $filenameS3='error'.'_'.$hostName.'_'.$timeStamp.'.log';
                $directory = 'Apache2';
                break;
            case "apache2_access":
                $source = '/var/log/apache2/';
                $filename='access.log';
                $newFilename='access'.'-'.$timeStamp.'.log';
                rename($source.$filename, $source.$newFilename);
                $filenameS3='access'.'_'.$hostName.'_'.$timeStamp.'.log';
                $directory = 'Apache2';
                break;
            case "apache2_other_vhosts_access":
                $source = '/var/log/apache2/';
                $filename='other_vhosts_access.log';
                $newFilename='other_vhosts_access'.'-'.$timeStamp.'.log';
                rename($source.$filename, $source.$newFilename);
                $filenameS3='other_vhosts_access'.'_'.$hostName.'_'.$timeStamp.'.log';
                $directory = 'Apache2';
                break;
            case "backup_while_delete_workflow":
                $source = $path.'public/backup/deletedWorkflowBackup/';
                $directory = 'deletedWorkflowBackup';
                $files = scandir($source);
                foreach ($files as $key => $value) {
                    if (!in_array($value, array(".",".."))) {
                        if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                        } else {
                            $file = pathinfo($dir . DIRECTORY_SEPARATOR . $value);
                            if ($file['extension'] == 'sql') {
                                $filenameS3 = $file['filename'].'_'.$hostName.".".$file['extension'];
                                $keyname =  $directory."/".$filenameS3;
                                $filepath = $source.$value;
                                $this->moveAction($keyname, $filepath);
                            }
                        }
                    }
                }
                break;
            case "backup_while_delete_field":
                $source = $path.'public/backup/deletedFieldBackup/';
                $directory = 'deletedFieldBackup';
                $files = scandir($source);
                foreach ($files as $key => $value) {
                    if (!in_array($value, array(".",".."))) {
                        if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                        } else {
                            $file = pathinfo($dir . DIRECTORY_SEPARATOR . $value);
                            if ($file['extension'] == 'sql') {
                                $filenameS3 = $file['filename'].'_'.$hostName.".".$file['extension'];
                                $keyname =  $directory."/".$filenameS3;
                                $filepath = $source.$value;
                                $this->moveAction($keyname, $filepath);
                            }
                        }
                    }
                }
                break;
            default:
                echo "No file!";
            }
        }
    }
    public function moveAction($keyname, $filepath) 
    {
        $config = $this->getServiceLocator()->get('Config');
        $supportEmail = $config['group']['group_id'];
        
        if (file_exists($filepath)) {
            $result = $this->forward()->dispatch(
                'Common\FileHandling\Controller\FileHandling',
                array(
                'action' => 'uploadFilesToAws',
                'keyname' => $keyname,
                'filepath' => $filepath,
                'del' => '1',
                )
            );
            
            if ($result) {
                $subject = $config['aws']['s3']['bucket_name'] . ' - File moved to S3';
                $htmlPart = "<html>"
                         . "<body>"
                         . "Dear User, </br>
                            <p>
                                ". $filepath . " has been successfully moved to " . $keyname . " </br>

                                <h5>Confidentiality Statement:</h5>
                                    This is a system generated correspondence. Please do not reply to this email </br>
                            </p>

                            <div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                                <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>Bioclinica</a>. All rights reserved.<br>
                            </div>
                        </body>
                     </html>";
                $res = $this->getMailService()->sendSesEmail($subject, $htmlPart, array($supportEmail), array());
            }            
        } else {
            date_default_timezone_set('Asia/Kolkata');
            $subject = $config['aws']['s3']['bucket_name'] . ' - File not moved to S3';
            $htmlPart = "<html>"
                     . "<body>"
                     . "Dear User, </br>
                        <p>
                            ". $filepath . " file has not been found to move at following date and time " . date("Y-m-d h:i:s A e") . " </br>

                            <h5>Confidentiality Statement:</h5>
                                This is a system generated correspondence. Please do not reply to this email </br>
                        </p>

                        <div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                            <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>Bioclinica</a>. All rights reserved.<br>
                        </div>
                    </body>
                 </html>";
            $res = $this->getMailService()->sendSesEmail($subject, $htmlPart, array($supportEmail), array());                            
        }
    }
    public function reportmoveToS3Action()
    {
        $request = $this->getRequest();
        if ($request instanceof ConsoleRequest) {
            $source = '';
            $filename = $request->getParam('filename');
            $filename = $filename.".zip";
            $directory = '';
            $source =  $path.'public/reports/';
            $directory = 'ReportMail';
            $keyname =  $directory."/".$filename;
            $filepath = $source.$filename;
            $result=$this->forward()->dispatch(
                'Common\FileHandling\Controller\FileHandling',
                array(
                'action' => 'uploadFilesToAws',
                'keyname' => $keyname,
                'filepath' => $filepath,
                'del' => '1',
                )
            );
        }
    }
}

