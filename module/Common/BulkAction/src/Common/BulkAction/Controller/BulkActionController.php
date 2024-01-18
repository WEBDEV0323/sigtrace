<?php

namespace Common\BulkAction\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Session\Container\SessionContainer;
use Zend\View\Model\ViewModel;

class BulkActionController extends AbstractActionController
{
    protected $_auditService;
    protected $_bulkActionService;
    
    public function getAuditService()
    {
        if (!$this->_auditService) {
            $sm = $this->getServiceLocator();
            $this->_auditService = $sm->get('Audit\Service');
        }
        return $this->_auditService;
    }
    
    public function getBulkActionService()
    {
        if (!$this->_bulkActionService) {
            $sm = $this->getServiceLocator();
            $this->_bulkActionService = $sm->get('BulkAction\Service');
        }
        return $this->_bulkActionService;
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
    public function indexAction()
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
            $bulkActions = array();
            if ($this->isHavingTrackerAccess($trackerId)) { 
                $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
                $trackerRoles = ($userSession->trackerRoles != '' && !empty($userSession->trackerRoles))?$userSession->trackerRoles:array();
                $userRoleId = ((!empty($trackerRoles)) && isset($trackerRoles[$trackerId]['sessionRoleId']))?$trackerRoles[$trackerId]['sessionRoleId']: 0;
                $userRoleType = ((!empty($trackerRoles)) && isset($trackerRoles[$trackerId]['sessionRoleType']))?$trackerRoles[$trackerId]['sessionRoleType']:$userSession->offsetGet('roleNameType');
                $bulkActions = $this->getBulkActionService()->fetchBulkActions($formId, $userRoleId, $userRoleType);
                $this->layout()->isHavingPermission = true;
            }
            return new ViewModel(
                array(
                    'bulkActions' => $bulkActions
                )
            );
        }
    }
    
    public function checkAction() 
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $resArr['responseCode'] = 0;
        $resArr['errMessage'] = '';
        $resArr['xtra_required'] = '';
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $resArr['errMessage'] = 'Your session has been expired. Please <a href="/">login</a> again';
        } else {
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            if ($this->isHavingTrackerAccess($trackerId)) {
                $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
                $asId = (int)$this->getEvent()->getRouteMatch()->getParam('actionId', 0);
                $resArr['xtra_required'] = $this->getBulkActionService()->getXtraRequiredDetails($asId);
                $resArr['responseCode'] = 1;
            }
        }
        return $response->setContent(\Zend\Json\Json::encode($resArr));
    }
    
    public function getManualFormFieldsAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $resArr['responseCode'] = 0;
        $resArr['errMessage'] = '';
        $resArr['data'] = '';
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $resArr['errMessage'] = 'Your session has been expired. Please <a href="/">login</a> again';
        } else {
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            if ($this->isHavingTrackerAccess($trackerId)) {
                $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
                $asId = (int)$this->getEvent()->getRouteMatch()->getParam('actionId', 0);
                $resArr['responseCode'] = 1;
                $details = $this->getBulkActionService()->getActionDetails($asId);
                $html = "";
                if (!empty($details)) {
                    $manualFieds = isset($details['xtra_fields'])?$details['xtra_fields']:'';
                    if ($manualFieds != '') {
                        $fields = json_decode($manualFieds, true);
                        if (!empty($fields) && $fields != '') {
                            foreach ($fields as $field=>$options) {
                                
                                if ($options['type'] == 'userRoleGroup') {
                                     $users = $this->getBulkActionService()->getUsersByRole($options['roleId']);
                                     $html .= '<div class="form-group row">';
                                     $html .= '<label class="col-sm-4 col-form-label" for="'.$field.'">'.$options['label'].'</label>';
                                     $html .= '<div class="col-sm-7">';
                                     $html .= '<select name="fields['.$field.']" id="'.$field.'" class="form-control">';
                                    foreach ($users as $user) {
                                        $html .= '<option value="'.$user['u_name'].'">'.$user['email'].'</option>';
                                    }
                                     $html .= '</select>';
                                     $html .= '</div>';
                                     $html .= '</div>';
                                }
                                if ($options['type'] == 'userRole') {
                                    $users = $this->getBulkActionService()->getUsers();
                                    $html .= '<div class="form-group row">';
                                    $html .= '<label class="col-sm-4 col-form-label" for="'.$field.'">'.$options['label'].'</label>';
                                    $html .= '<div class="col-sm-7">';
                                    $html .= '<select name="fields['.$field.']" id="'.$field.'" class="form-control">';
                                    foreach ($users as $user) {
                                        $html .= '<option value="'.$user['u_name'].'">'.$user['email'].'</option>';
                                    }
                                    $html .= '</select>';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }
                                if ($options['type'] == 'select') {
                                    $fieldValues = $this->getBulkActionService()->getFieldValues($field);
                                    $html .= '<div class="form-group row">';
                                    $html .= '<label class="col-sm-4 col-form-label" for="'.$field.'">'.$options['label'].'</label>';
                                    $html .= '<div class="col-sm-7">';
                                    $html .= '<select name="fields['.$field.']" id="'.$field.'" class="form-control">';
                                    foreach ($fieldValues as $value) {
                                        $html .= '<option value="'.$value['option_value'].'">'.$value['option_label'].'</option>';
                                    }
                                    $html .= '</select>';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }
                                if ($options['type'] == 'text') {
                                    $html .= '<div class="form-group row">';
                                    $html .= '<label class="col-sm-4 col-form-label" for="'.$field.'">'.$options['label'].'</label>';
                                    $html .= '<div class="col-sm-7">';
                                    $html .= '<input type="text" name="fields['.$field.']" id="'.$field.'" class="form-control" />';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }
                                if ($options['type'] == 'textarea') {
                                    $html .= '<div class="form-group row">';
                                    $html .= '<label class="col-sm-4 col-form-label" for="'.$field.'">'.$options['label'].'</label>';
                                    $html .= '<div class="col-sm-7">';
                                    $html .= '<textarea name="fields['.$field.']" id="'.$field.'" class="form-control">';
                                    $html .= '</textarea>';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }
                                if ($options['type'] == 'checkbox') {
                                    $html .= '<div class="form-group row">';
                                    $html .= '<label class="col-sm-4 col-form-label" for="'.$field.'">&nbsp;</label>';
                                    $html .= '<div class="checkbox col-sm-7">';
                                    $html .= '<input type="checkbox" name="fields['.$field.']" id="'.$field.'" />';
                                    $html .= '<label class="control-label">'.$options['label'].'</label>';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }
                                if ($options['type'] == 'date') {
                                    $html .= '<div class="form-group row">';
                                    $html .= '<label class="col-sm-4 col-form-label" for="'.$field.'">'.$options['label'].'</label>';
                                    $html .= '<div class="col-sm-7">';
                                    $html .= '<input type="text" name="fields['.$field.']" id="'.$field.'" class="form-control datepick" data-date-format="" autocomplete="off" readonly="readonly" />';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }
                                if ($options['type'] == 'datetime') {
                                    $html .= '<div class="form-group row">';
                                    $html .= '<label class="col-sm-4 col-form-label" for="'.$field.'">'.$options['label'].'</label>';
                                    $html .= '<div class="col-sm-7">';
                                    $html .= '<input type="text" name="fields['.$field.']" id="'.$field.'" class="form-control datepick" data-date-format="" autocomplete="off" readonly="readonly" />';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }
                                if ($options['type'] == 'daterange') {
                                    $html .= '<div class="form-group row">';
                                    $html .= '<label class="col-sm-4 col-form-label" for="'.$field.'">'.$options['label'].'</label>';
                                    $html .= '<div class="col-sm-7">';
                                    $html .= '<input type="text" name="fields['.$field.']" id="'.$field.'" class="form-control daterangepick" autocomplete="off" readonly="readonly" />';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }
                                if ($options['type'] == 'radio') {

                                }
                            }
                        }
                    }
                }
                $resArr['data'] = $html;
            }
        }
        return $response->setContent(\Zend\Json\Json::encode($resArr));
    }
    
    public function saveAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $resArr['responseCode'] = 0;
        $resArr['errMessage'] = '';
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $resArr['errMessage'] = 'Your session has been expired. Please <a href="/">login</a> again';
        } else {
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $userDetails = $userContainer->user_details;
            if ($this->isHavingTrackerAccess($trackerId)) {
                $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
                $post  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
                $reason_for_change = $post['reason'];
                unset($post['reason']);
                $set = ''; $valueArray = array();
                $ActionDetails = $this->getBulkActionService()->getActionDetails(intval($post['actionId']));
                if (isset($post['fields']) && !empty($post['fields'])) {
                    foreach ($post['fields'] as $field=>$value) {
                        $set .= ($set == '')?'`'.$field."` = ?": ', `'.$field ."` = ?";
                        $valueArray[] = $value;
                    }
                } else if ($ActionDetails['action_query'] != '') {
                    $fields = json_decode(trim($ActionDetails['action_query']), true);
                    foreach ($fields as $field=>$value) {
                        $set .= ($set == '')?'`'.$field."` = ?": ', `'.$field ."` = ?";
                        $valueArray[] = $value;
                    }
                }
                $iDs = explode(',', $post['recordIds']);
                $result = array();
                foreach ($iDs as $k => $iD) {
                    $where = ' WHERE '.$ActionDetails['where_field'].'= ?';
                    $result[] = $this->getBulkActionService()->applyBulkAction($trackerId, $formId, $set, $where, $valueArray, array("0"=>$iD));
                }
                if (count(array_diff($iDs, $result)) == 0) {
                    $trackerData = $this->getBulkActionService()->getTrackerInformation($trackerId);
                    $this->getAuditService()->saveToLog('{"'.implode('", "', $iDs).'}', $userDetails['email'], 'Bulk Action', '', json_encode($post, JSON_FORCE_OBJECT), $reason_for_change, 'Success', $trackerData['client_id']);
                    $resArr['errMessage'] = 'success';
                    $resArr['responseCode'] = 1;
                } else {
                    $resArr['errMessage'] = 'failed';
                    $resArr['responseCode'] = 2;
                }
            }
        }
        return $response->setContent(\Zend\Json\Json::encode($resArr));
    }
}
