<?php
namespace SigTRACE\Product\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Driver\ConnectionInterface;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Session\Container\SessionContainer;
use Zend\Mvc\Controller\AbstractActionController;

class MedicalConcept extends AbstractActionController
{
    protected $_adapter;
    
    public function __construct(Adapter $adapter)
    {
        $this->_adapter = $adapter;
    }
    
    public function dbConnection()
    {
         return $this->getServiceLocator()->get('trace')->getDriver()->getConnection();
    }
    
    public function getAllMedicalConcepts($coulmn, $condition)
    {
        $qry = 'SELECT '.$coulmn.' FROM preferred_terms WHERE '.$condition;//echo $qry;die;
        $statements = $this->_adapter->createStatement($qry, array());
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
        $statements->getResource()->closeCursor();
        return $arr;
    }
    
    public function trackerResults($trackerId)
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
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
            $roleId = $trackerUserGroups[$trackerId]['session_group_id'];
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
    
    public function addMedicalConcept($dataArr,$userContainer)
    {
        $medicalConceptName = isset($dataArr['medicalConceptName'])?$dataArr['medicalConceptName']:'';
        $medicalConceptName = ucwords(strtolower($medicalConceptName));
        $ptType = isset($dataArr['type'])?$dataArr['type']:'';
        $trackerId = isset($dataArr['trackerId'])?$dataArr['trackerId']:'';
        $preferredTermIds = isset($dataArr['preferredTermIds'])?$dataArr['preferredTermIds']:'';
        $actSubId = isset($dataArr['actSubId'])?$dataArr['actSubId']:'';
        $allMedConNames = $this->getAllMedicalConcepts('*', 'pt_archive=0');
        $match = 0;
        $responseCode = 0;
        foreach ($allMedConNames as $ind => $val) {
            if ($val['pt_name'] === $medicalConceptName) {
                $match++;
            }
        }
        if ($match > 0) {
            $responseCode = 0;
            $errMessage = 'Medical concept/preferred term already exists with same name. Enter new medical concept/preferred term name.'; 
        } else {
            try {
                    $sql = new Sql($this->_adapter);
                    $insert = $sql->insert('preferred_terms');
                    $newData = array('pt_name'=>$medicalConceptName, 'pt_created_date'=> date("Y-m-d H:i:s"),'pt_archive' =>0,'as_id'=>$actSubId,'pt_type'=>$ptType,'pt_last_modified_by'=>$userContainer->email,'pt_last_modified_date_time'=>date("Y-m-d H:i:s"));
                    $insert -> values($newData);
                    $statement = $sql->prepareStatementForSqlObject($insert);
                    $results = $statement->execute();
                    $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();
                if ($ptType == 'Medical Concept') {
                    $sql = new Sql($this->_adapter);
                    $update=$sql->update();
                    $update->table('preferred_terms');
                    $prodAs = array('mc_id'=>$lastInsertID);
                    $update->set($prodAs);
                    $update->where(array("pt_id "=>$preferredTermIds));        
                    $prepStatement = $sql->prepareStatementForSqlObject($update);
                    $result = $prepStatement->execute();
                }

                if ($lastInsertID > 0) {
                    $responseCode = 1;
                    $errMessage = 'Medical concept/Preferred Term created successfully';
                } else {
                    $responseCode = 0;
                    $errMessage = 'Error while inserting medical concept/preferred term';
                }                
            } catch(\Exception $e) {
                $responseCode = 0;
                $errMessage = 'Error while inserting medical concept/preferred term'; 
            } catch(\PDOException $e) {                    
                $responseCode = 0;
                $errMessage = 'Error while inserting medical concept/preferred term';
            }
        }
        if (empty($lastInsertID)) {
            $lastInsertID = "";
        }
        $clientName = $this->getClientNameByTrackerId($trackerId);
        $resultsArr['actionId'] = $lastInsertID;
        $resultsArr['clientId'] = $clientName['0']['client_id'];
        $resultsArr['trackerId'] = $trackerId;
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }
    
    public function saveMedicalConcept($dataArr,$userContainer, $prevMcName)
    {
        $medicalConceptName = isset($dataArr['medicalConceptName'])?$dataArr['medicalConceptName']:'';
        $medicalConceptName = ucwords(strtolower($medicalConceptName));
        $ptType = isset($dataArr['type'])?$dataArr['type']:'';
        $trackerId = isset($dataArr['trackerId'])?$dataArr['trackerId']:'';
        $formId = isset($dataArr['trackerId'])?$dataArr['trackerId']:'';
        $medicalConceptId = isset($dataArr['medicalConceptId'])?$dataArr['medicalConceptId']:'';
        $preferredTermIds = isset($dataArr['preferredTermIds'])?$dataArr['preferredTermIds']:'';
        $prevPtIds = $this->getAllMedicalConcepts('pt_id', 'mc_id='.$medicalConceptId);
        $formData = $this->getFormDataByTrackerId('form_id', 'tracker_id='.$trackerId);//   echo '<pre>'; print_r($formData);die;
        foreach ($prevPtIds as $key => $value) {
            $previousPt[] = $value['pt_id'];
        }
        $responseCode = 0;
        $dupMedCon = $this->checkDuplicateMedicalConcept($medicalConceptId);
        if (in_array($medicalConceptName, $dupMedCon)) {
            $responseCode = 0;
            $errMessage = 'Medical concept/preferred term already exists with same name. Enter new medical concept/preferred term.'; 
        } else {
            try {
                $sql = new Sql($this->_adapter);
                $update=$sql->update();
                $update->table('preferred_terms');
                $prodAs = array('pt_name'=>$medicalConceptName,'pt_type'=>$ptType,'pt_last_modified_by'=>$userContainer->email,'pt_last_modified_date_time'=>date("Y-m-d H:i:s"));
                $update->set($prodAs);
                $update->where(array("pt_id "=>$medicalConceptId));        
                $prepStatement = $sql->prepareStatementForSqlObject($update);
                $result = $prepStatement->execute();
                
                if (!empty($previousPt)) {
                    $sql = new Sql($this->_adapter);
                    $update=$sql->update();
                    $update->table('preferred_terms');
                    $prodAs = array('mc_id'=>0);
                    $update->set($prodAs);
                    $update->where(array("pt_id "=>$previousPt));        
                    $prepStatement = $sql->prepareStatementForSqlObject($update);//echo ($sql->getSqlStringForSqlObject($update));echo '>>>';
                    $result = $prepStatement->execute();
                }
                if ($ptType == 'Medical Concept') {
                    $sql = new Sql($this->_adapter);
                    $update=$sql->update();
                    $update->table('preferred_terms');
                    $prodAs = array('mc_id'=>$medicalConceptId);
                    $update->set($prodAs);
                    $update->where(array("pt_id "=>$preferredTermIds));        
                    $prepStatement = $sql->prepareStatementForSqlObject($update);//echo ($sql->getSqlStringForSqlObject($update));die;
                    $result = $prepStatement->execute();                                        
                    
                    foreach ($formData as $key => $value) {
                        $configVal = $this->getQuantitativeSettingsConfigs($value['form_id']);
                        if (!empty($configVal)) {
                            $ptValue = isset($configVal[0]['ptname']) ? $configVal[0]['ptname'] : 'ptname';
                            $sql = new Sql($this->_adapter);
                            $update=$sql->update();
                            $update->table('form_'.$trackerId.'_'.$value['form_id']);
                            $prodAs = array($ptValue=>$medicalConceptName);
                            $update->set($prodAs);
                            $update->where(array($ptValue=>$prevMcName['0']['pt_name']));        
                            $prepStatement = $sql->prepareStatementForSqlObject($update);//echo ($sql->getSqlStringForSqlObject($update));echo '<br>';
                            $result = $prepStatement->execute();
                        }
                    }
                    $qaConfigVal = $this->getQuantitativeAnalysisSettingsConfigs();
                    $mcValue = isset($qaConfigVal[0]['mc_name']) ? $qaConfigVal[0]['mc_name'] : 'mc_name';
                    $sql = new Sql($this->_adapter);
                    $update=$sql->update();
                    $update->table('quantitative_analysis');
                    $prodAs = array($mcValue=>$medicalConceptName);
                    $update->set($prodAs);
                    $update->where(array($mcValue=>$prevMcName['0']['pt_name']));        
                    $prepStatement = $sql->prepareStatementForSqlObject($update);//echo ($sql->getSqlStringForSqlObject($update));die;
                    $result = $prepStatement->execute();
                } else {
                    foreach ($formData as $key => $value) {
                        $configVal = $this->getQuantitativeSettingsConfigs($value['form_id']);
                        if (!empty($configVal)) {
                            $ptValue = isset($configVal[0]['pt_name']) ? $configVal[0]['pt_name'] : 'pt_name';
                            $sql = new Sql($this->_adapter);
                            $update=$sql->update();
                            $update->table('form_'.$trackerId.'_'.$value['form_id']);
                            $prodAs = array($ptValue=>$medicalConceptName);
                            $update->set($prodAs);
                            $update->where(array($ptValue=>$prevMcName['0']['pt_name']));        
                            $prepStatement = $sql->prepareStatementForSqlObject($update);//echo ($sql->getSqlStringForSqlObject($update));echo '<br>';
                            $result = $prepStatement->execute();
                        }
                    }
                    $qaConfigVal = $this->getQuantitativeAnalysisSettingsConfigs();
                    $mcValue = isset($qaConfigVal[0]['preferred_term']) ? $qaConfigVal[0]['preferred_term'] : 'preferred_term';
                    $sql = new Sql($this->_adapter);
                    $update=$sql->update();
                    $update->table('quantitative_analysis');
                    $prodAs = array($mcValue=>$medicalConceptName);
                    $update->set($prodAs);
                    $update->where(array($mcValue=>$prevMcName['0']['pt_name']));        
                    $prepStatement = $sql->prepareStatementForSqlObject($update);//echo ($sql->getSqlStringForSqlObject($update));die;
                    $result = $prepStatement->execute();
                }
                
                $responseCode = 1;
                $errMessage = 'Medical Concept/Preferred Term updated successfully';                                    
                
            } catch(\Exception $e) {
                $responseCode = 0;
                $errMessage = 'Error While updating medical concept/preferred term'; 
            } catch(\PDOException $e) {                    
                $responseCode = 0;
                $errMessage = 'Error While updating medical concept/preferred term';
            }
        }
        $clientName = $this->getClientNameByTrackerId($trackerId);
        $resultsArr['actionId'] = $medicalConceptId;
        $resultsArr['clientId'] = $clientName['0']['client_id'];
        $resultsArr['trackerId'] = $trackerId;
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }

    public function checkDuplicateMedicalConcept($medicalConceptId)
    {
        $qry = 'SELECT pt_name FROM preferred_terms WHERE pt_archive=0 AND pt_id!=?';
        $statements = $this->_adapter->createStatement($qry, array($medicalConceptId));
        $statements->prepare();
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            foreach ($arr as $value) {
                $resArr[]=$value['pt_name'];
            }
        }
        $statements->getResource()->closeCursor();
        return $resArr;
    }


    public function getSelectedMedicalConcept($medicalConceptId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('preferred_terms');
        $select->where(array('pt_id' => $medicalConceptId));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute(); 
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arrData = $resultSet->toArray();            
        }
        return $arrData;
    }

    public function getClientNameByTrackerId($trackerId)
    {
        $qry = 'SELECT client_id FROM tracker WHERE tracker_id=?';
        $statements = $this->_adapter->createStatement($qry, array($trackerId));
        $statements->prepare();
        $results = $statements->execute();        
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arrData = $resultSet->toArray();
        }
        $statements->getResource()->closeCursor();
        return $arrData;
    }
    
    
    
    public function deleteMedicalConcept($trackerId,$medicalConceptId,$userContainer)
    {
        try {            
            $qry = 'UPDATE preferred_terms SET pt_name = CONCAT(pt_name,"_deleted_'.time().'"), pt_archive=1, pt_archived_date="'.date("Y-m-d H:i:s").'", pt_last_modified_by="'.$userContainer->email.'", pt_last_modified_date_time="'.date("Y-m-d H:i:s").'" WHERE pt_id=?';
            $statements = $this->_adapter->createStatement($qry, array($medicalConceptId));
            $statements->prepare();
            $results = $statements->execute();
            $sql = new Sql($this->_adapter);
            $update=$sql->update();
            $update->table('preferred_terms');
            $prodAs = array('mc_id'=>0);
            $update->set($prodAs);
            $update->where(array("mc_id "=>$medicalConceptId));
            $prepStatement = $sql->prepareStatementForSqlObject($update);
            $result = $prepStatement->execute();
            
            $responseCode = 1;
            $errMessage = 'Medical concept deleted successfully';  
        } catch(\Exception $e) {
            $responseCode = 0;
            $errMessage = 'Error While updating medical concept'; 
        } catch(\PDOException $e) {
            $responseCode = 0;
            $errMessage = 'Error While updating medical concept';
        }
        $clientName = $this->getClientNameByTrackerId($trackerId);
        $resultsArr['clientId'] = $clientName['0']['client_id'];
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }
    
    public function getFormDataByTrackerId($columns, $whereCondition) 
    {
        $socQuery = "SELECT $columns FROM form  
                     WHERE $whereCondition";
        $connection = $this->dbConnection();
        $statement = $connection->execute($socQuery)->getResource();
        $configArr = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $configArr;        
    }
    
    public function getQuantitativeSettingsConfigs($formId)
    {
        $socQuery = "SELECT config_value FROM config  
                     WHERE scope_level = 'Form' AND scope_id = $formId AND config_key = 'qualitative_settings'";
        $connection = $this->dbConnection();
        $statement = $connection->execute($socQuery)->getResource();
        $configArr = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor(); 
        $configValue = array();
        if (count($configArr) > 0) {
            $configValue = json_decode($configArr[0]['config_value'], true);
        }
        
        return $configValue;
    }
    
    public function getQuantitativeAnalysisSettingsConfigs()
    {
        $socQuery = "SELECT config_value FROM config  
                     WHERE scope_level = 'Global' AND config_key = 'qualitative_analysis_settings'";
        $connection = $this->dbConnection();
        $statement = $connection->execute($socQuery)->getResource();
        $configArr = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor(); 
        $configValue = array();
        if (count($configArr) > 0) {
            $configValue = json_decode($configArr[0]['config_value'], true);
        }
        
        return $configValue;
    }
}
