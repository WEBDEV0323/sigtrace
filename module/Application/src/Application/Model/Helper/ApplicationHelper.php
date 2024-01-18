<?php

namespace Application\Model\Helper;

use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;
use Session\Container\SessionContainer;
use Zend\Mvc\Controller\AbstractActionController;

class ApplicationHelper extends AbstractActionController
{
    protected $_adapter;

    /**
     * Make the Adapter object available as local protected variable
     *
     * @param Adapter $adapter - DB PDO PgSQL conn
     */
    public function __construct(Adapter $adapter)
    {
        $this->_adapter = $adapter;
    }
    
    public function getWorkflows($trackerId, $formId, $roleName, $roleId)
    {
        $resArr = array();
        if ($roleName != 'SuperAdmin' && $roleName != 'Administrator') {
            $queryTracker1 = "SELECT tracker . tracker_id,  form.form_id, form.form_name
            FROM tracker
            JOIN form ON tracker.tracker_id = form.tracker_id
            JOIN form_access_setting on form_access_setting.form_id=form.form_id
            AND form_access_setting.role_id=$roleId
            AND form_access_setting.can_read='Yes'
            WHERE tracker.tracker_id = ?";
        } else {
            $queryTracker1 = "SELECT tracker.tracker_id,  form.form_id, form.form_name
            FROM tracker
            JOIN form ON tracker.tracker_id = form.tracker_id
            WHERE tracker.tracker_id = ?";
        }
        
        
        $statements1 = $this->_adapter->createStatement($queryTracker1, array($trackerId));
        $statements1->prepare();
        $results1 = $statements1->execute();
        $resultSet1 = new ResultSet;
        $resultSet1->initialize($results1);
        $resultSet1->buffer();
        $count1 = $resultSet1->count();
        if ($count1 > 0) {
            $resArr['forms'] = $resultSet1->toArray();
            foreach ($resArr['forms'] as $k=>$v) {
                $q = "SELECT workflow_id,workflow_name FROM workflow WHERE form_id = ? AND workflow_type = '2' ORDER BY sort_order ASC";
                $s = $this->_adapter->createStatement($q, array($v['form_id']));
                $s->prepare();
                $r = $s->execute();
                $rs = new ResultSet;
                $rs->initialize($r);
                $rs->buffer();
                $c = $rs->count();
                if ($c > 0) {
                    $resArr['forms'][$k]['workflows'] = $rs->toArray();
                }
            }
        }

        $queryTracker2 = "SELECT * FROM tracker WHERE tracker_id = ?";
        $statements2 = $this->_adapter->createStatement($queryTracker2, array($trackerId));
        $statements2->prepare();
        $results2 = $statements2->execute();
        $resultSet2 = new ResultSet;
        $resultSet2->initialize($results2);
        $resultSet2->buffer();
        $count2 = $resultSet2->count();
        if ($count2 > 0) {
            $resArr['tracker_details'] = $resultSet2->toArray();
        }
        
        $queryTracker3 = "SELECT form_id,workflow_id,workflow_name FROM workflow WHERE form_id = ? AND workflow_type=1 ORDER BY sort_order ASC";
        
        $statements3 = $this->_adapter->createStatement($queryTracker3, array($formId));
        $statements3->prepare();
        $results3 = $statements3->execute();
        $resultSet3 = new ResultSet;
        $resultSet3->initialize($results3);
        $resultSet3->buffer();
        $count3 = $resultSet3->count();
        if ($count3 > 0) {
            $resArr['workflows'] = $resultSet3->toArray();
        }
        return $resArr;
    }
    
    public function getReports($trackerId, $formId, $roleName, $roleId, $userId)
    {

            $options['default'] = $options['allocation'] = $options['custom'] = array();

            $optionRecords = $this->getAllReports($formId, $roleId, $roleName);
        foreach ($optionRecords as $option) {
            $options['default'][] = array('value' => $option['report_id'], 'label' => $option['report_name'],'query_type' => ['report_query_type']);
        }

            $allocationRecords = $this->getAllocationType($formId, $roleId, $trackerId, $roleName);
        foreach ($allocationRecords as $option) {
            $options['allocation'][] = array('value' => $option['allocation_id'], 'label' => $option['allocation_name']);
        }

            $customReports = $this->getCustomReports($trackerId, $formId, $userId);
        foreach ($customReports as $option) {
            $options['custom'][] = array('value' => $option['cr_id'], 'label' => "CR - ".$option['custom_report_name']);
        } 
        return $options;
    }
    public function getAllReports($formId, $roleId, $roleName)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('report');
        $select->columns(array('report_id', 'report_name','report_query_type'));
        if ($roleName != 'SuperAdmin' && $roleName != 'Administrator') {
            $select->join('report_access_setting', 'report_access_setting.report_id = report.report_id');
            $select->where(
                array('report.form_id' => $formId,
                'report.archived' => "No",
                'report_access_setting.can_access' => 'Yes',
                'report_access_setting.role_id' => $roleId
                )
            );
        } else {
            $select->where(
                array('report.form_id' => $formId,'report.archived' => "No",
                )
            );
        }
        $newadpater = $this->_adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    
    public function getAllocationType($form_id, $role_id, $tracker_id, $role_name)
    {

        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        if ($role_name != 'SuperAdmin' && $role_name != 'Administrator') {
            $select->from('allocation_details');
            $select->join('report_access_setting', 'report_access_setting.report_id = allocation_details.allocation_id');
            $select->where(
                array('allocation_details.form_id' => $form_id,
                'report_access_setting.can_access' => 'Yes',
                'report_access_setting.role_id' => $role_id
                )
            );
        } else {
            $select->from('allocation_details');
            $select->where(
                array('allocation_details.form_id' => $form_id,
                )
            );
        }
        // echo $select;
        $newadpater = $this->_adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        // print_r($arr);die;
        return $arr;
    }
    
    public function getCustomReports($tracker_id, $action_id, $u_id)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select()->from('custom_report')->columns(array('cr_id', 'custom_report_name'))->where(array('form_id' => $action_id, 'created_by' => $u_id));

        $newadpater = $this->_adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    
    public function getCalendarData($trackerId,$formId) 
    {
        return array($trackerId,$formId);
    }
    public function dashboardResults()
    {
        $session = new SessionContainer();
        $container = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');
        $userDetails = isset($container->user_details)?$container->user_details:array();
        $uId = isset($container->u_id)?$container->u_id:0;
        $roleId = isset($userDetails['group_id'])?$userDetails['group_id']:0;
        $queryTracker = "SELECT DISTINCT user_role_tracker . u_id , tracker.name, tracker.tracker_id
            FROM user_role_tracker
            JOIN `role` ON role.rid = user_role_tracker.group_id
            LEFT JOIN tracker ON role.tracker_id = tracker.tracker_id
            WHERE user_role_tracker.u_id = $uId AND tracker.archived=?";
        $statements = $this->_adapter->createStatement($queryTracker, array('0'));
        if ($roleId == 1) {
            $queryTracker = "SELECT tracker.*  FROM tracker join `client` on `tracker`.client_id=`client`.client_id where `tracker`.archived=? AND `client`.archived=?";
            $statements = $this->_adapter->createStatement($queryTracker, array('0', '0'));
        }

        $statements->prepare();
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        $retArr['trackerCounts'] = $count;
        $retArr['trackers'] = $arr;
        $trackerIds = array();
        foreach ($arr as $key => $value) {
            $trackerIds[] = $value['tracker_id'];
            $statements = $this->_adapter->createStatement("SELECT form_id FROM form WHERE tracker_id = ? LIMIT 1", array($value['tracker_id']));
            $statements->prepare();
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            $formId = 0;
            if ($count > 0) {
                $Id = $resultSet->toArray();
                $formId = $Id[0]['form_id'];
            }
            $retArr['trackers'][$key]['form_id'] = $formId;
        }
        $trackerContainer->tracker_ids = $trackerIds;        
        return $retArr;
    }
    public function getCodelistData($codelist_id)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select()->from('code_list_option')->columns(array('value', 'label'))->where(array('code_list_id' => $codelist_id));

        $newadpater = $this->_adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr; 
    }
    public function getData($dataQuery) 
    {
        $arr = array();
        $statement = $this->_adapter->createStatement($dataQuery);
        $statement->prepare();
        $results = $statement->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    
    public function dbConnection()
    {
        return $this->getServiceLocator()->get('trace')->getDriver()->getConnection();
    }
    public function getValidationWorkflows($formId)
    {
        $connection = $this->dbConnection();
        $qry = "SELECT form_id,workflow_id,workflow_name FROM workflow WHERE form_id = ".$formId." ORDER BY sort_order ASC";
        $statement = $connection->execute($qry)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (empty($result)) {
            return 0;
        } else {
            return $result;
        }
    }
    public function getResources($grpId,$trackerId,$userRoleType)
    {
        $resources = $resources_dump = $final_resources = $sort_resources = $permitted_resources = array();
        $connection = $this->dbConnection();
        $resource_query = "SELECT * FROM permission";
        $statement = $connection->execute($resource_query)->getResource();
        $resources_dump = $resources = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        echo "<pre>"; print_r($resources); die;
        foreach ($resources as $r) {
            switch($r['have_blocks']) {
            case 1:
                $final_resources[$r['id']] = $r;
                $sort_resources[$r['permission_block_level']] = $r;
                foreach ($resources_dump as $dump) {
                    if ($dump['permission_group_id'] == $r['id']) {
                        $final_resources[$r['id']]['blocks'][$dump['id']] = $dump;
                        $sort_resources[$r['permission_block_level']]['blocks'][$dump['permission_block_level']] = $dump;
                    }
                }
                break;
            case 0:
                if ($r['permission_group_id'] == 0) {
                    $final_resources[$r['id']] = $r;
                    $sort_resources[$r['permission_block_level']] = $r;
                }
                break;
            default:
                break;
            }
        }
        
        $permissionsQry = "SELECT permission_id FROM role_permission WHERE role_id = ".$grpId." AND tracker_id=".$trackerId;
        $statement1 = $connection->execute($permissionsQry)->getResource();
        $permitted_resourcesArray = $statement1->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($permitted_resourcesArray as $permitted) {
            $permitted_resources[] = $permitted['permission_id'];
        }
        $statement1->closeCursor();
        // Menu related functions
        ksort($sort_resources);
        foreach ($sort_resources as $key=>$val) {
            if (isset($sort_resources[$key]['blocks'])) {
                ksort($sort_resources[$key]['blocks']);
            }
        }
        $top_menu = $side_menu = array();

        foreach ($sort_resources as $r) {
            if ($r['top_menu'] == 1) {
                $top_menu[$r['id']] = $r;
                switch($r['have_blocks']) {
                case 1:
                    if (isset($top_menu[$r['id']]['blocks'])) {
                        foreach ($top_menu[$r['id']]['blocks'] as $block) {

                            if ((int)$block['top_menu'] != 1) {
                                unset($top_menu[$r['id']]['blocks'][$block['permission_block_level']]);
                            } else if (!in_array($block['id'], $permitted_resources)&& $userRoleType != 1) {
                                unset($top_menu[$r['id']]['blocks'][$block['permission_block_level']]);
                            }
                        }
                    } else { 
                        $top_menu[$r['id']]['blocks']=array();
                    }
                    break;
                case 0:
                    if ((int)$r['top_menu'] != 1) {
                        unset($top_menu[$r['id']]);
                    } else if (!in_array($r['id'], $permitted_resources) && $userRoleType != 1) {
                        unset($top_menu[$r['id']]);
                    }
                    break;
                default:
                    break;
                }
            }
            if ($r['side_menu'] == 1) {
                $side_menu[$r['id']] = $r;
                switch($r['have_blocks']) {
                case 1:
                    if (isset($side_menu[$r['id']]['blocks'])) {
                        foreach ($side_menu[$r['id']]['blocks'] as $block) {

                            if ($block['side_menu'] != 1) {
                                unset($side_menu[$r['id']]['blocks'][$block['permission_block_level']]);
                            } else if (!in_array($block['id'], $permitted_resources)&& $userRoleType != 1) {
                                unset($side_menu[$r['id']]['blocks'][$block['permission_block_level']]);
                            }
                        }
                    } else {
                        $side_menu[$r['id']]['blocks']=array();
                    }
                    break;
                case 0:
                    if ($r['side_menu'] != 1) {
                        unset($side_menu[$r['id']]);
                    } else if (!in_array($r['id'], $permitted_resources) && $userRoleType != 1) {
                        unset($side_menu[$r['id']]);
                    }
                    break;
                default:
                    break;
                }
            }
        }
        return array($top_menu,$side_menu,$permitted_resources);
    }

    public function getFormsInfo($formId)
    {
        $connection = $this->dbConnection();
        $qry = "SELECT form_id,form_name FROM form WHERE form_id = ".$formId;
        $statement = $connection->execute($qry)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (empty($result)) {
            return 0;
        } else {
            return $result[0];
        }
    }
    
    public function getAppConfigs()
    {
        $query="SELECT config_key,config_value FROM `config`";
        $statements = $this->_adapter->createStatement($query);
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $res = array();
        if ($count > 0) {
            $res = array_column($resultSet->toArray(), 'config_value', 'config_key');       
        }
        return $res;
    }
    
    public function getNavigations($grpId, $trackerId, $userRoleType)
    {
        $resources = $resources_dump = $final_resources = $sort_resources = $permitted_resources = array();
        $connection = $this->dbConnection();
        $resource_query = "SELECT a.* ,c.controller_name FROM action a LEFT JOIN controller c ON a.controller_id = c.controller_id WHERE a.archived = 0";
        $statement = $connection->execute($resource_query)->getResource();
        $resources_dump = $resources = $secondLevel = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        
        foreach ($resources as $r) {
            switch($r['have_blocks']) {
            case 1:
                if ($r['block_level'] == 1) {
                    $final_resources[$r['action_id']] = $r;
                    $sort_resources[$r['action_block_level']] = $r;
                    foreach ($resources_dump as $dump) {
                        if ($r['action_id'] == $dump['action_group_id']) {
                            $final_resources[$r['action_id']]['blocks'][$dump['action_id']] = $dump;
                            $sort_resources[$r['action_block_level']]['blocks'][$dump['action_block_level']] = $dump;
                            if ($dump['block_level'] == 2) {
                                foreach ($secondLevel as $second) {
                                    if ($second['action_group_id'] == $dump['action_id']) {
                                        $final_resources[$r['action_id']]['blocks'][$dump['action_id']]['blocks'][$second['action_id']] = $second; 
                                        $sort_resources[$r['action_block_level']]['blocks'][$dump['action_block_level']]['blocks'][$second['action_block_level']] = $second;
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
        
        $permissionsQry = "SELECT permission_id FROM role_permission WHERE role_id = ".$grpId." AND tracker_id=".$trackerId;
        $statement1 = $connection->execute($permissionsQry)->getResource();
        $permitted_resourcesArray = $statement1->fetchAll(\PDO::FETCH_ASSOC);
        $permitted_resources = array_column($permitted_resourcesArray, "permission_id");
        $statement1->closeCursor();
        // Menu related functions
        ksort($sort_resources); 
        foreach ($sort_resources as $key=>$val) {
            if (isset($sort_resources[$key]['blocks'])) {
                ksort($sort_resources[$key]['blocks']);
            }
        }
        $top_menu = $side_menu = array();
        foreach ($sort_resources as $r) {
            if ($r['top_menu'] == 1) {
                $top_menu[$r['action_id']] = $r;
                switch($r['have_blocks']) {
                case 1:
                    if ($r['block_level'] == 1) {
                        foreach ($top_menu[$r['action_id']]['blocks'] as $block) {
                            if ($block['top_menu'] != 1) {   
                                unset($top_menu[$r['action_id']]['blocks'][$block['action_block_level']]);   
                            } else {
                                if (isset($block['blocks'])) {
                                    foreach ($block['blocks'] as $b) {

                                        if ($b['top_menu'] != 1) {  
                                            unset($top_menu[$r['action_id']]['blocks'][$block['action_block_level']]['blocks'][$b['action_block_level']]);
                                        } else {
                                            if (!in_array(trim($b['action_id']), $permitted_resources) && $userRoleType != 1) {
                                                unset($top_menu[$r['action_id']]['blocks'][$block['action_block_level']]['blocks'][$b['action_block_level']]);
                                            }
                                        }
                                    }
                                    if (isset($top_menu[$r['action_id']]['blocks'][$block['action_block_level']]['blocks']) && empty($top_menu[$r['action_id']]['blocks'][$block['action_block_level']]['blocks']) && $top_menu[$r['action_id']]['blocks'][$block['action_block_level']]['controller_id'] == '0') {
                                        unset($top_menu[$r['action_id']]['blocks'][$block['action_block_level']]); 
                                    }
                                } else {
                                    if (!in_array($block['action_id'], $permitted_resources) && $userRoleType != 1) {
                                        unset($top_menu[$r['action_id']]['blocks'][$block['action_block_level']]);  
                                    } 
                                }
                            }
                        }
                    }
                    break;
                case 0:
                    if ($r['top_menu'] != 1) { 
                        unset($top_menu[$r['action_id']]); 
                    } else if (!in_array($r['action_id'], $permitted_resources) && $userRoleType != 1) {
                        unset($top_menu[$r['action_id']]); 
                    }
                    break;
                default:
                    break;
                }
            }

            if ($r['side_menu'] == 1) {
                $side_menu[$r['action_id']] = $r;
                switch($r['have_blocks']) {
                case 1: 
                    if (isset($side_menu[$r['action_id']]['blocks'])) {
                        foreach ($side_menu[$r['action_id']]['blocks'] as $block) {
                            if ($block['side_menu'] != 1) {
                                unset($side_menu[$r['action_id']]['blocks'][$block['action_block_level']]); 
                            } else if (!in_array($block['action_id'], $permitted_resources) && $userRoleType != 1) {
                                unset($side_menu[$r['action_id']]['blocks'][$block['action_block_level']]);  
                            }
                        }                        
                    }
                    break;
                case 0:
                    if ($r['side_menu'] != 1) {
                        unset($side_menu[$r['action_id']]);
                    } else if (!in_array($r['action_id'], $permitted_resources) && $userRoleType != 1) {
                           unset($side_menu[$r['action_id']]); 
                    }
                    break;
                default:
                    break;
                }
            }
        }
        return array($top_menu,$side_menu,$permitted_resources);
    }
}
