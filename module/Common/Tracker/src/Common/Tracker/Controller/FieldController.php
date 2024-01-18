<?php
namespace Common\Tracker\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;

class FieldController extends AbstractActionController
{
    protected $_modelService;
    protected $_auditService;
    
    public function getAuditService()
    {
        if (!$this->_auditService) {
            $sm = $this->getServiceLocator();
            $this->_auditService = $sm->get('Audit\Service');
        }
        return $this->_auditService;
    }

    public function getModelService()
    {
        if (!$this->_modelService) {
            $sm = $this->getServiceLocator();
            $this->_modelService = $sm->get('Field\Model');
        }
        return $this->_modelService;
    }
    public function isHavingTrackerAccess($trackerId)
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;
        $isSuperAdmin = isset($userDetails['isSuperAdmin']) ? $userDetails['isSuperAdmin'] : 0;
        if ($isSuperAdmin == 1 || ($userContainer->trackerRoles != '' && in_array($trackerId, array_keys($userContainer->trackerRoles)))) {
            return true;
        }
        return false;
    }
    public function isAdmin($trackerId) 
    {
        $session = new SessionContainer();
        $userContainer= $session->getSession("user");
        $trackerContainer = $session->getSession("tracker");
        $userDetails = $userContainer->user_details;
        $roleId = isset($userDetails['group_id'])?$userDetails['group_id']:0; 
        $trackerUserGroups = $trackerContainer->tracker_user_groups;
        $sessionGroup = isset($trackerUserGroups[$trackerId]['session_group'])?$trackerUserGroups[$trackerId]['session_group']:"";
        if ($roleId != 1 && $sessionGroup != "Administrator") {
            return $this->redirect()->toRoute('tracker');
        } 
        return true;
    }
    public function fieldManagementAction() 
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            if ($this->isHavingTrackerAccess($trackerId) && $formId  > 0) {
                $checkTableArray = $this->getModelService()->trackerCheckForms($trackerId, $formId);
                $formDetails = $checkTableArray['form_details'];
                $responseCode = $checkTableArray['responseCode'];
                if ($responseCode == 0) {
                    return $this->redirect()->toRoute('tracker');
                }
                $fieldsAarray = $this->getModelService()->trackerCheckFields($trackerId, $formId);
                $workflowArray = $this->getModelService()->getWorkFlows($formId);
                $codeLists = $this->getModelService()->getCodeList($trackerId);
                $trackerResults = $this->getModelService()->trackerResults($trackerId);
                $rolesData = $this->getModelService()->getRolesForTracker($trackerId);
                $fieldTypes = $this->getModelService()->getFieldTypes();
                $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id' => $formId));
                return new ViewModel(
                    array(
                        'trackerResults' => $trackerResults,
                        'formId' => $formId,
                        'trackerId' => $trackerId,
                        'formDetails' => $formDetails,
                        'fieldsArray' => $fieldsAarray,
                        'codeLists' => $codeLists,
                        'workflowArray' => $workflowArray,
                        'roles' => $rolesData,
                        'fieldTypes' => $fieldTypes,
                    )
                );
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                $response->setContent('You do not have permission to access this tracker');
                return $response;
            }
        }
    }
    
    public function addEditFieldAction() 
    {
        set_time_limit(0);
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $post = $this->getRequest()->getPost()->toArray();
        $post  = filter_var_array($post, FILTER_SANITIZE_SPECIAL_CHARS);
        if (!empty($post)) {
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $responseCode = 0;
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
                if ($this->isHavingTrackerAccess($trackerId) && $formId  > 0) {
                    $comment = $post['comment'];
                    $fieldId = (int)$post['f_id'];
                    $codeListId = (int)$post['code_list_id'];
                    $roleId = (int)$post['role_id'];
                    $label = (isset($post['fieldName']) && preg_match('/^[a-zA-Z0-9\. ]+$/', $post['fieldName']))?$post['fieldName']:'';
                    $fieldName = isset($post['edit_field_name_hidden'])?$post['edit_field_name_hidden']:"";
                    $kpi = (int)$post['kpi'];
                    $fieldType = $post['fieldType'];
                    $workflowId = isset($post['workflowId'])?$post['workflowId']:0;
                    $validationRequired = isset($post['validation_req'])?intval($post['validation_req']):0;
                    $defaultValue = isset($post['default_value'])?htmlspecialchars($post['default_value']):null;
                    $optionsId = ($fieldType == 'User Role')? $roleId : $codeListId;
                    $resArrayforfields = $this->getModelService()->getFieldsInfo(array($fieldId));
                    $resArr = $this->getModelService()->editFieldById($post);
                    $trackerData = $this->getModelService()->getTrackerDetails($post['tracker_id']);
                    $userDetails = $userSession->user_details;
                    switch ($resArr['responseCode']) {
                    case 1:
                        if ($validationRequired == 1) {
                            $validationData = "validation_required: 'Yes', rules: ".$resArr['newRules']."";
                        } else {
                            $validationData = "validation_required: 'No'";
                        }
                        $newValue =  "{field_name:'".$resArr['form_name']."', label: '".$label."',type: '".$fieldType."',options:'".$optionsId."',kpi:'".$kpi."',workflow_id:'".$workflowId."',default_value:'".$defaultValue."',$validationData}";
                        $this->getAuditService()->saveToLog($resArr['field_id'], $userDetails['email'], 'Add Field', "", $newValue, $comment, 'Success', $trackerData['client_id']);
                        $this->flashMessenger()->addMessage(array('success' => $resArr['errMessage']));
                        $responseCode = $resArr['responseCode'];
                        $errMessage = $resArr['errMessage'];
                        break;
                    case 2:
                        if ($validationRequired == 1) {
                            $validationData = "validation_required: 'Yes', rules: ".$resArr['newRules']."";
                        } else {
                            $validationData = "validation_required: 'No'";
                        }
                        if ($resArrayforfields['validation_required'] == 1) {
                            $oldValidationData = "validation_required: 'Yes', rules: ".$resArr['oldRules']."";
                        } else {
                            $oldValidationData = "validation_required: 'No'";
                        }
                        
                        $actualOptionId = ($resArrayforfields['field_type'] == 'User Role')? $resArrayforfields['formula']: $resArrayforfields['code_list_id'];
                        $actualValue =  "{field_name:'".$resArrayforfields['field_name']."', label: '".$resArrayforfields['label']."',type: '".$resArrayforfields['field_type']."',options:'".$actualOptionId."',kpi:'".$resArrayforfields['kpi']."',workflow_id:'".$resArrayforfields['workflow_id']."',default_value:'".$resArrayforfields['default_value']."',$oldValidationData}";
                        $newValue =  "{field_name:'".$fieldName."', label: '".$label."',type: '".$fieldType."',options:'".$optionsId."',kpi:'".$kpi."',workflow_id:'".$workflowId."',default_value:'".$defaultValue."',$validationData}";
                            
                        $this->getAuditService()->saveToLog($resArr['field_id'], $userDetails['u_name'], 'Update Field', $actualValue, $newValue, $comment, 'Success', $trackerData['client_id']);
                        $this->flashMessenger()->addMessage(array('success' => $resArr['errMessage']));
                        $responseCode = $resArr['responseCode'];
                        $errMessage = $resArr['errMessage'];
                        break;
                    default:
                        $responseCode = $resArr['responseCode'];
                        $errMessage = $resArr['errMessage'];
                        break; 
                    }
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                    $responseCode = 0;
                    $errMessage = 'You do not have permission to access this tracker'; 
                }
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $responseCode = 0;
            $errMessage = 'Method Not Allowed';
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage)));
        return $response;
    }
    
    public function deleteFieldAction()
    {
        set_time_limit(0);
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        //$post = $this->getRequest()->getPost()->toArray(); 
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if (!empty($post)) {
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $responseCode = 0;
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
                if ($this->isHavingTrackerAccess($trackerId) && $formId  > 0) {
                    $fieldId = (int)$this->getEvent()->getRouteMatch()->getParam('field_id', 0);
                    $comment = $post['comment']; 
                    if (strval(intval($post['fieldID'])) == strval($post['fieldID']) && strval(intval($post['tracker_id'])) == strval($post['tracker_id']) && strval(intval($post['form_id'])) == strval($post['form_id'])) {
                        $userDetails = $userSession->user_details;
                        $resArrayforfields = $this->getModelService()->getFieldsInfo(array($post['fieldID']));
                        $resultset = $this->getModelService()->deleteField($post);
                        switch ($resultset['responseCode']) {
                        case 1:
                            if (!empty($resArrayforfields)) { 
                                $actualValue =  "{field_name:'".$resArrayforfields['field_name']."', label: '".$resArrayforfields['label']."',type: '".$resArrayforfields['field_type']."', workflow_id:'".$resArrayforfields['workflow_id']."'}";
                                $trackerData = $this->getModelService()->getTrackerDetails($post['tracker_id']);
                                $this->getAuditService()->saveToLog($post['fieldID'], $userDetails['u_name'], 'Delete Field', $actualValue, '', $comment, 'Success', $trackerData['client_id']);
                                $this->flashMessenger()->addMessage(array('success' => $resultset['errMessage']));
                                $responseCode = $resultset['responseCode'];
                                $errMessage = $resultset['errMessage'];
                            } else {
                                $this->flashMessenger()->addMessage(array('error' => "You are trying to delete already deleted Field"));
                                $responseCode = 0;
                                $errMessage = "You are trying to delete already deleted Field";
                            }
                            break;
                        default:
                            $responseCode = $resultset['responseCode'];
                            $errMessage = $resultset['errMessage'];
                            break;
                        } 
                    } else {
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_501);
                        $this->flashMessenger()->addMessage(array('error' => 'Field form received invalid data'));
                        $response->setContent('Field form received invalid data');                                                
                    }
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                    $responseCode = 0;
                    $errMessage = 'You do not have permission to access this tracker'; 
                }
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $responseCode = 0;
            $errMessage = 'Method Not Allowed';
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage)));
        return $response;
    }
    
    public function getValidationRuleAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            if ($this->getRequest()->isXmlHttpRequest()) {
                $response = $this->getResponse();
                $post = $this->getRequest()->getPost()->toArray();
                $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
                if (isset($post['fieldtype'])) {
                    $fieldtype=filter_var($post['fieldtype'], FILTER_SANITIZE_STRING);
                } else {
                    $response->setContent(\Zend\Json\Json::encode('No data found'));
                    return $response;
                }
                $workflowArray = $this->getModelService()->getValidationRule($fieldtype);
                $response->setContent(\Zend\Json\Json::encode($workflowArray));
                return $response;
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('Your dont have permission to access this.');
                return $response;
            }
        }
    }
    
    public function formulaFieldsAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $trackerContainer = $session->getSession("tracker");
        $formulaFieldsContainer = $session->getSession("formula_fields");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            if ($this->isHavingTrackerAccess($trackerId) && $formId  > 0) {
                if (!isset($formulaFieldsContainer->formula_fields) || count($formulaFieldsContainer->formula_fields) == 0) {
                        $formulaFields = $this->getModelService()->getformulafields($formId);
                } else {
                    $formulaFields = $formulaFieldsContainer->formula_fields;
                }
                
                $checkTableArray = $this->getModelService()->trackerCheckForms($trackerId, $formId);
                $formDetails = $checkTableArray['form_details'];
                $responseCode = $checkTableArray['responseCode'];
                if ($responseCode == 0) {
                    return $this->redirect()->toRoute('tracker');
                }
                
                $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id' => $formId));
                $fieldsArray = $this->getModelService()->trackerCheckFieldsForFormula($trackerId, $formId);
                $formulaLists = $this->getModelService()->getFormulaList();
                return new ViewModel(
                    array(
                    'formId' => $formId,
                    'trackerId' => $trackerId,
                    'form_details' => $formDetails,
                    'formula_fields' => $formulaFields,
                    'fields_array' => $fieldsArray,
                    'formula_list' => $formulaLists,
                    )
                );
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                $response->setContent('You do not have permission to access this tracker');
                return $response; 
            }
        }
    }
    public function saveFormulaAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $post = $this->getRequest()->getPost()->toArray();
        $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
        $responseCode =0;
        $errMessage='';
        if (!empty($post)) {
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $responseCode = 0;
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
                if ($this->isHavingTrackerAccess($trackerId) && $formId  > 0) {
                    $comment = isset($post['reason'])?$post['reason']:'';
                    $formula = isset($post['formula'])?$post['formula']:'';
                    $fieldId = isset($post['fieldId'])?intval($post['fieldId']):0;
                    $trackerId = isset($post['trackerId'])?intval($post['trackerId']):0;
                    if ($fieldId == 0 OR $trackerId == 0) {
                        $responseCode = 0;
                        $errMessage = 'Method Not Allowed';
                    } else {
                        $userDetails = $userSession->user_details;
                        $formulaInfo = $this->getModelService()->getFormulaForField($fieldId);
                        $result = $this->getModelService()->saveFormula($fieldId, $formula);
                        switch ($result['responseCode']) {
                        case 1:
                            $trackerData = $this->getModelService()->getTrackerDetails($trackerId); 
                            $this->getAuditService()->saveToLog($fieldId, $userDetails['u_name'], 'Edit Formula', "formula : {".addslashes($formulaInfo['formula'])."}", "formula : {".addslashes($formula)."}", $comment, 'Success', $trackerData['client_id']);
                            break;
                        default:
                            break;
                        }
                        $responseCode = $result['responseCode'];
                        $errMessage = $result['errMessage'];
                    }
                }
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $responseCode = 0;
            $errMessage = 'Method Not Allowed';
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage)));
        return $response;
    }
}

