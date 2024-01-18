<?php

namespace Common\Notification\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Common\Notification\Form\NotificationForm;
use Zend\Console\Request as ConsoleRequest ;
use Session\Container\SessionContainer;
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use Common\Notification\Controller\EmailTemplate;

class EmailController extends AbstractActionController
{
    protected $_adminMapper;
    protected $_emailMapper;
    protected $_swiftMapper;
    protected $_emailMigrationMapper;
    protected $_trackerMapper;
    protected $_auditService;
    
    public function getAdminService()
    {
        if (!$this->_adminMapper) {
            $sm = $this->getServiceLocator();
            $this->_adminMapper = $sm->get('Application\Model\AdminMapper');
        }
        return $this->_adminMapper;
    }

    public function getmailService()
    {
        if (!$this->_emailMapper) {
            $sm = $this->getServiceLocator();
            $this->_emailMapper = $sm->get('Notification\Model\Email');
        }
        return $this->_emailMapper;
    }

    public function getEmailMigrationService()
    {
        if (!$this->_emailMigrationMapper) {
            $sm = $this->getServiceLocator();
            $this->_emailMigrationMapper = $sm->get('Notification\Model\EmailMigration');
        }
        return $this->_emailMigrationMapper;
    }

    public function getAuditService()
    {
        if (!$this->_auditService) {
            $sm = $this->getServiceLocator();
            $this->_auditService = $sm->get('Audit\Service');
        }
        return $this->_auditService;
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
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $templateId = (int) $this->getEvent()->getRouteMatch()->getParam('template_id', 0);
            $formId = 0;
            $resultset = array();
            $optionRecords = $this->getmailService()->trackerResults($trackerId);
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
                        'trackerRsults' => $this->getmailService()->trackerResults($trackerId),
                        'fields' => $fields,
                        'result' => $resultset,
                        'formnames'=>$options,
                        'tracker_id' => $trackerId, 'template_id' => $templateId, 'msg' => $msg
                    )
            );
        }
    }
    public function addSubscriptionAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $report = array();
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $msg = '';
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $templateId = (int) $this->getEvent()->getRouteMatch()->getParam('template_id', 0);
            $formId = 0;
            $resultset = array();
            $optionRecords = $this->getmailService()->trackerResults($trackerId);
            $options = array();
            if (isset($optionRecords['forms'])) {
                foreach ($optionRecords['forms'] as $option) {
                    $options[] = array('value' => $option['form_id'], 'label' => $option['form_name']);
                }
            }
            if ($templateId > 0) {
                $resultset = $this->getmailService()->getSubscriptionInfo($templateId);              
                $formId = $resultset[0]['notification_template_form_id'];
                $msg = $resultset[0]['notification_template_msg'];
                $report=($resultset[0]['notification_config'] != null) ? json_decode($resultset[0]['notification_config'], true) : array();
            }
            $fields = $this->getmailService()->getfieldname($formId);           
            $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
            return new ViewModel( 
                array(                      
                        'trackerRsults' => $this->getmailService()->trackerResults($trackerId),
                        'fields' => $fields,
                        'result' => $resultset,
                        'report' => $report,
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
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $userDetails = $userContainer->user_details;
            $roleId = (int)$userDetails['group_id'];
            $trackerUserGroups = $trackerContainer->tracker_user_groups;
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }
            $alltemplate = $this->getmailService()->getAlltemplate($trackerId);
            $trackerRsults = $this->getmailService()->trackerResults($trackerId);
            $formId = (isset($trackerRsults['forms'][0]['form_id'])) ? (int)$trackerRsults['forms'][0]['form_id'] : 0;
            $this->layout()->setVariables(array('tracker_id' => $trackerId, 'form_id'=>$formId));
            return new ViewModel(
                array(
                'alltemplate' =>  $alltemplate,
                'trackerRsults' => $trackerRsults,
                'tracker_id' => $trackerId
                )
            );
        }
    }
    
    // public function getTrackerService()
    // {
    //     if (!$this->_trackerMapper) {
    //         $sm = $this->getServiceLocator();
    //         $this->_trackerMapper = $sm->get('Tracker\Model\Tracker');
    //     }
    //     return $this->_trackerMapper;
    // }
   

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
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $templateId = (int) $this->getEvent()->getRouteMatch()->getParam('template_id', 0);
            $form = new NotificationForm();
            $form->setName('NotificationForm');
            $form->setAttribute('class', 'form-horizontal');
            $formId = 0;
            $resultset = array();
            $optionRecords = $this->getmailService()->trackerResults($trackerId);
            $options = array();
            if (isset($optionRecords['forms'])) {
                foreach ($optionRecords['forms'] as $option) {
                    $options[] = array('value' => $option['form_id'], 'label' => $option['form_name']);
                }
            }
            $form->get('n_form')->setAttribute('options', $options);
            if ($templateId > 0) {
                $resultset = $this->getmailService()->getTemplateInfo($templateId);               
                $formId = $resultset[0]['notification_template_form_id'] ?? 0;
                $form->get('n_name')->setAttribute('value', $resultset[0]['notification_template_name'] ?? '');
                $form->get('n_form')->setAttribute('value', $resultset[0]['notification_template_form_id'] ?? 0);
                $form->get('n_subject')->setAttribute('value', $resultset[0]['notification_template_subject'] ?? '');
                
                $form->get('n_cc')->setAttribute('value', $resultset[0]['notification_template_cc'] ?? '');
                $form->get('n_condition')->setAttribute('value', $resultset[0]['notification_template_condition_type'] ?? '');
                $msg = $resultset[0]['notification_template_msg'] ?? '';
            }
            $fields = $this->getmailService()->getfieldname($formId);
            $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
            return new ViewModel( 
                array(
                        'form' => $form,
                        'trackerRsults' => $this->getmailService()->trackerResults($trackerId),
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
        $container = $session->getSession('message');
        
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $post = $this->getRequest()->getPost()->toArray(); 
            //$post  = filter_var_array($post, FILTER_SANITIZE_STRING);
            if (!is_numeric($post['template_id']) || !is_numeric($post['tracker_id']) || !is_numeric($post['form_id'])) {
                    $container->message = 'Invalid Data';    
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                    $response->setContent('Invalid Data');
                    return $response;
            }
            if (isset($post['ccmail'])) {
                   $cc = explode(",", $post['ccmail']);
                foreach ($cc as $email) {
                    if (!preg_match("/^([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z._-]{2,4})?$/", trim($email))) {
                           $container->message = 'Invalid Data'; 
                           $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                           $response->setContent('Invalid Data');
                           return $response;
                    }
                }
            }
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $trackerData = (int) $this->getmailService()->getTrackerDetails($trackerId);
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
                    $this->getAuditService()->saveToLog($post['template_id'], $userDetails['email'], 'Edit Notification', json_encode($resultset), json_encode($aNewData), $comment, 'Success', $trackerData['client_id'] ?? 0);
                    $this->flashMessenger()->addMessage(array('success' => 'Notification Template '.$container->message.' successfully!'));
                } else {
                    $this->getAuditService()->saveToLog(0, $userDetails['email'], 'Add Notification', "", json_encode($post), $comment, 'Success', $trackerData['client_id'] ?? 0);                  
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
        $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
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
       
        //$optionRecords[0]['notification_template_to'];
        if (isset($optionRecords[0])) {
            $sendtoids = explode(',', $optionRecords[0]['notification_template_to']);
        } else {
            $sendtoids = array();
        }
        
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
                        $formRecords = $this->getmailService()->formRecords($trackerId, $formId, '', '', '', $id);
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
            $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
            $trackerId=(int)$post['tracker_id'];
            $formId=(int)$post['form_id'];
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = $trackerContainer->tracker_user_groups;            
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            $response = $this->getResponse();
            $notifyWhom = $this->getmailService()->getfieldname($formId);
            $dateFields = $this->getmailService()->getDateFields($formId);
            
            $resArr = array(); 
            $fieldsArray = $this->getmailService()->trackerCheckFieldsForFormula($trackerId, $formId);
            
            $resArr['fieldsArray']=$fieldsArray;
            $resArr['notifyWhom']=$notifyWhom;
            $resArr['dateFields']=$dateFields;            
            $response->setContent(\Zend\Json\Json::encode($resArr));
            return $response;
        }
    }
    
    public function getReportsAction()
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
            $resArr = array();
            $reportsArray = $this->getmailService()->reportDetails($formId);
            $resArr['reportsArray']=$reportsArray;           
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
            $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
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
        $container = $session->getSession('message');                
        $userDetails = $userContainer->user_details;
        
        $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);        
        $trackerData = $this->getmailService()->getTrackerDetails($trackerId);
        $templateId = (int) $this->getEvent()->getRouteMatch()->getParam('template_id', 0);
        
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $post = $this->getRequest()->getPost()->toArray();
            $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
            $comment = $post['comment'];
            if (!empty($post)) {     
                $template = $this->getmailService()->getTemplatetoSendmail($post['template_id']);
                $templateType = (isset($template[0]['notification_type']))?$template[0]['notification_type']:'';           
                $container->message = 'deleted';
                $resultset = $this->getmailService()->deletetemplate($post['template_id']);
                $this->getAuditService()->saveToLog($templateId, $userDetails['email'], 'Delete '.$templateType.' Template', "{'id':'" . $post['template_id'] . "', 'notification_template_status':Active }", "{'id':'" . $post['template_id'] . "', 'notification_template_status':Deactive }", $comment, 'Success', $trackerData['client_id'] ?? 0);
                $messages = 'deleted';
                $this->flashMessenger()->addMessage(array('success' => $templateType.' Template Deleted successfully!'));
                if (!empty($messages)) {
                    $response->setContent(\Zend\Json\Json::encode($messages));
                }
                    return $response;                
            } else {
                $this->getAuditService()->saveToLog($templateId, $userDetails['email'], 'Delete '.$templateType.' Template', "", "", "", 'Post Array is blank:Failure', $trackerData['client_id']);
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
    public function notificationNameCheckAction()
    {
        $response = $this->getResponse();
        $post = $this->getRequest()->getPost()->toArray();
        $notificationNameCheck = $this->getmailService()->notificationNameCheck($post['notificationName'], $post['templateId'], $post['templateType']);
        $msg = "";
        if (!empty($notificationNameCheck)) { 
            $msg = "ok";
        }
        $response->setContent(\Zend\Json\Json::encode($msg));
        return $response;
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
        //$setting = $this->getmailService()->getsettings();
        $config=$this->getServiceLocator()->get('Config');
        $groupId=$config['group']['group_id'];
        $logo=$config['logo']['logo_file_name'];
        $emailDatas = $this->getSwiftmailService()->getSendMailData();
        if (!empty($emailDatas)) {
            foreach ($emailDatas as $emailData) {
                $cc = explode(',', $emailData['cc']);
                $cc = preg_split("/[\s,]+/", $emailData['cc']);
                $to = explode(',', $emailData['to_log']);
                if (isset($to) && $to[0] == 'N/A') {
                    $status = 'failed';
                    $response = "invalid or empty to address";
                } else {
                    $emailTemplate = new EmailTemplate();
                    $htmlPart =  $emailTemplate->emailTemplate($emailData['body'], $groupId, $logo);
                    $response = $this->sendSesEmail($emailData['subject'], $htmlPart, $to, $cc);
                    if ($response == 0 || $response == null) {
                        $status = 'failed';
                    } else {
                        $status = 'sent';
                    }
                }
                $this->getSwiftmailService()->updateNotification($emailData['id'], $response, $status);
                break;
            }
        }
    }
    public function sendMailCronJob1Action()
    {
        //$setting = $this->getmailService()->getsettings();
        $emailDatas = $this->getSwiftmailService()->getSendMailDataBackup1();
        if (!empty($emailDatas)) {
            foreach ($emailDatas as $emailData) {
                $this->getSwiftmailService()->updateNotificationQueue($emailData['id']);
                $cc = explode(',', $emailData['cc']);
                $cc = preg_split("/[\s,]+/", $emailData['cc']);
                $to = explode(',', $emailData['to_log']);
                if (isset($to) && $to[0] == 'N/A') {
                    $status = 'failed';
                    $response = "invalid or empty to address";
                } else {
                    $emailTemplate = new EmailTemplate();
                    $htmlPart =  $emailTemplate->emailTemplate($emailData['body']);
                    $response = $this->sendSesEmail($emailData['subject'], $htmlPart, $to, $cc);      
                    if ($response == 0 || $response == null) {
                        $status = 'failed';
                    } else {
                        $status = 'sent';
                    }
                }
                
                $this->getSwiftmailService()->updateNotification1($emailData['id'], $response, $status);
                break;
            }
        }
    }
    public function sendMailCronJob2Action()
    {
        //$setting = $this->getmailService()->getsettings();
        $emailDatas = $this->getSwiftmailService()->getSendMailDataBackup2();
        if (!empty($emailDatas)) {
            foreach ($emailDatas as $emailData) {
                $this->getSwiftmailService()->updateNotificationQueue($emailData['id']);
                $cc = explode(',', $emailData['cc']);
                $cc = preg_split("/[\s,]+/", $emailData['cc']);
                $to = explode(',', $emailData['to_log']);
                $emailTemplate = new EmailTemplate();
                $htmlPart =   $emailTemplate->emailTemplate($emailData['body']);
                $response = $this->sendSesEmail($emailData['subject'], $htmlPart, $to, $cc);  
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
        //$setting = $this->getmailService()->getsettings();
        $emailDatas = $this->getSwiftmailService()->getSendMailDataBackup3();
        if (!empty($emailDatas)) {
            foreach ($emailDatas as $emailData) {
                $this->getSwiftmailService()->updateNotificationQueue($emailData['id']);
                $cc = explode(',', $emailData['cc']);
                $cc = preg_split("/[\s,]+/", $emailData['cc']);
                $to = explode(',', $emailData['to_log']);
                $emailTemplate = new EmailTemplate();
                $htmlPart =  $emailTemplate->emailTemplate($emailData['body']);
                $response = $this->sendSesEmail($emailData['subject'], $htmlPart, $to, $cc);              
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
        //$setting = $this->getmailService()->getsettings();
        $emailDatas = $this->getSwiftmailService()->getSendMailDataBackup4();
        if (!empty($emailDatas)) {
            foreach ($emailDatas as $emailData) {
                $this->getSwiftmailService()->updateNotificationQueue($emailData['id']);
                $cc = explode(',', $emailData['cc']);
                $cc = preg_split("/[\s,]+/", $emailData['cc']);
                $to = explode(',', $emailData['to_log']);
                $emailTemplate = new EmailTemplate();
                $htmlPart =  $emailTemplate->emailTemplate($emailData['body']);
                $response = $this->sendSesEmail($emailData['subject'], $htmlPart, $to, $cc);
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
        $groupId=$config['group']['group_id'];
        $fileEncode=base64_encode($filename.".zip");
        $path=$url."email/getdownloadFile/".$fileEncode;

        $htmlPart = "<html><body>Dear " . $user ."</br></br> Password : " . $securedKey ."</br>
                   <br>Report file : <a href=".$path." target='_blank' style='color: #005399;text-decoration: none;'>".$filename.".zip"."</a></br></br>

                   <p>
                                    Confidentiality Statement:</br>
                    <h5>This Document is the asset of BioClinica Inc. and should not be distributed other than selected personnel within BioClinica Inc. This Access should only be granted $
                                     This is a system generated correspondence. Please do not reply to this email </br>
                                         Please contact ".$groupId." in case you have any questions.</br>
                                   </p>
                      <div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                        <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>Bioclinica</a>. All rights reserved.<br>
                </div></body></html>";

        //$response = $this->getSwiftmailService()->sendMail($setting['smtp_host'], $setting['smtp_user'], $setting['smtp_pwd'], $setting['smtp_port'], $sublect, $htmlPart, $to, $cc);
        $response = $this->sendSesEmailAction($sublect, $htmlPart, $to, $cc);
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
        $keyname='csvImport/'.base64_decode($filename);
        $keyname= base64_encode($keyname);
        if (!isset($_SESSION['u_id'])) {
            $_SESSION['ref'] = 'email/getDownloadFile/'.$filename;
            return $this->redirect()->toRoute('home');
        } else {
            $result=$this->forward()->dispatch(
                'Common\FileHandling\Controller\FileHandling',
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
        $csvfilewithdlr = dirname(dirname(dirname(dirname(dirname(dirname(dirname(__DIR__))))))) . "/public/unittestxml/dlrsheet.csv"; 
        $dir = dirname(dirname(dirname(dirname(dirname(dirname(dirname(__DIR__))))))) . "/public/unittestxml"; 
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
                                        if (isset($children->testcase)) {
                                            foreach ($children->testcase as $child) {
                                                if ($data[$functionname] == $child->attributes()->name) {
                                                    $child->addAttribute('DLR', $data[$dlr]);
                                                    $child->addAttribute('Date_of_execution', date("Y-m-d"));
                                                    $xml->asXML($xmlfile);
                                                    // $j++;
                                                }
                                                //$j++;
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
            $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
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
            $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
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
        $container = $session->getSession('message');
       
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $post = $this->getRequest()->getPost()->toArray();
            //$post  = filter_var_array($post, FILTER_SANITIZE_STRING);      
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $trackerData = $this->getmailService()->getTrackerDetails($trackerId);
            $comment= isset($post['comment'])?$post['comment'] : "";
            unset($post['comment']);
            $post['status']='Active';   
            if ($post['template_id'] > 0) {
                $resultset = $this->getmailService()->getTemplateAllInfo((int)$post['template_id']);
                $resultset['msg'] = html_entity_decode($resultset['msg']);
            }
            $optionRecords = $this->getmailService()->saveReminder($post, $userContainer->u_id);
            if ($optionRecords=='duplicate') {
                $container->message ='duplicate';
                echo 'duplicate';
            } elseif ($post['template_id'] > 0) {
                $container->message = 'updated';
                echo 'updated';
            } else {
                $container->message = 'inserted';
                echo 'inserted';
            }
            if ($post['template_id'] > 0) {
                $this->getAuditService()->saveToLog($post['template_id'], $userDetails['email'], 'Edit Reminder', json_encode($resultset), json_encode($post), $comment, 'Success', $trackerData['client_id']);
                $this->flashMessenger()->addMessage(array('success' => 'Reminder Template '.$container->message.' successfully!'));
            } else {
                $this->getAuditService()->saveToLog(0, $userDetails['email'], 'Add Reminder', "", json_encode($post), $comment, 'Success', $trackerData['client_id'] ?? 0);                  
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
       
        $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);  
        
        $trackerData = $this->getmailService()->getTrackerDetails($trackerId);      
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
                $post = $this->getRequest()->getPost()->toArray();
                $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
            if (isset($post['id'])) {
                    $id   = (strval(intval($post['id'])) == $post['id']) ? $post['id'] : 0;
                try {
                    $this->getmailService()->deleteNotificationCondition($id);
                    $this->getAuditService()->saveToLog($id, $userDetails['email'], 'Delete Notification Condition', "{'id':'" . $post['id'] . "' }", "{'id':'null' }", '', 'Success', $trackerData['client_id'] ?? 0);
                    $resultArr = array(
                        'responseCode' => 1,
                        'errMessage' => 'Success'
                    );
                    return $response->setContent(\Zend\Json\Json::encode($resultArr));
                }
                catch (Exception $e) {
                    $this->getAuditService()->saveToLog($id, $userDetails['email'], 'Delete Notification Condition', "", "", "", 'Fail', $trackerData['client_id']);
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
                    echo $e-getMessage();
                }
                
                if (count($getReminderData) > 0) {
                    foreach ($getReminderData as $recordId) {
                        $this->saveEmailForReminder($key, $tableName, $recordId['id']);
                    }
                }
            }
        }
        exit;
    }
    public function checkSubscriptionAction()
    {
        $response = $this->getResponse();
        $resultSet = $this->getmailService()->checkSubscription();
        $getSubscription = $this->generateSubscriptionCondition($resultSet);
        if (isset($getSubscription)) {
            foreach ($getSubscription as $key => $subscription) {
                $getSubscriptionData=array();
                try {
                    switch ($subscription['Frequency']) {
                    case 'Daily':
                        if ($subscription['Frequency_value'] == 'Daily') {
                            $getSubscriptionData = $this->getmailService()->getSubscriptionData($subscription);
                            
                        }
                        break;
                    case 'Weekly':
                        $dayOfTheWeek= explode(',', $subscription['Frequency_value']);
                        foreach ($dayOfTheWeek as $value) {
                            if (date('Y-m-d', strtotime(''.$value.' this week')) == date('Y-m-d')) {
                                $getSubscriptionData = $this->getmailService()->getSubscriptionData($subscription);
                            }
                        }
                        break;
                        
                    case 'Monthly':
                        $dayOfTheMonth= explode(',', $subscription['Frequency_value']);
                        foreach ($dayOfTheMonth as $value) {
                            if (date("j", strtotime(date('Y-m-d'))) == $value) {
                                $getSubscriptionData = $this->getmailService()->getSubscriptionData($subscription);
                            }
                        }
                        break;

                    default:
                        break;
                    }
                    if (!empty($getSubscriptionData)) {
                        $hostName=gethostname();
                        $filename = str_replace(" ", "_", $subscription['Report_name']).".csv";
                        $bolCols = true;
                        if ($bolCols) {
                            $aryCols = array_keys($getSubscriptionData[0]);
                            array_unshift($getSubscriptionData, $aryCols);
                        }
                        $csvFile = dirname(dirname(dirname(dirname(dirname(dirname(dirname(__DIR__)))))))."/data/csvForSubscription/".$filename;
                        if (!file_exists(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__DIR__)))))))."/data/csvForSubscription/")) {
                            mkdir(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__DIR__)))))))."/data/csvForSubscription/", 0777, true);
                        }
                        chmod(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__DIR__)))))))."/data/csvForSubscription/", 0777);
                        $fp = fopen($csvFile, "w");
                        foreach ($getSubscriptionData as $fields) {
                            fputcsv($fp, $fields);
                        }
                        fclose($fp);
                        $fileInfo = pathinfo($csvFile);
                        $newFileName=$fileInfo['filename']."_".$hostName."_".Date('YmdHis').".".$fileInfo['extension'];
                        $keyname =  "csvImport/".$newFileName;
                        if (file_exists(realpath($csvFile))) {
                            $awsResult=$this->forward()->dispatch(
                                'Common\FileHandling\Controller\FileHandling',
                                array(
                                        'action' => 'uploadFilesToAws',
                                        'keyname' => $keyname,
                                        'filepath' => realpath($csvFile),
                                        'del' => '1'
                                    )
                            );
                        }
                        $this->saveEmailForSubscription($key, $newFileName, $subscription['tracker_id']);
                    }
                    
                } catch (Exception $ex) {
                    echo $e-getMessage();
                }
            }
        }
        exit;
    }
    public function generateSubscriptionCondition($templateArray)
    {
        $subscriptionArray = array();
        $iArrayCount = count($templateArray);
        if ($iArrayCount > 0) {
            foreach ($templateArray as $key => $value) {
                
                $subscriptionArray[$value['notification_template_id']]['notification_template_form_id'] = $value['notification_template_form_id'];
                $subscriptionArray[$value['notification_template_id']]['tracker_id'] = $value['tracker_id'];
                $frequency = json_decode($value['notification_config'], true);
                $subscriptionArray[$value['notification_template_id']]['Frequency'] = $frequency['Frequency'];
                $subscriptionArray[$value['notification_template_id']]['Frequency_value'] = $frequency['Frequency value'];
                $subscriptionArray[$value['notification_template_id']]['Report_name'] = $frequency['Report Name'];
            }
        }
        return $subscriptionArray;
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
                $reminderArray[$value['notification_template_id']]['notification_template_condition_type'] = $value['notification_template_condition_type'];

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
    
    public function saveEmailForSubscription($templateId, $newFileName, $trackerId)
    {
        $cc = array();
        $optionRecords = $this->getmailService()->gettemplateDetail($templateId);
        $optionRecords[0]['notification_template_to'];
        $sendtoids = $optionRecords[0]['notification_template_to'];
        $formId = $optionRecords[0]['notification_template_form_id'];
        if (isset($optionRecords[0]['notification_template_cc']) && !empty($optionRecords[0]['notification_template_cc'])) {
            $cc = $optionRecords[0]['notification_template_cc'];
        }
        $setting = $this->getmailService()->getsettings();
        $subject = $optionRecords[0]['notification_template_subject'];
        $msg = $optionRecords[0]['notification_template_msg'];
        $filename=$newFileName;
        $config=$this->getServiceLocator()->get('config');
        $url=$config['appurl']['url'];
        $groupId=$config['group']['group_id'];
        $fileEncode=base64_encode($filename);
        $path=$url."email/getdownloadFile/".$trackerId."/".$templateId."/".$fileEncode;

        $htmlPart = "<html><body>Dear " ."</br>
                   <br>Report file : <a href=".$path." target='_blank' style='color: #005399;text-decoration: none;'>".$filename."</a></br></br>
                       <br> ".$msg." <br>

                   <p>
                                    Confidentiality Statement:</br>
                    <h5>This Document is the asset of BioClinica Inc. and should not be distributed other than selected personnel within BioClinica Inc.
                                     This is a system generated correspondence. Please do not reply to this email </br>
                                   </p>
                      </body></html>";
        if ($sendtoids!='') {
            $results = $this->getSwiftmailService()->saveMail($formId, '', $setting['smtp_user'], $sendtoids, $cc, $subject, $htmlPart);
            return true;
        }
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
                        $fieldsArray = $this->getmailService()->recordsCanView($trackerId, $formId, false);
                        $fieldsArray = isset($fieldsArray['fields'][$value])?$fieldsArray['fields'][$value]:array();
                        $formRecords = $this->getmailService()->formRecords($trackerId, $formId, '', '', '', $id);
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
    
    public function sendSesEmail($subject, $htmlPart, $sendTo, $sendCc)
    {
        //$sendTo=array('0'=>'arun.sb@bioclinica.com');
        $setting = $this->getmailService()->getsettings();
        $htmlBody = html_entity_decode($htmlPart);
        $config = $this->getServiceLocator()->get('Config');
        $configData= $this->getAdminService()->getConfigData('Global');
        $access_key_id = isset($configData['ses_access_key_id']) ? $configData['ses_access_key_id']:"";
        $secret_access_key = isset($configData['ses_secret_access_key']) ? $configData['ses_secret_access_key']:"";
        $sender_email = trim($setting['smtp_user']);
        $recipient_emails = $sendTo;
        $CC = array_filter($sendCc);
        $char_set = $configData['char_set'];
        $credentials = new \Aws\Credentials\Credentials($access_key_id, $secret_access_key);
        // Instantiate the client.
        $SesClient = new SesClient(
            [
                'version' => '2010-12-01',
                'region'  => $config['aws']['region'],
                'credentials' => $credentials,
                'http'    => [
                    'verify' => false
                ]
            ]
        );
   
        try {
            if (empty($CC)) {
                $result = $SesClient->sendEmail(
                    [
                    'Destination' => [
                        'ToAddresses' => $recipient_emails,
                    ],
                    'ReplyToAddresses' => [$sender_email],
                    'Source' => $sender_email,
                    'Message' => [
                        'Body' => [
                            'Html' => [
                                'Charset' => $char_set,
                                'Data' => $htmlBody,
                            ],
                            'Text' => [
                                'Charset' => $char_set,
                                'Data' => $htmlBody,
                            ],
                        ],
                        'Subject' => [
                            'Charset' => $char_set,
                            'Data' => $subject,
                        ],
                    ],
                    ]
                );                
            } else {
                $result = $SesClient->sendEmail(
                    [
                    'Destination' => [
                       'ToAddresses' => $recipient_emails,
                       'CcAddresses' => $CC,
                    ],
                    'ReplyToAddresses' => [$sender_email],
                    'Source' => $sender_email,
                    'Message' => [
                      'Body' => [
                          'Html' => [
                              'Charset' => $char_set,
                              'Data' => $htmlBody,
                          ],
                          'Text' => [
                              'Charset' => $char_set,
                              'Data' => $htmlBody,
                          ],
                      ],
                      'Subject' => [
                          'Charset' => $char_set,
                          'Data' => $subject,
                      ],
                    ],
                    ]
                );                                
            }
            return $result['MessageId'];
        } catch (AwsException $e) {
            // output error message if fails
            return $e->getMessage()."<<Time>> ".date('Y-m-d H:i:s');
        }
    }
    
    public function savesubscriptionAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;
        $container = $session->getSession('message');
       
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $post = $this->getRequest()->getPost()->toArray();
            $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $trackerData = $this->getmailService()->getTrackerDetails($trackerId);
            $comment= isset($post['comment'])?$post['comment'] : "";
            unset($post['comment']);
            $post['status']='Active';   
            if ($post['template_id'] > 0) {
                $resultset = $this->getmailService()->getTemplateAllInfo((int)$post['template_id']);
                $resultset['msg'] = html_entity_decode($resultset['msg']);
            }
            $optionRecords = $this->getmailService()->saveSubscription($post, $userContainer->u_id);
            if ($optionRecords=='duplicate') {
                $container->message ='duplicate';
                echo 'duplicate';
            } elseif ($post['template_id'] > 0) {
                $container->message = 'updated';
                echo 'updated';
            } else {
                $container->message = 'inserted';
                echo 'inserted';
            }
            if ($post['template_id'] > 0) {
                $this->getAuditService()->saveToLog($post['template_id'], $userDetails['email'], 'Edit Subscription', json_encode($resultset), json_encode($post), $comment, 'Success', $trackerData['client_id'] ?? 0);
                $this->flashMessenger()->addMessage(array('success' => 'Subscription Template '.$container->message.' successfully!'));
            } else {
                $this->getAuditService()->saveToLog(0, $userDetails['email'], 'Add Subscription', "", json_encode($post), $comment, 'Success', $trackerData['client_id'] ?? 0);                  
                $this->flashMessenger()->addMessage(array('success' => 'Subscription Template '.$container->message.' successfully!'));
            }
            unset($post['status']);
            return $response;  
        }
    }
}
