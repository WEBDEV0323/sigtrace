<?php

namespace Common\Report\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;

class ReportTable
{
    protected $tableGateway;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }
    public function fetchAll()
    {

        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from('report');
        $newadpater = $this->adapter;
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

    public function getReport($id, $username,$sLimit,$sWhere,$sOrder)
    {
        $sql = new Sql($this->adapter);
        /******
* START updating last run  
*/
        $data = array(
            'last_run_time' => date('Y-m-d H:i:s'),
        );
        $update = $sql->update();
        $update->table('report');
        $update->set($data);
        $update->where(array('report_id' => $id));
        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute($update);
        /******
* END updating last run  
*/
        $res = array();
        $connection = $this->adapter->getDriver()->getConnection();
        $qry = 'call sp_custom_report("' . $id . '","0000-00-00","0000-00-00","' . $username . '","' . $sLimit . '","' . $sWhere . '","' . $sOrder . '")';
        //echo $qry;die;
        $result = $connection->execute($qry);

        $statement = $result->getResource();

        // Result set 1
        $resultSet1 = $statement->fetchAll(\PDO::FETCH_OBJ);

        foreach ($resultSet1 as $row) {
            $res[0] = $row->repname;
            $res[1] = $row->filter;
        }

        // Result set 2
        $statement->nextRowSet(); // Advance to the second result set
        $resultSet2 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[2] = $resultSet2;

        // Result set 3
        $statement->nextRowSet(); // Advance to the second result set
        $resultSet2 = $statement->fetchAll(\PDO::FETCH_OBJ);
        foreach ($resultSet2 as $row) {
            $res[3] = $row->report_type;
        }
        return $res;
    }
    public function countReport($id,$username,$sWhere,$customWhere1)
    {

        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from('report');
        $select->where(array('report_id' => $id));
        $newadpater = $this->adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $arr_rep=array();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            foreach ($arr as $key => $value) {
                $rep_query = $value['report_query'];
                $rep_group = $value['query_group_by'];
                $rep_having = $value['query_having'];
                $rep_order = $value['query_order_by'];
                $rep_where = $value['query_where'];
                $rep_type = $value['report_type'];
                $rep_user = $value['custom_user'];
                $rep_filter = $value['custom_filter'];
                $customWhere = " and date(".$rep_filter.")".$customWhere1;
                if (strpos($rep_query, 'fn_') !== false) {
                    $connection = $this->adapter->getDriver()->getConnection();
                    $result = $connection->execute($rep_query);
                    $statement = $result->getResource();
                    $resultSet1 = $statement->fetchAll(\PDO::FETCH_OBJ);
                    foreach ($resultSet1 as $rowArray) {
                        foreach ($rowArray as $column => $value) {
                            $rep_query=$value;
                        }
                    }
                    if ($rep_type == 'mycase') {
                        $query = $rep_query . " " . $rep_where . " and " . $rep_user . " = '" . $username . "' " . $rep_group . " " . $rep_having . " " . $sWhere . " " . $rep_order;
                    } else if (empty($customWhere1)) {
                        $query = $rep_query . " " . $rep_where . " " . $rep_group . " " . $sWhere;
                    } else {
                        $query = $rep_query . " " . $rep_where . " " . $customWhere . " " . $rep_group . " " . $sWhere;
                    }
                } else {

                    if ($rep_type == 'mycase') {
                        $query = $rep_query . " " . $rep_where . " and " . $rep_user . " = '" . $username . "' " . $rep_group . " " . $rep_having . " " . $sWhere . " " . $rep_order;
                    } else if (empty($customWhere1)) {
                        $query = $rep_query . " " . $rep_where . " " . $rep_group . " " . $sWhere;
                    } else {
                        $query = $rep_query . " " . $rep_where . " " . $customWhere . " " . $rep_group . " " . $sWhere;
                    }
                }
                //  echo $query;die;
                $countReport = $this->getQueryCountResult($query);
            }
        }
        return $countReport;
    }
    public function getQueryCountResult($query)
    {
        $statements = $this->adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $count;
    }
    public function getAllReports($form_id,$role_id,$tracker_id)
    {
        //echo $form_id.">>r>>".$role_id.">>t>>".$tracker_id."\n"."???????????";
        $user_details = $_SESSION['user_details'];
        $role_name = $user_details['group_name'];
        if (isset($_SESSION['tracker_user_groups']) && $role_name!='SuperAdmin' ) {
            $tracker_user_groups = $_SESSION['tracker_user_groups'];
            $role_name = $tracker_user_groups[$tracker_id]['session_group'];;
        }
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        if ($role_name!='SuperAdmin' && $role_name!='Administrator') {

            $select->from('report');
            $select->join('report_access_setting', 'report_access_setting.report_id = report.report_id');
            $select->where(
                array('report.form_id' => $form_id,
                'report_access_setting.can_access' => 'Yes',
                'report_access_setting.role_id' => $role_id
                )
            );
        } else {

            $select->from('report');
            $select->where(
                array('report.form_id' => $form_id,
                )
            );
        }
        $newadpater = $this->adapter;
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
    public function getCustomReport($id, $sDate, $eDate,$username,$sLimit,$sWhere,$sOrder)
    {
        $res = array();
        $connection = $this->adapter->getDriver()->getConnection();
        $qry = 'call sp_custom_report("' . $id . '","' . $sDate . '","' . $eDate . '","' . $username . '","' . $sLimit . '","' . $sWhere . '","' . $sOrder . '")';
        $result = $connection->execute($qry);
        $statement = $result->getResource();
        // Result set 1
        $resultSet1 = $statement->fetchAll(\PDO::FETCH_OBJ);
        foreach ($resultSet1 as $row) {
            $res[0] = $row->repname;
            $res[1] = $row->filter;
        }
        // Result set 2
        $statement->nextRowSet(); // Advance to the second result set
        $resultSet2 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[2] = $resultSet2;

        $statement->nextRowSet(); // Advance to the second result set
        $resultSet2 = $statement->fetchAll(\PDO::FETCH_OBJ);
        foreach ($resultSet2 as $row) {
            $res[3] = $row->report_type;
        }
        return $res;
    }

    public function getAllUsers()
    {
        $query = "Select u.u_id,u.u_realname from user as u left join user_role_tracker as t on t.u_id=u.u_id where t.group_id=47";
        $statements = $this->adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function saveUsersForForms($data_array)
    {
        $arisgNo = @$data_array['arisgNo'];
        $select_uName = $data_array['select_uName'];
        $check_allcocatio_rep = $data_array['check_allcocation'];
        foreach ($check_allcocatio_rep as $key => $value) {
            if ($value == true) {
                $email_intake_by = $select_uName[$key];
                $arisg_no = $arisgNo[$key];
                $sql = new Sql($this->adapter);
                $insert = $sql->insert('form_' . $data_array['tracker_id'] . '_' . $data_array['form_id']);
                $newData = array(
                    'email_intake_by' => $email_intake_by,
                    'arisg_no' => $arisg_no,
                );
                $insert->values($newData);
                $selectString = $sql->getSqlStringForSqlObject($insert);
                $results = $this->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
            }
        }
        return $lastInsertID = $this->adapter->getDriver()->getLastGeneratedValue();
    }

    public function saveUsersForDEAllocation($data_array)
    {
        $aerno = @$data_array['aerno'];
        $select_uName = $data_array['select_uName'];
        $check_allcocatio_rep = $data_array['check_allcocation'];

        foreach ($check_allcocatio_rep as $key => $value) {
            if ($value == true) {
                $synassociatename = $select_uName[$key];
                $aernos = $aerno[$key];
                $sql = new Sql($this->adapter);

                if ($data_array['flag'] == 0) {
                    $insert = $sql->insert('form_' . $data_array['tracker_id'] . '_' . $data_array['form_id']);
                    $newData = array(
                        'synassociatename' => $synassociatename,
                        'aerno' => $aernos,
                    );
                    $insert->values($newData);
                    $selectString = $sql->getSqlStringForSqlObject($insert);

                } else if ($data_array['flag'] == 1) {
                    $update = $sql->update('form_' . $data_array['tracker_id'] . '_' . $data_array['form_id'])
                        ->set(
                            array(
                            'qcname' => $synassociatename

                            )
                        )
                        ->where(
                            array(
                            'aerno' => $aernos
                            )
                        );
                    $selectString = $sql->getSqlStringForSqlObject($update);
                } else if ($data_array['flag'] == 2) {
                    $update = $sql->update('form_' . $data_array['tracker_id'] . '_' . $data_array['form_id'])
                        ->set(
                            array(
                            'mrname' => $synassociatename

                            )
                        )
                        ->where(
                            array(
                            'aerno' => $aernos
                            )
                        );
                    $selectString = $sql->getSqlStringForSqlObject($update);
                }
                $results = $this->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
            }
        }
        return $lastInsertID = $this->adapter->getDriver()->getLastGeneratedValue();

    }

    public function getAllassociatename($group_id)
    {
        $query = "Select u.u_id,u.u_name from user as u left join user_role_tracker as t on t.u_id=u.u_id where t.group_id=" . $group_id;
        $statements = $this->adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function getstatus($tracker_id, $form_id, $statusfor)
    {
        switch ($statusfor) {
        case 'dc':
            $query = "Select * from form_" . $tracker_id . "_" . $form_id . " where destatus='Completed' and (qcname IS NULL OR qcname = '') order by lrdlatestreceiptdate ASC";
            break;
        case 'qc':
            $query = "Select * from form_" . $tracker_id . "_" . $form_id . " where qcstatus='Completed' AND (mrname IS NULL OR mrname = '') order by lrdlatestreceiptdate ASC";
            break;
        default:
            break;
        }
        $statements = $this->adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function mycases($tracker_id, $form_id, $group_id, $username, $report_id)
    {
        switch ($report_id) {
        case '400':
            $query = "Select * from form_" . $tracker_id . "_" . $form_id . " where synassociatename='$username'";
            break;
        case '500':
            $query = "Select * from form_" . $tracker_id . "_" . $form_id . " where qcname='$username'";
            break;
        case '600':
            $query = "Select * from form_" . $tracker_id . "_" . $form_id . " where mrname='$username'";
            break;
        default:
            break;
        }
        $statements = $this->adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function allocateusers1($columnname,$data2DArray, $id, $form_ids,$tracker_id)
    {
        $query = "Select * from allocation_details where form_id=$form_ids and allocation_id ='$id'";
        $statements = $this->adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $inserted=0;
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        if ($arr[0]['update_on']!='') {
            $columns=$arr[0]['update_on'].','.$arr[0]['fields_name'];
        } else {
            $columns=$arr[0]['fields_name'];
        }
        if ($columnname!=$columns) {
            //echo "here";die;
            return 'invalid';
        }
        foreach ($data2DArray as $res) {
            if ($arr[0]['allocation_type'] == 0) {
                $aernos = addslashes($res[0]);
                $username = addslashes($res[1]);
                $query = "insert into form_" . $tracker_id . "_" . $form_ids ."(" . $arr[0]['fields_name'] . ") VALUES ('" . $aernos . "','" . $username . "')";
                $statements = $this->adapter->query($query);
                $results = $statements->execute();
                $inserted++;

            } else if ($arr[0]['allocation_type'] == 1) {
                $form_id = addslashes($res[0]);
                $aernos = addslashes($res[1]);
                $username = addslashes($res[2]);
                $fields = explode(',', $arr[0]['fields_name']);
                $query = "update form_" . $tracker_id . "_" . $form_ids ." set " . $fields[0] . "='" . $aernos . "'," . $fields[1] . "='" . $username . "' where id=$form_id";
                $statements = $this->adapter->query($query);
                $inserted++;

            }
        }
        return $inserted;
    }
    public function allocateusers($columnname,$data2DArray, $id, $form_ids,$tracker_id)
    {
        $query = "Select * from allocation_details where form_id=$form_ids and allocation_id ='$id'";
        $statements = $this->adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $inserted=0;
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        if ($arr[0]['update_on']!='') {
            $columns=$arr[0]['update_on'].','.$arr[0]['fields_name'];
        } else {
            $columns=$arr[0]['fields_name'];
        }
        if ($columnname!=$columns) {
            //echo "here";die;
            return 'invalid';
        }

        foreach ($data2DArray as $res) {
            if ($arr[0]['allocation_type'] == 0) {
                $values='';
                foreach ($res as $value) {
                    $value=addslashes($value);
                    if ($values=='') {
                        $values="'$value'";
                    } else {
                        $values=$values.','."'$value'";
                    }
                }
                $query = "insert into form_" . $tracker_id . "_" . $form_ids ."(" . $arr[0]['fields_name'] . ") VALUES ($values)";
                $statements = $this->adapter->query($query);
                $results = $statements->execute();
                $inserted++;
            } else if ($arr[0]['allocation_type'] == 1) {
                $form_id = addslashes($res[0]);
                $aernos = addslashes($res[1]);
                $username = addslashes($res[2]);
                $fields = explode(',', $arr[0]['fields_name']);
                $columns_name = explode(',', $columnname);
                array_shift($columns_name);
                $update='';
                $i=1;
                foreach ($columns_name as $name) {
                    $res[$i]=addslashes($res[$i]);
                    //echo $name;
                    if ($update=='') {
                         $update="$name='$res[$i]'";
                    } else {
                        $update=$update.','."$name='$res[$i]'";
                    }
                    $i++;
                    
                }
                $query = "update form_" . $tracker_id . "_" . $form_ids ." set " .$update.  " where id=$form_id";
                $statements = $this->adapter->query($query);
                $results = $statements->execute();
                $inserted++;
            }
        }
        return $inserted;
    }
    public function getAllocationType($form_id,$role_id,$tracker_id)
    {
        //echo $form_id.">>rAAA>>".$role_id.">>t>>".$tracker_id."\n"."???????????";die;
        $user_details = $_SESSION['user_details'];
        $role_name = $user_details['group_name'];

        if (isset($_SESSION['tracker_user_groups']) && $role_name!='SuperAdmin' ) {
            $tracker_user_groups = $_SESSION['tracker_user_groups'];
            $role_name = $tracker_user_groups[$tracker_id]['session_group'];;
        }
        //echo $user_details.">>r>>".$role_name.">>t>>".$tracker_user_groups."\n";die;
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        if ($role_name!='SuperAdmin' && $role_name!='Administrator') {

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
        $newadpater = $this->adapter;
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
    public function checkallocatetype($id, $form_id)
    {
        $query = "Select * from allocation_details where form_id=$form_id and allocation_id = '$id'";
        $statements = $this->adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }

    public function createCSVFileOld($id,$type) 
    {
        $filename="CSV".time().".csv";
        $myfile = fopen($_SERVER['DOCUMENT_ROOT'].'/files/'.$filename, "w");//or die("Unable to open file!");

        $query = "SELECT * FROM allocation_details where allocation_id='$id'";
        $statements = $this->adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        $column=explode(',', $arr[0]['fields_name']);

        $line = '"'.$arr[0]['update_on'].'","'.$column[0].'"'.',"'.$column[1].'"';
        $line .= "\n";
        fputs($myfile, $line);
        if ($type==1) {
            $query = $arr[0]['query'];
            $statements = $this->adapter->query("$query");
            $results = $statements->execute();
            $arr = array();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = @$resultSet->count();
            if ($count > 0) {
                $arr = $resultSet->toArray();
            }
            foreach ($arr as $value) {
                $line = "";
                $comma = "";
                foreach ( $value as $key => $values) {
                    $line .= $comma . '"' . str_replace('"', '""', $values) . '"';
                    if ($key==count($value)-1) {
                        $comma = "";
                    } else {
                        $comma = ",";
                    }
                }
                $line .= "\n";
                fputs($myfile, $line);
            }
        }
        fclose($myfile);
        return '/files/'.$filename;
    }
    
    public function createCSVFile($id)
    {
        $filename="CSV".time().".csv";
        $myfile = fopen($_SERVER['DOCUMENT_ROOT'].'/files/'.$filename, "w");//or die("Unable to open file!");
        $query = "SELECT * FROM allocation_details where allocation_id='$id'";
        $statements = $this->adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        $j=0;
        $column=explode(',', $arr[0]['fields_name']);
        if ($arr[0]['allocation_type']==0) {
            $line = '"'.$column[0].'"'.',"'.$column[1].'"';
             $line='';
            foreach ($column as $columns) {
                if ($line=='') {
                    $line = '"'.$columns.'"';
                } else {
                    $line = $line.',"'.$columns.'"';
                }
            }
        } else {
            $line='';
            foreach ($column as $columns) {
                if ($line=='') {
                    $line = '"'.$arr[0]['update_on'].'","'.$columns.'"';
                } else {
                    $line = $line.',"'.$columns.'"';
                }
                
            }
        }
        $line .= "\n";
        fputs($myfile, $line);
        if ($arr[0]['allocation_type']==0) {
            fclose($myfile);
            return '/files/'.$filename;
        }
        $query = $arr[0]['query'];
        $statements = $this->adapter->query("$query");
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        foreach ($arr as $value) {
            $line = "";
            $comma = "";
            foreach ( $value as $key => $values) {
                $line .= $comma . '"' . str_replace('"', '""', $values) . '"';
                if ($key==count($value)-1) {
                    $comma = "";
                } else {
                    $comma = ",";
                }
            }
            $line .= "\n";
            fputs($myfile, $line);
        }
        fclose($myfile);
        return '/files/'.$filename;
    }
    public function getReportMetadata($id)
    {

        $metadata = new \Zend\Db\Metadata\Metadata($this->adapter);
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from('report');
        $select->where(array('report_id' => $id));
        $newadpater = $this->adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);

        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            foreach ($arr as $key => $value) {
                $rep_query = $value['report_query'];
            }
        }
        $query='select * from'.$rep_query;
        $tableName1 = substr($rep_query, stripos($rep_query, 'from')+5);
        $tableName=explode(" ", $tableName1);
        $fields=$metadata->getColumnNames($tableName[0]);
        print_r($fields);
        exit;
    }
    public function fetchReportData($trackerId,$actionId,$id,$product_ids,$sDate,$eDate,$prefferedterms)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from('report');
        $res=array();
        $select->where(array('report_id' => $id));
        $newadpater = $this->adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);

        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $arr_rep = array();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            foreach ($arr as $key => $value) {
                $rep_query = $value['report_query'];
                $rep_id = $value['report_id'];
                $rep_group = $value['query_group_by'];
                $rep_having = $value['query_having'];
                $rep_order = $value['query_order_by'];
                $rep_where = $value['query_where'];
                $rep_type = $value['report_type'];
                $rep_filter = $value['custom_filter'];
                $rep_name = $value['report_name'];
                $rep_user = $value['custom_user'];
                $query='';
                $customWhere = " and date(".$rep_filter.")";
                if ($rep_type == 2) {
                    if ($sDate != '' && $eDate !='') {
                        $position = strpos($rep_filter, ",");
                        if ($position != false) {
                            $filtr = '(';
                            $filterArray = explode(",", $rep_filter);
                            foreach ($filterArray as $filter) {
                                if ($filtr == '(') {
                                    $filtr = $filtr.$filter." BETWEEN "."'$sDate' AND '$eDate' "; 
                                } else {
                                    $filtr = $filtr." OR ".$filter." BETWEEN "."'$sDate' AND '$eDate' "; 
                                }
                            }
                            $filtr = $filtr.')';                        
                            $query = $rep_query." ".$rep_where." AND ".$filtr.$rep_group;
                        } else {
                            $query = $rep_query." ".$rep_where." AND ".$rep_filter." BETWEEN "."'$sDate' AND '$eDate' ".$rep_group;  
                        }
                    } else {
                        $query = $rep_query." ".$rep_where." ".$rep_group;  
                    }
                } else if ($rep_type == 1) {
                        $query = 'call '.$rep_query.'("' . $trackerId . '","'.$actionId.'","'.$product_ids.'","'.$sDate.'","'.$eDate.'","'.$rep_filter.'","' . $prefferedterms . '")';
                }
                //echo $query; die;
                $connection = $this->adapter->getDriver()->getConnection();
                $result = $connection->execute($query);
                $statement = $result->getResource();
                $resultSet = $statement->fetchAll(\PDO::FETCH_NUM);
                $res[0]=$rep_name;
                $res[1]=$resultSet;
                return $res;
            }
        }
    }

    /*
    * to get field label
    */
    public function fetchReportHeader($formId,$reportid)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from('report');
        $select->where(array('report_id' => $reportid));
        $newadpater = $this->adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $arr = $arry = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $arry = $resultSet->toArray();
        foreach ($arry as $key => $value) {
            $rep_query = $value['report_query'];
            $rep_id = $value['report_id'];
            $rep_group = $value['query_group_by'];
            $rep_having = $value['query_having'];
            $rep_order = $value['query_order_by'];
            $rep_where = $value['query_where'];
            $rep_type = $value['report_type'];
            $rep_filter = $value['custom_filter'];
            $rep_name = $value['report_name'];
            $rep_user = $value['custom_user'];
            $qry='';
            if ($rep_type == 2) {
                $qry = $rep_query;
                $connection = $this->adapter->getDriver()->getConnection();
                $result = $connection->execute($qry);
                $statement = $result->getResource();
                $data = $statement->fetchAll(\PDO::FETCH_CLASS);
                if (isset($data[0])) {
                    $arr = array_keys((array)$data[0]);
                }
            } else {
                if ($reportid==1) {
                    $qry='SELECT fd.field_name,fd.label FROM field fd
                        LEFT JOIN workflow w ON w.workflow_id = fd.workflow_id
                        LEFT JOIN form f ON f.form_id = w.form_id
                        where f.form_id = '.$formId;
                } else if ($reportid==2) {
                    $qry = 'SELECT fd.field_name,fd.label FROM field fd
                        LEFT JOIN workflow w ON w.workflow_id = fd.workflow_id
                        LEFT JOIN form f ON f.form_id = w.form_id
                        where f.form_id = '.$formId.' AND w.workflow_id != 710';
                } else if ($reportid==3 || $reportid==4) {
                    $qry = 'SELECT fd.field_name,fd.label  FROM field fd
                        LEFT JOIN workflow w ON w.workflow_id = fd.workflow_id where w.form_id = ' . $formId.
                            ' ORDER BY fd.workflow_id,fd.sort_order,fd.field_id ASC;';
                } else if ($reportid == 5) {
                    $qry = ' SELECT "Term for Validation","Product Name","Initial Assessment Status","QC Status","DSL review status","Qualitative Summary Status","Validation Status"';
                } else if ($reportid == 6) {
                    $qry = ' SELECT "PT / Medical concept","Category","Product code","Source (Quarter YYYY-Qx)","DSL responsible","Target date for qualitative analysis","Actual date for qualitative analysis","Qualitative",'
                            . '"Target date for review of qualitative analysis","Actual date for review of qualitative analysis","Qualitative Review",'
                            . '"DSL reassignement","Signal validation meeting date","Decision if Validated Signal"';
                } else if ($reportid == 7 || $reportid == 8) {
                    $qry='SELECT fd.field_name,fd.label FROM field fd
                        LEFT JOIN workflow w ON w.workflow_id = fd.workflow_id
                        LEFT JOIN form f ON f.form_id = w.form_id
                        where f.form_id = '.$formId;
                } else if ($reportid==12) {
                    $qry = 'select f.label from field f
			Left join workflow w ON w.workflow_id = f.workflow_id
			WHERE w.form_id ='.$formId.' ORDER BY f.workflow_id,f.sort_order,f.field_id ASC';
                }
                $statements = $this->adapter->query($qry);
                $results = $statements->execute();
                $arr = array();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $count = @$resultSet->count();
                if ($count > 0) {
                    $arr = $resultSet->toArray();
                }
            }
        }
        return $arr;

    }


    public function exportReportData($id,$sDate,$eDate,$username)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from('report');
        $select->where(array('report_id' => $id));
        $newadpater = $this->adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);

        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $arr_rep=array();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            foreach ($arr as $key => $value) {
                $rep_query = $value['report_query'];
                $rep_group = $value['query_group_by'];
                $rep_having = $value['query_having'];
                $rep_order = $value['query_order_by'];
                $rep_where = $value['query_where'];
                $filter = $value['custom_filter'];
                $rep_type = $value['report_type'];
                $rep_user = $value['custom_user'];
                $customWhere= " and date(".$filter.") between '".$sDate."' and '".$eDate."'" ;
                //echo "$customWhere".$customWhere;
                $query='';
                if (strpos($rep_query, 'fn_') !== false) {
                    $connection = $this->adapter->getDriver()->getConnection();
                    $result = $connection->execute($rep_query);
                    $statement = $result->getResource();
                    $resultSet1 = $statement->fetchAll(\PDO::FETCH_OBJ);
                    foreach ($resultSet1 as $rowArray) {
                        foreach ($rowArray as $column => $value) {
                            $rep_query=$value;
                        }
                    }
                    if ($rep_type == 'mycase') {
                        $query = $rep_query . " " . $rep_where . " and " . $rep_user . " = '" . $username . "' " . $rep_group . " " . $rep_having . " " . $rep_order;
                    } else if (empty($sDate) && empty($eDate)) {
                        $query = $rep_query . " " . $rep_where . " " . $rep_group . " " . $rep_having . " " . $rep_order ;
                    } else {
                        $query = $rep_query . " " . $rep_where . " " . $customWhere . " " . $rep_group . " " . $rep_having . " " . $rep_order ;
                    }
                } else {
                    if ($rep_type == 'mycase') {
                        $query = $rep_query . " " . $rep_where . " and " . $rep_user . " = '" . $username . "' " . $rep_group . " " . $rep_having . " " . $rep_order ;
                    } else if (empty($sDate) && empty($eDate)) {
                        $query = $rep_query . " " . $rep_where . " " . $rep_group . " " . $rep_having . " " . $rep_order ;
                    } else {
                        $query = $rep_query . " " . $rep_where . " " . $customWhere . " " . $rep_group . " " . $rep_having . " " . $rep_order ;
                    }
                }


                /*  if (empty($sDate) && empty($eDate)) {
                    $query= $rep_query." ".$rep_where." ".$rep_group." ".$rep_having." ".$rep_order;
                } else {
                    $query= $rep_query." ".$rep_where." ".$CustomWhere." ".$rep_group." ".$rep_having." ".$rep_order;
                }*/


                $arr_rep = $this->exportQueryResult($query);
                //print_r($arr_rep);die;
            }
        }
        return $arr_rep;
    }

    public function exportQueryResult($query)
    {
        $statements = $this->adapter->query($query);
        //echo $query;die;
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        // print_r($arr);die;
        return $arr;
    }

    /* function to get default report name*/
    public function getDefaultReports($role_id, $form_id)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();

        $select->from('default_report_setting');
        $select->where(array('form_id' => $form_id,'role_id'=>$role_id));
        $newadpater = $this->adapter;
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
    /*
     * function to get info if report can be exported or not
     */
    public function getexportReportdetail($id,$form_id,$role_id )
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();

        $select->from('report_export_setting');
        $select->where(array('form_id' => $form_id,'role_id'=>$role_id,'report_id'=>$id));
        $newadpater = $this->adapter;
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

    public function getallProduct($trackerId, $form_id,$report_id)
    {
        $query = "select product.product_id,product.product_name
        from product where tracker_id=".$trackerId." and product_archive=0 ORDER BY product_name ASC" ;
        $statements = $this->adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        $res[0]=$arr;
        /*$query = "select form.form_type
        from form where form_id=".$form_id ;
        $statements = $this->adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        $res[1]=$arr;*/
        return $res;

    }

    public function getAllPrefferedTerms($trackerId, $form_id,$report_id,$productids)
    {
        //$query = 'call sp_getPreferredTerms("' . $trackerId . '","'.$form_id.'","'.$productids.'")';
        $query = "SELECT DISTINCT ptname from form_".$trackerId."_".$form_id." WHERE product_id IN(".$productids.") ORDER BY ptname ASC";
        $connection = $this->adapter->getDriver()->getConnection();
        $result = $connection->execute($query);
        $statement = $result->getResource();
        $resultSet = $statement->fetchAll(\PDO::FETCH_ASSOC);
        return $resultSet;
    }
    public function getReportDataById($reportid)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from('report');
        $select->where(array('report_id' => $reportid));
        $newadpater = $this->adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $arr = $arry = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $arry = $resultSet->toArray();
        return $arry;
    }
}
