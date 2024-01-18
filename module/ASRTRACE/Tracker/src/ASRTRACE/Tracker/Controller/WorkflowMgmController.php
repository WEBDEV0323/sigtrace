<?php

namespace ASRTRACE\Tracker\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;
use Application\Controller\IndexController;
use TheSeer\Tokenizer\Exception;

class WorkflowMgmController extends AbstractActionController
{
    protected $_WorkflowMgmService;
    protected $_roleMapper;

    public function getWorkflowMgmService()
    {
        if (!$this->_WorkflowMgmService) {
            $sm = $this->getServiceLocator();
            $this->_WorkflowMgmService = $sm->get('Tracker\Model\WorkflowMgmModule');
        }
        return $this->_WorkflowMgmService;
    }

    public function getRoleService()
    {
        if (!$this->_roleMapper) {
            $sm = $this->getServiceLocator();
            $this->_roleMapper = $sm->get('Role\Model\Role');
        }
        return $this->_roleMapper;
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
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $userDetails = $userSession->user_details;
            $role_id = $userDetails['group_id'];
            $role_name = $userDetails['group_name'];
            if (isset($trackerContainer->tracker_user_groups) && $role_name != 'SuperAdmin') {
                $tracker_user_groups = $trackerContainer->tracker_user_groups;
                $role_name = $tracker_user_groups[$trackerId]['session_group'];
                $role_id = $tracker_user_groups[$trackerId]['session_group_id'];
            }

            $formId = $this->params()->fromRoute('form_id', 0);
            $workflowId = $this->params()->fromRoute('workflow_id', 0);
            $recordId = $this->params()->fromRoute('record_id', 0);
            $tableName="form_".$trackerId."_".$formId;
            $fieldsArray=$this->getWorkflowMgmService()->getFieldsBasedOnWorkflow($workflowId);
            
            $canUpdate=$this->getWorkflowMgmService()->getCanReadAndCanUpdateAccess($workflowId, $role_id, $role_name);
            
            $recordData=$this->getWorkflowMgmService()->getRecordDatabyId($tableName, $recordId);
            if (count($recordData) > 0) {
                foreach ($recordData[0] as $key => $value) {
                    if (Date('Y-m-d', strtotime($value)) == $value) {
                        $recordData[0][$key]=Date('d-M-Y', strtotime($value)); 
                    }
                }                
            }
            $configData=$this->getWorkflowMgmService()->getconfigDataByForm($formId);
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
                    $userList=$this->getWorkflowMgmService()->getUserList($value['formula']);
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
            $otherWorkflowFields=$this->getWorkflowMgmService()->otherWorkflowFields($formId, $workflowId);
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
                if (isset($recordData['created_by']) || $recordData['created_by']=='') {
                    unset($recordData['created_by']);
                }
                if (isset($recordData['last_updated_by']) || $recordData['last_updated_by']=='') {
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
        $conditions=$this->getWorkflowMgmService()->getWorkflowRuleWhen($formId, $workflowId);
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
            $getQueryData=$this->getWorkflowMgmService()->getQuerydata($fieldName, $recordId, $tableName);
            foreach ($conditions as $key => $condition) {
                switch ($condition['action']) {
                case 'Hide Fields':
                    if (!empty($getQueryData)) {
                        $ruleActionArray = $this->getWorkflowMgmService()->getRuleAction($condition['rule_id']);
                        
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
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $formId = $this->params()->fromRoute('form_id', 0);
            $workflowId = $this->params()->fromRoute('workflow_id', 0);
            $recordId = $this->params()->fromRoute('record_id', 0);

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
            $workflowOldData=$this->getWorkflowMgmService()->getAllWorkflowFieldsData($tableName, $recordId, $columnName);
             /**
             * Check notification and send 
            */
            $notificationConditionArray = $this->getWorkflowMgmService()->checkNotification($formId);
           
            /**
            * Converting old form data into Json form for the audit Trails 
            */
            $workflowOldDataJSON = json_encode(isset($workflowOldData[0])?$workflowOldData[0]:'data', JSON_UNESCAPED_SLASHES);
            $dataArrJSON = json_encode($dataArr, JSON_UNESCAPED_SLASHES);
            /**
            * Update form data 
            */
            $updateResult=$this->getWorkflowMgmService()->updateAndSaveWorkflowData($tableName, $recordId, $dataArr);
            
            $notificationCheck = $this->notificationCheck($notificationConditionArray, $dataArr, $tableName, $recordId, $workflowOldData[0]);
            /**
             * Audit trail update 
            */
            if (is_object($updateResult)) {
                $checkBoxResult=$this->getWorkflowMgmService()->updateAndSaveCheckBoxData($recordId, $checkboxCheckedArray);
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
