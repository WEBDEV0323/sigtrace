<?php
namespace SigTRACE\Dashboard\Model;

use Zend\Mvc\Controller\AbstractActionController;
use Session\Container\SessionContainer;
use Zend\Db\adapter\adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;

class Dashboard extends AbstractActionController
{
    protected $_adapter;

    public function __construct(adapter $_adapter)
    {
        $this->_adapter = $_adapter;
    }
    /**
     * Function used to get instance of DB 
     *
     * @return instance 
     */
    public function dbConnection()
    {
         return $this->getServiceLocator()->get('trace')->getDriver()->getConnection();
    }
    public function getAllProducts($trackerId, $formId, $dateRangeSql)
    {
        $connection = $this->dbConnection();
        $query = "CALL sp_getAllProducts($trackerId,$formId)";
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (empty($result)) {
            return array();  
        } else {
            return $result; 
        }  
    }
    
    public function getQuantitativeProductsList($trackerId, $formId, $productId, $preferredTerm, $socName, $seriousNess, $type)
    {
        $connection = $this->dbConnection();
        $query = "CALL sp_getQuantitativeList($trackerId,$formId,$productId,$preferredTerm,$socName,$seriousNess,'$type')";
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (empty($result)) {
            return array();  
        } else {
            return $result; 
        }  
    }
    
    public function getDashboard($trackerId = 0,$formId = 0)
    {
        $r1=$r2=$r3=$r4=$r5=$r6=$r7=$r8=$r9=$r10=$r11=$r12= array();
        if ($trackerId !== 0 && $formId !== 0) {
            $connection = $this->dbConnection();
            $query = "CALL sp_getDashboard($trackerId,$formId)";
            $statement = $connection->execute($query)->getResource();
            $r1 = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $statement->nextRowSet();
            $r2 = $statement->fetchAll(\PDO::FETCH_ASSOC); 
            $statement->nextRowSet();
            $r3 = $statement->fetchAll(\PDO::FETCH_ASSOC); 
            $statement->nextRowSet();
            $r4 = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $statement->nextRowSet();
            $r5 = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $statement->nextRowSet();
            $r6 = $statement->fetchAll(\PDO::FETCH_ASSOC); 
            $statement->nextRowSet();
            $r7 = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $statement->nextRowSet();
            $r8 = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $statement->nextRowSet();
            $r9 = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $statement->nextRowSet();
            $r10 = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $statement->nextRowSet();
            $r11 = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $statement->nextRowSet();
            $r12 = $statement->fetchAll(\PDO::FETCH_ASSOC);

            $statement->closeCursor();
        } 
        return array($r1,$r2,$r3,$r4,$r5,$r6,$r7,$r8,$r9,$r10,$r11,$r12);  
        
    }
    public function getFatalData($trackerId,$formId)
    {
        $connection = $this->dbConnection();
        // $query = 'CALL sp_fatalCumulativeAnalysis('.$trackerId.','.$formId.')';
        $query = 'CALL sp_importAndDisplayList('.$trackerId.','.$formId.',0,0,"",0,"",0,"","","FATAL")';
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_NUM);
        $statement->closeCursor();
        if (empty($result)) {
            return array();  
        } else {
            return $result; 
        }   
    }
    
    public function getHeaderList($formId)
    {
        $connection = $this->dbConnection();
        $query = "SELECT fd.label FROM field fd 
                LEFT JOIN workflow w ON w.workflow_id = fd.workflow_id
                LEFT JOIN form f ON f.form_id = w.form_id
                where f.form_id = ".$formId;
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (empty($result)) {
            return array();  
        } else {
            return $result; 
        } 
    }
    
    public function dashboardResults($uId, $roleId)
    {
        $connection = $this->dbConnection();
        $session = new SessionContainer();
        $container_trackerids = $session->getSession('trackerids');
        $queryTracker = "SELECT DISTINCT user_role_tracker.u_id,tracker.name,tracker.tracker_id
            FROM user_role_tracker
            JOIN `role` ON role.rid = user_role_tracker.group_id
            LEFT JOIN tracker ON role.tracker_id = tracker.tracker_id
            WHERE user_role_tracker.u_id = $uId AND tracker.archived=0";

        if ($roleId == 1) {
            $queryTracker = "SELECT tracker.*  FROM tracker where archived=0";
        }
        $statement = $connection->execute($queryTracker)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        
        $trackerIds = array();
        foreach ($result as $key => $value) {
            $trackerIds[] = $value['tracker_id'];
        }
        $container_trackerids->tracker_ids = $trackerIds;
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

    public function getAllProductList($dataArr,$dateRangeSql)
    {
        
        $tabList =  $dataArr['tabData'];
        
        $dbQuery = ($tabList['countQuery'] != '')?$tabList['countQuery']:"";
        $where = ($tabList['where'] != '')?" WHERE ".$tabList['where']:"";
        $groupBy = isset($tabList["groupBy"])?" GROUP BY ".$tabList["groupBy"]:"";
    
        if (isset($tabList["orderBy"]) && $tabList['orderBy']!='') {
            $orderBy =  " ORDER BY ".$tabList["orderBy"];
        } else {
            $orderBy = '';
        }
        $query = $dbQuery." ".$where." ".$dateRangeSql." ".$groupBy." ".$orderBy;
      
        $connection = $this->dbConnection();
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $result;  
    }

    public function getWorkflowList($formId)
    {
        $sql = new Sql($this->_adapter);
        
        $qry='select w.workflow_id, w.workflow_name, q.query from workflow as w join qualitative_setting as q where w.workflow_id = q.workflow_id and w.form_id = ? and w.status = ? and w.workflow_type = ?';
       
        $statements = $this->_adapter->createStatement($qry, array($formId,'Active',1));
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
        return $arr;
        
    }

    public function getWorkflowCondition($workflowId)
    {
        $connection = $this->dbConnection();
        $qry = 'SELECT query from qualitative_setting where workflow_id = '.$workflowId;
        $statement = $connection->execute($qry)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $result;  
    }

    public function getAllTabs($trackerId)
    {
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('dashboard');
        $select->columns(array('dashboard_id','dashboard_name','label','countQuery','where','groupBy','orderBy','formId','within_dashId','filters'));
        $select->where(array('trackerId'=>$trackerId,'archived' => 0));
        $select->order(array('sort_order ASC'));
        $selectString = $sql->prepareStatementForSqlObject($select); 
        $results = $selectString->execute(); 
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $resArr = $resultSet->toArray();
            
        }
        return $resArr; 
    }

    public function getDashboardTabById($dashboardId)
    {
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('dashboard');
        $select->columns(array('dashboard_id','dashboard_name','label','countQuery','where','groupBy','orderBy','formId','within_dashId','filters'));
        $select->where(array('dashboard_id'=>$dashboardId));
        $select->order(array('sort_order ASC'));
        $selectString = $sql->prepareStatementForSqlObject($select); 
        $results = $selectString->execute(); 
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $resArr = $resultSet->toArray();
            
        }
        return $resArr; 
    }

    public function getDashboardById($dashboardId)
    {
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('dashboard');
        $select->columns(array('dashboard_name','label','countQuery','listQuery','listQueryLabels','where','groupBy','orderBy','qualitative_query_count','formId','filters'));
        $select->where(array('dashboard_Id'=>$dashboardId));
        $selectString = $sql->prepareStatementForSqlObject($select); 
        $results = $selectString->execute(); 
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $resArr = $resultSet->toArray();
        }
        return $resArr; 
    }

    public function executeQuery($query)
    {
        try {
            $connection = $this->dbConnection();
            $statement = $connection->execute($query)->getResource();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $statement->closeCursor();
            return $result; 
        } catch(\Exception $e) {
            return $arr;
        } catch(\PDOException $e) {
            return $arr;
        }  
    }

    public function fetchAllData($trackerId, $formId, $dashboardId, $asId, $filter)
    { 
        $listsQueryData = $this->getDashboardById($dashboardId);
        $arr = array(); 
        try{
            if (!empty($listsQueryData)) { 
                foreach ($listsQueryData as $key=>$list) {
                    $dbQuery = ($list['listQuery'] != '')?$list['listQuery']:"";
                    
                    $where = ($list['where'] != '')?" WHERE ".$list['where']:"";
                    $where = $where." and a.as_id = ".$asId;
                    $filter = ($filter != '')?" AND ".$filter:" ";

                    $groupBy = isset($list["groupBy"])?" GROUP BY ".$list["groupBy"]:"";
                    $groupBy = '';
                    $orderBy = isset($list["orderBy"])?" ORDER BY ".$list["orderBy"]:"";
                    $query = $dbQuery." ".$where." ".$filter." ".$groupBy." ".$orderBy;
                    $arr['labels'] = $list['listQueryLabels'];
                   
                    if (!empty($list["qualitative_query_count"])) {
                        $countQuery = json_decode($list["qualitative_query_count"]); 
                        $pendingCountQuery = $countQuery['0']->query." where ".$countQuery['0']->where." and a.as_id =  ".$asId." group by ".$countQuery['0']->group;
                        $qDashboardId=$countQuery['0']->dashboardId;
                        $sPendingCondition=$countQuery['0']->pendingCondition;
                        $sNewCondition=$countQuery['0']->newCondition;
                    }
                } 
                $statements = $this->_adapter->createStatement($query);
                $results = $statements->execute();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $count = $resultSet->count();
                $arr['data'] = array();
                if ($count > 0) {
                    $arr['data'] = $resultSet->toArray();
                }
                if ($pendingCountQuery!='') { 
                    $getPendingCount = $this->executeQuery($pendingCountQuery);
                }
                if (!empty($getPendingCount)) {
                    foreach ($arr['data'] as $key => $data) {
                        foreach ($getPendingCount as  $pCount) { 
                          
                            if (trim($data['pt_medical_concept']) == trim($pCount['ptname'])) { 
                                $queryStringPending = "cond=".base64_encode("ptname='".$pCount['ptname']."' and (".$sPendingCondition.")");
                                $queryStringNew = "cond=".base64_encode("ptname='".$pCount['ptname']."' and (".$sNewCondition.")");
                                $queryStringTotal = "cond=".base64_encode("ptname='".$pCount['ptname']."'");
                                
                                $qCount = '<a href="/dashboard/list/'.$trackerId.'/'.$formId.'/'.$qDashboardId.'/'.$asId.'?'.$queryStringNew.'"> '.$pCount['new'].' </a>/';
                                $qCount .= '<a href="/dashboard/list/'.$trackerId.'/'.$formId.'/'.$qDashboardId.'/'.$asId.'?'.$queryStringPending.'"> '.$pCount['pending_cnt'].' </a>/';
                                $qCount .= '<a href="/dashboard/list/'.$trackerId.'/'.$formId.'/'.$qDashboardId.'/'.$asId.'?'.$queryStringTotal.'"> '.$pCount['total'].' </a>';
                                $arr['data'][$key] = array("qCount" => $qCount) + $arr['data'][$key] ;
                            } else {
                                $qCount =  "0 / 0 / 0";

                                if (!isset($arr['data'][$key]['qCount'])) {
                                    $arr['data'][$key] = array("qCount" => $qCount) + $arr['data'][$key] ;
                                } 
                            } 
                        } 
                        $finalArray[$key] = array("id"=>$arr['data'][$key]['id']) + $arr['data'][$key];
                    }
                    unset($arr['data']);
                    $arr['data'] =array();
                    $arr['data'] =$finalArray;
                } 
                              
                if (!empty($arr['data'])) {
                    $arr['names'] = array_keys($arr['data'][0]);
                }
               
            } else {
                $arr['labels'] = $arr['data'] = $arr['names'] = $names = array();
                $query = "SELECT field_name,label from field where workflow_id = (select workflow_id from workflow where form_id=".$formId." limit 1) AND lower(field_type) != 'heading'";
                $statements = $this->_adapter->createStatement($query);
                $statements->prepare();
                $results = $statements->execute();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $count = $resultSet->count();
                if ($count > 0) {
                    $arr['labels'] = $resultSet->toArray();
                    $arr['names'] = array_column($arr['labels'], 'field_name');
                    $arr['labels'] = implode(",", array_column($arr['labels'], 'label'));
                }
                array_unshift($arr['names'], "id");
                $sql = new Sql($this->_adapter);
                $select = $sql->select();
                $select->from("form_".$trackerId."_".$formId);
                $select->columns($arr['names']);
                $select->where(array('is_deleted' => 'No'));
                $select->order('id DESC');
                $selectString = $sql->prepareStatementForSqlObject($select);
                $dRresults = $selectString->execute();
                $data = array();
                $dResultSet = new ResultSet;
                $dResultSet->initialize($dRresults);
                $dResultSet->buffer();
                $dCount = $dResultSet->count();
                if ($dCount > 0) {
                    $data = $dResultSet->toArray();
                    foreach ($data as $key => $value) {
                        $data[$key]['action'] = $value['id'];
                    }
                }
                $arr['data'] =  $data; 
            }
            return $arr;
        } catch(\Exception $e) {
            return $arr;
        } catch(\PDOException $e) {
            return $arr;
        } 
        
    }

    public function sourceActions($trackerId) 
    {
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('source');
    
        $select->where(array('tracker_id'=>$trackerId));
                
        $selectString = $sql->prepareStatementForSqlObject($select); 
        $results = $selectString->execute(); 
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $resArr = $resultSet->toArray();
        }
        return $resArr;
    }
    
    public function fetchBulkActions($formId, $userRoleId, $userRoleType) 
    {
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('bulk_action');
        if ($userRoleType != 1) {
            $select->where(array('form_id'=>$formId,'status' => 'Active', 'role_id' =>$userRoleId));
        } else {
            $select->where(array('form_id'=>$formId,'status' => 'Active'));
            $select->group('action_name');
        }
        
        $selectString = $sql->prepareStatementForSqlObject($select); 
        $results = $selectString->execute(); 
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $resArr = $resultSet->toArray();
        }
        return $resArr;
    }
    
    public function canInsert($formId, $userRoleId, $sessionRoleName) 
    {
        $sql = new Sql($this->_adapter);
        $arr = array();
        if ($sessionRoleName == 'Administrator' || $sessionRoleName == 'SuperAdmin') {
            
            $qry='SELECT record_name FROM form where form.form_id=?';
            $statements = $this->_adapter->createStatement($qry, array($formId));
            $statements->prepare(); 
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                $arr = $resultSet->toArray();
            }
            $arr[0]['can_insert']='Yes';
        } else {
           
            $qry='select workflow_role.can_insert ,workflow_role.workflow_id, form.record_name from workflow_role
                        join workflow on workflow.workflow_id = workflow_role.workflow_id
                        join form on workflow.form_id = form.form_id
                        where workflow.workflow_id in (select workflow.workflow_id from workflow where workflow.form_id= ? ) 
                        and workflow_role.role_id =? group by workflow_role.can_insert';
            $statements = $this->_adapter->createStatement($qry, array($formId,$userRoleId));
            $statements->prepare(); 
            $results = $statements->execute();          
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                $arr = $resultSet->toArray();
            }
        }
        return $arr;
    }
}
