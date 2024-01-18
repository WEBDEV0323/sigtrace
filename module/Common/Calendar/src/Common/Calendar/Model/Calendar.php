<?php

namespace Common\Calendar\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Session\Container\SessionContainer;

class Calendar
{
    protected $_calendarServiceLocator;

    /**
     * Make the Adapter object available as local protected variable
     *
     * @param Adapter $adapter - DB PDO PgSQL conn
     */
    public function __construct(Adapter $adapter)
    {
        $this->_adapter = $adapter;
    }

     /**
      * @return result object
      */
    
    public function trackerResults($trackerId)
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        
        $userDetails = $userContainer->user_details;
        $roleId = (int)$userDetails['group_id'] ?? 0;
        $roleName = $userDetails['group_name'];
        if (isset($trackerContainer->tracker_user_groups) && $roleName != 'SuperAdmin' && $trackerId != 0) {
            $trackerUserGroups = $trackerContainer->tracker_user_groups;
            if (!array_key_exists($trackerId, $trackerUserGroups)) {
                $applicataionModel= new \Application\Model\AdminMapper($this->_adapter);
                $applicataionModel->accessTrackerGroups($userDetails['u_id'], $roleId, $userDetails['user_type']);
                $trackerUserGroups = $trackerContainer->tracker_user_groups;
            }
            $roleName = (isset($trackerUserGroups[$trackerId]))?$trackerUserGroups[$trackerId]['session_group']:'';
            $roleId = (isset($trackerUserGroups[$trackerId]))?(int)$trackerUserGroups[$trackerId]['session_group_id'] ?? 0:0;
        }
        if ($roleName != 'SuperAdmin' && $roleName != 'Administrator') {
            $queryTracker = "SELECT tracker . tracker_id,  form.form_id, form.form_name
            FROM tracker
            JOIN form ON tracker.tracker_id = form.tracker_id
            Join form_access_setting on form_access_setting.form_id=form.form_id
            and form_access_setting.role_id=$roleId
            and form_access_setting.can_read='Yes'
            WHERE tracker.tracker_id = ?";
        } else {
            $queryTracker = "SELECT tracker . tracker_id,  form.form_id, form.form_name
            FROM tracker
            JOIN form ON tracker.tracker_id = form.tracker_id
            WHERE tracker.tracker_id = ?";
        }
        
        $statements = $this->_adapter->createStatement($queryTracker, array($trackerId));
        $statements->prepare();
        $resArr = array();
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            $resArr['forms'] = $arr;
        }

        $queryTracker = "SELECT * FROM tracker WHERE tracker_id = ?";
        $statements = $this->_adapter->createStatement($queryTracker, array($trackerId));
        $statements->prepare();
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            $resArr['tracker_details'] = $arr[0];
        }
        return $resArr;
    }
    
    public function getUserActivityList($trackerId,$month) 
    {
        $resArr = array();
        $queryTracker = "SELECT u.u_name, ce.event_name, ce.colour_code, ue.start_date, ue.end_date, ue.event_data
                        FROM user_event ue
                        JOIN calendar_event ce ON ue.event_id = ce.id
                        JOIN user u ON ue.u_id = u.u_id
                        WHERE ue.customer_id = ? AND ue.is_archived = ? AND (YEAR(ue.start_date) = YEAR(?) AND MONTH(ue.start_date) = MONTH(?) OR YEAR(ue.end_date) = YEAR(?) AND MONTH(ue.end_date) = MONTH(?))";
        $statements = $this->_adapter->createStatement($queryTracker, array($trackerId,0,$month,$month,$month,$month));
        $statements->prepare(); //print_r( $statements);die;
        $results = $statements->execute();       
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $arr = $resultSet->toArray();
            $resArr['event_details'] = $arr;
            $resArr['user_names']=array();
            foreach ($arr as $key ) {
                if (!in_array($key['u_name'], $resArr['user_names'])) {
                    $resArr['user_names'][]=$key['u_name'];
                }                
            }
        } //print_r($resArr);die;
        return $resArr;
    }
    
    public function getGeneriActivityList($trackerId,$month) 
    {
        $resArr = array();
        $queryTracker = "SELECT ce.event_name, ce.colour_code, ue.start_date, ue.end_date, ue.event_data
                        FROM user_event ue
                        JOIN calendar_event ce ON ue.event_id = ce.id                        
                        WHERE ue.customer_id = ? AND ue.is_archived = ? AND  ue.u_id = ? AND (YEAR(ue.start_date) = YEAR(?) AND MONTH(ue.start_date) = MONTH(?) OR YEAR(ue.end_date) = YEAR(?) AND MONTH(ue.end_date) = MONTH(?) ) ";
        $statements = $this->_adapter->createStatement($queryTracker, array($trackerId,0,0,$month,$month,$month,$month));
        $statements->prepare();//print_r( $statements);die;
        $results = $statements->execute();        
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $resArr = $resultSet->toArray();
            
        } //print_r($resArr);die;
        return $resArr;
    }
    
    public function getUsernames($trackerId) 
    {      
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();                
        $select ->from('user')              // need to add distinct here.
            ->join('user_role_tracker', 'user_role_tracker.u_id = user.u_id', array('u_id'))
            ->join('role', 'user_role_tracker.group_id = role.rid', array('rid'));       
        $select->columns(array('u_name'));
        $select->where(array('role.tracker_id' => $trackerId));
        $selectString = $sql->prepareStatementForSqlObject($select); //echo ($sql->getSqlStringForSqlObject ($select));die;
        $results = $selectString->execute(); 
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $arr = $resultSet->toArray();
            foreach ($arr as $key ) {
                if (!in_array($key['u_name'], $resArr)) {
                    $resArr[]=$key['u_name'];
                }                
            }
        }
        return $resArr;
    }

    
    public function getProductReportDates($trackerId,$formId) 
    {
        $fields= $this->getFieldsToShowInCalendar($formId);
        if (!isset($fields['field_name'])) {
            return array();
        }
        $fieldNames = $fields['field_name'];
        $condition = $this->getCondition($formId);
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();                
        $select ->from('form_'.$trackerId.'_'.$formId)              
            ->columns($fieldNames);
        if (count($condition) > 0) {
            $select->where(array($condition['calendar_report_condition']));
        }
        $selectString = $sql->prepareStatementForSqlObject($select); //echo ($sql->getSqlStringForSqlObject ($select));die;
        $results = $selectString->execute(); 
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
             $resArr['list'] = $resultSet->toArray();           
        } 
        $resArr['fields'] = $fields;
        return $resArr;    
    }
    public function getCondition($formId)
    {
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('config')
            ->columns(array('config_key', 'config_value'));
        $select->where(array('scope_level' =>'Form','scope_id'=>$formId));
        $selectString = $sql->prepareStatementForSqlObject($select); //echo ($sql->getSqlStringForSqlObject ($select));die;
        $results = $selectString->execute(); 
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $arr = $resultSet->toArray();
            foreach ($arr as $config) {
                $resArr[$config['config_key']] = $config['config_value'];
            }
        } //print_r($resArr);die;
        return $resArr;    
    }

    public function getColorCodes($trackerId)
    {
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('calendar_event')
            ->columns(array('event_name','colour_code'));
        $select->where(array('event_type' => null,'customer_id'=>$trackerId));
        $selectString = $sql->prepareStatementForSqlObject($select); //echo ($sql->getSqlStringForSqlObject ($select));die;
        $results = $selectString->execute(); 
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $arr = $resultSet->toArray();
            foreach ($arr as $event) {
              
                $resArr[$event['event_name']] = $event['colour_code'];
            }
            
        } //print_r($resArr);die;
        return $resArr;        
    }
    public function getColorCodesWithFieldMapping($trackerId)
    {
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('calendar_event')
            ->columns(array('event_name','colour_code','mapped_field_id'));
        $select->where(array('event_type' => null,'customer_id'=>$trackerId,'mapped_field_id'!=null));
        $selectString = $sql->prepareStatementForSqlObject($select); //echo ($sql->getSqlStringForSqlObject ($select));die;
        $results = $selectString->execute(); 
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $arr = $resultSet->toArray();
            foreach ($arr as $event) {
              
                $resArr[$event['mapped_field_id']] = array($event['colour_code'], $event['event_name']);
            }
            
        } 
        return $resArr;        
    }
    public function getFieldsToShowInCalendar($formId)
    {
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('field')
            ->join('workflow', 'workflow.workflow_id = field.workflow_id', array('workflow_id'))
            ->columns(array('field_name', 'label'));                
        $select->where(array('workflow.form_id' => $formId,'field.show_in_calendar'=>1))
            ->order('field.field_id');
        $selectString = $sql->prepareStatementForSqlObject($select); //echo ($sql->getSqlStringForSqlObject ($select));die;
        $results = $selectString->execute();        
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
             $arr = $resultSet->toArray();
            foreach ($arr as $value) {
                $resArr['field_name'][]=$value['field_name'];
                $resArr['label'][]=$value['label'];
            }
            
        } 
        return $resArr;    
    }
    public function getHolidayList($trackerId,$eventType,$user) 
    {
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('user_event')
            ->join('calendar_event', 'user_event.event_id = calendar_event.id', array('event_name','event_type'));
        $select->columns(array('id','event_data','start_date','end_date','event_data'));
        $select->where(array('user_event.is_archived' => 0, 'user_event.customer_id' => $trackerId, 'user_event.u_id' => $user,'calendar_event.event_type'=>$eventType));
        $selectString = $sql->prepareStatementForSqlObject($select); //echo ($sql->getSqlStringForSqlObject ($select));die;
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
    
    public function getEventType($trackerId) 
    {
        $resArr = array();
        $queryTracker = "SELECT DISTINCT(event_type) FROM calendar_event where customer_id= ? ";
        $statements = $this->_adapter->createStatement($queryTracker, array($trackerId));
        $statements->prepare();
        $results = $statements->execute();        
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $arr = $resultSet->toArray();
            foreach ($arr as $value) {
                $resArr[]=$value['event_type'];
            }
        }
        return $resArr;
    }
    
    public function getEventNames($trackerId,$type) 
    {
        $resArr = array();        
        $queryTracker = "SELECT DISTINCT(event_name),id FROM calendar_event where customer_id= ? AND event_type in (?) ";                    
        $statements = $this->_adapter->createStatement($queryTracker, array($trackerId,$type));
        $statements->prepare();
        $results = $statements->execute();        
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();     
        $arr = array();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            foreach ($arr as $value) {
                $resArr['event_name'][]=$value['event_name'];
                $resArr['event_id'][]=$value['id'];
            }            
        } return $arr;
    }
    
    public function saveEvent($data) 
    {
        try{
            $sql = new Sql($this->_adapter);
            $insert=$sql->insert('user_event');
            $insert->values($data);
            $selectString = $sql->prepareStatementForSqlObject($insert);
            $selectString->execute();
            $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();
            return $lastInsertID;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function getEventData($eventId) 
    {
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from('user_event')
                ->join('calendar_event', 'user_event.event_id = calendar_event.id', array('event_type','event_name'));
            $select->columns(array('event_id','event_data','start_date','end_date'));
            $select->where(array('user_event.id ' => $eventId));
            $selectString = $sql->prepareStatementForSqlObject($select);
            $results = $selectString->execute();
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

    public function saveEditEvent($data,$id) 
    {
        
            $sql = new Sql($this->_adapter);
            $update=$sql->update();
            $update->table('user_event');
            $update->set($data);
            $update->where(array('id'=>$id));        
            $selectString = $sql->prepareStatementForSqlObject($update);        //echo ($sql->getSqlStringForSqlObject ($update));die;
        try { 
            $result= $selectString->execute();
            return $count = $result->count();
        } catch (\Exception $e) {
            //                $result = $e->getMessage(); 
            return 0;
        }            
    }

    public function deleteEvent($id) 
    {
        $lastModifiedTime=date("Y-m-d H:i:s");
        try { 
            $sql = new Sql($this->_adapter);
            $update = $sql->update('user_event')
                ->set(array('is_archived' => 1,'last_modified_time'=>$lastModifiedTime))
                ->where(
                    array(
                    'id' => $id
                    )
                );
            $selectString = $sql->prepareStatementForSqlObject($update); //echo ($sql->getSqlStringForSqlObject ($update));die;
            $results = $selectString->execute();
            return $count = $results->count();
        } catch (\Exception $e) {
            return 0;
        }
    }      
    
    public function checkDuplicateEvent($uId, $trackerId,$id) 
    {
        //$uId=0;
        try { 
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from('user_event')
                ->join('calendar_event', 'user_event.event_id = calendar_event.id', array('event_name'));
            $select->columns(array('id','start_date','end_date','event_data'));
                $select->where(array('u_id' => array(0,$uId), 'user_event.customer_id' => $trackerId, 'user_event.is_archived' =>0));
            if ($id != 0) {
                $select->where($select->where->notEqualTo('user_event.id', $id));            
            }
            $select->order('start_date ASC');
            $selectString = $sql->prepareStatementForSqlObject($select); //echo ($sql->getSqlStringForSqlObject ($select));die;
            $results = $selectString->execute(); 
            $arr = array();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                $arr = $resultSet->toArray();
            }
            return $arr;
        } catch (\Exception $e) {
            echo $e->getMessage();
            return 0;
        }
    }
    
    public function getLegend($trackerId) 
    {
        try
        {
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from('calendar_event');
            $select->columns(array('event_name','colour_code'));
            $select->where(array('customer_id' => $trackerId))
                ->order('id ASC');
            $selectString = $sql->prepareStatementForSqlObject($select); //echo ($sql->getSqlStringForSqlObject ($select));die;
            $results = $selectString->execute(); 
            $arr = array();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                $arr = $resultSet->toArray();
            }
            return $arr;
        } catch (Exception $ex) {
            echo $ex->getMessage();
            return 0;
        }
    }
    
    public function getConfigDataByForm($formId)
    {
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('config');
        $select->columns(array('config_key','config_value'));
        $select->where(array('scope_id'=>$formId));
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
}
