<?php
namespace SigTRACE\FatalCumulativeAnalysis\Model;

use Zend\Mvc\Controller\AbstractActionController;

class FatalCumulativeAnalysis extends AbstractActionController
{
    protected $adapter;

    /**
     * Function used to get instance of DB 
     *
     * @return instance 
     */
    public function dbConnection()
    {
         return $this->getServiceLocator()->get('trace')->getDriver()->getConnection();
    }
    
    public function getFatalData($trackerId,$formId)
    {
        try {
            $connection = $this->dbConnection();
            $q1 = "SET @@group_concat_max_len =  1000000;";
            $connection->execute($q1)->getResource();

            $queryFields = "SELECT GROUP_CONCAT(fd.field_name) as fields FROM field fd 
                                            LEFT JOIN workflow w ON w.workflow_id = fd.workflow_id
                                            LEFT JOIN form f ON f.form_id = w.form_id
                                            where f.form_id = ".$formId." ORDER BY fd.workflow_id,fd.sort_order,fd.field_id ASC";
            $statement = $connection->execute($queryFields)->getResource();
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $statement->closeCursor();
            if (empty($result)) {
                return array();  
            } else {
                $query = "SELECT id, ".$result[0]['fields']." FROM form_".$trackerId."_".$formId. " WHERE LOWER(outcomecompany)='fatal'";
                $statement = $connection->execute($query)->getResource();
                $result = $statement->fetchAll(\PDO::FETCH_NUM);
                $statement->closeCursor();
                if (empty($result)) {
                    return array();  
                } else {
                    return $result; 
                }  
            }
        } catch(\Exception $e) {
             return array();
        } catch(\PDOException $e) {
             return array();
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
}
