<?php

namespace Common\Notification\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\AdapterAwareInterface;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;
use Session\Container\SessionContainer;
use Application\Controller\IndexController;

//require './library/Swiftmailer/lib/swift_required.php';
use Zend\Db\ResultSet\ResultSetInterface;

class Email
{
    protected $_adapter;
    protected $_serviceLocator;

    /**
     * Make the Adapter object avilable as local prtected variable
     *
     * @param Adapter $adapter - DB PDO PgSQL conn
     */
    public function __construct(Adapter $_adapter)
    {
        $this->_adapter = $_adapter;
    }

    /*
     * function to get all template list
     */

    public function getAlltemplate($trackerId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('notification_template');
        $select->join('form', 'form.form_id = notification_template.notification_template_form_id');
        $select->join('user', 'user.u_id = notification_template.notification_template_updated_by', 'u_name', 'left');
        $select->where(array('notification_template_status' => 'Active','form.tracker_id'=>$trackerId));
        $statement = $sql->prepareStatementForSqlObject($select); //echo ($sql->getSqlStringForSqlObject ($select)); echo '<pre>';
        $results = $statement->execute();
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

    /*
     * function to save template
     */

    public function saveTemplate($data, $uId)
    {        
        $fieldIds = implode(',', $data['field_id']);
        $cc=$data['ccmail'];
        $sql = new Sql($this->_adapter);
        if ($data['template_id'] > 0) {
            $qry='select if ((SELECT count("notification_template_id") FROM notification_template where notification_template_name="'.$data['template_name'].'" AND notification_template_id <>'.$data['template_id'].' AND notification_template_form_id ='.$data['form_id'].' AND notification_type="Notification")>0, 3, 0) as `checkduplicate`';
        } else {
            $qry='select if ((SELECT count("notification_template_id") FROM notification_template where notification_template_name="'.$data['template_name'].'" AND notification_template_form_id ='.$data['form_id'].' AND notification_type="Notification")>0, 3, 0) as `checkduplicate`';
        }
        $statements = $this->_adapter->createStatement($qry);
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
        if ($arr[0]['checkduplicate']!=0) {
            return 'duplicate';
        }
        if ($data['status']==''|| $data['status']==null) {
            $data['status'] = 'Active';
        }
       
        $newData = array(
            'notification_template_name' => filter_var($data['template_name'], FILTER_SANITIZE_STRING),
            'notification_template_subject' =>  filter_var($data['subject'], FILTER_SANITIZE_STRING),
            'notification_template_msg' => htmlentities($data['msg']),
            'notification_template_updated_by' => filter_var($uId, FILTER_SANITIZE_STRING),
            'notification_template_condition_type' => filter_var($data['n_cond'], FILTER_SANITIZE_STRING),
            'notification_template_form_id' => filter_var($data['form_id'], FILTER_SANITIZE_STRING),
            'notification_template_cc' => filter_var($cc, FILTER_SANITIZE_STRING),
            'notification_template_to' =>filter_var($fieldIds, FILTER_SANITIZE_STRING),
            'notification_template_status' => filter_var($data['status'], FILTER_SANITIZE_STRING),
        );
        $conditionOnField=$data['condition_on_field'];

        $conditionOperand=$data['condition_operand'];
        $value=$data['value'];
        if ($data['template_id'] > 0) {
            $update = $sql->update('notification_template');
            $update->set($newData);
            $update->where(
                array(
                'notification_template_id' => $data['template_id']
                )
            );
            $statement = $sql->prepareStatementForSqlObject($update);
            $results = $statement->execute();
            $templateId=$data['template_id'];
            
            /*delete all records from  condition_for templates table having $template_id*/
            $qry='delete from `condition_for_templates` where condition_notification_template_id='.$templateId;
            $statements = $this->_adapter->query($qry);
            $results = $statements->execute();
            /*---------------------*/
        } else {
            $newData['notification_template_created_by'] = filter_var($uId, FILTER_SANITIZE_STRING);
            $insert = $sql->insert('notification_template');

            $insert->values($newData);
            $statement = $sql->prepareStatementForSqlObject($insert);
            $results = $statement->execute();
            $templateId = $this->_adapter->getDriver()->getLastGeneratedValue();
        }

        /*insert into condition_for_templates table */
        if (is_array($conditionOnField)) {
            $i = 0;
            foreach ($conditionOnField as $key => $values) {
                $conditionData = array(
                    'condition_notification_template_id' => $templateId,
                    'condition_field_name' =>$values,
                    'condition_operand' => $conditionOperand[$key],
                    'condition_value' =>  $value[$key],
                );
                $insert = $sql->insert('condition_for_templates');
                $insert->values($conditionData);
                $statement = $sql->prepareStatementForSqlObject($insert);
                $results = $statement->execute();
            }
        }
    }

    /*
     * function to get template info
     */

    public function getTemplateInfo($templateId)
    {
        $templateId = htmlspecialchars($templateId, ENT_QUOTES);
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        //$qry = 'select ctable.*,ttable.name as tracker_name  from  settings ctable left join tracker ttable on ttable.client_id=ctable.id where archived=0 group by ctable.id';
        $select->from('notification_template');
        $select->join('condition_for_templates', 'notification_template.notification_template_id=condition_for_templates.condition_notification_template_id', array('condition_id','condition_field_name','condition_operand','condition_value'));
        $select->where(array('notification_template_id' => $templateId));
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();

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

    public function getTemplateBody($template)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('notification_template');
        $select->where(array('notification_template_name' => $template));
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        /*$newadpater = $this->_adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);*/
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

    /*
     * get send to fields to populate dropdown
     */

    public function getfieldname($formId)
    {
        $trackerId = htmlspecialchars($formId, ENT_QUOTES);
        $arr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('field');
        $select->join('workflow', 'field.workflow_id = workflow.workflow_id', array('workflow_id'));
        $select->where(array('field_type' => 'User Role', 'workflow.form_id' => $formId));
        $select->columns(array('field_id','field_name','label'));
       
        $statement = $sql->prepareStatementForSqlObject($select); 
        
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
    /*
    * get send to fields to populate dropdown
    */

    public function getworkflowdname($formId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('field');
        $select->join('workflow', 'field.workflow_id = workflow.workflow_id');
        $select->where(array('workflow.form_id' => $formId));
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
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

    /*
     * get fields and send to info from template tablke
     */

    public function gettemplateDetail($templateId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $qry = 'SELECT * FROM notification_template nt
        left join field f on find_in_set(f.field_id,nt.notification_template_to)
        where notification_template_id=' . $templateId . ' group by  notification_template_id';

        $statements = $this->_adapter->createStatement($qry);
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

    /*
     * function to get mails id for each field
     */

    public function getMailids($id, $field, $formName)
    {
        
        $qry = 'select user.email as mail_id,user.u_name from ' . $formName . '
           left join user on user.u_name=(select  '.$field.'  from ' . $formName . '  where id=' . $id . ')
           where id=' . $id;        
        $statements = $this->_adapter->createStatement($qry); //echo $qry;die;
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

    public function getTemplatetoSendmail($template)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('notification_template');
        $select->where(array('notification_template_id' => $template));
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
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

    public function notificationNameCheck($notificationName, $templateId, $templateType)
    {
        $sql = 'SELECT `notification_template`.notification_template_id, `notification_template`.notification_type FROM `notification_template` WHERE notification_template_name =? AND notification_template_archive =? AND notification_template_id !=? AND notification_type =?';
        $statement = $this->_adapter->createStatement($sql, array(trim($notificationName),0,$templateId,$templateType));
        $results = $statement->execute();
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
    public function gettemplateFields($templateId)
    {
        $qry = 'SELECT `notification_template`.*,`condition_for_templates`.* FROM `notification_template`
                left join `condition_for_templates`
                on `condition_for_templates`.condition_notification_template_id=`notification_template`.notification_template_id
                WHERE `notification_template_id` = ?';
        $statements = $this->_adapter->createStatement($qry, array($templateId));
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
    /*
    * function to get template info
    */
    public function getfieldsvalue($id, $msgplaceholders, $formName)
    {
        try {
            $placeholders=$msgplaceholders[0];
            $str='';
            foreach ($placeholders as $holders) {
                if ($str=='') {
                    $str=$holders;
                } else {
                    $str.=','.$holders;
                }
            }
            $qry = 'select '.$str .'  from `'.  $formName.'`  where id='.$id;
            $statements = $this->_adapter->query($qry);
            $results = $statements->execute();
            $arr = array();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                $arr = $resultSet->toArray();
            }
            return $arr[0];
        } catch (\Exception $e) {
            //return 0;
        }
    }

    /*
    * function to get template info
    */

    public function getworkflowvalue($id, $workflowplaceholders, $formId)
    {
        $placeholders=$workflowplaceholders[0];
        $arr = array();
        foreach ($placeholders as $holders) {
            $qry = 'select * from `field` where workflow_id=(select `workflow_id`  from `workflow` where workflow_name="'.$holders.'" and form_id='.$formId.')';
            $statements = $this->_adapter->createStatement($qry);
            $statements->prepare();
            $results = $statements->execute();
            /*$statements = $this->_adapter->query($qry);
            $results = $statements->execute();*/

            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                $arr[] = $resultSet->toArray();
            }
        }
        return $arr;
    }
    public function recordsCanView($trackerId, $actionId, $bNotification = true)
    {
        $resArr = array();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;
        $trackerContainer = $session->getSession('tracker');
        $trackerUserGroups = $trackerContainer->tracker_user_groups;
        $roleId = (int)$userDetails['group_id'];
        $queryFormFields = "SELECT field.*, workflow.workflow_name 
        FROM field 
        LEFT JOIN workflow ON field.workflow_id = workflow.workflow_id
        WHERE workflow.form_id = ? ORDER BY workflow.sort_order ASC, field.sort_order ASC";
        //var_dump($trackerUserGroups);die;
        $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
        $sessionGroupId = @$trackerUserGroups[$trackerId]['session_group_id'];
        $sessionGroupId = (int)$sessionGroupId;
        if ($roleId != 1 && $sessionGroup != "Administrator" && $bNotification) {
            $queryFormFields = "SELECT field . * , workflow.workflow_name, workflow_role.role_id, workflow_role.can_read
            FROM field
            JOIN workflow ON field.workflow_id = workflow.workflow_id
            JOIN workflow_role ON workflow_role.workflow_id = workflow.workflow_id
            WHERE workflow.form_id = ? AND (workflow_role.can_read = 'Yes' OR workflow_role.can_read = 'Self')
            ORDER BY workflow.sort_order ASC , field.sort_order ASC";
        } //echo $queryFormFields;die;
        $statements = $this->_adapter->createStatement($queryFormFields, array($actionId));
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $fieldsArr = array();
        if ($count > 0) {
            $fieldsArr = $resultSet->toArray();
        }
        $workflowArray = array();
        $wkfArr = array();
        foreach ($fieldsArr as $key => $value) {
            $workflowName = $value['workflow_name'];

            if (!in_array($workflowName, $workflowArray)) {
                $workflowArray[] = $value['workflow_name'];
            }
            $wkfArr[$workflowName][] = $value;
        }
        $resArr['workflows'] = $workflowArray;
        $resArr['fields'] = $wkfArr;        //print_r($resArr);die;
        return $resArr;
    }
    /*
    * get send to fields to populate dropdown
    */
    public function getfield($fieldId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('field');
        $select->where(array('field_id' => $fieldId));
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        if (!empty($arr)) {
            return $arr[0]['field_name'];
        }
    }
    /*
     * Function to delete template :to make template archive
     */
    public function deletetemplate($templateId)
    {
        $sql = new Sql($this->_adapter);
        $update = $sql->update('notification_template')
            ->set(array('notification_template_archive' => 1,'notification_template_status'=>'Deactive'))
            ->where(
                array(
                'notification_template_id' => $templateId
                )
            );
        $selectString = $sql->getSqlStringForSqlObject($update);
        $results = $this->_adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
    }

    /*
     * function to get mail settings
     */

    public function getsettings()
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('settings');
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
      
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $arr = $resultSet->toArray();
        $resultSArr = $arr[0];
        return $resultSArr;
    }
    
    public function getTemplateAllInfo($templateId)
    {
        $qry='SELECT notification_template.notification_template_name as template_name,
                notification_template.notification_template_subject as subject,
                notification_template.notification_template_msg as msg,
                notification_template.notification_template_to as field_id,
                notification_template.notification_template_form_id as form_id,
                notification_template.notification_template_status as status,
                notification_template.notification_template_condition_type as n_cond,
                notification_template.notification_template_cc as ccmail,
                group_concat(condition_for_templates.condition_field_name) as condition_on_field,
                group_concat(condition_for_templates.condition_operand) as condition_operand,
                group_concat(condition_for_templates.condition_value)as value
                FROM notification_template left join condition_for_templates
                on condition_for_templates.condition_notification_template_id=notification_template.notification_template_id where notification_template.notification_template_id=?';
        $statements = $this->_adapter->createStatement($qry);
        $statements->prepare();
        $results = $statements->execute(array($templateId));
        /* $statements = $this->_adapter->query($qry,array($templateId));
         $results = $statements->execute();*/
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr[0];
    }
    
    
    
    public function getWorkflowNames($formId) 
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('workflow');
        $select->columns(array('workflow_name'));
        $select->where(array('workflow.form_id'=>$formId));                
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
      
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $arr = $resultSet->toArray();       
        return $arr;
    }
    
    public function notifyWhomFields($formId) 
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('field');
        $select->join('workflow', 'field.workflow_id=workflow.workflow_id', array('workflow_name'));
        $select->columns(array('field_name','label','field_type'));
        $select->where(array('field.field_type'=>'User Role', 'workflow.form_id'=>$formId))
            ->order('workflow_name');
        $statement = $sql->prepareStatementForSqlObject($select); //var_dump($sql->getSqlStringForSqlObject ($select));die;
        $results = $statement->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        //$count = $resultSet->count();
        $arr = $resultSet->toArray();
        return $arr;
      
    }
    
    public function getWorkflowFields($formId,$workflowName)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('field');
        $select->join('workflow', 'field.workflow_id=workflow.workflow_id', array('workflow_name'));
        $select->columns(array('field_name','label'));
        $select->where(array('workflow.workflow_name'=>$workflowName, 'workflow.form_id'=>$formId))
            ->order('workflow_name');
        $statement = $sql->prepareStatementForSqlObject($select); //var_dump($sql->getSqlStringForSqlObject ($select));die;
        $results = $statement->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        //$count = $resultSet->count();
        $arr = $resultSet->toArray();
        
        return $arr;
        
    }
    
    public function deleteNotificationCondition($id)
    {
        $qry='delete from `condition_for_templates` where condition_id='.$id;
        $statements = $this->_adapter->query($qry);
        $results = $statements->execute();
    }
    
    public function saveReminder($data,$uId)
    {
        $fieldIds = implode(',', $data['field_id']);
        $cc=$data['ccmail'];
        $sql = new Sql($this->_adapter);
        if ($data['template_id'] > 0) {
            $qry='select if ((SELECT count("notification_template_id") FROM notification_template where notification_template_name="'.$data['template_name'].'" AND notification_template_id ='.$data['template_id'].' AND notification_template_form_id <>'.$data['form_id'].' AND notification_type="Reminder")>0, 3, 0) as `checkduplicate`';
        } else {
            $qry='select if ((SELECT count("notification_template_id") FROM notification_template where notification_template_name="'.$data['template_name'].'" AND notification_template_form_id ='.$data['form_id'].' AND notification_type="Reminder")>0, 3, 0) as `checkduplicate`';
        }
        $statements = $this->_adapter->createStatement($qry);
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
        if ($arr[0]['checkduplicate']!=0) {
            return 'duplicate';
        }
        if ($data['status']==''|| $data['status']==null) {
            $data['status'] = 'Active';
        }
       
        $newData = array(
            'notification_template_name' => filter_var($data['template_name'], FILTER_SANITIZE_STRING),
            'notification_template_subject' =>  filter_var($data['subject'], FILTER_SANITIZE_STRING),
            'notification_template_msg' => htmlentities($data['msg']),
            'notification_template_updated_by' => filter_var($uId, FILTER_SANITIZE_STRING),
            'notification_template_condition_type' => filter_var($data['n_cond'], FILTER_SANITIZE_STRING),
            'notification_template_form_id' => filter_var($data['form_id'], FILTER_SANITIZE_STRING),
            'notification_template_cc' => filter_var($cc, FILTER_SANITIZE_STRING),
            'notification_template_to' =>filter_var($fieldIds, FILTER_SANITIZE_STRING),
            'notification_template_status' => filter_var($data['status'], FILTER_SANITIZE_STRING),
            'notification_type' => 'Reminder'            
        );
        $conditionOnField=$data['condition_on_field'];

        $conditionOperand=$data['condition_operand'];
        $value=$data['value'];
        if ($data['template_id'] > 0) {
            $update = $sql->update('notification_template');
            $update->set($newData);
            $update->where(
                array(
                'notification_template_id' => $data['template_id']
                )
            );
            $statement = $sql->prepareStatementForSqlObject($update);
            $results = $statement->execute();
            $templateId=$data['template_id'];
            
            /*delete all records from  condition_for templates table having $template_id*/
            $qry='delete from `condition_for_templates` where condition_notification_template_id='.$templateId;
            $statements = $this->_adapter->query($qry);
            $results = $statements->execute();
            
            $qry='delete from `condition_for_reminder` where notification_id='.$templateId;
            $statements = $this->_adapter->query($qry);
            $results = $statements->execute();
            /*---------------------*/
        } else {
            $newData['notification_template_created_by'] = filter_var($uId, FILTER_SANITIZE_STRING);
            $insert = $sql->insert('notification_template');

            $insert->values($newData);
            $statement = $sql->prepareStatementForSqlObject($insert);
            $results = $statement->execute();
            $templateId = $this->_adapter->getDriver()->getLastGeneratedValue();
        }

        /*insert into condition_for_templates table */
        if (is_array($conditionOnField)) {
            $i = 0;
            foreach ($conditionOnField as $key => $values) {
                $conditionData = array(
                'condition_notification_template_id' => $templateId,
                'condition_field_name' =>$values,
                'condition_operand' => $conditionOperand[$key],
                'condition_value' =>  $value[$key],
                );
                $insert = $sql->insert('condition_for_templates');
                $insert->values($conditionData);
                $statement = $sql->prepareStatementForSqlObject($insert);
                $results = $statement->execute();
            }
        }    
        $conditionReminderData=array(
        'notification_id'=>$templateId,
        'days'=>$data['days'],
        'before_after'=>$data['beforeAfter'],
        'fields'=>$data['dateFields']
        );
        $insert = $sql->insert('condition_for_reminder');
        $insert->values($conditionReminderData);
        $statement = $sql->prepareStatementForSqlObject($insert);
        $results = $statement->execute();            
    }
    
    public function saveSubscription($data,$uId)
    {
        //$fieldIds = implode(',', $data['field_id']);
        $cc=$data['ccmail'];
        $sql = new Sql($this->_adapter);
        if ($data['template_id'] > 0) {
            $qry='select if ((SELECT count("notification_template_id") FROM notification_template where notification_template_name="'.$data['template_name'].'" AND notification_template_id <>'.$data['template_id'].' AND notification_template_form_id ='.$data['form_id'].' AND notification_type="Subscription")>0, 3, 0) as `checkduplicate`';
        } else {
            $qry='select if ((SELECT count("notification_template_id") FROM notification_template where notification_template_name="'.$data['template_name'].'" AND notification_template_form_id ='.$data['form_id'].' AND notification_type="Subscription")>0, 3, 0) as `checkduplicate`';
        }
        $statements = $this->_adapter->createStatement($qry);
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
        if ($arr[0]['checkduplicate']!=0) {
            return 'duplicate';
        }
        if ($data['status']==''|| $data['status']==null) {
            $data['status'] = 'Active';
        }
        $jsonColData =json_encode(array('Frequency' => $data['frequency'], 'Frequency value' => $data['frequency_value'], 'Report Name' => $data['report_name']));//        print_r($jsonColData);die;
        $newData = array(
        'notification_template_name' => filter_var($data['template_name'], FILTER_SANITIZE_STRING),
        'notification_template_subject' =>  filter_var($data['subject'], FILTER_SANITIZE_STRING),
        'notification_template_msg' => htmlentities($data['msg']),
        'notification_template_updated_by' => filter_var($uId, FILTER_SANITIZE_STRING),
        //        'notification_template_condition_type' => filter_var($data['n_cond'], FILTER_SANITIZE_STRING),
        'notification_template_form_id' => filter_var($data['form_id'], FILTER_SANITIZE_STRING),
        'notification_template_cc' => filter_var($cc, FILTER_SANITIZE_STRING),
        'notification_template_to' =>filter_var($data['field_id'], FILTER_SANITIZE_STRING),
        'notification_template_status' => filter_var($data['status'], FILTER_SANITIZE_STRING),
        'notification_type' => 'Subscription',
        'notification_config' => $jsonColData
        );
        if ($data['template_id'] > 0) {
            $update = $sql->update('notification_template');
            $update->set($newData);
            $update->where(
                array(
                'notification_template_id' => $data['template_id']
                )
            );
            $statement = $sql->prepareStatementForSqlObject($update);
            $results = $statement->execute();
            $templateId=$data['template_id'];
            
            /*delete all records from  condition_for templates table having $template_id*/
            //            $qry='delete from `condition_for_templates` where condition_notification_template_id='.$templateId;
            //            $statements = $this->_adapter->query($qry);
            //            $results = $statements->execute();
            //            
            //            $qry='delete from `condition_for_reminder` where notification_id='.$templateId;
            //            $statements = $this->_adapter->query($qry);
            //            $results = $statements->execute();
            /*---------------------*/
        } else {
            $newData['notification_template_created_by'] = filter_var($uId, FILTER_SANITIZE_STRING);
            $insert = $sql->insert('notification_template');

            $insert->values($newData);
            $statement = $sql->prepareStatementForSqlObject($insert);
            $results = $statement->execute();
            $templateId = $this->_adapter->getDriver()->getLastGeneratedValue();
        }
    }
    public function getDateFields($formId) 
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('field');
        $select->join('workflow', 'field.workflow_id=workflow.workflow_id', array('workflow_name'));
        $select->columns(array('field_name','label','field_type'));
        $select->where(array('field.field_type'=>array('Date','Formula Date','Formula','Date Time','ReadOnly'), 'workflow.form_id'=>$formId))
            ->order('workflow_name');
        $statement = $sql->prepareStatementForSqlObject($select); //var_dump($sql->getSqlStringForSqlObject ($select));die;
        $results = $statement->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        //$count = $resultSet->count();
        $arr = $resultSet->toArray();
        return $arr;
        
    }
    
    public function getReminderInfo($templateId)
    {
        $templateId = htmlspecialchars($templateId, ENT_QUOTES);
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        //$qry = 'select ctable.*,ttable.name as tracker_name  from  settings ctable left join tracker ttable on ttable.client_id=ctable.id where archived=0 group by ctable.id';
        $select->from('notification_template');
        $select->join('condition_for_templates', 'notification_template.notification_template_id=condition_for_templates.condition_notification_template_id', array('condition_id','condition_field_name','condition_operand','condition_value'));
        $select->join('condition_for_reminder', 'notification_template.notification_template_id=condition_for_reminder.notification_id', array('reminder_id','days','before_after','fields'));
        $select->where(array('notification_template_id' => $templateId));
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();

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
    
    public function getSubscriptionInfo($templateId)
    {
        $templateId = htmlspecialchars($templateId, ENT_QUOTES);
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        //$qry = 'select ctable.*,ttable.name as tracker_name  from  settings ctable left join tracker ttable on ttable.client_id=ctable.id where archived=0 group by ctable.id';
        $select->from('notification_template');
        $select->where(array('notification_template_id' => $templateId));
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();

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

    public function checkReminder()
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('notification_template');
        //$select->columns(array('notification_template_id','notification_template_form_id'));
        $select->join('form', 'notification_template.notification_template_form_id=form.form_id', array('tracker_id'));
        $select->join('condition_for_reminder', 'notification_template.notification_template_id=condition_for_reminder.notification_id', array('days','before_after','fields'));
        $select->join('condition_for_templates', 'notification_template.notification_template_id=condition_for_templates.condition_notification_template_id', array('condition_field_name','condition_operand','condition_value'));
        $select->where(array('notification_template.notification_template_status'=>'Active', 'notification_template.notification_type'=>'Reminder'));
        $statement = $sql->prepareStatementForSqlObject($select); //print_r($sql->getSqlStringForSqlObject ($select));die;
        $results = $statement->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $templatearray = array();
        if ($count > 0) {
            $templatearray = $resultSet->toArray();
        }
        return $templatearray;
    }
    public function checkSubscription()
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('notification_template');
        $select->join('form', 'notification_template.notification_template_form_id=form.form_id', array('tracker_id'));
        $select->where(array('notification_template.notification_template_status'=>'Active', 'notification_template.notification_type'=>'Subscription'));
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $templatearray = array();
        if ($count > 0) {
            $templatearray = $resultSet->toArray();
        }
        return $templatearray;
    }

    public function getReminderData($query)
    {
        $statements = $this->_adapter->createStatement($query);
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
    public function getSubscriptionData($reportName)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('report');
        $select->where(array('report_name'=>$reportName['Report_name']));
        $statement = $sql->prepareStatementForSqlObject($select); 
        $results = $statement->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        $arr = call_user_func_array('array_merge', ($arr));
        $where = !empty($arr['report_where'])?' WHERE '.$arr['report_where']:'';
        $groupBy = !empty($arr['report_group_by'])?' GROUP BY '.$arr['report_group_by']:'';
        $orderBy = !empty($arr['report_order_by'])?' ORDER BY '.$arr['report_order_by']:'';
        $having = !empty($arr['report_having'])?' HAVING '.$arr['report_having']:'';
        $query = !empty($arr['report_query'])? $arr['report_query']:'';       
        $sqlQuery = "$query $where $groupBy $orderBy $having";
        $data=$this->reportData($sqlQuery);
        return $data;        
    }
    public function reportData($query)
    {
        $statements = $this->_adapter->createStatement($query);
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

    public function trackerCheckFieldsForFormula($trackerId, $actionId)
    {
        $tableName = "form_$trackerId" . "_$actionId";
        $queryField = "show columns from $tableName Where Field NOT IN('id', 'created_by', 'last_updated_by')";
        $statements = $this->_adapter->createStatement($queryField);
        $statements->prepare();
        $resArr = array();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $resArr = $resultSet->toArray();
        }
        
        return $resArr;
    }

    public function getTrackerDetails($trackerId) 
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('tracker');
        $select->where(array('tracker_id' => $trackerId, 'archived' => 0));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute(); 
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arrData = $resultSet->toArray();
            $arr     = isset($arrData[0])?$arrData[0]:array();
        }
        return $arr;
    }

    public function trackerResults($trackerId)
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        
        $userDetails = $userContainer->user_details;
        $roleId = $userDetails['group_id'];
        $roleName = $userDetails['group_name'];
        if (isset($trackerContainer->tracker_user_groups) && $roleName != 'SuperAdmin' && $trackerId != 0) {
            $trackerUserGroups = $trackerContainer->tracker_user_groups;
            if (!array_key_exists($trackerId, $trackerUserGroups)) {
                $applicataionModel= new \Application\Model\AdminMapper($this->_adapter);
                $applicataionModel->accessTrackerGroups($userDetails['u_id'], $roleId, $userDetails['user_type']);
                $trackerUserGroups = $trackerContainer->tracker_user_groups;
            }
            $roleName = $trackerUserGroups[$trackerId]['session_group'];
            $roleId = (int)$trackerUserGroups[$trackerId]['session_group_id'];
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

    public function formRecords($trackerId, $actionId, $sLimit, $search, $sOrder, $subactionId = 0)
    {
        $formName = 'form_' . $trackerId . '_' . $actionId;
        $queryClients = "SELECT * FROM $formName WHERE  is_deleted='No' ";
        if ($subactionId != 0) {
            if ($search != '') {
                $queryClients .= " AND id = $subactionId AND $search";
            } else {
                $queryClients .= " AND id = $subactionId";
            }
        } else {
            if ($search != '') {
                $queryClients.= " AND $search";
            }
        }
        $queryClients .= $sOrder;
        $query = "SELECT count(id) as tot FROM $formName WHERE  is_deleted='No' ";
        if ($subactionId != 0) {
            if ($search != '') {
                $query .= " AND id = $subactionId AND $search";
            } else {
                $query .= " AND id = $subactionId";
            }
        } else {
            if ($search != '') {
                $query.= " AND $search";
            }
        }
        $queryClients .= $sLimit;
        $statements = $this->_adapter->query($queryClients);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $resultsArr = array();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        $resArr['form_data'] = $arr;
        $statements = $this->_adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        $resArr['tot'] = $arr[0]['tot'];
        return $resArr;
    }
    public function reportDetails($formId)
    {
        $queryReport= "SELECT * FROM report where form_id = $formId";
        $statements = $this->_adapter->createStatement($queryReport);
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
}
