<?php

namespace ASRTRACE\Workflow\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;
use Application\Controller\IndexController;
use TheSeer\Tokenizer\Exception;

class WorkflowController extends AbstractActionController
{
    protected $_WorkflowService;
    protected $_roleService;
    protected $_auditService;
    
    public function getAuditService()
    {
        if (!$this->_auditService) {
            $sm = $this->getServiceLocator();
            $this->_auditService = $sm->get('Audit\Service');
        }
        return $this->_auditService;
    }
    public function getWorkflowService()
    {
        if (!$this->_WorkflowService) {
            $sm = $this->getServiceLocator();
            $this->_WorkflowService = $sm->get('Workflow\Service');
        }
        return $this->_WorkflowService;
    }

    public function getRoleService()
    {
        if (!$this->_roleService) {
            $sm = $this->getServiceLocator();
            $this->_roleService = $sm->get('Role\Service');
        }
        return $this->_roleService;
    }
    public function isAdmin($trackerId) 
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');
        $userDetails = $userContainer->user_details;
        $roleId = isset($userDetails['group_id'])?$userDetails['group_id']:0; 
        $trackerUserGroups = $trackerContainer->tracker_user_groups;
        $sessionGroup = isset($trackerUserGroups[$trackerId]['session_group'])?$trackerUserGroups[$trackerId]['session_group']:'';
        if ($roleId != 1 && $sessionGroup != "Administrator") {
            return $this->redirect()->toRoute('tracker');
        }
        return true;
    }
    
    public function editrecordAction()
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
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $recordId = (int)$this->getEvent()->getRouteMatch()->getParam('recordId', 0);
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    if ($formId == 0 | $recordId == 0) {
                        return $this->redirect()->toRoute('tracker');
                    }
                    $checkForm = $this->getWorkflowService()->trackerCheckForms($trackerId, $formId);
                    $formDetails = isset($checkForm['form_details'])?$checkForm['form_details']:array();
                    $responseCode = isset($checkForm['responseCode'])?$checkForm['responseCode']:0;
                    if ($responseCode == 0) {
                        return $this->redirect()->toRoute('tracker');
                    }
                    $fieldsArray = $this->getWorkflowService()->recordsCanEdit($trackerId, $formId);
                    
                    $userDetails = $userContainer->user_details;
                    $roleId = (int)$userDetails['group_id'];
                    $roleName = $userDetails['group_name'];

                    if (isset($trackerContainer->tracker_user_groups) && $role_name != 'SuperAdmin') {
                        $tracker_user_groups = $trackerContainer->tracker_user_groups;
                        $roleId = $tracker_user_groups[$trackerId]['session_group_id'];
                        $roleName = $tracker_user_groups[$trackerId]['session_group'];
                    }
                    
                    $workflowArray = $this->getWorkflowService()->trackerCheckWorkFlows($trackerId, $formId, $roleId, $roleName);
                    $checkHolidayList = $this->getWorkflowService()->checkHolidayList($trackerId);
                    
                    $holidayList = array();
                    foreach ($checkHolidayList as $holidays) {
                        $sDate = date($holidays['start_date']);
                        $eDate = date($holidays['end_date']);
                        if (strtotime($eDate) > strtotime($sDate)) {
                            $holidayList[] = date("d-M-Y", strtotime($sDate));
                            do {
                                $sDate = date('Y-m-d', strtotime($sDate .' +1 day'));
                                $holidayList[] = date("d-M-Y", strtotime($sDate));;
                            } while (strtotime($eDate) > strtotime($sDate));
                        } else {
                            $holidayList[] = date("d-M-Y", strtotime($sDate));
                        }
                    }
                    $this->layout()->setVariables(array('trackerId' => $trackerId, 'formId'   => $formId));
                    $rulesInfo = $this->getWorkflowService()->getValidationRules($formId);
                    
                    $rules='';
                    $messages='';
                    $field_name='';
                    $rules_detail='';
                    $msg_detail='';
                    for ($i=0; $i<count($rulesInfo); $i++) {
                        $rules_start = ('"'.$rulesInfo[$i]["field_name"].'":{');
                        $msg_start = ('"'.$rulesInfo[$i]["field_name"].'":{');
                        $rules1='';
                        $messages1='';
                        for ($j=$i; $j <= $i; $j++) {
                            $ruleType = $rulesInfo[$j]["rule_name"];
                            switch ($ruleType) {
                            case 'required':
                            case 'url':
                            case 'email':
                                $rulesInfo[$j]["value"]='true';
                                break;
                            case 'regex':
                                if ($rulesInfo[$j]["value"]!='') {
                                    if (preg_match("/(^\\/)/", $rulesInfo[$j]["value"]) > 0 ) {
                                        $rulesInfo[$j]["value"]=$rulesInfo[$j]["value"];
                                    } else {
                                        $rulesInfo[$j]["value"]="/".$rulesInfo[$j]["value"];
                                    }
                                    if (preg_match("/\\/$/", $rulesInfo[$j]["value"]) > 0) {
                                        $rulesInfo[$j]["value"] = $rulesInfo[$j]["value"];
                                    } else {
                                        $rulesInfo[$j]["value"] = $rulesInfo[$j]["value"]."/";
                                    }
                                } else {
                                    $rulesInfo[$j]["value"] = "/^$/";
                                }
                                break;
                            default:
                                $rulesInfo[$j]["value"] = $rulesInfo[$j]["value"];
                            }
                            if ($rulesInfo[$j]["field_name"] != $field_name) {
                                $rules_detail = $rulesInfo[$j]["rule_name"].':'.$rulesInfo[$j]["value"];
                                $msg_detail = $rulesInfo[$j]["rule_name"].':"'.$rulesInfo[$j]["message"].'"';
                            } else {
                                $rules_detail= ($rules_detail !=''? $rules_detail.','.$rulesInfo[$j]["rule_name"].':'.$rulesInfo[$j]["value"] :$rulesInfo[$j]["rule_name"].':'.$rulesInfo[$j]["value"]);
                                $msg_detail= ($msg_detail !=''? $msg_detail.','.$rulesInfo[$j]["rule_name"].':"'.$rulesInfo[$j]["message"].'"' :$rulesInfo[$j]["rule_name"].':"'.$rulesInfo[$j]["message"].'"');
                            }
                            $rules_end= ('}');
                            $msg_end= ('}');
                            $rules1=$rules_start.$rules_detail.$rules_end;
                            $messages1=$msg_start.$msg_detail.$msg_end;
                            $field_name = $rulesInfo[$j]["field_name"];
                        }
                        if (isset($rulesInfo[$j]["field_name"])) {
                            if ($rulesInfo[$j]["field_name"] != $field_name) {
                                $rules= ($rules !=''? $rules.','. $rules1 :$rules1);
                                $messages= ($messages !=''? $messages.','. $messages1 :$messages1);
                            }
                        }
                        if (!isset($rulesInfo[$j]["field_name"])) {
                            $rules= ($rules !=''? $rules.','. $rules1 :$rules1);
                            $messages= ($messages !=''? $messages.','. $messages1 :$messages1);
                        }
                    }
                    $script = htmlentities(
                        ' 
                    $("#workflowForm").validate({
                        errorElement: "div",
                        onkeyup: function(element) {$(element).valid()},
                        onfocusout: function(element) {$(element).valid()},
                        onclick: function(element) {$(element).valid()},
                        onsubmit: function(element) {$(element).valid()},
                        onchange: function(element) {$(element).valid()},
                        onselect: function(element) {$(element).valid()},
                        onClose: function(element) {$(element).valid()},
                        errorPlacement: function(error, element) {
                           if (element[0].type == "checkbox") {
                                error.appendTo(element.parent("div").siblings(":last"));
                            } else {
                                error.insertAfter(element);
                            }
                        },
                    rules: {'.$rules.'},
                    messages:{'.$messages.'},
                    submitHandler: function(form) {
                        
                        submitAfterValidate();
                      }
                    });
                    
                    $.validator.addMethod("regex", function(value, element, regexpr) {
                        return regexpr.test(value);
                    });
                    $.validator.addMethod("depends", function(value, element,depends_text) {
                        if ($("#"+depends_text.id).val()=="" && value=="Yes") {
                            $("#"+element.id).val("");
                            return false;
                        } else {
                            return true;
                        }
                    });
                    $.validator.addMethod("dates",function(value, element) {
                        var dateReg = /^(\d{4})\-(\d{2})\-(\d{2}) (\d{2}):(\d{2})$/;
                        return value.match(dateReg);
                    },
                    "Invalid date"
                    );
                    $.validator.addMethod("Dates",function(value, element) {
                        var inputDate=new Date(value);
                        var inDate=toDate(inputDate);
                        var dateReg = /^(\d{2})\-(\d{2})\-(\d{4})$/;
                        return inDate.match(dateReg);
                    },
                    "Invalid date"
                    );
                    $.validator.addMethod("maxDate", function(value,elemen,param) {
                        if (value=="") {
                            return true;
                        }
                       var inDate=toformatdatetime(value,"date");
                       var curDate = toformatdatetime(new Date(),"date");
                       inDate= Date.parse(inDate)/1000;    //change date time to unix time stamp
                       curDate= Date.parse(curDate)/1000;  //change date time to unix time stamp
                       if (inDate <= curDate)
                       return true;
                       return false;
                    },"Invalid DateTime!");
                    $.validator.addMethod("maxDateTime", function(value, elemen,param) {
                      
                       var inDate=toformatdatetime(value,"datetime");
                       var curDate = toformatdatetime(new Date(),"datetime");
                       inDate= Date.parse(inDate)/1000;    //change date time to unix time stamp
                       curDate= Date.parse(curDate)/1000;  //change date time to unix time stamp
                       if (inDate <= curDate)return true;
                       return false;
                    },"Invalid DateTime!");

                     $.validator.addMethod("minDateTime", function(value, elemen,param) {
                       var inputDate=new Date(value);
                       var inDate=toDateTime(inputDate);
                       var curDate = moment().subtract(param, "day").format("YYYY-MM-DD HH:mm");
                       if (inDate <= curDate) return true;
                       return false;
                    },"Invalid DateTime!"); '
                    );
                    $type = $this->getEvent()->getRouteMatch()->getParam('type', 'all');
                    $filter = $this->getEvent()->getRouteMatch()->getParam('filter', 'all');
                    $workflowOpen =  $this->getEvent()->getRouteMatch()->getParam('workflow', 'all');
                    
                    $configData = $this->getWorkflowService()->getconfigDataByForm($formId);
                    $configData = array_column($configData, 'config_value', 'config_key');
                    
                    return new ViewModel(
                        array(
                            'action_id' => $formId,
                            'tracker_id' => $trackerId,
                            'form_details' => $formDetails,
                            'fields_array_val' => $fieldsArray,
                            'record_id' => $recordId,
                            'validation_script'=>$script,
                            'workflow' =>$workflowArray,
                            'label' => $type,
                            'workflowOpen' => $workflowOpen,
                            'dateFormat' => isset($configData['dateFormat'])?$configData['dateFormat']:'DD-MMM-YYYY',
                            'dateTimeFormat' => isset($configData['dateTimeFormat'])?$configData['dateTimeFormat']:'DD-MMM-YYYY hh:mm A',
                            'holidayList' => $holidayList,
                            'type' => $type,
                            'filter' => $filter
                        )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
        exit;
    }
    
    public function viewrecordAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user'); 
        $trackerContainer = $session->getSession('tracker');  
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('trackerId', 0);

            $userDetails = $userContainer->user_details;
            $role_id = $userDetails['group_id'];
            $role_name = $userDetails['group_name'];
            
            if (isset($trackerContainer->tracker_user_groups) && $role_name != 'SuperAdmin') {
                $tracker_user_groups = $trackerContainer->tracker_user_groups;
                $role_name = $tracker_user_groups[$trackerId]['session_group'];
                $role_id = $tracker_user_groups[$trackerId]['session_group_id'];
            }
            $actionId = $this->params()->fromRoute('action_id', 0);
            $subactionId = $this->params()->fromRoute('subaction_id', 0);
            $userDetails = $userContainer->user_details;

            $trackerIds = $trackerContainer->tracker_ids;
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    if ($actionId == 0 || $subactionId == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    if ($subactionId == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId, 'action_id' => $actionId));
                    }
                    $checkTableArray = $this->getTrackerService()->trackerCheckForms($trackerId, $actionId, $subactionId);
                    $formDetails = $checkTableArray['form_details'];
                    $responseCode = $checkTableArray['responseCode'];
                    if ($responseCode == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    $workflowArray = $this->getTrackerService()->trackerCheckWorkFlows($trackerId, $actionId, $role_id, $role_name);
                   
                    $recordData = $this->getTrackerService()->formRecords($trackerId, $actionId, '', '', '', $subactionId);
                    $fieldsArray = $this->getTrackerService()->allFieldsOfForm($trackerId, $actionId);

                    $canReadArray=$this->getWorkflowService()->getCanReadAndCanUpdateAccessAllWorkflow($actionId, $role_id, $role_name);
                    $configData=$this->getWorkflowService()->getconfigDataByForm($actionId);
                    $configData = array_column($configData, 'config_value', 'config_key');
                    
                    $fieldDetails=array();
                    foreach ($fieldsArray as $key => $value) {
                        $fieldDetails[$value['workflow_name']][$value['field_name']]['field_name']=$value['field_name'];
                        $fieldDetails[$value['workflow_name']][$value['field_name']]['fieldlabel']=$value['fieldlabel'];
                        $fieldDetails[$value['workflow_name']][$value['field_name']]['field_type']=$value['field_type'];

                        $fieldDetails[$value['workflow_name']][$value['field_name']]['dateFormat'] = isset($configData['dateFormat'])?$configData['dateFormat']:"";
                        $fieldDetails[$value['workflow_name']][$value['field_name']]['dateTimeFormat'] = isset($configData['dateTimeFormat'])?$configData['dateTimeFormat']:"";
                        $fieldDetails[$value['workflow_name']][$value['field_name']]['fieldValue']=isset($recordData['form_data'][0][$value['field_name']])?$recordData['form_data'][0][$value['field_name']]:"";
                        foreach ($canReadArray as $canRead) {
                            if (isset($canRead['workflow_id']) && $canRead['workflow_id']==$value['workflow_id']) {
                                $fieldDetails[$value['workflow_name']]['can_read']=$canRead['can_read'];
                            } else if ($role_name == 'SuperAdmin'||$role_name == 'Administrator') {
                                $fieldDetails[$value['workflow_name']]['can_read']="Yes";
                            }
                        }
                                            
                        if ($value['code_list_id']!=0) {
                            $optionArray=array();
                            $optionArray['optionValue']=$value['optionValue'];
                            $optionArray['optionLabel']=$value['optionLabel'];
                            $optionArray['kpi']=$value['kpi'];
                            $fieldDetails[$value['workflow_name']][$value['field_name']]['options'][]=$optionArray;
                        }
                        if ($value['field_type']=='Check Box') {
                            $fieldDetails[$value['workflow_name']][$value['field_name']]['comment']=isset($recordData['form_data'][0][$value['field_name']])?$recordData['form_data'][0]['comment_checkbox_'.$value['field_name']]:"";
                        }
                    }
                    
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => @$trackerId,
                        'form_id'   => $actionId
                            )
                    );
                    // $queryString = filter_input(INPUT_SERVER, 'QUERY_STRING');
                    // parse_str(filter_input(INPUT_SERVER, 'QUERY_STRING'), $queryArray);
                    // $type = isset($queryArray['type'])?trim($queryArray['type']):"all";
                    // $filter = isset($queryArray['filter'])?trim($queryArray['filter']):"all";
                    // $queryString='?type='.$type.'&filter='.$filter;
                    $type = $this->getEvent()->getRouteMatch()->getParam('type', 'all');
                    $filter = $this->getEvent()->getRouteMatch()->getParam('filter', 'all');
                    return new ViewModel(
                        array(
                        'action_id' => $actionId,
                        'tracker_id' => $trackerId,
                        'form_details' => $formDetails,
                        'fields_array_val' => $fieldsArray,
                        'record_id' => $subactionId,
                        'user_name' => $userDetails['u_name'],
                        'workflow' => $workflowArray,
                        'field_details' => $fieldDetails,
                        'filter' => $filter,
                        'type' => $type,
                        )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
        exit;
    }
    
    public function saveeditrecordAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $applicationController = new \Application\Controller\IndexController;
        $tracker_id = $this->params()->fromRoute('trackerId', 0);
        $action_id = $this->params()->fromRoute('action_id', 0);
        $subaction_id = $this->params()->fromRoute('subaction_id', 0);
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if ($post) {
            $dataArr = $post;
            $comment = $dataArr['addcomment'];
            unset($dataArr['addcomment']);
            $referal = $dataArr['referal'];
            $respJson = 0;
            if (!isset($userContainer->u_id)) {
                foreach ($dataArr as $key => $value) {
                    if (is_array($value)) {
                        $optionsArray = array();
                        foreach ($value as $keyCheck => $valueCheck) {
                            $valuesCheckboxValues = json_decode($valueCheck, true);
                            $optionsArray[] = $valuesCheckboxValues['option'];
                        }
                        $values = implode(',', $optionsArray);
                        $applicationController->saveToLogFile($subaction_id, $key, $values, '', 'Edit Record', '', $tracker_id, $action_id, 0, IP, $comment, 'session time out:failure');
                    } else {
                        $value = addslashes($value);
                        $applicationController->saveToLogFile($subaction_id, $key, $value, '', 'Edit Record', '', $tracker_id, $action_id, 0, IP, $comment, 'session time out:failure');
                    }
                }
                return $this->redirect()->toRoute('home');
            } else {
                $folderPath = "attachment/attach_".$tracker_id.'_'.$action_id;
                if (!file_exists($folderPath)) {
                    mkdir("$folderPath", 0777, true);
                }
                $filename=array();
                // $filepath should be absolute path to a file on disk
                $uploadToAws= new AwsController();
                if (is_array($_FILES)) {
                    foreach ($_FILES as $fileName=>$fileDetails) {
                        if (!empty($fileDetails['name'])) {
                            $dataArr[$fileName]=$fileDetails['name'];
                            $file = pathinfo($fileDetails['name']);

                            $filename[$fileName]= $file['filename'].'_'.time().'.'.$file['extension'];
                            $keyname = $folderPath.'/'.$filename[$fileName];
                            $filepath = $fileDetails['tmp_name'];
                            $this->forward()->dispatch(
                                'Tracker\Controller\Aws',
                                array(
                                'action' => 'uploadFilesToAws',
                                'keyname' => $keyname,
                                'filepath' => $filepath,
                                )
                            );
                        } else {
                            $filename[] =$dataArr['hidden_file_'.$fileName];
                        }
                    }
                }
                $userDetails = $userContainer->user_details;
                $recordArray = $this->getTrackerService()->formRecordsByID($tracker_id, $action_id, $subaction_id);
                $resArr = $this->getTrackerService()->saveeditrecord($dataArr, $tracker_id, $action_id, $subaction_id, $filename);
                foreach ($dataArr as $key => $value) {
                    if (isset($recordArray[$key])) {
                        if ($recordArray[$key] != $dataArr[$key]) {
                            if (is_array($value)) {
                                $optionsArray = array();
                                foreach ($value as $keyCheck => $valueCheck) {
                                    $valueCheck=stripslashes(html_entity_decode($valueCheck));
                                    $valuesCheckboxValues = json_decode($valueCheck, true);
                                    $optionsArray[] = $valuesCheckboxValues['option'];
                                }
                                $values = implode(',', $optionsArray);
                                $applicationController->saveToLogFile($subaction_id, $key, $values, $recordArray[$key], 'Record Edited', $userDetails['u_name'], $tracker_id, $action_id, $subaction_id, IP, $comment, 'Success');
                            } else {
                                $value = addslashes($value);
                                $applicationController->saveToLogFile($subaction_id, $key, $value, $recordArray[$key], 'Record Edited', $userDetails['u_name'], $tracker_id, $action_id, $subaction_id, IP, $comment, 'Success');
                            }
                        }
                    }
                }
                foreach ($resArr['templateids'] as $templateids) {
                    $this->forward()->dispatch(
                        'Notification\Controller\Email',
                        array(
                        'action' => 'sendemail',
                        'param1' => $templateids,
                        'param2' => 'form_' . $tracker_id . '_' . $action_id,
                        'param3' => $subaction_id,
                        )
                    );
                }
                if (isset($referal) && !empty($referal)) {
                    return $this->redirect()->toUrl($referal);
                } else {
                    return $this->redirect()->toRoute('tracker', array('action' => 'form', 'tracker_id' => $tracker_id, 'action_id' => $action_id));
                }
            }
        } else {
            $userDetails = $userContainer->user_details;
            $applicationController->saveToLogFile($subaction_id, '', '', '', 'Edit Record', $userDetails['u_name'], $tracker_id, $action_id, 0, IP, '', 'Postdata is blank:failure');
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
        }
        exit;
    }
    
    
    
    public function getFieldsByWorkflowIdAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker'); 
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = $this->params()->fromRoute('trackerId', 0);
            $userDetails = $userSession->user_details;
            $role_id = $userDetails['group_id'];
            $role_name = $userDetails['group_name'];
            if (isset($trackerContainer->tracker_user_groups) && $role_name != 'SuperAdmin') {
                $tracker_user_groups = $trackerContainer->tracker_user_groups;
                $role_name = $tracker_user_groups[$trackerId]['session_group'];
                $role_id = $tracker_user_groups[$trackerId]['session_group_id'];
            }

            $formId = $this->params()->fromRoute('formId', 0);
            $workflowId = $this->params()->fromRoute('workflowId', 0);
            $recordId = $this->params()->fromRoute('recordId', 0);
            $tableName="form_".$trackerId."_".$formId;
            $fieldsArray=$this->getWorkflowService()->getFieldsBasedOnWorkflow($workflowId);
            
            $canUpdate=$this->getWorkflowService()->getCanReadAndCanUpdateAccess($workflowId, $role_id, $role_name);
            
            $recordData=$this->getWorkflowService()->getRecordDatabyId($tableName, $recordId);
            if (count($recordData) > 0) {
                foreach ($recordData[0] as $key => $value) {
                    if (Date('Y-m-d', strtotime($value)) == $value) {
                        $recordData[0][$key]=Date('d-M-Y', strtotime($value)); 
                    }
                }                
            }
            $configData=$this->getWorkflowService()->getconfigDataByForm($formId);
            $configData = array_column($configData, 'config_value', 'config_key');
            $workflowRule=$this->workflowRule($formId, $workflowId, $recordId, $tableName, $recordData[0]);
            
            $fieldDetails=array();
            foreach ($fieldsArray as $key => $value) {
                $fieldDetails[$value['field_name']]['field_name']=$value['field_name'];
                $fieldDetails[$value['field_name']]['fieldlabel']=$value['fieldlabel'];
                $fieldDetails[$value['field_name']]['field_type']=$value['field_type'];
                $fieldDetails[$value['field_name']]['validation_required']=$value['validation_required'];
                $fieldDetails[$value['field_name']]['formula']=$value['formula'];
                $fieldDetails[$value['field_name']]['formula_dependent']=$value['formula_dependent'];

                $fieldDetails[$value['field_name']]['dateFormat'] = isset($configData['dateFormat'])?$configData['dateFormat']:"";
                $fieldDetails[$value['field_name']]['dateTimeFormat'] = isset($configData['dateTimeFormat'])?$configData['dateTimeFormat']:"";
                $fieldDetails[$value['field_name']]['can_update']=isset($canUpdate[0]['can_update'])?$canUpdate[0]['can_update']:'No';
                $fieldDetails[$value['field_name']]['can_read']=isset($canUpdate[0]['can_read'])?$canUpdate[0]['can_read']:'No';
                $fieldDetails[$value['field_name']]['fieldValue']=isset($recordData[0][$value['field_name']])?$recordData[0][$value['field_name']]:"";
                               
                if ($value['code_list_id']!=0) {
                    $optionArray=array();
                    $optionArray['optionValue']=$value['optionValue'];
                    $optionArray['optionLabel']=$value['optionLabel'];
                    $optionArray['kpi']=$value['kpi'];
                    $fieldDetails[$value['field_name']]['options'][]=$optionArray;
                }
                if ($value['field_type']=='Check Box') {
                    $fieldDetails[$value['field_name']]['comment']=isset($recordData[0][$value['field_name']])?$recordData[0]['comment_checkbox_'.$value['field_name']]:"";
                }
                if ($value['field_type']=='User Role' && $value['formula']!='CurrentUser') {
                    $userList=$this->getWorkflowService()->getUserList($value['formula']);
                    $optionArray=array();
                    foreach ($userList as $key => $user) {
                        $optionArray[$key]['u_id']=$user['u_id'];
                        $optionArray[$key]['u_name']=$user['u_name'];
                    }
                    $fieldDetails[$value['field_name']]['options']=$optionArray;
                }

                if (!empty($workflowRule['hide']) && in_array($value['field_id'], $workflowRule['hide'])) {
                    // $fieldDetails[$value['field_name']]['field_type']='hidden'; 
                    // $fieldDetails[$value['field_name']]['can_update']='No';
                    unset($fieldDetails[$value['field_name']]);
                }
                if (!empty($workflowRule['edit'])) {
                    $fieldDetails[$value['field_name']]['can_update']=$workflowRule['edit'][$workflowId];
                } 
            }
            $recordData = array_diff_key($recordData[0], $fieldDetails);
            $pattern = '/^comment_checkbox_/';
            foreach ($recordData as $key => $value) {
                if (preg_match($pattern, $key)) {
                    unset($recordData[$key]);
                }
            }
            $otherWorkflowFields=$this->getWorkflowService()->otherWorkflowFields($formId, $workflowId);
            foreach ($otherWorkflowFields as $key => $value) {
                $fieldDetails[$value['field_name']]['field_name']=$value['field_name'];
                $fieldDetails[$value['field_name']]['field_type']=$value['field_type'];
                $fieldDetails[$value['field_name']]['formula']=$value['formula'];
                $fieldDetails[$value['field_name']]['formula_dependent']=$value['formula_dependent'];
                $fieldDetails[$value['field_name']]['display']='No';
                unset($recordData[$value['field_name']]);
            } 
            if (!empty($recordData)) {
                if (isset($recordData['id'])) {
                    unset($recordData['id']);
                }
                if (isset($recordData['created_by'])) {
                    unset($recordData['created_by']);
                }
                if (isset($recordData['last_updated_by'])) {
                    unset($recordData['last_updated_by']);
                }
                if (isset($recordData['is_deleted'])) {
                    unset($recordData['is_deleted']);
                }
                if (isset($recordData['created_date_time'])) {
                    unset($recordData['created_date_time']);
                }
                $fieldDetails['hidden'][]=$recordData;
            } 
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_200);
            //echo "<pre>"; print_r($fieldDetails);
            return $response->setContent(\Zend\Json\Json::encode($fieldDetails));
        }
    }

    public function workflowRule($formId,$workflowId,$recordId,$tableName,$recordData)
    {
        $conditions=$this->getWorkflowService()->getWorkflowRuleWhen($formId, $workflowId);
        $hideWorkflowRule=array();
        $editWorkflowRule=array();
        $condition='';
        $fieldName='';
        if (!empty($conditions)) {
            foreach ($conditions as $key => $condition) {
                if ($recordData[$condition['field_name']] == $condition['value']) {
                    if ($fieldName!='') {
                        $fieldName=$fieldName." ".$conditions[$key]['operator'];
                    }
                    $fieldName = $fieldName." ". $condition['field_name']." ".$condition['comparision_op']." '".$condition['value']."'" ;
                } else {
                    unset($conditions[$key]);
                }
            }
            if ($fieldName == '') {
                $fieldName=1;
            }
            $fieldName= "(".$fieldName.")";
            $getQueryData=$this->getWorkflowService()->getQuerydata($fieldName, $recordId, $tableName);
            foreach ($conditions as $key => $condition) {
                switch ($condition['action']) {
                case 'Hide Fields':
                    if (!empty($getQueryData)) {
                        $ruleActionArray = $this->getWorkflowService()->getRuleAction($condition['rule_id']);
                        
                        foreach ($ruleActionArray as $ruleAction) {
                            if ($ruleAction['action_workflow_id']==$workflowId) {
                                switch ($ruleAction['action']) {
                                case 'Hide Fields':
                                    array_push($hideWorkflowRule, $ruleAction['action_fields']);
                                    break;
                                case 'Edit Workflow':
                                    $editWorkflowRule[$workflowId]= 'Yes';
                                    break;
                                }
                            }
                        }
                    }
                    break;
                case 'Edit Workflow':
                    if (empty($getQueryData)) {
                        $editWorkflowRule[$workflowId]= 'No';
                    }
                    break;
                }
            }
        }
        $workflowRuleArray['hide']=array_unique($hideWorkflowRule);
        $workflowRuleArray['edit']=array_unique($editWorkflowRule);
        return $workflowRuleArray;
    }
  
    public function updateAndSaveWorkflowDataAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $userDetails = $userSession->user_details;
        $applicationController = new \Application\Controller\IndexController;
        $flag=0;
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = $this->params()->fromRoute('trackerId', 0);
            $formId = $this->params()->fromRoute('formId', 0);
            $workflowId = $this->params()->fromRoute('workflowId', 0);
            $recordId = $this->params()->fromRoute('recordId', 0);

            $tableName="form_".$trackerId."_".$formId;
            $request = $this->getRequest();
            $response = $this->getResponse();

            $postData = array_merge_recursive($request->getPost()->toArray(), $request->getFiles()->toArray());
            
            $dataArr = [];
            $checkboxArray = isset($postData['checkbox'])?json_decode($postData['checkbox'], true):'';
            $content = isset($postData['post'])?json_decode($postData['post']):"";
            if (!empty($content)) {
                foreach ($content as $key => $innerArray) {
                    $dateValue = '';
                    if (Date('j-M-Y', strtotime($innerArray->value)) == $innerArray->value || Date('d-M-Y', strtotime($innerArray->value)) == $innerArray->value) {
                        $dateValue = Date('Y-m-d', strtotime($innerArray->value)); 
                    } else {
                        $dateValue = $innerArray->value;
                    }
                    
                    if (array_key_exists($innerArray->name, $dataArr)) {
                        $dataArr[$innerArray->name]=$dataArr[$innerArray->name].",".$dateValue;
                    } else {
                        $dataArr[$innerArray->name]=$dateValue;
                    }
                }
            }
            
            $checkboxCheckedArray = [];
            if (!empty($checkboxArray)) {
                $i=0;
                foreach ($checkboxArray as $key => $value) {
                    $checkboxCheckedArray[$key]=$value;
                    $checkboxCheckedArray[$key]['record_id']=$recordId;
                    $checkboxCheckedArray[$key]['form_id']=$formId;
                    $checkboxCheckedArray[$key]['user_id']=$userSession->u_id;
                    if ($value['checked']=='Unchecked') { 
                        if (!array_key_exists($value['field_name'], $dataArr)) { 
                            $dataArr[$value['field_name']]=''; 
                            $dataArr['comment_checkbox_'.$value['field_name']]='';
                        }  
                    }
                    if (!array_key_exists('comment_checkbox_'.$value['field_name'], $dataArr)) {
                        $dataArr['comment_checkbox_'.$value['field_name']]='';
                        
                    }              
                }
            }
            $hostName=gethostname();
            foreach ($postData as $key => $allData) {
                if ($key != 'post' && $key != 'checkbox') {
                    if ($allData!='undefined') {
                        $fileName = isset($allData['name'])?$allData['name']:"";
                        $file=isset($allData['tmp_name'])?$allData['tmp_name']:"";

                        if (file_exists($file)) {        
                            $fileInfo = pathinfo($fileName);
                            $newFileName=$fileInfo['filename']."_".$hostName."_".Date('YmdHis').".".$fileInfo['extension'];
                            $dataArr[$key] = $newFileName;
                            $keyname =  "workflowFiles/".$newFileName;
                            $awsResult=$this->forward()->dispatch(
                                'Tracker\Controller\Aws',
                                array(
                                    'action' => 'uploadFilesToAws',
                                    'keyname' => $keyname,
                                    'filepath' => $file,
                                    'del' => '1'
                                )
                            );
                        }
                    }       
                }
            }
           
            /**
             * Getting old data from form Table 
            */
            $columnName=array_keys($dataArr);
           
            $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
            echo $tableName; die;
            $workflowOldData=$this->getWorkflowService()->getAllWorkflowFieldsData($tableName, $recordId, $columnName);
             /**
             * Check notification and send 
            */
            $notificationConditionArray = $this->getWorkflowService()->checkNotification($formId);
           
            /**
            * Converting old form data into Json form for the audit Trails 
            */
            $workflowOldDataJSON = json_encode(isset($workflowOldData[0])?$workflowOldData[0]:'data', JSON_UNESCAPED_SLASHES);
            $dataArrJSON = json_encode($dataArr, JSON_UNESCAPED_SLASHES);
            /**
            * Update form data 
            */
            $updateResult=$this->getWorkflowService()->updateAndSaveWorkflowData($tableName, $recordId, $dataArr);
            
            $notificationCheck = $this->notificationCheck($notificationConditionArray, $dataArr, $tableName, $recordId, $workflowOldData[0]);
            /**
             * Audit trail update 
            */
            if (is_object($updateResult)) {
                $checkBoxResult=$this->getWorkflowService()->updateAndSaveCheckBoxData($recordId, $checkboxCheckedArray);
                $applicationController->saveToLogFile($workflowId, isset($userDetails['email'])?$userDetails['email']:'', "Workflow edit Record", $workflowOldDataJSON, $dataArrJSON, "Workflow Update", "Success", $trackerData['client_id']);
                $updateResult="Data updated successfully.";
                $flag=1;
            } else {
                $applicationController->saveToLogFile($workflowId, isset($userDetails['email'])?$userDetails['email']:'', "Workflow edit Record", $workflowOldDataJSON, $dataArrJSON, $updateResult, "Failed", $trackerData['client_id']);
                $updateResult="Column mismatch in db";
            }
            $resultArray=array();
            $resultArray['result']=$updateResult;
            $resultArray['flag']=$flag;
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_200);
            return $response->setContent(\Zend\Json\Json::encode($resultArray));
        }
    }

    function notificationCheck($notificationConditionArray, $newDataArr, $tableName, $recordId, $oldDataArr)
    {
        if (!empty($notificationConditionArray)) {
            $notificationArray=array();
            $condition='';
            foreach ($notificationConditionArray as $con) {
                $notificationArray[$con['notification_template_id']]['name'] = $con['notification_template_name'];
                $notificationArray[$con['notification_template_id']]['subject'] = $con['notification_template_subject'];
                $notificationArray[$con['notification_template_id']]['msg'] = $con['notification_template_msg'];
                $notificationArray[$con['notification_template_id']]['to'] = $con['notification_template_to'];
                $notificationArray[$con['notification_template_id']]['oprand'] = $con['notification_template_condition_type'];
                $notificationArray[$con['notification_template_id']]['cc'] = $con['notification_template_cc'];
                
                if ($con['condition_operand'] == 'Changes To') {
                    if (($newDataArr[$con['condition_field_name']] != $oldDataArr[$con['condition_field_name']]) && isset($newDataArr[$con['condition_field_name']])) {
                        
                        if (($newDataArr[$con['condition_field_name']] == $con['condition_value'])) {
                            $condition = 'TRUE';
                        } else {
                            $condition = 'FALSE';
                        }
                    } else {
                        $condition = 'FALSE';
                    }
                } else {
                    $condition = isset($newDataArr[$con['condition_field_name']])? $newDataArr[$con['condition_field_name']] : "1";
                    $condition = "'".$condition."' ".$con['condition_operand']." '".$con['condition_value']."'";
                }
                $notificationArray[$con['notification_template_id']]['condition'][] = $condition;
                
            }
            
            if (!empty($notificationArray)) {
                foreach ($notificationArray as $key => $nCondition) {
                    if ($nCondition['oprand'] == 'AND') {
                        $oprand = '&&';
                    } else {
                        $oprand = '||';
                    }
                    $nCon='';
                    foreach ($nCondition['condition'] as $condition) {
                        if ($nCon == '') {
                            $nCon = $condition;
                        } else {
                            $nCon = $nCon." ".$oprand." ".$condition;
                        }
                    }
                    
                    try {
                        if (eval("return $nCon ;")) {
                            $this->forward()->dispatch(
                                'Notification\Controller\Email',
                                array(
                                'action' => 'sendemail',
                                'param1' => $key,
                                'param2' => $tableName,
                                'param3' => $recordId,
                                )
                            );
                        }
                    } catch (\Exception $ex) {
                        return 'Error: exception '.get_class($ex).', '.$ex->getMessage().'.';
                    }
                }
            }
        }
    }
}
