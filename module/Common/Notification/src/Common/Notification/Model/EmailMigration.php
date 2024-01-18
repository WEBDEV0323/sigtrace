<?php

namespace Common\Notification\Model;

use Zend\Mvc\Controller\AbstractActionController;

class EmailMigration extends AbstractActionController
{
    /**
     * Function used to get instance of DB
     *
     * @return instance
     */
    public function dbConnection()
    {
        return $this->getServiceLocator()->get('db')->getDriver()->getConnection();
    }
    public function getUserId($userName)
    {
        $connection = $this->dbConnection();
        $query = 'SELECT u_id FROM user WHERE LOWER(u_name) = "'.strtolower($userName).'"';
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (empty($result)) {
            return 0;
        } else {
            return $result[0]['u_id'];
        }
    }
    public function getTrackerIds($userId)
    {
        $connection = $this->dbConnection();
        //$query = 'SELECT tracker_id FROM user_role_tracker WHERE u_id = '.$userId.' AND tracker_id !=0';
        $query = 'select tracker_id from `group` where group_id IN (SELECT group_id FROM user_role_tracker where u_id ='.$userId.' AND tracker_id IS NOT NULL)';
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (empty($result)) {
            return 0;
        } else {
            return $result;
        }
    }
    public function getFormIds($trackerId)
    {
        $connection = $this->dbConnection();
        $query = 'SELECT form_id FROM form WHERE tracker_id = '.$trackerId;
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (empty($result)) {
            return 0;
        } else {
            return $result;
        }
    }
    public function getUserTypeFields($formId)
    {
        $connection = $this->dbConnection();
        $query = 'SELECT field_name FROM field WHERE workflow_id IN (SELECT workflow_id FROM workflow WHERE form_id = '.$formId.')  AND LOWER(field_type) = "user"';
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (empty($result)) {
            return 0;
        } else {
            return $result;
        }
    }
    public function updateUserTypeFieldsData($trackerId, $formId, $UserTypeFields, $bioData)
    {
        $connection = $this->dbConnection();
        foreach ($UserTypeFields as $UserTypeField) {
            $qry = "SHOW COLUMNS FROM form_".$trackerId."_".$formId." LIKE '".$UserTypeField['field_name']."'";
            $stmt = $connection->execute($qry)->getResource();
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            if (!empty($result)) {
                //$query = "<div style='background:#C71585; color:#FFF; padding:3px;'>";
                $query = 'UPDATE form_'.$trackerId.'_'.$formId.' SET '.$UserTypeField['field_name'].' = "'.strtolower($bioData['Bioclinica']).'" WHERE LOWER('.$UserTypeField['field_name'].') = "'.strtolower($bioData['Synowledge']).'"';
                //$query .= "</div>";
                $statement = $connection->execute($query)->getResource();
                $statement->closeCursor();
                //echo $query."<br>";
            }
        }
    }
    public function updateUserTypeFieldsDataForBio($trackerId, $formId, $UserTypeFields, $bioData)
    {
        $connection = $this->dbConnection();
        foreach ($UserTypeFields as $UserTypeField) {
            $qry = "SHOW COLUMNS FROM form_".$trackerId."_".$formId." LIKE '".$UserTypeField['field_name']."'";
            $stmt = $connection->execute($qry)->getResource();
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            if (!empty($result)) {
                //$query = "<div style='background:#FFA500; padding:3px;'>";
                $query = 'UPDATE form_'.$trackerId.'_'.$formId.' SET '.$UserTypeField['field_name'].' = "'.strtolower($bioData).'" WHERE LOWER('.$UserTypeField['field_name'].') = "'.strtolower($bioData).'@bioclinica.com"';
                //$query .= "</div>";
                $statement = $connection->execute($query)->getResource();
                $statement->closeCursor();
                //echo $query."<br>";
            }
        }
    }
    public function updateUser($bioData)
    {
        $connection = $this->dbConnection();
        $qry = 'SELECT * FROM user WHERE LOWER(u_name) = "'.strtolower($bioData['Synowledge']).'"';
        $statement = $connection->execute($qry)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (!empty($result)) {
            if (count($result) > 1) {
                $qry = 'DELETE FROM user WHERE u_id = '.$result[0]['u_id'];
                $statement = $connection->execute($qry)->getResource();
                $statement->closeCursor();
                
                $qry1 = 'SELECT group_id FROM user_role_tracker WHERE u_id IN ('.$result[0]['u_id'].','.$result[1]['u_id'].') group by group_id';
                $state = $connection->execute($qry1)->getResource();
                $GrpResult = $state->fetchAll(\PDO::FETCH_ASSOC);
                $state->closeCursor();
                
                if (count($GrpResult) > 0) {
                    $qry2 = 'DELETE FROM user_role_tracker WHERE u_id IN ('.$result[0]['u_id'].','.$result[1]['u_id'].')';
                    $state1 = $connection->execute($qry2)->getResource();
                    $state1->closeCursor();

                    $insert = "INSERT into user_role_tracker VALUES";
                    $i = 0;
                    foreach ($GrpResult as $grp) {
                        $insert .= "(".$result[1]['u_id'].",".$grp['group_id'].",0)";
                        if (count($GrpResult)-1 > $i) {
                            $insert .= ", ";
                        }
                        $i++;
                    }
                    $state3 = $connection->execute($insert)->getResource();
                    $state3->closeCursor();
                }
                
                $query = 'UPDATE user SET u_name = "'.strtolower($bioData['Bioclinica']).'" , `status` = "Active", email = "'.strtolower($bioData['Bioclinica']).'@bioclinica.com", u_realname = "'.strtolower($bioData['Name']).'" WHERE LOWER(u_name) = "'.strtolower($bioData['Synowledge']).'"';
                
                $statement = $connection->execute($query)->getResource();
                $statement->closeCursor();
                
            } else {
                $query = 'UPDATE user SET u_name = "'.strtolower($bioData['Bioclinica']).'" , `status` = "Active", email = "'.strtolower($bioData['Bioclinica']).'@bioclinica.com", u_realname = "'.strtolower($bioData['Name']).'", user_type= "LDAP" WHERE LOWER(u_name) = "'.strtolower($bioData['Synowledge']).'"';
                $statement = $connection->execute($query)->getResource();
                $statement->closeCursor();
            }
        }
    }
    public function updateNormalUserToLDAPUser($bioData)
    {
        $connection = $this->dbConnection();
        $qry = 'SELECT * FROM user WHERE (LOWER(u_name) = "'.strtolower($bioData['Bioclinica']).'" OR LOWER(u_name) = "'.strtolower($bioData['Bioclinica']).'@bioclinica.com")';
        $statement = $connection->execute($qry)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (!empty($result)) {
            if (count($result) > 1) {
                $qry = 'DELETE FROM user WHERE u_id = '.$result[0]['u_id'];
                $statement = $connection->execute($qry)->getResource();
                $statement->closeCursor();
                
                $qry1 = 'SELECT group_id FROM user_role_tracker WHERE u_id IN ('.$result[0]['u_id'].','.$result[1]['u_id'].') group by group_id';
                $state = $connection->execute($qry1)->getResource();
                $GrpResult = $state->fetchAll(\PDO::FETCH_ASSOC);
                $state->closeCursor();
                
                if (count($GrpResult) > 0) {  
                    $qry2 = 'DELETE FROM user_role_tracker WHERE u_id IN ('.$result[0]['u_id'].','.$result[1]['u_id'].')';
                    $state1 = $connection->execute($qry2)->getResource();
                    $state1->closeCursor();

                    $insert = "INSERT into user_role_tracker VALUES";
                    $i = 0;
                    foreach ($GrpResult as $grp) {
                        $insert .= "(".$result[1]['u_id'].",".$grp['group_id'].",0)";
                        if (count($GrpResult)-1 > $i) {
                            $insert .= ", ";
                        }
                        $i++;
                    } //echo $insert; die;
                    $state3 = $connection->execute($insert)->getResource();
                    $state3->closeCursor();
                }
                
                $query = 'UPDATE user SET u_name = "'.strtolower($bioData['Bioclinica']).'", `status` = "Active", u_realname = "'.strtolower($bioData['Name']).'", user_type= "LDAP" WHERE LOWER(u_name) = "'.strtolower($bioData['Bioclinica']).'@bioclinica.com"';
                $statement = $connection->execute($query)->getResource();
                $statement->closeCursor();
            } else {
                $query = 'UPDATE user SET u_name = "'.strtolower($bioData['Bioclinica']).'", `status` = "Active", u_realname = "'.strtolower($bioData['Name']).'", user_type= "LDAP" WHERE LOWER(u_name) = "'.strtolower($bioData['Bioclinica']).'@bioclinica.com"';
                $statement = $connection->execute($query)->getResource();
                $statement->closeCursor();
            }
        }
    }
    public function getSuperAdmins()
    {
        $connection = $this->dbConnection();
        $qry = 'SELECT u_id FROM user where u_id IN (SELECT u_id from user_role_tracker where group_id = 1)';
        $statement = $connection->execute($qry)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $resultArray = array();
        foreach ($result as $r) {
            $resultArray[] = $r['u_id'];
        }
        $statement->closeCursor();
        return $resultArray;
    }
    public function getAllTrackerIds()
    {
        $connection = $this->dbConnection();
        $qry = 'SELECT tracker_id FROM tracker WHERE LOWER(`status`) = "active"';
        $statement = $connection->execute($qry)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $result;
    }
}
