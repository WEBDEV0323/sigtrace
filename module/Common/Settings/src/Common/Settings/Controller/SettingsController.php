<?php

namespace Common\Settings\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;
use Common\Settings\Form\UploadForm;

class SettingsController extends AbstractActionController
{

    protected $_modelService;
    protected $_auditService;
    protected $_dbMapper;

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
            $this->_modelService = $sm->get('Settings\Service');
        }
        return $this->_modelService;
    }

    public function getServiceModel()
    {
        if (!$this->_dbMapper) {
            $sm = $this->getServiceLocator();
            $this->_dbMapper = $sm->get('Settings\Model\Db');
        }
        return $this->_dbMapper;
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
        $userContainer = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');
        $userDetails = $userContainer->user_details;
        $roleId = isset($userDetails['group_id']) ? $userDetails['group_id'] : 0;
        $trackerUserGroups = $trackerContainer->tracker_user_groups;
        $sessionGroup = isset($trackerUserGroups[$trackerId]['session_group']) ? $trackerUserGroups[$trackerId]['session_group'] : '';
        if ($roleId != 1 && $sessionGroup != "Administrator") {
            return $this->redirect()->toRoute('tracker');
        }
        return true;
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
            return new ViewModel();
        }
    }

    public function authSettingsAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $actualAuditData = $newAuditData = array();
                $savingPermissionIds = $permissionIds = array();
                $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
                $userDetails = $userSession->user_details;
                if (!empty($post)) {
                    $trackerId = isset($post['TrackerId']) ? $post['TrackerId'] : 0;
                    $roleId = isset($post['RoleId']) ? $post['RoleId'] : 0;
                    $comment = isset($post['reason']) ? $post['reason'] : "";
                    $resourse = isset($post['resource']) ? $post['resource'] : array();
                    $newAuditData = array_keys($post['resource']);
                    if ($trackerId > 0 && $roleId > 0) {
                        $actualAuditData = array_column($this->getServiceModel()->fetch(array('r' => 'role_permission'), array('role_id' => $roleId, 'tracker_id' => $trackerId), array('permission_id')), 'permission_id');
                        if (!empty($resourse)) {
                            $trackerData = $this->getModelService()->getTrackerInformation($trackerId);
                            $msgSession = $session->getSession("auth_setting_msg");
                            foreach ($resourse as $key => $value) {
                                if (!empty($value)) {
                                    foreach ($value as $res) {
                                        array_push($savingPermissionIds, $res);
                                        $id = $this->getServiceModel()->fetch(array('r' => 'role_permission'), array('permission_id' => $res, 'role_id' => $roleId, 'tracker_id' => $trackerId), array('id'));
                                        if (count($id) > 0) {
                                            $this->getServiceModel()->update('role_permission', array('id' => $id[0]['id']), array('permission_id' => $res, 'role_id' => $roleId, 'tracker_id' => $trackerId));
                                        } else {
                                            $this->getServiceModel()->insert('role_permission', array('permission_id' => $res, 'role_id' => $roleId, 'tracker_id' => $trackerId));
                                        }
                                    }
                                }
                            }
                            $permissionIds = $this->getServiceModel()->fetch(array('r' => 'role_permission'), array('role_id' => $roleId, 'tracker_id' => $trackerId), array('permission_id'));
                            if (!empty($permissionIds)) {
                                foreach ($permissionIds as $id) {
                                    if (!in_array($id['permission_id'], $savingPermissionIds)) {
                                        $this->getServiceModel()->delete('role_permission', array('permission_id' => $id['permission_id'], 'role_id' => $roleId, 'tracker_id' => $trackerId));
                                    }
                                }
                            }
                        } else {
                            $this->getServiceModel()->delete('role_permission', array('role_id' => $roleId, 'tracker_id' => $trackerId));
                        }
                        $msgSession->msg = "Authorization Settings Updated Successfully.";
                        $this->getAuditService()->saveToLog(0, $userDetails['email'], 'Authorization Setting', '{"tracker_id":"' . $trackerId . '","role_id"' . $roleId . ', "permission_ids":' . json_encode($actualAuditData) . '}', '{"tracker_id":"' . $trackerId . '","role_id"' . $roleId . ', "permission_ids":' . json_encode($newAuditData) . '}', $comment, 'Success', $trackerData['client_id']);
                        return $this->redirect()->toUrl('/settings/auth_settings');
                    }
                }
            }
            $trackers = $this->getServiceModel()->fetch(array('t' => 'tracker'), array('archived' => 0, 'status' => 'Active'), array('tracker_id', 'name'));
            $view = new ViewModel();
            $view->setVariables(array('trackers' => $trackers));
            return $view;
        }
    }

    public function getRolesDataAction()
    {
        $response = $this->getResponse();
        $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
        $groups = $this->getServiceModel()->fetch(array('t' => 'role'), array('archived' => 0, 'super_admin' => 0, 'tracker_id' => $post['trackerId']), array('rid', 'role_name'));
        $html = '<option value="0"></option>';
        if (!empty($groups)) {
            foreach ($groups as $group) {
                $html .= '<option value="' . $group['rid'] . '">' . $group['role_name'] . '</option>';
            }
        }
        echo $html;
        return $response;
    }

    public function getAccessDataAction()
    {
        $response = $this->getResponse();
        $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
        $res = $permitted_resources = $perResourceArray = $resources_dump = $resources = $sort_resources = array();
        $resources_dump = $resources = $secondLevel = $thirdLevel = $this->getServiceModel()->fetch(array('r' => 'action'), array('archived' => '0'), array('*'));
        foreach ($resources as $r) {
            switch ($r['have_blocks']) {
            case 1:
                if ($r['block_level'] == 1) {
                    $final_resources[$r['action_id']] = $r;
                    $sort_resources[$r['action_block_level']] = $r;
                    foreach ($resources_dump as $dump) {
                        if ($dump['action_group_id'] == $r['action_id']) {
                            $final_resources[$r['action_id']]['blocks'][$dump['action_id']] = $dump;
                            $sort_resources[$r['action_block_level']]['blocks'][$dump['action_block_level']] = $dump;
                            if ($dump['block_level'] == 2) {
                                foreach ($secondLevel as $second) {
                                    if ($second['action_group_id'] == $dump['action_id']) {
                                        $final_resources[$r['action_id']]['blocks'][$dump['action_id']]['blocks'][$second['action_id']] = $second;
                                        $sort_resources[$r['action_block_level']]['blocks'][$dump['action_block_level']]['blocks'][$second['action_block_level']] = $second;
                                        if ($second['block_level'] == 3) {
                                            foreach ($thirdLevel as $third) {
                                                if ($third['action_group_id'] == $second['action_id']) {
                                                    $final_resources[$r['action_id']]['blocks'][$dump['action_id']]['blocks'][$second['action_id']]['blocks'][$third['action_id']] = $third;
                                                    $sort_resources[$r['action_block_level']]['blocks'][$dump['action_block_level']]['blocks'][$second['action_block_level']]['blocks'][$third['action_id']] = $third;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                break;
            case 0:
                if ($r['action_group_id'] == 0) {
                    $final_resources[$r['action_id']] = $r;
                    $sort_resources[$r['action_block_level']] = $r;
                }
                break;
            default:
                break;
            }
        }
        $permitted_resources = $this->getServiceModel()->fetch(array('r' => 'role_permission'), array('role_id' => $post['role_id'], 'tracker_id' => $post['tracker_id']), array('permission_id'));
        foreach ($permitted_resources as $pr) {
            $perResourceArray[] = $pr['permission_id'];
        }
        $html = "<table class='table table-bordered table-striped' style='width:100%;'>";
        $i = 1;
        foreach ($sort_resources as $cId => $row) {
            if (!empty($row['blocks'])) {
                if ($i % 2 != 0) {
                    $html .= "<tr><td>";
                } else {
                    $html .= "<td>";
                }
                $html .= "<div class='custom-control custom-checkbox'>";
                if ($row['controller_id'] != '0') {
                    $html .= "<input type='checkbox' value='" . $row['action_id'] . "' class='custom-control-input'";
                    if (in_array($row['action_id'], $perResourceArray)) {
                        $html .= " checked ";
                    }
                    $html .= "name='resource[" . $row['action_id'] . "][]' id='id_" . $row['action_id'] . "'>";
                }
                $html .= "<label ";
                if ($row['controller_id'] != '0') {
                    $html .= "class='custom-control-label' for='id_" . $row['action_id'] . "'";
                }
                $html .= ">" . $row['action_label'];
                $html .= "</label>";
                $html .= "</div>";
                if (!empty($row['blocks'])) {
                    $html .= "<div class='controls' style='margin-left: 40px;'>";
                    foreach ($row['blocks'] as $block) {
                        $html .= "<div class='custom-control custom-checkbox'>";
                        if ($block['controller_id'] != '0') {
                            $html .= "<input type='checkbox' value='" . $block['action_id'] . "' class='custom-control-input'";
                            if (in_array($block['action_id'], $perResourceArray)) {
                                $html .= " checked ";
                            }
                            $html .= "name='resource[" . $block['action_id'] . "][]' id='id_" . $block['action_id'] . "'>";
                        }
                        $html .= "<label ";
                        if ($block['controller_id'] != '0') {
                            $html .= "class='custom-control-label' for='id_" . $block['action_id'] . "'";
                        }
                        $html .= ">" . $block['action_label'];
                        $html .= "</label>";
                        $html .= "</div>";
                        if (!empty($block['blocks'])) {
                            $html .= "<div class='controls' style='margin-left: 40px;'>";
                            foreach ($block['blocks'] as $block2) {
                                $html .= "<div class='custom-control custom-checkbox'>";
                                if ($block2['controller_id'] != '0') {
                                    $html .= "<input type='checkbox' value='" . $block2['action_id'] . "' class='custom-control-input'";
                                    if (in_array($block2['action_id'], $perResourceArray)) {
                                        $html .= " checked ";
                                    }
                                    $html .= "name='resource[" . $block2['action_id'] . "][]' id='id_" . $block2['action_id'] . "'>";
                                }
                                $html .= "<label ";
                                if ($block2['controller_id'] != '0') {
                                    $html .= "class='custom-control-label' for='id_" . $block2['action_id'] . "'";
                                }
                                $html .= ">" . $block2['action_label'];
                                $html .= "</label>";
                                $html .= "</div>";
                                if (!empty($block2['blocks'])) {
                                    $html .= "<div class='controls' style='margin-left: 40px;'>";
                                    foreach ($block2['blocks'] as $block3) {
                                        $html .= "<div class='custom-control custom-checkbox'>";
                                        if ($block3['controller_id'] != '0') {
                                            $html .= "<input type='checkbox' value='" . $block3['action_id'] . "' class='custom-control-input'";
                                            if (in_array($block3['action_id'], $perResourceArray)) {
                                                $html .= " checked ";
                                            }
                                            $html .= "name='resource[" . $block3['action_id'] . "][]' id='id_" . $block3['action_id'] . "'>";
                                        }
                                        $html .= "<label ";
                                        if ($block3['controller_id'] != '0') {
                                            $html .= "class='custom-control-label' for='id_" . $block3['action_id'] . "'";
                                        }
                                        $html .= ">" . $block3['action_label'];
                                        $html .= "</label>";
                                        $html .= "</div>";
                                    }
                                    $html .= "</div>";
                                }
                            }
                            $html .= "</div>";
                        }
                    }
                    $html .= "</div>";
                }

                if ($i % 2 == 0) {
                    $html .= "</td></tr>";
                } else {
                    $html .= "</td>";
                }

                $i++;
            } else {
                if ($i % 2 != 0) {
                    $html .= "<tr><td>";
                } else {
                    $html .= "<td>";
                }
                $html .= "<div class='custom-control custom-checkbox'>";
                if ($row['controller_id'] != '0' || $row['action_name'] == 'forms') {
                    $html .= "<input type='checkbox' value='" . $row['action_id'] . "' class='custom-control-input'";
                    if (in_array($row['action_id'], $perResourceArray)) {
                        $html .= " checked ";
                    }
                    $html .= "name='resource[" . $row['action_id'] . "][]' id='id_" . $row['action_id'] . "'>";
                }
                $html .= "<label ";
                if ($row['controller_id'] != '0' || $row['action_name'] == 'forms') {
                    $html .= "class='custom-control-label' for='id_" . $row['action_id'] . "'";
                }
                $html .= ">" . $row['action_label'];
                $html .= "</label>";
                $html .= "</div>";
                if ($i % 2 == 0) {
                    $html .= "</td></tr>";
                } else {
                    $html .= "</td>";
                } $i++;
            }
        }
        $html .= "</table>";
        $response->setContent($html);
        return $response;
    }

    public function formAccessSettingsAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            set_time_limit(0);
            $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            if ($this->isHavingTrackerAccess($trackerId)) {
                $result = $this->getModelService()->getFormAccessDetails($trackerId);
                return new ViewModel(
                    array(
                    'forms' => $this->getModelService()->getTrackerFormsByTrackerId($trackerId),
                    'trackerResults' => $this->getModelService()->trackerResults($trackerId),
                    'users' => $this->getModelService()->getTrackerRoles($trackerId),
                    'tracker_id' => $trackerId,
                    'resultset' => $result,
                        )
                );
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('You do not have permission to access this tracker');
                return $response;
            }
        }
    }

    public function saveFormAccessSettingsAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $trackerData = $this->getModelService()->getTrackerInformation($trackerId);
        $post = $this->getRequest()->getPost()->toArray();
        $resArr = array();
        if ($post) {
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $resArr['responseCode'] = 0;
                $resArr['errMessage'] = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $userDetails = $userContainer->user_details;
                $comment = $post['comment'];
                unset($post['comment']);
                $can_read = $post['can_read'];
                $accessArr = $this->getModelService()->getFormAccessSetting($post);
                $resArr = $this->getModelService()->saveFormSetting($post);
                $postDataArr = Array();
                foreach ($can_read as $key => $value) {
                    $postDataArr[$key]['form_id'] = $post['form_id'][$key];
                    $postDataArr[$key]['role_id'] = $post['role_id'];
                    $postDataArr[$key]['can_read'] = $value;
                }
                $this->getAuditService()->saveToLog(0, $userDetails['email'], 'Form Access Setting', json_encode($accessArr), json_encode($postDataArr), $comment, 'Success', $trackerData['client_id']);
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $resArr['responseCode'] = 0;
                $resArr['errMessage'] = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $resArr['responseCode'] = 0;
                $resArr['errMessage'] = "Access Denied";
            }
        }
        return $response->setContent(\Zend\Json\Json::encode($resArr));
    }

    public function workflowAccessSettingsAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            set_time_limit(0);
            $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            if ($this->isHavingTrackerAccess($trackerId)) {
                $result = $this->getModelService()->getWorkflowRoleForForms($trackerId);
                $result[0] = $this->sortArrOfObj($result[0], 'workflow_id');
                $this->layout()->setVariables(array('tracker_id' => $trackerId));
                return new ViewModel(
                    array(
                    'trackerResults' => $this->getModelService()->trackerResults($trackerId),
                    'users' => $this->getModelService()->getTrackerRoles($trackerId),
                    'tracker_id' => $trackerId,
                    'resultset' => $result,
                        )
                );
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('You do not have permission to access this tracker');
                return $response;
            }
        }
    }

    public function sortArrOfObj($array, $sortby, $direction = 'asc')
    {
        $sortedArr = array();
        $tmpArray = array();

        foreach ($array as $k => $v) {
            $tmpArray[] = strtolower($v->$sortby);
        }
        if ($direction == 'asc') {
            asort($tmpArray);
        } else {
            arsort($tmpArray);
        }

        foreach ($tmpArray as $k => $tmp) {
            $sortedArr[] = $array[$k];
        }
        return $sortedArr;
    }

    public function saveUpdateSettingAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $post = $this->getRequest()->getPost()->toArray();
        $resArr = array();
        $resArr['responseCode'] = 0;
        if (!empty($post)) {
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $resArr['responseCode'] = 0;
                $resArr['errMessage'] = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $userDetails = $userContainer->user_details;
                $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $trackerData = $this->getModelService()->getTrackerInformation($trackerId);
                    $comment = $post['comment'];
                    unset($post['comment']);
                    $can_update = $post['can_update'];
                    $accessArr = $this->getModelService()->getUpdateAccessSetting($post);
                    $resArr = $this->getModelService()->saveUpdateSetting($post);
                    $postDataArr = Array();
                    foreach ($can_update as $key => $value) {
                        $postDataArr[$key]['workflow_id'] = $post['workflow_id'][$key];
                        $postDataArr[$key]['can_update'] = $value;
                        $postDataArr[$key]['role_id'] = $post['role_id'];
                        $postDataArr[$key]['form_id'] = $post['form_id'];
                    }
                    $this->getAuditService()->saveToLog(0, $userDetails['email'], 'Workflow Access Setting-For update', json_encode($accessArr), json_encode($postDataArr), $comment, 'Success', $trackerData['client_id']);
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                    $resArr['errMessage'] = 'You do not have permission to access this tracker';
                }
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $resArr['errMessage'] = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $resArr['errMessage'] = "Access Denied";
            }
        }
        return $response->setContent(\Zend\Json\Json::encode($resArr));
    }

    public function saveReadSettingAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $post = $this->getRequest()->getPost()->toArray();
        $resArr = array();
        $resArr['responseCode'] = 0;
        if (!empty($post)) {
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $resArr['responseCode'] = 0;
                $resArr['errMessage'] = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $userDetails = $userContainer->user_details;
                $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $trackerData = $this->getModelService()->getTrackerInformation($trackerId);
                    $comment = $post['comment'];
                    unset($post['comment']);
                    $can_read = $post['can_read'];
                    $accessArr = $this->getModelService()->getReadAccessSetting($post);
                    $postDataArr = Array();
                    foreach ($can_read as $key => $value) {
                        $postDataArr[$key]['workflow_id'] = $post['workflow_id'][$key];
                        $postDataArr[$key]['can_read'] = $value;
                        $postDataArr[$key]['role_id'] = $post['role_id'];
                        $postDataArr[$key]['form_id'] = $post['form_id'];
                    }
                    $resArr = $this->getModelService()->saveReadSetting($dataArr);
                    $this->getAuditService()->saveToLog(0, $userDetails['email'], 'Workflow Access Setting-For read', json_encode($accessArr), json_encode($postDataArr), $comment, 'Success', $trackerData['client_id']);
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                    $resArr['errMessage'] = 'You do not have permission to access this tracker';
                }
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $resArr['errMessage'] = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $resArr['errMessage'] = "Access Denied";
            }
        }
        return $response->setContent(\Zend\Json\Json::encode($resArr));
    }

    public function recordAccessSettingsAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            set_time_limit(0);
            $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            if ($this->isHavingTrackerAccess($trackerId)) {
                $result = $this->getModelService()->getWorkflowRoleForForms($trackerId);
                $result[0] = $this->sortArrOfObj($result[0], 'workflow_id');
                $this->layout()->setVariables(array('tracker_id' => $trackerId));
                return new ViewModel(
                    array(
                    'trackerResults' => $this->getModelService()->trackerResults($trackerId),
                    'users' => $this->getModelService()->getTrackerRoles($trackerId),
                    'tracker_id' => $trackerId,
                    'resultset' => $result,
                        )
                );
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('You do not have permission to access this tracker');
                return $response;
            }
        }
    }

    public function saveRecordAccessSettingAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $resArr = array();
        $resArr['responseCode'] = 0;
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $resArr['responseCode'] = 0;
            $resArr['errMessage'] = 'Your session has been expired. Please <a href="/">login</a> again';
        } else {
            $post = $this->getRequest()->getPost()->toArray();
            if (!empty($post)) {
                $userDetails = $userContainer->user_details;
                $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $trackerData = $this->getModelService()->getTrackerInformation($trackerId);
                    $comment = $post['comment'];
                    unset($post['comment']);
                    $datapostarray['can_insert'] = $post['can_insert'];
                    $datapostarray['can_delete'] = $post['can_delete'];
                    $accessArr = $this->getModelService()->getRecordAccessSettings($post);
                    $resArr = $this->getModelService()->saveRecordAccessSetting($post);
                    $this->getAuditService()->saveToLog($post['role_id'], $userDetails['email'], 'Access Setting-For insert and delete', json_encode($accessArr), json_encode($post), $comment, 'Success', $trackerData['client_id']);
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                    $resArr['errMessage'] = 'You do not have permission to access this tracker';
                }
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $resArr['errMessage'] = "Access Denied";
            }
            return $response->setContent(\Zend\Json\Json::encode($resArr));
        }
    }

    public function reportAccessSettingsAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            set_time_limit(0);
            $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            if ($this->isHavingTrackerAccess($trackerId)) {
                $result = $this->getModelService()->getReportAccessSetting($trackerId);
                return new ViewModel(
                    array(
                    'trackerResults' => $this->getModelService()->trackerResults($trackerId),
                    'users' => $this->getModelService()->getTrackerRoles($trackerId),
                    'tracker_id' => $trackerId,
                    'resultset' => $result,
                        )
                );
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('You do not have permission to access this tracker');
                return $response;
            }
        }
    }

    public function saveReportAccessSettingAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $resArr = array();
        $resArr['responseCode'] = 0;
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $resArr['responseCode'] = 0;
            $resArr['errMessage'] = 'Your session has been expired. Please <a href="/">login</a> again';
        } else {
            $post = $this->getRequest()->getPost()->toArray();
            if (!empty($post)) {
                $userDetails = $userContainer->user_details;
                $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $trackerData = $this->getModelService()->getTrackerInformation($trackerId);
                    $comment = $post['comment'];
                    unset($post['comment']);
                    $can_update = $post['can_update'];
                    $can_read = $post['can_read'];
                    $form_id = $post['form_id'];
                    $report_ids = $post['report_id'];
                    $aNewReportAccessSettings = array_combine($post['report_id'], $post['can_update']);
                    $aNewReportAccessSettings['form_id'] = $form_id;
                    $aNewReportAccessSettings['role_id'] = $post['role_id'];
                    $accessArr = $this->getModelService()->getReportSetting($post);
                    $aOldReportAccessSettings = array();
                    if (isset($accessArr)) {
                        foreach ($accessArr as $key => $value) {
                            $aOldReportAccessSettings[$value['report_id']] = $value['can_access'];
                        }

                        $aOldReportAccessSettings['form_id'] = $form_id;
                        $aOldReportAccessSettings['role_id'] = $post['role_id'];
                    }
                    $resArr = $this->getModelService()->saveReportAccessSetting($post);
                    $this->getAuditService()->saveToLog($post['role_id'], $userDetails['email'], 'Report Access Settings', json_encode($aOldReportAccessSettings), json_encode($aNewReportAccessSettings), $comment, 'Success', $trackerData['client_id']);
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                    $resArr['errMessage'] = 'You do not have permission to access this tracker';
                }
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $resArr['errMessage'] = "Access Denied";
            }
            return $response->setContent(\Zend\Json\Json::encode($resArr));
        }
    }

    public function workflowRuleSettingsAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $rules = $this->getModelService()->getWorkflowRule($trackerId);
            $trackerResults = $this->getModelService()->trackerResults($trackerId);
            $formId = $trackerResults['forms'][0]['form_id'];
            $this->layout()->setVariables(array('tracker_id' => $trackerId, 'form_id' => $formId));
            if ($this->isHavingTrackerAccess($trackerId)) {
                return new ViewModel(
                    array(
                    'trackerResults' => $trackerResults,
                    'workflowRules' => $rules,
                    'trackerId' => $trackerId
                        )
                );
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('You do not have permission to access this tracker');
                return $response;
            }
        }
    }

    public function getWorkflowsAndFieldsAction()
    {
        $response = $this->getResponse();
        $post = $this->getRequest()->getPost()->toArray();
        $fieldsArray = $workflowArray = array();
        if ($post) {
            $session = new SessionContainer();
            $userSession = $session->getSession("user");
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $responseCode = 0;
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $formId = isset($post['form_id']) ? (int) $post['form_id'] : 0;
                    $fieldsArray = $this->getModelService()->getFields($formId);
                    $workflowArray = $this->getModelService()->getWorkFlows($formId);
                    $responseCode = 1;
                    $errMessage = 'successfully fetched';
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                    $errMessage = 'You do not have permission to access this tracker';
                    $responseCode = 0;
                }
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $responseCode = 0;
            $errMessage = 'Method Not Allowed';
        }
        $response->setContent(\Zend\Json\Json::encode(array('workflows' => $workflowArray, 'fields' => $fieldsArray, 'responseCode' => $responseCode, 'errMessage' => $errMessage)));
        return $response;
    }

    public function saveRuleAction()
    {
        $response = $this->getResponse();
        $post = $this->getRequest()->getPost()->toArray();
        if ($post) {
            $session = new SessionContainer();
            $userSession = $session->getSession("user");
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $responseCode = 0;
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $result = $this->getModelService()->saveRule($post);
                    switch ($result['responseCode']) {
                    case 1:
                        $reason = isset($post['reason']) ? $post['reason'] : '';
                        $userDetails = $userSession->user_details;
                        $trackerData = $this->getModelService()->getTrackerInformation($trackerId);
                        switch ($result['action']) {
                        case 'adding':
                            $this->getAuditService()->saveToLog($result['ruleId'], $userDetails['email'], 'Add Workflow Rule', "", $result['new'], $reason, 'Success', $trackerData['client_id']);
                            break;
                        case 'updating':
                            $this->getAuditService()->saveToLog($result['ruleId'], $userDetails['email'], 'Update Workflow Rule', $result['old'], $result['new'], $reason, 'Success', $trackerData['client_id']);
                            break;
                        default:
                            break;
                        }
                        $this->flashMessenger()->addMessage(array('success' => $result['errMessage']));
                        $responseCode = $result['responseCode'];
                        $errMessage = $result['errMessage'];
                        break;
                    default:
                        $responseCode = $result['responseCode'];
                        $errMessage = $result['errMessage'];
                        break;
                    }
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                    $errMessage = 'You do not have permission to access this tracker';
                    $responseCode = 0;
                }
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $responseCode = 0;
            $errMessage = 'Method Not Allowed';
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage' => $errMessage)));
        return $response;
    }

    public function getRuleInfoAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $fieldsArray = array();
        $responseCode = 0;
        $errMessage = "";
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
        } else {
            $post = $this->getRequest()->getPost()->toArray();
            if ($post) {
                $fieldsArray = $this->getModelService()->getRuleInfo($post);
                $responseCode = 1;
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                $errMessage = 'Method Not Allowed';
            }
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage' => $errMessage, 'data' => $fieldsArray)));
        return $response;
    }

    public function deleteRuleAction()
    {
        $response = $this->getResponse();
        $post = $this->getRequest()->getPost()->toArray();
        if ($post) {
            $session = new SessionContainer();
            $userSession = $session->getSession("user");
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $responseCode = 0;
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $ruleId = isset($post['ruleId']) ? intval($post['ruleId']) : 0;
                    $formId = isset($post['formId']) ? intval($post['formId']) : 0;
                    $resArr = $this->getModelService()->deleteRule($ruleId, $formId);
                    switch ($resArr['responseCode']) {
                    case 1:
                        $reason = isset($post['reason']) ? $post['reason'] : '';
                        $userDetails = $userSession->user_details;
                        $trackerData = $this->getModelService()->getTrackerInformation($trackerId);
                        $this->getAuditService()->saveToLog($ruleId, $userDetails['email'], 'Delete Workflow Rule', $resArr['actual'], "", $reason, 'Success', $trackerData['client_id']);
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
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                    $errMessage = 'You do not have permission to access this tracker';
                    $responseCode = 0;
                }
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $responseCode = 0;
            $errMessage = 'Method Not Allowed';
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage' => $errMessage)));
        return $response;
    }

    public function getFieldsByWorkflowIdAction()
    {
        $response = $this->getResponse();
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $result = array();
        if ($post) {
            $result = $this->getModelService()->getFieldsByWorkflowId($post);
        }
        $response->setContent(\Zend\Json\Json::encode($result));
        return $response;
    }

    public function uploadUserManualAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");

        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = $this->params()->fromRoute('trackerId', 0);
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            $form = new UploadForm('upload-form');
            $form->setAttribute('class', 'form-horizontal');
            $form->get('t_hidden')->setAttribute('value', $trackerId);
            $request = $this->getRequest();
            if ($request->isPost()) {
                $post = array_merge_recursive($request->getPost()->toArray(), $request->getFiles()->toArray());
                $form->setData($post);
                if ($form->isValid()) {
                    $data = $form->getData();
                    $allowedExts = array("pdf", "doc", "docx");
                    $file_name = explode(".", $data["image-file"]['name']);
                    $extension = end(explode(".", $data["image-file"]['name']));
                    if (in_array($extension, $allowedExts)) {
                        $userManualPath = files . "manual";
                        if (!file_exists($userManualPath)) {
                            mkdir($userManualPath, 0777, true);
                        }
                        $path = files . 'manual/' . $file_name[0] . '_' . time() . '.' . $file_name[1];
                        $path1 = files_fetch_path . 'manual/' . $file_name[0] . '_' . time() . '.' . $file_name[1];
                        move_uploaded_file($data['image-file']['tmp_name'], $path);
                        $userDetails = $userSession->user_details;
                        $lastInsertID = $this->getModelService()->saveAttachmentInfo($path1, $userDetails['u_id'], $trackerId);
                        $session->setSession('usermanual_settings', array("msg" => 'uploaded'));
                        $reason = isset($post['reason']) ? $post['reason'] : "";
                        $trackerData = $this->getModelService()->getTrackerDetails($trackerId);
                        $this->getAuditService()->saveToLog(intval($lastInsertID), $userDetails['email'], 'Upload User Manual', "", $path1, $reason, 'Success', $trackerData['client_id']);
                        return $this->redirect()->toRoute('settings', array('action' => 'upload_user_manual', 'trackerId' => $trackerId));
                    } else {
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                        $response->setContent('Uploaded file is not in correct format. Please upload again');
                        return $response;
                    }
                }
            }
            $view = new ViewModel();
            $view->setVariables(array('form' => $form, 'tracker_id' => $trackerId));
            return $view;
        }
    }

}
