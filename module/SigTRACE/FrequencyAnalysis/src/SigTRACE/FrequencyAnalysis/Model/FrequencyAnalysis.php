<?php
namespace SigTRACE\FrequencyAnalysis\Model;

use Zend\Mvc\Controller\AbstractActionController;

class FrequencyAnalysis extends AbstractActionController
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
    
    public function getFrequencyAnalysisData($trackerId, $formId, $exp1, $exp2)
    {
        $connection = $this->dbConnection();
        $query = 'CALL sp_frequencyAnalysis('.$trackerId.','.$formId.','.$exp1.','.$exp2.')';
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

