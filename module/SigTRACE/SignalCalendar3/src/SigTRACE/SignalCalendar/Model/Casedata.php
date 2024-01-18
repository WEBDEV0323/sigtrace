<?php
namespace SigTRACE\SignalCalendar\Model;

use Zend\Mvc\Controller\AbstractActionController;
class Casedata extends AbstractActionController
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
    
    public function checkDupilcate($trackerId,$formId,$productId,$caseId,$verNo,$ptName,$reactionRank)
    {
        $connection = $this->dbConnection();
        $query = 'CALL sp_importAndDisplayList('.$trackerId.','.$formId.','.$productId.',0,"'.$caseId.'",'.$verNo.',"'.addslashes($ptName).'",'.$reactionRank.',"","","DUPLICATE")';
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (empty($result)) {
            return 0;  
        } else {
            return $result[0]['count']; 
        }
    }
    public function getQuantitativeSettingsConfigs($formId) 
    {
        $socQuery = "SELECT config_value FROM config  
                     WHERE scope_level = 'Form' AND scope_id = $formId AND config_key = 'quantitative_settings'";
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
    public function updateQuantitativeAnalysis($trackerId,$formId,$activeSubstance,$actSubId, $configValue, $sourceCustomer = 'safetydb')
    {
        $ptValue = '';
        $socValue = '';
        $ssValue = '';
        $asValue = '';
        if (count($configValue) > 0) {

            

            $socValue = isset($configValue[0]['socname']) ? $configValue[0]['socname'] : '';
            $ptValue = isset($configValue[0]['ptname']) ? $configValue[0]['ptname'] : '';
            $rankValue = isset($configValue[0]['rank']) ? $configValue[0]['rank'] : '';
            
            $ssValue = isset($configValue[0]['seriousnessevent']) ? $configValue[0]['seriousnessevent'] : '';
            $asValue = isset($configValue[0]['active_ingredient']) ? $configValue[0]['active_ingredient'] : '';
        
            $rationaleInsert = date("Y-m-d H:i:s") . " : " . "Added from import data.";
            $rationaleUpdate = date("Y-m-d H:i:s") . " : " . "Updated from import data.";
            $tableName = 'form_'.$trackerId.'_'.$formId;
            
            $insertQuery = "INSERT INTO quantitative_analysis 
                        (soc_name, `rank`, preferred_term, as_id, as_name, mc_id, mc_name, medical_evaluation, rationale, tracker_id,  import_source) 
                        SELECT $socValue AS soc_name, $rankValue AS `rank`, $ptValue As pt_name, $actSubId AS as_id, '$activeSubstance' AS as_name, m.pt_id AS mc_id, m.pt_name AS mc_name, "
                . "(CASE "
                . " WHEN (SELECT count(pt_id) FROM preferred_terms ad WHERE ad.pt_name = d.$ptValue AND ad.as_id = $actSubId AND ad.pt_archive = 0 AND ad.pt_type = 'Label Event') > 0 
                    THEN 1 
                    WHEN (SELECT count(pt_id) FROM preferred_terms ad WHERE ad.pt_name = d.$ptValue AND ad.as_id = $actSubId AND ad.pt_archive = 0 AND ad.pt_type = 'Synonym') > 0 
                    THEN 4 
                    WHEN (SELECT count(pt_id) FROM preferred_terms ad WHERE ad.pt_name = d.$ptValue AND ad.as_id = $actSubId AND ad.pt_archive = 0 AND ad.pt_type = 'Special Situation') > 0 "
                . " THEN 'NA' "

               . " WHEN (
                    SUM(IF(d.$ssValue= 'serious' AND d.$asValue = '$activeSubstance', 1,0)) >= 3
                    OR SUM(IF(d.$ssValue= 'not serious' OR d.$ssValue= '' AND d.$asValue =  '$activeSubstance', 1,0)) >= 12
                  )"
                . " THEN 3 "
                ."  WHEN ( 
                    SUM(IF(d.$ssValue= 'serious' AND d.$asValue =  '$activeSubstance', 1,0)) >= 0
                    AND SUM(IF(d.$ssValue= 'serious' AND d.$asValue =  '$activeSubstance', 1,0)) < 3
                  )"
                ."  THEN 2 "
                ."  WHEN (
                   SUM(IF(d.$ssValue= 'not serious' AND d.$asValue =  '$activeSubstance', 1,0)) >= 0
                   AND SUM(IF(d.$ssValue= 'not serious' AND d.$asValue =  '$activeSubstance', 1,0)) < 12
                  ) "
                ."  THEN 2 "
                . " ELSE 0 "
                . " END) AS risk_count" 
                . ", '$rationaleInsert' AS rationale "
                . ", $trackerId AS tracker_id "
                . ", '$sourceCustomer' AS import_source"
                . " FROM $tableName d "
                . " LEFT JOIN preferred_terms AS p ON d.$ptValue = p.pt_name AND p.as_id = $actSubId"
                . " LEFT JOIN preferred_terms AS m ON p.mc_id = m.pt_id AND m.pt_type = 'Medical Concept'" 
                . " WHERE d.$asValue = '$activeSubstance'"
                . " GROUP BY $ptValue, $socValue "
                . " ON DUPLICATE KEY UPDATE "
                . " medical_evaluation = VALUES(medical_evaluation), "
                . " rationale = IF(rationale IS NULL,  VALUES(rationale), CONCAT('$rationaleUpdate', '#', rationale)), "
                . " priority = NULL, "
                . " import_source = '$sourceCustomer'";
                $connection = $this->dbConnection();
                $statement = $connection->execute($insertQuery)->getResource();
                $statement->closeCursor();
        }
    }
    public function runZeroMedicalEvaluation($productId)
    {
        $query = "update quantitative_analysis set medical_evaluation = 0 where current_total = 0 AND product_id = ".$productId;
        $connection = $this->dbConnection();
        $statement = $connection->execute($query)->getResource();
        $statement->closeCursor();
    }
    public function getProductsList($trackerId)
    {
        $query = 'SELECT product_id, product_name, product_status, product_created_date, 
                 (SELECT count(le_id) FROM label_event WHERE product_id = product.product_id AND le_archive = 0) as labeled_count, 
                 (SELECT count(syn_id) FROM synonym WHERE product_id = product.product_id AND syn_archive = 0) as syn_count,
                 (SELECT count(ss_id) FROM special_situation WHERE product_id = product.product_id AND ss_archive = 0) as special_count
                  FROM product WHERE product_archive != 1 AND tracker_id = '.$trackerId.' ORDER BY product_id desc';
        $connection = $this->dbConnection();
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor(); 
        return $result;
    }
    public function getActiveIngradientByName($activeIngradient)
    {
        $query = "SELECT as_id, as_name 
                  FROM active_substance WHERE LOWER(TRIM(as_name)) =  '" . strtolower(trim($activeIngradient)) . "'";
        $connection = $this->dbConnection();
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor(); 
        return $result;
    }   
    public function getActiveIngradientFromProduct($productName, $trackerId)
    {
        $query = "SELECT a.as_id, b.as_name 
                  FROM product a 
                  JOIN active_substance b on b.as_id = a.as_id 
                  WHERE LOWER(TRIM(a.product_name)) =  '" . strtolower(trim($productName)) . "' AND tracker_id = $trackerId AND product_archive = 0";
        $connection = $this->dbConnection();
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor(); 
        return $result;
    }   
    public function insertNewPreferredTerms($ptNames)
    {
        $query = "INSERT IGNORE INTO preferred_terms (`pt_name`, `pt_created_date`, `as_id`) VALUES " . implode(',', $ptNames);
        $connection = $this->dbConnection();
        $statement = $connection->execute($query)->getResource();
        $statement->closeCursor();
    }    
    public function getErmrSettingsConfigs() 
    {
        $socQuery = "SELECT config_value FROM config  
                     WHERE scope_level = 'Global' AND config_key = 'ermr_headers'";
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
    public function getPTCodes($activeSubstance, $ptName)
    {
        $insertQuery = "SELECT "
                . "(CASE "
                . " WHEN (SELECT count(pt_id) FROM preferred_terms WHERE pt_name = '$ptName' AND as_id = d.as_id AND pt_archive = 0 AND pt_type = 'Label Event') > 0 
                    THEN '1' 
                    WHEN (SELECT count(pt_id) FROM preferred_terms WHERE pt_name = '$ptName' AND as_id = d.as_id AND pt_archive = 0 AND pt_type = 'Synonym') > 0 
                    THEN '4' 
                    WHEN (SELECT count(pt_id) FROM preferred_terms WHERE pt_name = '$ptName' AND as_id = d.as_id AND pt_archive = 0 AND pt_type = 'Special Situation') > 0 "
                . " THEN 'NA' "
                . " ELSE '0' "
                . " END) AS risk_count "
                . " FROM active_substance d "
                . " WHERE d.as_name = '$activeSubstance'";
        $connection = $this->dbConnection();
        $statement = $connection->execute($insertQuery)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $result;
    }
    public function insertQuantitativeAnalysis($values)
    {
        $insertQuery = "INSERT INTO quantitative_analysis 
                        (soc_name, preferred_term, as_id, as_name, medical_evaluation, priority, ror_all, rationale, non_ime_sdr, tracker_id, import_source) VALUES " . implode(',', $values)  
                . " ON DUPLICATE KEY UPDATE "
                . " medical_evaluation = VALUES(medical_evaluation), "
                . " priority = VALUES(priority), "
                . " ror_all = VALUES(ror_all), "
                . " rationale = VALUES(rationale), "
                . " non_ime_sdr = VALUES(non_ime_sdr), "
                . " import_source = VALUES(import_source)";
        $connection = $this->dbConnection();
        $statement = $connection->execute($insertQuery)->getResource();
        $statement->closeCursor();
    } 
    public function updateMedicalConcept($trackerId = 0)
    {
        $query = "UPDATE quantitative_analysis q 
                  JOIN preferred_terms AS p ON q.preferred_term = p.pt_name AND q.as_id = p.as_id 
                  JOIN preferred_terms AS m ON p.mc_id = m.pt_id AND m.pt_type = 'Medical Concept' 
                  SET q.mc_id = m.pt_id, q.mc_name = m.pt_name 
                  WHERE q.mc_id is null AND q.mc_name is null AND q.tracker_id = $trackerId";
        $connection = $this->dbConnection();
        $statement = $connection->execute($query)->getResource();
        $statement->closeCursor();
    }     
    public function getPTNameByNameAndActiveSubstance($asId, $ptName)
    {
        $insertQuery = "SELECT pt_id FROM preferred_terms WHERE pt_name = '$ptName' AND as_id = $asId";
        $connection = $this->dbConnection();
        $statement = $connection->execute($insertQuery)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $result;
    }    
    public function getQuantitativeDataByPTNameAndActiveSubstance($asId, $ptName, $trackerId)
    {
        $insertQuery = "SELECT medical_evaluation, priority, ror_all, rationale FROM quantitative_analysis WHERE preferred_term = '$ptName' AND as_id = $asId AND tracker_id = $trackerId";
        $connection = $this->dbConnection();
        $statement = $connection->execute($insertQuery)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $result;
    }  
    public function insertlitimportdata($arrInput, $userdetails)
    {
        $aidate = $arrInput['aip_ip_entry_date'];
        if ($aidate != '') {
            $date=date_create($aidate);
            $aidate1 = date_format($date, "Y-m-d");
            $arrInput['aip_ip_entry_date'] = $aidate1;
        }
        $fullrecorddate = $arrInput['full_record_entry_date'];
        if ($fullrecorddate != '') {
            $fullrecorddate1=date_create($fullrecorddate);
            $fullrecorddate1 = date_format($fullrecorddate1, "Y-m-d");
            $arrInput['full_record_entry_date'] = $fullrecorddate1;
        }
        array_push($arrInput['created_by']);
        $arrInput['created_by'] = $userdetails;
        $head_key = array_keys($arrInput);
        $string_key = implode(',', $head_key);
        $data ='';
        $count = count($head_key);
        $i= 0;
        foreach ($head_key as $arr) {
            if ($i != $count-1) {
                $data .= '"'.$arrInput[$arr].'",';
            } else {
                $data .= '"'.$arrInput[$arr].'"';
            }
            $i++;
        }
        $insertQuery = "INSERT INTO form_literature_109_199 (".$string_key.") VALUES (".$data.")";
        $connection = $this->dbConnection();
        $statement = $connection->execute($insertQuery)->getResource();
        $statement->closeCursor();
       
    }  
    public function getcommonimportSettingsConfigs($formId, $wherecheck) 
    {
        $socQuery = "SELECT config_value FROM config  
                     WHERE scope_level = 'Form' AND scope_id = $formId AND config_key = '".$wherecheck."'";
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
    public function insertcommonimportdata($arrInput, $userdetails, $tablename, $tracker_Id)
    {
        $date_of_the_report = $arrInput['date of the report'];
        if ($date_of_the_report != '') {
            $date=date_create($date_of_the_report);
            $date_of_the_report1 = date_format($date, "Y-m-d");
        }
        $date_of_review_sig = $arrInput['date of review in signal detection'];
        if ($date_of_review_sig != '') {
            $date=date_create($date_of_review_sig);
            $date_of_review_sig1 = date_format($date, "Y-m-d");
        }
        $date_of_signal_identification = $arrInput['date of signal identification'];
        if ($date_of_signal_identification != '') {
            $date=date_create($date_of_signal_identification);
            $date_of_signal_identification1 = date_format($date, "Y-m-d");
        }
        $source_reference_information = str_replace('"', ' ', $arrInput['source reference information']);
        $source_reference_information = str_replace("'", " ", $source_reference_information);
        $link = str_replace('"', ' ', $arrInput['link']);
        $link = str_replace("'", " ", $link);
        $recommendations_in_source = str_replace('"', ' ', $arrInput['recommendations in source']);
        $recommendations_in_source = str_replace("'", " ", $recommendations_in_source);
        $PTs_applicable_safety_information = str_replace('"', ' ', $arrInput['pts / applicable safety information']);
        $PTs_applicable_safety_information = str_replace("'", " ", $PTs_applicable_safety_information);
        $PT_safety_information_considered_for_further_analysis = str_replace('"', ' ', $arrInput['pt / safety information considered for further analysis']);
        $PT_safety_information_considered_for_further_analysis = str_replace("'", " ", $PT_safety_information_considered_for_further_analysis);
        $justification_notes = str_replace('"', ' ', $arrInput['justification notes']);
        $justification_notes = str_replace("'", " ", $justification_notes);
        $comments = str_replace('"', ' ', $arrInput['comments']);
        $comments = str_replace("'", " ", $comments);
        if ($tablename == 'aggregate_reports') {
            $tbl_name = 'form_aggregate_'.$tracker_Id.'';
        }
        if ($tablename == 'risk_management_plans') {
            $tbl_name = 'form_rmp_'.$tracker_Id.'';
        }
        if ($tablename == 'regulatory_recommendations') {
            $tbl_name = 'form_regulatory_re_'.$tracker_Id.'';
        }
        if ($tablename == 'clinical_trial') {
            $tbl_name = 'form_ct_'.$tracker_Id.'';
        }
        if ($tablename == 'other_sources') {
            $tbl_name = 'form_other_sources_'.$tracker_Id.'';
        }
        //$tablename = $tablename;
        // $insertQuery = "INSERT INTO form_aggregate_109 ( created_by, link, source_reference_information, date_of_the_report, date_of_review_in_signal_detection, recommendations_in_source, PTs_applicable_safety_information, date_of_signal_identification, PT_safety_information_considered_for_further_analysis, justification_notes, comments) 
        // VALUES ('".$userdetails."','".$link."','".$source_reference_information."','".$date_of_the_report1."','".$date_of_review_sig1."','".$recommendations_in_source."','".$PTs_applicable_safety_information."','".$date_of_signal_identification1."','".$PT_safety_information_considered_for_further_analysis."','".$justification_notes."','".$comments."')";
        $insertQuery = 'INSERT INTO '.$tbl_name.' ( created_by, link, source_reference_information, date_of_the_report, date_of_review_in_signal_detection, recommendations_in_source, PTs_applicable_safety_information, date_of_signal_identification, PT_safety_information_considered_for_further_analysis, justification_notes, comments) 
        VALUES ("'.$userdetails.'","'.$link.'","'.$source_reference_information.
        '","'.$date_of_the_report1.
        '","'.$date_of_review_sig1.'","'
        .$recommendations_in_source.'","'
        .$PTs_applicable_safety_information.'","'
        .$date_of_signal_identification1.'","'
        .$PT_safety_information_considered_for_further_analysis.'","'
        .$justification_notes.'","'
        .$comments.'")';
        $connection = $this->dbConnection();
        $statement = $connection->execute($insertQuery)->getResource();
        $statement->closeCursor();
    }  
    public function insertsignalcalendardata($arrInput, $tableName, $file_path)
    {
        $userdetails = $arrInput['created_by'];
        $filename = $arrInput['file_name'];
        $file_path = $arrInput['file_path'];
        $insertQuery = 'INSERT INTO '.$tableName.' ( created_by, file_name, file_path) 
        VALUES ("'.$userdetails.'","'.$filename.'","'.$file_path.'")';
        $connection = $this->dbConnection();
        $statement = $connection->execute($insertQuery)->getResource();
        $statement->closeCursor();
    } 
    public function getsignalcalendardata($trackerId) 
    {
        $socQuery = "select id, DATE_FORMAT(created_date_time,\"%d-%M-%Y\") , file_name, created_by from signal_calendar_file_109 where is_deleted = 'No'";
        $connection = $this->dbConnection();
        $statement = $connection->execute($socQuery)->getResource();
        $sigcalArr = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor(); 
        $sigcalValue = array();
        // if (count($sigcalArr) > 0) {
        //     $sigcalValue = json_decode($sigcalArr);
        // }
            // print_r ($sigcalValue); die;   
        return $sigcalArr;
    }  
    
}
