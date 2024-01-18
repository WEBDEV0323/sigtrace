<?php

namespace Notification\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Model\AadminMapper;
use Notification\Model\Email;
use Notification\Model\Swiftmailer;
use Zend\Session\Container;
use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mime\Mime;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;
use Notification\Form\NotificationForm;
use Zend\Http\Request as HttpRequest ;
use Zend\Console\Request as ConsoleRequest ;
use Session\Container\SessionContainer;
use Application\Controller\IndexController;

class EmailController extends AbstractActionController
{
    protected $_adminMapper;
    protected $_emailMapper;
    protected $_swiftMapper;
    protected $_emailMigrationMapper;
    protected $_roleMapper;
    
    public function getRoleService()
    {
        if (!$this->_roleMapper) {
            $sm = $this->getServiceLocator();
            $this->_roleMapper = $sm->get('Role\Model\Role');
        }
        return $this->_roleMapper;
    }

    public function addReminderAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $msg = '';
            $trackerId = $this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $templateId = $this->getEvent()->getRouteMatch()->getParam('template_id', 0);
            $formId = 0;
            $resultset = array();
            $optionRecords = $this->getTrackerService()->trackerRsults($trackerId);
            $options = array();
            if (isset($optionRecords['forms'])) {
                foreach ($optionRecords['forms'] as $option) {
                    $options[] = array('value' => $option['form_id'], 'label' => $option['form_name']);
                }
            }
            if ($templateId > 0) {
                $resultset = $this->getmailService()->getReminderInfo($templateId);              
                $formId = $resultset[0]['notification_template_form_id'];
                $msg = $resultset[0]['notification_template_msg'];
            }
            $fields = $this->getmailService()->getfieldname($formId);           
            $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
            return new ViewModel( 
                array(                      
                        'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                        'fields' => $fields,
                        'result' => $resultset,
                        'formnames'=>$options,
                        'tracker_id' => $trackerId, 'template_id' => $templateId, 'msg' => $msg
                    )
            );
        }
    }

    public function indexAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = $this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $userDetails = $userContainer->user_details;
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = $trackerContainer->tracker_user_groups;
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }
            $this->layout()->setVariables(array('tracker_id' => @$trackerId));
            return new ViewModel(
                array(
                'alltemplate' => $this->getmailService()->getAlltemplate($trackerId),
                'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                'tracker_id' => $trackerId
                )
            );
        }
    }
    public function getmailService()
    {
        if (!$this->_emailMapper) {
            $sm = $this->getServiceLocator();
            $this->_emailMapper = $sm->get('Notification\Model\Email');
        }
        return $this->_emailMapper;
    }
    public function getTrackerService()
    {
        if (!$this->_adminMapper) {
            $sm = $this->getServiceLocator();
            $this->_adminMapper = $sm->get('Tracker\Model\TrackerModule');
        }
        return $this->_adminMapper;
    }
    public function getEmailMigrationService()
    {
        if (!$this->_emailMigrationMapper) {
            $sm = $this->getServiceLocator();
            $this->_emailMigrationMapper = $sm->get('Notification\Model\EmailMigration');
        }
        return $this->_emailMigrationMapper;
    }

    public function addtemplateAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $msg = '';
            $trackerId = $this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $templateId = $this->getEvent()->getRouteMatch()->getParam('template_id', 0);
            $form = new NotificationForm();
            $form->setName('NotificationForm');
            $form->setAttribute('class', 'form-horizontal');
            $formId = 0;
            $resultset = array();
            $optionRecords = $this->getTrackerService()->trackerRsults($trackerId);
            $options = array();
            if (isset($optionRecords['forms'])) {
                foreach ($optionRecords['forms'] as $option) {
                    $options[] = array('value' => $option['form_id'], 'label' => $option['form_name']);
                }
            }
            $form->get('n_form')->setAttribute('options', $options);
            if ($templateId > 0) {
                $resultset = $this->getmailService()->getTemplateInfo($templateId);               
                $formId = $resultset[0]['notification_template_form_id'];
                $form->get('n_name')->setAttribute('value', $resultset[0]['notification_template_name']);
                $form->get('n_form')->setAttribute('value', $resultset[0]['notification_template_form_id']);
                $form->get('n_subject')->setAttribute('value', $resultset[0]['notification_template_subject']);
                //$form->get('n_status')->setAttribute('value', $resultset[0]['notification_template_status']);
                $form->get('n_cc')->setAttribute('value', $resultset[0]['notification_template_cc']);
                $form->get('n_condition')->setAttribute('value', $resultset[0]['notification_template_condition_type']);
                $msg = $resultset[0]['notification_template_msg'];
            }
            $fields = $this->getmailService()->getfieldname($formId);
            $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
            return new ViewModel( 
                array(
                        'form' => $form,
                        'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                        'fields' => $fields,
                        'result' => $resultset,
                        'tracker_id' => $trackerId, 'template_id' => $templateId, 'msg' => $msg
                    )
            );
        }
    }
    
    /*
     * function to show particular template list and initiate function to save info
     */
    public function savetemplateAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;
        $container = $session->setSession('message');
        $applicationController = new IndexController();
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $post = $this->getRequest()->getPost()->toArray(); 
            $trackerId = $this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
            $comment= isset($post['comment'])?$post['comment'] : "";
            unset($post['comment']);
            $post['status']='Active';   
            if ($post['template_id'] > 0) {
                $resultset = $this->getmailService()->getTemplateAllInfo((int)$post['template_id']);
                $resultset['msg'] = html_entity_decode($resultset['msg']);
            }
            $optionRecords = $this->getmailService()->saveTemplate($post, $userContainer->u_id);
            if ($optionRecords=='duplicate') {
                echo 'duplicate';
            } elseif ($post['template_id'] > 0) {
                $container->message = 'updated';
                echo 'updated';
            } else {
                $container->message = 'inserted';
                echo 'inserted';
            }
            
            if ($optionRecords!='duplicate') {
                if ($post['template_id'] > 0) {
                    $aNewData = array();
                    $aNewData['template_name'] = $post['template_name']; 
                    $aNewData['subject'] = $post['subject']; 
                    $aNewData['msg'] = $post['msg'];
                    $aNewData['field_id'] = $post['field_id'];
                    $aNewData['form_id'] = $post['form_id'];
                    $aNewData['status'] = $post['status'];
                    $aNewData['n_cond'] = $post['n_cond'];
                    $aNewData['ccmail'] = $post['ccmail'];
                    $aNewData['condition_on_field'] = $post['condition_on_field'];
                    $aNewData['condition_operand'] = $post['condition_operand'];
                    $aNewData['value'] = $post['value'];
                    $applicationController->saveToLogFile($post['template_id'], $userDetails['email'], 'Edit Notification', json_encode($resultset), json_encode($aNewData), $comment, 'Success', $trackerData['client_id']);
                    $this->flashMessenger()->addMessage(array('success' => 'Notification Template '.$container->message.' successfully!'));
                } else {
                    $applicationController->saveToLogFile(0, $userDetails['email'], 'Add Notification', "", json_encode($post), $comment, 'Success', $trackerData['client_id']);                  
                    $this->flashMessenger()->addMessage(array('success' => 'Notification Template '.$container->message.' successfully!'));
                }
            }
            unset($post['status']);
            return $response;            
        }
    }
    /*
     * function to save template
     */
   
    public function getWorkflownameAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $post = $this->getRequest()->getPost()->toArray();
        $formId=(int)$post['form_id'];
        $optionRecords = $this->getmailService()->getworkflowdname($formId);
        $response->setContent(\Zend\Json\Json::encode($optionRecords));
        return $response;
    }
    public function sendemailAction()
    {
        $response = $this->getResponse();
        $templateId = $this->getEvent()->getRouteMatch()->getParam('param1');
        $formName = $this->getEvent()->getRouteMatch()->getParam('param2');
        $id = $this->getEvent()->getRouteMatch()->getParam('param3');
       
        $cc = array();
        $optionRecords = $this->getmailService()->gettemplateDetail($templateId);
       
        $optionRecords[0]['notification_template_to'];
        $sendtoids = explode(',', $optionRecords[0]['notification_template_to']);
        if (isset($optionRecords[0]['notification_template_cc']) && !empty($optionRecords[0]['notification_template_cc'])) {
            $cc = $optionRecords[0]['notification_template_cc'];
        }
        $setting = $this->getmailService()->getsettings();
        $email1=array();
        foreach ($sendtoids as $sendto) {
            $fieldName = $this->getmailService()->getfield($sendto);
            
            $getmaiilsId = $this->getmailService()->getMailids($id, $fieldName, $formName);
           
            $email = isset($getmaiilsId[0]['mail_id']) ? $getmaiilsId[0]['mail_id'] : "N/A";
            $email1[]=$email;
            $formname = explode('_', $formName);
            $formId = $formname[2];
            $trackerId = $formname[1];
            if (isset($email) && !empty($email)) {
                $matches = array();
                $templateinfo = $this->getmailService()->getTemplatetoSendmail($templateId);
                $msg = $templateinfo[0]['notification_template_msg'];
                preg_match_all('~\{{(.+?)\}}~', $msg, $matches);
                preg_match_all('~\[\[(.+?)\]\]~', $msg, $matchesWorkflow);
                array_shift($matches);
                array_shift($matchesWorkflow);
                if (isset($matches[0]) && !empty($matches[0])) {
                    $fieldsvalue = $this->getmailService()->getfieldsvalue($id, $matches, $formName);
                    
                    foreach ($fieldsvalue as $key => $value) {
                        $msg = str_replace('{{' . $key . '}}', $value, $msg);
                    }
                }
                if (isset($matchesWorkflow[0]) && !empty($matchesWorkflow[0])) {
                    $fieldsvalue = $this->getmailService()->getworkflowvalue($id, $matchesWorkflow, $formId);
                    foreach ($matchesWorkflow[0] as $key => $value) {
                        $workflowmsg = '<div class="panel panel-default"><div class="panel-body"><h4><span class="glyphicon glyphicon-list" aria-hidden="true"></span>' . $value . '</h4>';
                        $fieldsArray = $this->getmailService()->recordsCanView($trackerId, $formId);
                        $fieldsArray = isset($fieldsArray['fields'][$value])?$fieldsArray['fields'][$value]:array();
                        $formRecords = $this->getTrackerService()->formRecords($trackerId, $formId, '', '', '', $id);
                        $formRecordsArray = $formRecords['form_data'];
                        foreach ($fieldsArray as $field => $fValues) {
                            $label = $fValues['label'];
                            $fieldName = $fValues['field_name'];
                            $fieldType = $fValues['field_type'];
                            $optionsId = $fValues['code_list_id'];
                            $recordValue = @$formRecordsArray[0][$fieldName];

                            if ($fieldType == 'Heading') {
                                $workflowmsg = $workflowmsg . '<br><div class="col-md-12"><h4>' . $label . '</h4></div>';
                            } else {
                                if ($fieldType == "Check Box") {
                                    if (strlen(@$recordValue) > 0) {
                                        $workflowmsg = $workflowmsg . '<div class="col-md-6"><label class="col-md-4 control-label" style="padding-left: 0px; margin-top:2px">' . $label . ' : </label>' . $recordValue . '';
                                    }
                                } else {
                                    $workflowmsg = $workflowmsg . '<div class="col-md-6"><label class="col-md-4 control-label" style="padding-left: 0px; margin-top:2px">' . $label . ' : </label>' . $recordValue . '';
                                }
                                if ($fieldType == "Check Box") {
                                    if (strlen(@$recordValue) > 0) {
                                        $workflowmsg .= '<div><label>Comment : </label>' . @$formRecordsArray[0]["comment_checkbox_$fieldName"] . '</div>';
                                    }
                                }
                                $workflowmsg = $workflowmsg . '</div></div>';
                            }
                        }
                        $workflowmsg = $workflowmsg . '</div></div>';
                        $msg = str_replace('[[' . $value . ']]', $workflowmsg, $msg);
                    }
                }
                $matches = array();
                $subject = $templateinfo[0]['notification_template_subject'];
                preg_match_all('~\{{(.+?)\}}~', $subject, $matches);
                array_shift($matches);
                if (isset($matches[0]) && !empty($matches[0])) {
                    $fieldsvalue = $this->getmailService()->getfieldsvalue($id, $matches, $formName);
                    foreach ($fieldsvalue as $key => $value) {
                        $subject = str_replace('{{' . $key . '}}', $value, $subject);
                    }
                }
                $htmlPart = "<html><body>" . $msg . "<div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                    <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>Bioclinica</a>. All rights reserved.<br>
                </div></body></html>";
            }
        }
        $email3=implode(",", $email1);
        $email2= trim($email3, ",");
        if ($email2!='') {
            $this->getSwiftmailService()->saveMail($formId, $id, $setting['smtp_user'], $email2, $cc, $subject, $msg);
            return true;
        }
    }

    public function getSwiftmailService()
    {
        if (!$this->_swiftMapper) {
            $sm = $this->getServiceLocator();
            $this->_swiftMapper = $sm->get('Notification\Model\Swiftmailer');
        }
        return $this->_swiftMapper;
    }

    public function getformfieldsAction()
    {
        
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $post = $this->getRequest()->getPost()->toArray();
            $trackerId=(int)$post['tracker_id'];
            $formId=(int)$post['form_id'];
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = $trackerContainer->tracker_user_groups;            
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }
            $this->layout()->setVariables(array('tracker_id' => @$trackerId));
            $response = $this->getResponse();
            $notifyWhom = $this->getmailService()->getfieldname($formId); 
            $dateFields = $this->getmailService()->getDateFields($formId);
            $resArr = array(); 
            $fieldsArray = $this->getTrackerService()->trackerCheckFieldsForFormula($trackerId, $formId);
            $resArr['fieldsArray']=$fieldsArray;
            $resArr['notifyWhom']=$notifyWhom;
            $resArr['dateFields']=$dateFields;
            $response->setContent(\Zend\Json\Json::encode($resArr));
            return $response;
        }
    }

    public function populatefieldsAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $post = $this->getRequest()->getPost()->toArray();
            $template_id=(int)$post['template_id'];
            $resultset = $this->getmailService()->gettemplateFields($template_id);
            $response->setContent(\Zend\Json\Json::encode($resultset));
            return $response;
        }

    }
    /*
    *Function to delete template :to make template archive
    */
    public function deletetemplateAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $container = $session->setSession('message');                
        $userDetails = $userContainer->user_details;
        $applicationController = new IndexController();
        $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);        
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
        $templateId = $this->getEvent()->getRouteMatch()->getParam('template_id', 0);
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $post = $this->getRequest()->getPost()->toArray();
           
            $comment = $post['comment'];
            if (!empty($post)) {                
                    $container->message = 'deleted';
                    $resultset = $this->getmailService()->deletetemplate($post['template_id']);
                    $applicationController->saveToLogFile($templateId, $userDetails['email'], 'Delete Notification Template', "{'id':'" . $post['template_id'] . "', 'notification_template_status':Active }", "{'id':'" . $post['template_id'] . "', 'notification_template_status':Deactive }", $comment, 'Success', $trackerData['client_id']);
                    $messages = 'deleted';
                    $this->flashMessenger()->addMessage(array('success' => 'Notification Template Deleted successfully!'));
                if (!empty($messages)) {
                    $response->setContent(\Zend\Json\Json::encode($messages));
                }
                    return $response;                
            } else {
                $applicationController->saveToLogFile($templateId, $userDetails['email'], 'Delete Notification Template', "", "", "", 'Post Array is blank:Failure', $trackerData['client_id']);
                $this->flashMessenger()->addMessage(array('failure' => 'Error in deteing Notification Template!'));
                $responseCode = 0;
                $errMessage = "Access Denied";
                $resArr['responseCode'] = $responseCode;
                $resArr['errMessage'] = $errMessage;
                if (!empty($messages)) {
                    $response->setContent(\Zend\Json\Json::encode($resArr));
                }
                return $response;                
            }
        }
    }
    public function usercreationmailAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $cc = array();
            $username = $this->getEvent()->getRouteMatch()->getParam('param1');
            $trackerName = $this->getEvent()->getRouteMatch()->getParam('param2');
            $email =  $this->getEvent()->getRouteMatch()->getParam('param3');
            $setting= $this->getmailService()->getsettings();
            $msg = "Dear ".$username.",<br/> You are added for ".$trackerName;
            $subject ="Welcome to PvTRACE";
            $htmlPartSwift ="<html><body>" . $msg . "<div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                    <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>Bioclinica</a>. All rights reserved.<br>
                </div></body></html>";
            $this->getSwiftmailService()->saveMail(0, 0, $setting['smtp_user'], $email, $cc, $subject, $msg);
            return true;
        }
    }

    public function changepasswordmailAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $cc = array();
            $email = $this->getEvent()->getRouteMatch()->getParam('param1');
            $trackerName = $this->getEvent()->getRouteMatch()->getParam('param2');
            $key =  $this->getEvent()->getRouteMatch()->getParam('param3');
            $protocol = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? "https://" : "http://";
            $link = $protocol.$_SERVER['HTTP_HOST'].'/profile/index/'.$key;
            $setting= $this->getmailService()->getsettings();
            $msg = "Dear User,<br/><br/> You have been added as a user to the ".$trackerName. ". Kindly click on the below link to reset your password. <br/>".$link."<br/><br/>
            Thank You<br/>Bioclinica PvTRACE support";
            $subject ="Set Your Password";
            $htmlPart = "<html><body>" . $msg . "<div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                        <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>Bioclinica Inc</a>. All rights reserved.<br>
                    </div></body></html>";

            $this->getSwiftmailService()->saveMail(0, 0, $setting['smtp_user'], $email, $cc, $subject, $msg);
            return true;
        }
    }

    public function setpasswordmailAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $cc = array();
            $email = $this->getEvent()->getRouteMatch()->getParam('param1');
            $key =  $this->getEvent()->getRouteMatch()->getParam('param2');
            $config=$this->getServiceLocator()->get('config');
            $url=$config['appurl']['url'];
            $link = $url.'profile/index/'.$key;
            $setting= $this->getmailService()->getsettings();
            $msg = "Dear User,<br/><br/>Kindly click on the below link to reset your password. <br/>".$link."<br/><br/>
            Thank You<br/>Bioclinica PvTRACE support";
            $subject ="Set Your Password";
            $htmlPart ="<html><body>" . $msg . "<div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                        <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>Bioclinica</a>. All rights reserved.<br>
                    </div></body></html>";
            $this->getSwiftmailService()->saveMail(0, 0, $setting['smtp_user'], $email, $cc, $subject, $msg);
            return true;
        }
    }
    public function sendMailCronJobAction()
    {
        $setting = $this->getmailService()->getsettings();
        $emailDatas = $this->getSwiftmailService()->getSendMailData();
        if (!empty($emailDatas)) {
            foreach ($emailDatas as $emailData) {
                $cc = explode(',', $emailData['cc']);
                $cc = preg_split("/[\s,]+/", $emailData['cc']);
                $to = explode(',', $emailData['to_log']);
                // $htmlPart = "<html><body>" . $emailData['body'] . "<div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                //             <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>Bioclinica</a>. All rights reserved.<br>
                //         </div></body></html>";
                $htmlPart =  $this->emailTemplate($emailData['body']);
                $response = $this->getSwiftmailService()->sendMail($setting['smtp_host'], $setting['smtp_user'], $setting['smtp_pwd'], $setting['smtp_port'], $emailData['subject'], $htmlPart, $to, $cc);
                if ($response == 0 || $response == null) {
                    $status = 'failed';
                } else {
                    $status = 'sent';
                }
                $this->getSwiftmailService()->updateNotification($emailData['id'], $response, $status);
                die;
            }
        }
    }
    public function sendMailCronJob1Action()
    {
        $setting = $this->getmailService()->getsettings();
        $emailDatas = $this->getSwiftmailService()->getSendMailDataBackup1();
        if (!empty($emailDatas)) {
            foreach ($emailDatas as $emailData) {
                $this->getSwiftmailService()->updateNotificationQueue($emailData['id']);
                $cc = explode(',', $emailData['cc']);
                $cc = preg_split("/[\s,]+/", $emailData['cc']);
                $to = explode(',', $emailData['to_log']);
                // $htmlPart = "<html><body>" . $emailData['body'] . "<div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                //             <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>BioClinica</a>. All rights reserved.<br>
                //         </div></body></html>";
                $htmlPart =  $this->emailTemplate($emailData['body']);
                $response = $this->getSwiftmailService()->sendMail($setting['smtp_host'], $setting['smtp_user'], $setting['smtp_pwd'], $setting['smtp_port'], $emailData['subject'], $htmlPart, $to, $cc);
                if ($response == 0 || $response == null) {
                    $status = 'failed';
                } else {
                    $status = 'sent';
                }
                $this->getSwiftmailService()->updateNotification1($emailData['id'], $response, $status);
                die;
            }
        }
    }
    public function sendMailCronJob2Action()
    {
        $setting = $this->getmailService()->getsettings();
        $emailDatas = $this->getSwiftmailService()->getSendMailDataBackup2();
        if (!empty($emailDatas)) {
            foreach ($emailDatas as $emailData) {
                $this->getSwiftmailService()->updateNotificationQueue($emailData['id']);
                $cc = explode(',', $emailData['cc']);
                $cc = preg_split("/[\s,]+/", $emailData['cc']);
                $to = explode(',', $emailData['to_log']);
                // $htmlPart = "<html><body>" . $emailData['body'] . "<div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                //             <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>BioClinica</a>. All rights reserved.<br>
                //         </div></body></html>";
                $htmlPart =  $this->emailTemplate($emailData['body']);
                $response = $this->getSwiftmailService()->sendMail($setting['smtp_host'], $setting['smtp_user'], $setting['smtp_pwd'], $setting['smtp_port'], $emailData['subject'], $htmlPart, $to, $cc);
                if ($response == 0 || $response == null) {
                    $status = 'failed';
                } else {
                    $status = 'sent';
                }
                $this->getSwiftmailService()->updateNotification2($emailData['id'], $response, $status);
                die;
            }
        }
    }
    public function sendMailCronJob3Action()
    {
        $setting = $this->getmailService()->getsettings();
        $emailDatas = $this->getSwiftmailService()->getSendMailDataBackup3();
        if (!empty($emailDatas)) {
            foreach ($emailDatas as $emailData) {
                $this->getSwiftmailService()->updateNotificationQueue($emailData['id']);
                $cc = explode(',', $emailData['cc']);
                $cc = preg_split("/[\s,]+/", $emailData['cc']);
                $to = explode(',', $emailData['to_log']);
                // $htmlPart = "<html><body>" . $emailData['body'] . "<div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                //             <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>BioClinica</a>. All rights reserved.<br>
                //         </div></body></html>";
                $htmlPart =  $this->emailTemplate($emailData['body']);
                $response = $this->getSwiftmailService()->sendMail($setting['smtp_host'], $setting['smtp_user'], $setting['smtp_pwd'], $setting['smtp_port'], $emailData['subject'], $htmlPart, $to, $cc);
                if ($response == 0 || $response == null) {
                    $status = 'failed';
                } else {
                    $status = 'sent';
                }
                $this->getSwiftmailService()->updateNotification3($emailData['id'], $response, $status);
                die;
            }
        }
    }
    public function sendMailCronJob4Action()
    {
        $setting = $this->getmailService()->getsettings();
        $emailDatas = $this->getSwiftmailService()->getSendMailDataBackup4();
        if (!empty($emailDatas)) {
            foreach ($emailDatas as $emailData) {
                $this->getSwiftmailService()->updateNotificationQueue($emailData['id']);
                $cc = explode(',', $emailData['cc']);
                $cc = preg_split("/[\s,]+/", $emailData['cc']);
                $to = explode(',', $emailData['to_log']);
                // $htmlPart = "<html><body>" . $emailData['body'] . "<div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                //             <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>BioClinica</a>. All rights reserved.<br>
                //         </div></body></html>";
                $htmlPart =  $this->emailTemplate($emailData['body']);
                $response = $this->getSwiftmailService()->sendMail($setting['smtp_host'], $setting['smtp_user'], $setting['smtp_pwd'], $setting['smtp_port'], $emailData['subject'], $htmlPart, $to, $cc);
                if ($response == 0 || $response == null) {
                    $status = 'failed';
                } else {
                    $status = 'sent';
                }
                $this->getSwiftmailService()->updateNotification4($emailData['id'], $response, $status);
                die;
            }
        }
    }

    public function updateDataBaseForEmailChangeAction()
    {
        $bioData = array();
        $data = $this->readCsvData("./data/MasterList-All_Employees_Email IDs.csv", ",");
        array_shift($data);
        try {
            foreach ($data as $empDetails) {
                $bioData['Name'] = $empDetails[0];
                $bioData['Synowledge'] = strtolower(trim($empDetails[2]));
                $bioData['Bioclinica'] = strtolower(trim($empDetails[4]));
                if ($bioData['Synowledge'] != 'na' && $bioData['Synowledge'] != '') {
                    $userId = $this->getEmailMigrationService()->getUserId($bioData['Synowledge']);
                    if ($userId != 0) {
                        $superAdmins = $this->getEmailMigrationService()->getSuperAdmins();
                        if (in_array($userId, array_values($superAdmins))) {
                            $trackerIds = $this->getEmailMigrationService()->getAllTrackerIds();
                            if ($trackerIds != 0) {
                                foreach ($trackerIds as $trackerId) {
                                    $formIds = $this->getEmailMigrationService()->getFormIds(intval($trackerId['tracker_id']));
                                    if ($formIds != 0) {
                                        foreach ($formIds as $formId) {
                                            $UserTypeFields = $this->getEmailMigrationService()->getUserTypeFields(intval($formId['form_id']));
                                            if ($UserTypeFields !=0) {
                                                $this->getEmailMigrationService()->updateUserTypeFieldsData(intval($trackerId['tracker_id']), intval($formId['form_id']), $UserTypeFields, $bioData);
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            $trackerIds = $this->getEmailMigrationService()->getTrackerIds($userId);
                            if ($trackerIds != 0) {
                                foreach ($trackerIds as $trackerId) {
                                    $formIds = $this->getEmailMigrationService()->getFormIds(intval($trackerId['tracker_id']));
                                    if ($formIds != 0) {
                                        foreach ($formIds as $formId) {
                                            $UserTypeFields = $this->getEmailMigrationService()->getUserTypeFields(intval($formId['form_id']));
                                            if ($UserTypeFields !=0) {
                                                $this->getEmailMigrationService()->updateUserTypeFieldsData(intval($trackerId['tracker_id']), intval($formId['form_id']), $UserTypeFields, $bioData);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $this->getEmailMigrationService()->updateUser($bioData);
                    }
                }

                if ($bioData['Bioclinica'] != '' && $bioData['Bioclinica'] != 'na') {
                    $userId = $this->getEmailMigrationService()->getUserId($bioData['Bioclinica'].'@bioclinica.com');
                    if ($userId != 0) {
                        $this->getEmailMigrationService()->updateNormalUserToLDAPUser($bioData);
                        $trackerIds = $this->getEmailMigrationService()->getTrackerIds($userId);
                        if ($trackerIds != 0) {
                            foreach ($trackerIds as $trackerId) {
                                $formIds = $this->getEmailMigrationService()->getFormIds(intval($trackerId['tracker_id']));
                                if ($formIds != 0) {
                                    foreach ($formIds as $formId) {
                                        $UserTypeFields = $this->getEmailMigrationService()->getUserTypeFields(intval($formId['form_id']));
                                        if ($UserTypeFields !=0) {
                                            $this->getEmailMigrationService()->updateUserTypeFieldsDataForBio(intval($trackerId['tracker_id']), intval($formId['form_id']), $UserTypeFields, $bioData['Bioclinica']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }catch (\Zend\Db\Adapter\Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            die;
        }
        catch (\Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
            die;
        }
        echo "Updated \n";
        die;
    }
    public function readCsvData($file, $delimiter)
    {
        $data2DArray = array();
        if (($handle = fopen($file, "r")) !== false) {
            $i = 0;
            while (($lineArray = fgetcsv($handle, 0, $delimiter)) !== false) {
                for ($j = 0; $j < count($lineArray); $j++) {
                    $data2DArray[$i][$j] = trim(str_replace(array("\n\r","\r\n","\r","\n","\\r","\\n","\\r\\n","\\n\\r"), "", $lineArray[$j]));
                }
                $i++;
            }
            fclose($handle);
        }
        return $data2DArray;
    }

    public function sendMailForCSVReportAction()
    {
        $request = $this->getRequest();
        // Make sure that we are running in a console and the user has not tricked our
        // application into running this action from a public web server.
        if (!$request instanceof ConsoleRequest) {
            throw new \RuntimeException('You can only use this action from a console!');
        }
        // Get user email from console and check if the user used --verbose or -v flag
        $securedKey   = $request->getParam('secured_key');
        $filename=$request->getParam('csv_files');
        $server=$_SERVER['REQUEST_URI'];
        $id=$request->getParam('id');
        $userId = $request->getParam('mail_id');
        $userArray = explode("@", $userId);
        $user=$userArray[0];
        $to = $request->getParam('mail_id');
        $cc = array();
        $sublect="Report-".$filename;
        $setting = $this->getmailService()->getsettings();
        $config=$this->getServiceLocator()->get('config');
        $url=$config['appurl']['url'];
        $fileEncode=base64_encode($filename.".zip");
        $path=$url."email/getdownloadFile/".$fileEncode;

        $htmlPart = "<html><body>Dear " . $user ."</br></br> Password : " . $securedKey ."</br>
                   <br>Report file : <a href=".$path." target='_blank' style='color: #005399;text-decoration: none;'>".$filename.".zip"."</a></br></br>

                   <p>
                                    Confidentiality Statement:</br>
                    <h5>This Document is the asset of BioClinica Inc. and should not be distributed other than selected personnel within BioClinica Inc. This Access should only be granted $
                                     This is a system generated correspondence. Please do not reply to this email </br>
                                         Please contact _Synowledge.LitTrace.Support@bioclinica.com in case you have any questions.</br>
                                   </p>
                      <div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                        <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>Bioclinica</a>. All rights reserved.<br>
                </div></body></html>";

        $response = $this->getSwiftmailService()->sendMail($setting['smtp_host'], $setting['smtp_user'], $setting['smtp_pwd'], $setting['smtp_port'], $sublect, $htmlPart, $to, $cc);
        if ($response == 0 || $response == null) {
            $status = 'failed';
            $this->getmailService()->updateStatusForReportCSV($id);
        } else {
            $status = 'sent';
        }
        die;
    }

    public function getSchemaInfoAction()
    {
        $request = $this->getRequest();
        // Make sure that we are running in a console and the user has not tricked our
        // application into running this action from a public web server.
        if (!$request instanceof ConsoleRequest) {
            throw new \RuntimeException('You can only use this action from a console!');
        }
        $config=$this->getServiceLocator()->get('config');
        $server = explode("=", $config['db']['dsn']);
        $res=array();
        $db = explode(":", $server[1]);
        $db = explode(";", $db[0]);
        $host=$server[2];
        $res['database_name']='SCHEMA='."$db[0]";
        $res['user_name']='USER='.$config['db']['username'];//$config['db']['username'];
        $res['password']='USER_PASSWORD='.$config['db']['password'];//$config['db']['password'];
        $res['host']='HOST='.$host;//$config['db']['password'];
        $dbinfo = implode(" ", $res);
        echo $dbinfo;
        die ;
    }
    public function getdownloadFileAction()
    {
        session_start();
        $filename = $this->params()->fromRoute('param1', null);
        $keyname='ReportMail/'.base64_decode($filename);
        $keyname= base64_encode($keyname);
        if (!isset($_SESSION['u_id'])) {
            $_SESSION['ref'] = 'email/getDownloadFile/'.$filename;
            return $this->redirect()->toRoute('home');
        } else {
            $result=$this->forward()->dispatch(
                'Tracker\Controller\Aws',
                array(
                'action' => 'downloadFilesFromAws',
                'keyname' => $keyname,
                'filename' => $filename
                )
            );
            return $result;
            die;
        }
    }

    public function readUnitTestCSVAction()
    {
        $csvfilewithdlr = dirname(dirname(dirname(dirname(dirname(__DIR__))))) . "/public/unittestxml/dlrsheet.csv";
        $dir = dirname(dirname(dirname(dirname(dirname(__DIR__))))) . "/public/unittestxml";
        $files = scandir($dir);
        $row = 1;
        $count = -1;
        foreach ($files as $key => $value) {
            if (!in_array($value, array(".", ".."))) {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {

                } else {
                    $j = 0;
                    $file = pathinfo($dir . DIRECTORY_SEPARATOR . $value);
                    if ($file['extension'] == 'xml') {
                        $xmlfile = $file['dirname'] . '/' . $file['basename'];
                        if (($handle = fopen($csvfilewithdlr, "r")) !== false) {
                            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                                $num = count($data);
                                $row++;
                                for ($functionname = 2, $dlr = 1, $dlrid = 0; $functionname < $num; $functionname++, $dlr++, $dlrid++) {
                                    $xml = simplexml_load_file($xmlfile);
                                    $element = $xml->testsuite;

                                    foreach ($element->children()->testsuite as $children) {
                                        if (isset($children->testcase[$j])) {
                                            if ($data[$functionname] == $children->testcase[$j]->attributes()->name) {
                                                $children->testcase[$j]->addAttribute('DLR', $data[$dlr]);
                                                $children->testcase[$j]->addAttribute('Date_of_execution', date("Y-m-d"));
                                                $xml->asXML($xmlfile);
                                                $j++;
                                            }
                                        }
                                    }
                                }
                            }
                            fclose($handle);
                        }
                    }
                }
            }
        }
        foreach ($files as $key => $value) {
            if (!in_array($value, array(".", ".."))) {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {

                } else {
                    $j = 0;
                    $file = pathinfo($dir . DIRECTORY_SEPARATOR . $value);
                    if ($file['extension'] =='xml') {
                         $xmlfile = $file['dirname'] . '/' . $file['basename'];
                        if (($handle = fopen($csvfilewithdlr, "r")) !== false) {
                            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                                $num = count($data);
                                $row++;

                                for ($functionname = 2, $dlr = 1, $dlrid = 0; $functionname < $num; $functionname++, $dlr++, $dlrid++) {
                                    $xml = simplexml_load_file($xmlfile);
                                    $element = $xml->testsuite;
                                    if (count($element->children()->testsuite) > 1) {
                                        $j = 0;
                                    }
                                    foreach ($element->children()->testsuite as $children) {
                                        if (isset($children->testcase[$j])) {
                                            if ($data[$functionname] == $children->testcase[$j]->attributes()->name) {
                                                $children->testcase[$j]->addAttribute('DLR', $data[$dlr]);
                                                $children->testcase[$j]->addAttribute('Date_of_execution', date("Y-m-d"));
                                                $xml->asXML($xmlfile);
                                                $j++;
                                            }
                                        }
                                    }
                                }
                            }
                            fclose($handle);
                        }
                    }
                }
            }
        }
        die;
    }
    
    public function getWorkflowandfieldsAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $response = $this->getResponse();
            $post = $this->getRequest()->getPost()->toArray();
            $formId=(int)$post['form_id'];
            $arr = $this->getmailService()->getWorkflowNames($formId);
            $resultSArr=array();
            $resultSArr['workflow_name'][] = isset($arr[0]['workflow_name']) ? $arr[0]['workflow_name']:'' ;
            foreach ($arr as $workflow) {
                if (!in_array($workflow['workflow_name'], $resultSArr['workflow_name']) ) {
                    $resultSArr['workflow_name'][]=$workflow['workflow_name'];                
                }                       
            }               
            $response->setContent(\Zend\Json\Json::encode($resultSArr));
            return $response;
        }
    }
    
    public function getWorkflowFieldsAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $response = $this->getResponse();
            $post = $this->getRequest()->getPost()->toArray();
            $formId=(int)$post['form_id'];
            $workflowName=$post['workflowName'];
            $arr = $this->getmailService()->getWorkflowFields($formId, $workflowName);            
            $response->setContent(\Zend\Json\Json::encode($arr));
            return $response;
        }
    }
    
    public function savereminderAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;
        $container = $session->setSession('message');
        $applicationController = new IndexController();
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $post = $this->getRequest()->getPost()->toArray();      
            $trackerId = $this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
            $comment= isset($post['comment'])?$post['comment'] : "";
            unset($post['comment']);
            $post['status']='Active';   
            if ($post['template_id'] > 0) {
                $resultset = $this->getmailService()->getTemplateAllInfo((int)$post['template_id']);
                $resultset['msg'] = html_entity_decode($resultset['msg']);
            }
            $optionRecords = $this->getmailService()->saveReminder($post, $userContainer->u_id);
            if ($optionRecords=='duplicate') {
                echo 'duplicate';
            } elseif ($post['template_id'] > 0) {
                $container->message = 'updated';
                echo 'updated';
            } else {
                $container->message = 'inserted';
                echo 'inserted';
            }
            
            if ($post['template_id'] > 0) {
                $applicationController->saveToLogFile($post['template_id'], $userDetails['email'], 'Edit Reminder', json_encode($resultset), json_encode($post), $comment, 'Success', $trackerData['client_id']);
                $this->flashMessenger()->addMessage(array('success' => 'Reminder Template '.$container->message.' successfully!'));
            } else {
                $applicationController->saveToLogFile(0, $userDetails['email'], 'Add Reminder', "", json_encode($post), $comment, 'Success', $trackerData['client_id']);                  
                $this->flashMessenger()->addMessage(array('success' => 'Reminder Template '.$container->message.' successfully!'));
            }
            unset($post['status']);
            return $response;            
        }
    }
    
    function deleteConditionAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;
        $applicationController = new IndexController();
        $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);  
        
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);      
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
                $post = $this->getRequest()->getPost()->toArray();
            if (isset($post['id'])) {
                    $id   = (strval(intval($post['id'])) == $post['id']) ? $post['id'] : 0;
                try {
                    $this->getmailService()->deleteNotificationCondition($id);
                    $applicationController->saveToLogFile($id, $userDetails['email'], 'Delete Notification Condition', "{'id':'" . $post['id'] . "' }", "{'id':'null' }", '', 'Success', $trackerData['client_id']);
                    $resultArr = array(
                        'responseCode' => 1,
                        'errMessage' => 'Success'
                    );
                    return $response->setContent(\Zend\Json\Json::encode($resultArr));
                }
                catch (Exception $e) {
                    $applicationController->saveToLogFile($id, $userDetails['email'], 'Delete Notification Condition', "", "", "", 'Fail', $trackerData['client_id']);
                    $resultArr = array(
                        'responseCode' => 5,
                        'errMessage' => $e->getMessage()
                    );
                    return $response->setContent(\Zend\Json\Json::encode($resultArr));
                }

            } else {
                $response   = 2;
                $errMessage = "Invalid data provided.";
                $resultArr  = array(
                    'responseCode' => $response,
                    'errMessage' => $errMessage
                );
                return $response->setContent(\Zend\Json\Json::encode($resultArr));
            }
        }
    }


    public function emailTemplate($msg)
    {
        $year=date("Y");
        $endsection = '<br><br><div class="col-md-6">For any questions please email us back at  _AsrTrace.Support@bioclinica.com</div><br><br><div class="col-md-6">Regards, </div><div class="col-md-6">Bioclinica AsrTRACE Team</div>';
        $htmlPart = '<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
            <head>
            <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Email Template</title>
            <style type="text/css">
            p{
            margin:10px 0;
            padding:0;
            }
            table{
            border-collapse:collapse;
            }
            h1,h2,h3,h4,h5,h6{
            display:block;
            margin:0;
            padding:0;
            }
            img,a img{
            border:0;
            height:auto;
            outline:none;
            text-decoration:none;
            }
            body,#bodyTable,#bodyCell{
            height:100%;
            margin:0;
            padding:0;
            width:100%;
            }
            .mcnPreviewText{
            display:none !important;
            }
            #outlook a{
            padding:0;
            }
            img{
            -ms-interpolation-mode:bicubic;
            }
            table{
            mso-table-lspace:0pt;
            mso-table-rspace:0pt;
            }
            .ReadMsgBody{
            width:100%;
            }
            .ExternalClass{
            width:100%;
            }
            p,a,li,td,blockquote{
            mso-line-height-rule:exactly;
            }
            a[href^=tel],a[href^=sms]{
            color:inherit;
                cursor:default;
                text-decoration:none;
            }
            p,a,li,td,body,table,blockquote{
                -ms-text-size-adjust:100%;
                -webkit-text-size-adjust:100%;
            }
            .ExternalClass,.ExternalClass p,.ExternalClass td,.ExternalClass div,.ExternalClass span,.ExternalClass font{
                line-height:100%;
            }
            a[x-apple-data-detectors]{
                color:inherit !important;
                text-decoration:none !important;
                font-size:inherit !important;
                font-family:inherit !important;
                font-weight:inherit !important;
                line-height:inherit !important;
            }
            #bodyCell{
                padding:10px;
            }
            .templateContainer{
                max-width:600px !important;
            }
            a.mcnButton{
                display:block;
            }
            .mcnImage{
                vertical-align:bottom;
            }
            .mcnTextContent{
                word-break:break-word;
            }
            .mcnTextContent img{
                height:auto !important;
            }
            .mcnDividerBlock{
                table-layout:fixed !important;
            }
            /*
            @tab Page
            @section Background Style
            @tip Set the background color and top border for your email. You may want to choose colors that match your companys branding.
            */
            body,#bodyTable{
                /*@editable*/background-color:#FFFFFF;
                /*@editable*/background-image:none;
                /*@editable*/background-repeat:no-repeat;
                /*@editable*/background-position:center;
                /*@editable*/background-size:cover;
            }
            /*
            @tab Page
            @section Background Style
            @tip Set the background color and top border for your email. You may want to choose colors that match your companys branding.
            */
            #bodyCell{
                /*@editable*/border-top:0;
            }
            /*
            @tab Page
            @section Email Border
            @tip Set the border for your email.
            */
            .templateContainer{
                /*@editable*/border:0;
            }
            /*
            @tab Page
            @section Heading 1
            @tip Set the styling for all first-level headings in your emails. These should be the largest of your headings.
            @style heading 1
            */
            h1{
                /*@editable*/color:#202020;
                /*@editable*/font-family:Helvetica;
                /*@editable*/font-size:26px;
                /*@editable*/font-style:normal;
                /*@editable*/font-weight:bold;
                /*@editable*/line-height:125%;
                /*@editable*/letter-spacing:normal;
                /*@editable*/text-align:left;
            }
            /*
            @tab Page
            @section Heading 2
            @tip Set the styling for all second-level headings in your emails.
            @style heading 2
            */
            h2{
                /*@editable*/color:#202020;
                /*@editable*/font-family:Helvetica;
                /*@editable*/font-size:22px;
                /*@editable*/font-style:normal;
                /*@editable*/font-weight:bold;
                /*@editable*/line-height:125%;
                /*@editable*/letter-spacing:normal;
                /*@editable*/text-align:left;
            }
            /*
            @tab Page
            @section Heading 3
            @tip Set the styling for all third-level headings in your emails.
            @style heading 3
            */
            h3{
                /*@editable*/color:#202020;
                /*@editable*/font-family:Helvetica;
                /*@editable*/font-size:20px;
                /*@editable*/font-style:normal;
                /*@editable*/font-weight:bold;
                /*@editable*/line-height:125%;
                /*@editable*/letter-spacing:normal;
                /*@editable*/text-align:left;
            }
            /*
            @tab Page
            @section Heading 4
            @tip Set the styling for all fourth-level headings in your emails. These should be the smallest of your headings.
            @style heading 4
            */
            h4{
                /*@editable*/color:#202020;
                /*@editable*/font-family:Helvetica;
                /*@editable*/font-size:18px;
                /*@editable*/font-style:normal;
                /*@editable*/font-weight:bold;
                /*@editable*/line-height:125%;
                /*@editable*/letter-spacing:normal;
                /*@editable*/text-align:left;
            }
            /*
            @tab Header
            @section Header Style
            @tip Set the borders for your emails header area.
            */
            #templateHeader{
                /*@editable*/border-top:0;
                /*@editable*/border-bottom:0;
            }
            /*
            @tab Header
            @section Header Text
            @tip Set the styling for your emails header text. Choose a size and color that is easy to read.
            */
            #templateHeader .mcnTextContent,#templateHeader .mcnTextContent p{
                /*@editable*/color:#202020;
                /*@editable*/font-family:Helvetica;
                /*@editable*/font-size:16px;
                /*@editable*/line-height:150%;
                /*@editable*/text-align:left;
            }
            /*
            @tab Header
            @section Header Link
            @tip Set the styling for your emails header links. Choose a color that helps them stand out from your text.
            */
            #templateHeader .mcnTextContent a,#templateHeader .mcnTextContent p a{
                /*@editable*/color:#2BAADF;
                /*@editable*/font-weight:normal;
                /*@editable*/text-decoration:underline;
            }
            /*
            @tab Body
            @section Body Style
            @tip Set the borders for your emails body area.
            */
            #templateBody{
                /*@editable*/border-top:0;
                /*@editable*/border-bottom:0;
            }
            /*
            @tab Body
            @section Body Text
            @tip Set the styling for your emails body text. Choose a size and color that is easy to read.
            */
            #templateBody .mcnTextContent,#templateBody .mcnTextContent p{
                /*@editable*/color:#202020;
                /*@editable*/font-family:Helvetica;
                /*@editable*/font-size:16px;
                /*@editable*/line-height:150%;
                /*@editable*/text-align:left;
            }
            /*
            @tab Body
            @section Body Link
            @tip Set the styling for your emails body links. Choose a color that helps them stand out from your text.
            */
            #templateBody .mcnTextContent a,#templateBody .mcnTextContent p a{
                /*@editable*/color:#2BAADF;
                /*@editable*/font-weight:normal;
                /*@editable*/text-decoration:underline;
            }
            /*
            @tab Footer
            @section Footer Style
            @tip Set the borders for your emails footer area.
            */
            #templateFooter{
                /*@editable*/border-top:0;
                /*@editable*/border-bottom:0;
            }
            /*
            @tab Footer
            @section Footer Text
            @tip Set the styling for your emails footer text. Choose a size and color that is easy to read.
            */
            #templateFooter .mcnTextContent,#templateFooter .mcnTextContent p{
                /*@editable*/color:#202020;
                /*@editable*/font-family:Helvetica;
                /*@editable*/font-size:12px;
                /*@editable*/line-height:150%;
                /*@editable*/text-align:left;
            }
            /*
            @tab Footer
            @section Footer Link
            @tip Set the styling for your emails footer links. Choose a color that helps them stand out from your text.
            */
            #templateFooter .mcnTextContent a,#templateFooter .mcnTextContent p a{
                /*@editable*/color:#202020;
                /*@editable*/font-weight:normal;
                /*@editable*/text-decoration:underline;
            }
            @media only screen and (min-width:768px) {
            .templateContainer{
                width:600px !important;
            }

            }	@media only screen and (max-width: 480px) {
            body,table,td,p,a,li,blockquote{
                -webkit-text-size-adjust:none !important;
            }

            }	@media only screen and (max-width: 480px) {
            body{
                width:100% !important;
                min-width:100% !important;
            }

            }	@media only screen and (max-width: 480px) {
            #bodyCell{
                padding-top:10px !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnImage{
                width:100% !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnCartContainer,.mcnCaptionTopContent,.mcnRecContentContainer,.mcnCaptionBottomContent,.mcnTextContentContainer,.mcnBoxedTextContentContainer,.mcnImageGroupContentContainer,.mcnCaptionLeftTextContentContainer,.mcnCaptionRightTextContentContainer,.mcnCaptionLeftImageContentContainer,.mcnCaptionRightImageContentContainer,.mcnImageCardLeftTextContentContainer,.mcnImageCardRightTextContentContainer{
                max-width:100% !important;
                width:100% !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnBoxedTextContentContainer{
                min-width:100% !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnImageGroupContent{
                padding:9px !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnCaptionLeftContentOuter .mcnTextContent,.mcnCaptionRightContentOuter .mcnTextContent{
                padding-top:9px !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnImageCardTopImageContent,.mcnCaptionBlockInner .mcnCaptionTopContent:last-child .mcnTextContent{
                padding-top:18px !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnImageCardBottomImageContent{
                padding-bottom:9px !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnImageGroupBlockInner{
                padding-top:0 !important;
                padding-bottom:0 !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnImageGroupBlockOuter{
                padding-top:9px !important;
                padding-bottom:9px !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnTextContent,.mcnBoxedTextContentColumn{
                padding-right:18px !important;
                padding-left:18px !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcnImageCardLeftImageContent,.mcnImageCardRightImageContent{
                padding-right:18px !important;
                padding-bottom:0 !important;
                padding-left:18px !important;
            }

            }	@media only screen and (max-width: 480px) {
            .mcpreview-image-uploader{
                display:none !important;
                width:100% !important;
            }

            }	@media only screen and (max-width: 480px) {
            /*
            @tab Mobile Styles
            @section Heading 1
            @tip Make the first-level headings larger in size for better readability on small screens.
            */
            h1{
                /*@editable*/font-size:22px !important;
                /*@editable*/line-height:125% !important;
            }

            }	@media only screen and (max-width: 480px) {
            /*
            @tab Mobile Styles
            @section Heading 2
            @tip Make the second-level headings larger in size for better readability on small screens.
            */
            h2{
                /*@editable*/font-size:20px !important;
                /*@editable*/line-height:125% !important;
            }

            }	@media only screen and (max-width: 480px) {
            /*
            @tab Mobile Styles
            @section Heading 3
            @tip Make the third-level headings larger in size for better readability on small screens.
            */
            h3{
                /*@editable*/font-size:18px !important;
                /*@editable*/line-height:125% !important;
            }

            }	@media only screen and (max-width: 480px) {
            /*
            @tab Mobile Styles
            @section Heading 4
            @tip Make the fourth-level headings larger in size for better readability on small screens.
            */
            h4{
                /*@editable*/font-size:16px !important;
                /*@editable*/line-height:150% !important;
            }

            }	@media only screen and (max-width: 480px) {
            /*
            @tab Mobile Styles
            @section Boxed Text
            @tip Make the boxed text larger in size for better readability on small screens. We recommend a font size of at least 16px.
            */
            table.mcnBoxedTextContentContainer td.mcnTextContent,td.mcnBoxedTextContentContainer td.mcnTextContent p{
                /*@editable*/font-size:14px !important;
                /*@editable*/line-height:150% !important;
            }

            }	@media only screen and (max-width: 480px) {
            /*
            @tab Mobile Styles
            @section Header Text
            @tip Make the header text larger in size for better readability on small screens.
            */
            td#templateHeader td.mcnTextContent,td#templateHeader td.mcnTextContent p{
                /*@editable*/font-size:16px !important;
                /*@editable*/line-height:150% !important;
            }

            }	@media only screen and (max-width: 480px) {
            /*
            @tab Mobile Styles
            @section Body Text
            @tip Make the body text larger in size for better readability on small screens. We recommend a font size of at least 16px.
            */
            td#templateBody td.mcnTextContent,td#templateBody td.mcnTextContent p{
                /*@editable*/font-size:16px !important;
                /*@editable*/line-height:150% !important;
            }

            }	@media only screen and (max-width: 480px) {
            /*
            @tab Mobile Styles
            @section Footer Text
            @tip Make the footer content text larger in size for better readability on small screens.
            */
            td#templateFooter td.mcnTextContent,td#templateFooter td.mcnTextContent p{
                /*@editable*/font-size:14px !important;
                /*@editable*/line-height:150% !important;
            }

            }
            </style>
            </head>

                        <body style="background-color: #fafafa;">
                        <span class="mcnPreviewText" style="display:none; font-size:0px; line-height:0px; max-height:0px; max-width:0px; opacity:0; overflow:hidden; visibility:hidden; mso-hide:all;"></span><!--<![endif]-->
            <!--*|END:IF|*-->
            <center>
                <table align="center" border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
                    <tr>
                        <td align="left" valign="top" id="bodyCell">
                            <!-- BEGIN TEMPLATE // -->
                            <!--[if gte mso 9]>
                            <table align="center" border="0" cellspacing="0" cellpadding="0" width="600" style="width:600px;">
                            <tr>
                            <td align="center" valign="top" width="600" style="width:600px;">
                            <![endif]-->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" class="templateContainer">
                                <tr>
                                    <td valign="top" id="templateHeader"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnImageBlock" style="min-width:100%;">
            <tbody class="mcnImageBlockOuter">
                <tr>
                    <td valign="top" style="padding:9px" class="mcnImageBlockInner">
                        <table align="left" width="100%" border="0" cellpadding="0" cellspacing="0" class="mcnImageContentContainer" style="min-width:100%;">
                            <tbody><tr>
                                <td class="mcnImageContent" valign="top" style="padding-right: 9px; padding-left: 9px; padding-top: 0; padding-bottom: 0;">


                            <img align="left" alt="" src="https://gallery.mailchimp.com/b8c1bf751511076c3b3fbd5b5/images/344ad82b-8a1d-4558-a590-188bf178752e.png" width="100" style="max-width:200px; padding-bottom: 0; display: inline !important; vertical-align: bottom;" class="mcnImage">


                                </td>

                            </tr>
                        </tbody></table>
                    </td>
                </tr>
            </tbody>
            </table>
            <table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
            <tbody class="mcnDividerBlockOuter">
            <tr>
                <td class="mcnDividerBlockInner" style="min-width: 100%; padding: 5px 18px;">
                    <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%;border-top: 2px solid #EAEAEA;">
                        <tbody><tr>
                            <td>
                                <span></span>
                            </td>
                        </tr>
                    </tbody></table>
            <!--
                    <td class="mcnDividerBlockInner" style="padding: 18px;">
                    <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
            -->
                </td>
            </tr>
            </tbody>
            </table></td>
            </tr>
            <tr bgcolor="#ffffff">
                            <td bgcolor="#ffffff" style="padding:5px 10px;">'.$msg.$endsection.'</td>
                        </tr>

            </table><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnDividerBlock" style="min-width:100%;">
            <tbody class="mcnDividerBlockOuter">
            <tr>
                <td class="mcnDividerBlockInner" style="min-width: 100%; padding: 5px 18px;">
                    <table class="mcnDividerContent" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%;border-top: 2px solid #EAEAEA;">
                        <tbody><tr>
                            <td>
                                <span></span>
                            </td>
                        </tr>
                    </tbody></table>
            <!--
                    <td class="mcnDividerBlockInner" style="padding: 18px;">
                    <hr class="mcnDividerContent" style="border-bottom-color:none; border-left-color:none; border-right-color:none; border-bottom-width:0; border-left-width:0; border-right-width:0; margin-top:0; margin-right:0; margin-bottom:0; margin-left:0;" />
            -->
                </td>
            </tr>
            </tbody>
            </table></td>
            </tr>
                                <tr>
            <td valign="top" id="templateFooter"><table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnBoxedTextBlock" style="min-width:100%;">
            <!--[if gte mso 9]>
            <table align="center" border="0" cellspacing="0" cellpadding="0" width="100%">
            <![endif]-->
            <tbody class="mcnBoxedTextBlockOuter">
            <tr>
                <td valign="top" class="mcnBoxedTextBlockInner">

                    <!--[if gte mso 9]>
                    <td align="center" valign="top" ">
                    <![endif]-->
                    <table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width:100%;" class="mcnBoxedTextContentContainer">
                        <tbody><tr>

                            <td style="padding-top:9px; padding-left:18px; padding-bottom:9px; padding-right:18px;">

                                <table border="0" cellpadding="18" cellspacing="0" class="mcnTextContentContainer" width="100%" style="min-width: 100% !important;background-color: #404040;border: 1px solid;">
                                    <tbody><tr>
                                        <td valign="top" class="mcnTextContent" style="color: #F2F2F2;font-family: Helvetica;font-size: 13px;font-weight: normal;line-height: 100%;text-align: left;">
                                            <em>&copy; '.$year.' <a style="color: #00FFFF;" href="http://www.bioclinica.com">Bioclinica</a>. All rights reserved.&nbsp;</em>Privacy &amp; Legal
                                        </td>
                                    </tr>
                                </tbody></table>
                            </td>
                        </tr>
                    </tbody></table>
                    <!--[if gte mso 9]>
                    </td>
                    <![endif]-->

                    <!--[if gte mso 9]>
                    </tr>
                    </table>
                    <![endif]-->
                </td>
            </tr>
            </tbody>
            </table></td>
                                </tr>
                            </table>
                            <!--[if gte mso 9]>
                            </td>
                            </tr>
                            </table>
                            <![endif]-->
                            <!-- // END TEMPLATE -->
                        </td>
                    </tr>
                </table>
                        <tr style="height: 5px;">
                            <td></td>
                        </tr>
                        </table>
                        </body>
                        </html>';
            return $htmlPart;
    }

    public function checkReminderAction()
    {
        $resultset = $this->getmailService()->checkReminder();  
        $getReminder = $this->generateReminderCondition($resultset);
        if (isset($getReminder)) {
            foreach ($getReminder as $key => $reminder) {
                $tableName = 'form_'.$reminder['tracker_id'].'_'.$reminder['notification_template_form_id'];
                
                $query = 'SELECT id FROM '.$tableName.' WHERE '.$reminder['condition']." AND ".$reminder['remCondition'];
                
                try {
                    $getReminderData = $this->getmailService()->getReminderData($query);
                } catch (\Exception $e) {
                    //echo $e-getMessage();
                }
                
                if (count($getReminderData) > 0) {
                    foreach ($getReminderData as $recordId) {
                        $this->saveEmailForReminder($key, $tableName, $recordId['id']);
                        // $this->forward()->dispatch(
                        //     'Notification\Controller\Email',
                        //     array(
                        //     'action' => 'sendemail',
                        //     'param1' => $key,
                        //     'param2' => $tableName,
                        //     'param3' => $recordId['id'],
                        //     )
                        // );
                    }
                }
            }
        }
        exit;
    }

    public function generateReminderCondition($templatearray)
    {
        $reminderArray = array();
        $condition='';
        $iOldTemplateId = 0;
        $iArrayCount = count($templatearray);
        $inc = 0;
        $temp = 0;
        if ($iArrayCount > 0) {
            $iInitTemplateId = $templatearray[0]['notification_template_id'];
            foreach ($templatearray as $key => $value) {
            
                $reminderArray[$value['notification_template_id']]['notification_template_form_id'] = $value['notification_template_form_id'];
                $reminderArray[$value['notification_template_id']]['tracker_id'] = $value['tracker_id'];

                $oprand =$value['condition_operand'];
                if ($value['condition_operand'] == '!=') {
                    $oprand = "<>";
                } elseif ($value['condition_operand'] == '==') {
                    $oprand = "=";
                }

                $sReminderCondition = '';
                if ($value['before_after'] == 'after') {
                    $sReminderCondition = 'date('.$value['fields'].') = CURDATE() - INTERVAL '.$value['days'].' DAY';
                } else if ($value['before_after'] == 'before') {
                    $sReminderCondition = 'date('.$value['fields'].') = CURDATE() + INTERVAL '.$value['days'].' DAY';
                } 

                if ($iOldTemplateId != $value['notification_template_id']) {
                    if ($oprand == '<>') {
                         $condition = "(".$value['condition_field_name']." ".$oprand." '".$value['condition_value']."' OR ".$value['condition_field_name'].' is null )';
                    } else {
                        $condition = "(".$value['condition_field_name']." ".$oprand." '".$value['condition_value']."')";
                    }
                    
                } else {
                    $condition = $condition." ".$value['notification_template_condition_type']." ";
                    if ($oprand == '<>') {
                        $condition = $condition." (".$value['condition_field_name']." ".$oprand." '".$value['condition_value']."' OR ".$value['condition_field_name'].' is null )';
                    } else {
                        $condition = $condition." (".$value['condition_field_name']." ".$oprand." '".$value['condition_value']."')";
                    }
                    //$condition = $condition." ".$value['condition_field_name']." ".$oprand." '".$value['condition_value']."'";
                }  

                $reminderArray[$value['notification_template_id']]['condition'] = "(".$condition.")";
                $reminderArray[$value['notification_template_id']]['remCondition'] = $sReminderCondition;
                $iOldTemplateId = $value['notification_template_id'];
            }
        }
        return $reminderArray;
    }

    public function saveEmailForReminder($templateId, $formName, $id)
    {
        $cc = array();
        $optionRecords = $this->getmailService()->gettemplateDetail($templateId);
       
        $optionRecords[0]['notification_template_to'];
        $sendtoids = explode(',', $optionRecords[0]['notification_template_to']);
        if (isset($optionRecords[0]['notification_template_cc']) && !empty($optionRecords[0]['notification_template_cc'])) {
            $cc = $optionRecords[0]['notification_template_cc'];
        }
        $setting = $this->getmailService()->getsettings();
        $email1=array();
        foreach ($sendtoids as $sendto) {
            $fieldName = $this->getmailService()->getfield($sendto);
            
            $getmaiilsId = $this->getmailService()->getMailids($id, $fieldName, $formName);
            //echo "<pre>"; print_r($getmaiilsId);
            $email = isset($getmaiilsId[0]['mail_id']) ? $getmaiilsId[0]['mail_id'] : "N/A";
            $email1[]=$email;
            $formname = explode('_', $formName);
            $formId = $formname[2];
            $trackerId = $formname[1];
            if (isset($email) && !empty($email)) {
                $matches = array();
                $templateinfo = $this->getmailService()->getTemplatetoSendmail($templateId);
                $msg = $templateinfo[0]['notification_template_msg'];
                preg_match_all('~\{{(.+?)\}}~', $msg, $matches);
                preg_match_all('~\[\[(.+?)\]\]~', $msg, $matchesWorkflow);
                array_shift($matches);
                array_shift($matchesWorkflow);
                if (isset($matches[0]) && !empty($matches[0])) {
                    $fieldsvalue = $this->getmailService()->getfieldsvalue($id, $matches, $formName);
                    
                    foreach ($fieldsvalue as $key => $value) {
                        $msg = str_replace('{{' . $key . '}}', $value, $msg);
                    }
                }
                if (isset($matchesWorkflow[0]) && !empty($matchesWorkflow[0])) {
                    $fieldsvalue = $this->getmailService()->getworkflowvalue($id, $matchesWorkflow, $formId);
                    foreach ($matchesWorkflow[0] as $key => $value) {
                        $workflowmsg = '<div class="panel panel-default"><div class="panel-body"><h4><span class="glyphicon glyphicon-list" aria-hidden="true"></span>' . $value . '</h4>';
                        $fieldsArray = $this->getmailService()->recordsCanView($trackerId, $formId);
                        $fieldsArray = isset($fieldsArray['fields'][$value])?$fieldsArray['fields'][$value]:array();
                        $formRecords = $this->getTrackerService()->formRecords($trackerId, $formId, '', '', '', $id);
                        $formRecordsArray = $formRecords['form_data'];
                        foreach ($fieldsArray as $field => $fValues) {
                            $label = $fValues['label'];
                            $fieldName = $fValues['field_name'];
                            $fieldType = $fValues['field_type'];
                            $optionsId = $fValues['code_list_id'];
                            $recordValue = @$formRecordsArray[0][$fieldName];

                            if ($fieldType == 'Heading') {
                                $workflowmsg = $workflowmsg . '<br><div class="col-md-12"><h4>' . $label . '</h4></div>';
                            } else {
                                if ($fieldType == "Check Box") {
                                    if (strlen(@$recordValue) > 0) {
                                        $workflowmsg = $workflowmsg . '<div class="col-md-6"><label class="col-md-4 control-label" style="padding-left: 0px; margin-top:2px">' . $label . ' : </label>' . $recordValue . '';
                                    }
                                } else {
                                    $workflowmsg = $workflowmsg . '<div class="col-md-6"><label class="col-md-4 control-label" style="padding-left: 0px; margin-top:2px">' . $label . ' : </label>' . $recordValue . '';
                                }
                                if ($fieldType == "Check Box") {
                                    if (strlen(@$recordValue) > 0) {
                                        $workflowmsg .= '<div><label>Comment : </label>' . @$formRecordsArray[0]["comment_checkbox_$fieldName"] . '</div>';
                                    }
                                }
                                $workflowmsg = $workflowmsg . '</div></div>';
                            }
                        }
                        $workflowmsg = $workflowmsg . '</div></div>';
                        $msg = str_replace('[[' . $value . ']]', $workflowmsg, $msg);
                    }
                }
                $matches = array();
                $subject = $templateinfo[0]['notification_template_subject'];
                preg_match_all('~\{{(.+?)\}}~', $subject, $matches);
                array_shift($matches);
                if (isset($matches[0]) && !empty($matches[0])) {
                    $fieldsvalue = $this->getmailService()->getfieldsvalue($id, $matches, $formName);
                    foreach ($fieldsvalue as $key => $value) {
                        $subject = str_replace('{{' . $key . '}}', $value, $subject);
                    }
                }
                $htmlPart = "<html><body>" . $msg . "<div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                    <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>Bioclinica</a>. All rights reserved.<br>
                </div></body></html>";
            }
        }
        $email3=implode(",", $email1);
        $email2= trim($email3, ",");
        if ($email2!='') {
            $results = $this->getSwiftmailService()->saveMail($formId, $id, $setting['smtp_user'], $email2, $cc, $subject, $msg);
            return true;
        }
    }
}
