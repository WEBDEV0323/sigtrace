<?php
namespace SigTRACE\SignalCalendar\Model;

use Zend\Mvc\Controller\AbstractActionController;
class SignalCalendar extends AbstractActionController
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
    public function getQuantitativeAnalysis($trackerId)
    {    
        

        $query = "select created_by, created_date_time, file_name, file_path from signal_calendar_file_109
        where is_deleted = 'No' ";
        
        $connection = $this->dbConnection();
        $statement = $connection->execute($query)->getResource();
       // print_r ($statement); die;
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        
        $statement->closeCursor();
        $rResult = array();
        
        return $result;          
    }
    public function getproductname($productId)
    {
        $connection = $this->dbConnection();
        $query = "SELECT product_name FROM  product where product_id= ".$productId;
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (empty($result)) {
            return array();
        } else {
            return $result;
        }
    }
    public function getAllProducts($trackerId,$formId)
    {
        $connection = $this->dbConnection();
        $query = 'CALL sp_importAndDisplayList('.$trackerId.','.$formId.',0,0,"",0,"",0,"","","QUANTITATIVE_PRODUCTS")';
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (empty($result)) {
            return array();  
        } else {
            return $result; 
        }  
    }
    public function getQuantitativeIndividualList($trackerId,$formId,$productId,$ptName,$type,$tp)
    {
        $connection = $this->dbConnection();
        $qry = 'CALL sp_importAndDisplayList('.$trackerId.','.$formId.','.$productId.',0,"",0,"'.$ptName.'",0,"'.$type.'","'.$tp.'","QUANTITATIVE_PRODUCTS_LIST")';
        $statement = $connection->execute($qry)->getResource();
        $r2 = $statement->fetchAll(\PDO::FETCH_NUM);
        $statement->closeCursor();  
        return $r2;
    }
    public function getViewHeader($formId)
    {
        $connection = $this->dbConnection();
        $query = "SELECT fd.label FROM field fd 
                LEFT JOIN workflow w ON w.workflow_id = fd.workflow_id
                LEFT JOIN form f ON f.form_id = w.form_id
                where f.form_id = ".$formId." ORDER BY fd.workflow_id,fd.sort_order,fd.field_id ASC";
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (empty($result)) {
            return array();  
        } else {
            return $result; 
        }   
    }
    public function updateQuantitativeAnalysis($trackerId,$formId,$productId)
    {
        $query = 'CALL sp_importAndDisplayList('.$trackerId.','.$formId.','.$productId.',0,"",0,"",0,"","","MEDICAL_EVALUATION")';
        $connection = $this->dbConnection();
        $statement = $connection->execute($query)->getResource();
        $statement->closeCursor(); 
    }
    public function downloadExcel($productId, $trackerId, $formId, $configValue, $uname, $dateRange, $filterArr, $condition = '')
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        
        $objPHPExcel = new \PHPExcel();
        $connection = $this->dbConnection();
        
        $query = "SELECT as_name AS product_name FROM active_substance WHERE as_id = $productId";
        $statement = $connection->execute($query)->getResource();
        $product = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (empty($product)) {
            $productName = "";
        } else {
            $productName = $product[0]['product_name'];
        }
        
        $head = array(
                    "soc_name"=> "SOC Name",
                    "preferred_term" => "Preferred Term" ,
                    "mc_name" => "Medical Concept" ,
                    "medical_evaluation" =>"Medical Evaluation",  
                    "priority" =>"Priority", 
                    "rationale" => "Rationale",            
                    "cur_serious_count" =>"Serious", 
                    "cur_not_serious_count" =>"Not Serious",
                    "current_total" =>"Total",
                    "cum_serious_count" =>"Serious",
                    "cum_not_serious_count" =>"Not Serious",
                    "cumulative_total" =>"Total", 
                    "ror_all" =>"ROR (-) All"
                );
        $hArray = array(0=>$head);
        
        $tableName = 'form_'.$trackerId.'_'.$formId;
        $aiValue = 'active_ingredient';
        $ptValue = 'ptname';
        $socValue = 'socname';
        $ssValue = 'seriousnessevent';
        $asValue = 'as_name';
        $nvlValue = 'nvl';
        $dateValue = 'initialreceiptdate';
        $idValue = 'id';
        if (count($configValue) > 0) {
            $socValue = isset($configValue[0]['socname']) ? $configValue[0]['socname'] : 'socname';
            $ptValue = isset($configValue[0]['ptname']) ? $configValue[0]['ptname'] : 'ptname';
            $ssValue = isset($configValue[0]['seriousnessevent']) ? $configValue[0]['seriousnessevent'] : 'seriousnessevent';
            $asValue = isset($configValue[0]['as_name']) ? $configValue[0]['as_name'] : 'as_name';
            $aiValue = isset($configValue[0]['active_ingredient']) ? $configValue[0]['active_ingredient'] : 'active_ingredient';
            $nvlValue = isset($configValue[0]['nvl']) ? $configValue[0]['nvl'] : 'nvl';
            $dateValue = isset($configValue[0]['datefield']) ? $configValue[0]['datefield'] : 'initialreceiptdate';
            $idValue = isset($configValue[0]['countfield']) ? $configValue[0]['countfield'] : 'id';
        }        
      
        $sQuery = "SELECT qa.soc_name, qa.preferred_term, qa.mc_name, qa.medical_evaluation, IFNULL(qa.priority, '-') as priority, qa.rationale,
                count(case when(seriousnessevent = 'serious' and $filterArr) then 1 end) as current_serious_frequency,
                count(case when(seriousnessevent = 'not serious' and $filterArr) then 1 end) as current_nonserious_frequency,
                count(case when($filterArr) then 1 end) as current_total,
                (select count(seriousnessevent) from $tableName where seriousnessevent = 'serious' and ptname = qa.preferred_term and active_ingredient = qa.as_name) as cumulative_serious_frequency, 
                (select count(seriousnessevent) from $tableName where seriousnessevent = 'not serious' and ptname = qa.preferred_term and active_ingredient = qa.as_name) as cumulative_non_serious_frequency, 
                (select count(seriousnessevent) from $tableName where ptname = qa.preferred_term and active_ingredient = qa.as_name) as cumulative_total,  
                qa.ror_all
                from quantitative_analysis as qa join ".$tableName." as f 
                WHERE qa.as_name = f.$aiValue and f.$ptValue = qa.preferred_term and qa.as_id = $productId "
                . " AND qa.tracker_id = $trackerId "
                . " and $filterArr "
                . " GROUP BY qa.preferred_term, qa.soc_name";
        
        $statement2 = $connection->execute($sQuery)->getResource();
        $aResult = $statement2->fetchAll(\PDO::FETCH_ASSOC);
        $statement2->closeCursor();
       
        $excelData = array_merge($hArray, $aResult);
        
        // Set properties
        $objPHPExcel->getProperties()->setCreator("SigTRACE");        
        $objPHPExcel->getProperties()->setLastModifiedBy("SigTRACE");
        $objPHPExcel->getProperties()->setTitle("Quantitative_Analysis_".$productName);
        $objPHPExcel->getProperties()->setSubject("Quantitative_Analysis_".$productName);
        $objPHPExcel->getProperties()->setDescription("Quantitative_Analysis_".$productName);
                
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
        $objPHPExcel->getActiveSheet()->getPageSetup()->setPaperSize(\PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
        $objPHPExcel->getActiveSheet()->getPageSetup()->setFitToPage(true);
        $objPHPExcel->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $objPHPExcel->getActiveSheet()->getPageSetup()->setFitToHeight(0);
            
        /*$objPHPExcel->getActiveSheet()->getStyle("B2")->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->SetCellValue('B2', "Company : Pharma Comapy");*/
        
        $objPHPExcel->getActiveSheet()->getStyle("B3")->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->SetCellValue('B3', "Report Name : Quantitative Analysis");
        
        $objPHPExcel->getActiveSheet()->getStyle("B4")->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->SetCellValue('B4', "Date of Generation : ".date("d-M-Y"));
        
        if ($productName != '') {
            $objPHPExcel->getActiveSheet()->getStyle("B5")->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->SetCellValue('B5', "Active Ingredient : $productName"); 
        }
        
        $objPHPExcel->getActiveSheet()->getStyle("B6")->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->SetCellValue('B6', "Author of Generation : ".$uname);
        
        $objPHPExcel->getActiveSheet()->getStyle("G7")->getFont()->setBold(true);
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('G7:I7');                
        $objPHPExcel->getActiveSheet()->getStyle('G7')->getAlignment()->setWrapText(true);    
        $objPHPExcel->getActiveSheet()->getRowDimension(7)->setRowHeight(30);
        $objPHPExcel->getActiveSheet()->SetCellValue('G7', "Selected frequency (".$dateRange. ")");
        
        $objPHPExcel->getActiveSheet()->getStyle("J7")->getFont()->setBold(true);
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('J7:L7');
        $objPHPExcel->getActiveSheet()->SetCellValue('J7', "Cumulative frequency");
        
        $header = '8:8';
        $objPHPExcel->getActiveSheet()->getStyle($header)->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle($header)->getAlignment()->setWrapText(true);
        $objPHPExcel->getActiveSheet()->fromArray($excelData, null, 'A8');
        
        
        
        $objPHPExcel->getActiveSheet()->setTitle(substr("Quantitative_Analysis_".$productName, 0, 30));
        
        // required for IE
        if (ini_get('zlib.output_compression')) { 
            ini_set('zlib.output_compression', 'Off');
        }

        $fileName  = "Quantitative_Analysis_".$productName."_".date("d-M-Y").".xls";
    
        ob_end_clean();
        header("Content-Type: application/vnd.ms-excel");
        header('Content-Disposition: attachment;filename="'.$fileName.'"');
        header("Cache-Control: max-age=0");
        
        // Writing to excel file using PHPExcel Object
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        ob_get_clean();
        $objWriter->save('php://output');
        ob_end_flush();
        exit();
    
    }
    public function downloadCSV($productId, $trackerId, $formId, $configValue, $uname, $dateRange, $filterArr, $condition = '')
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $connection = $this->dbConnection();
        
        $query = "SELECT as_name AS product_name FROM active_substance WHERE as_id = $productId";
        $statement = $connection->execute($query)->getResource();
        $product = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (empty($product)) {
            $productName = "";
        } else {
            $productName = $product[0]['product_name'];
        }
        
        $tableName = 'form_'.$trackerId.'_'.$formId;
        $aiValue = 'active_ingredient';
        $ptValue = 'ptname';
        $socValue = 'socname';
        $ssValue = 'seriousnessevent';
        $asValue = 'as_name';
        $nvlValue = 'nvl';
        $dateValue = 'initialreceiptdate';
        $idValue = 'id';
        if (count($configValue) > 0) {
            $socValue = isset($configValue[0]['socname']) ? $configValue[0]['socname'] : 'socname';
            $ptValue = isset($configValue[0]['ptname']) ? $configValue[0]['ptname'] : 'ptname';
            $ssValue = isset($configValue[0]['seriousnessevent']) ? $configValue[0]['seriousnessevent'] : 'seriousnessevent';
            $asValue = isset($configValue[0]['as_name']) ? $configValue[0]['as_name'] : 'as_name';
            $aiValue = isset($configValue[0]['active_ingredient']) ? $configValue[0]['active_ingredient'] : 'active_ingredient';
            $nvlValue = isset($configValue[0]['nvl']) ? $configValue[0]['nvl'] : 'nvl';
            $dateValue = isset($configValue[0]['datefield']) ? $configValue[0]['datefield'] : 'initialreceiptdate';
            $idValue = isset($configValue[0]['countfield']) ? $configValue[0]['countfield'] : 'id';
        }
        
        $hArray = array(
                    "soc_name"=> "SOC Name",
                    "preferred_term" => "Preferred Term" ,
                    "mc_name" => "Medical Concept" ,
                    "medical_evaluation" =>"Medical Evaluation",  
                    "priority" =>"Priority",   
                    "rationale" => "Rationale",          
                    "current_serious_frequency" =>"Serious", 
                    "current_nonserious_frequency" =>"Not Serious",
                    "current_total" =>"Total",
                    "cumulative_serious_frequency" =>"Serious",
                    "cumulative_non_serious_frequency" =>"Not Serious",
                    "cumulative_total" =>"Total", 
                    "ror_all" =>"ROR (-) All"
                );
     
                
        $sQuery = "SELECT qa.pt_id, qa.soc_name, qa.preferred_term, qa.mc_name, qa.medical_evaluation, IFNULL(qa.priority, '-') as priority, qa.ror_all, qa.rationale,
                count(case when(seriousnessevent = 'serious' and $filterArr) then 1 end) as current_serious_frequency,
                count(case when(seriousnessevent = 'not serious' and $filterArr) then 1 end) as current_nonserious_frequency,
                count(case when($filterArr) then 1 end) as current_total,
                (select count(seriousnessevent) from $tableName where seriousnessevent = 'serious' and ptname = qa.preferred_term and active_ingredient = qa.as_name) as cumulative_serious_frequency, 
                (select count(seriousnessevent) from $tableName where seriousnessevent = 'not serious' and ptname = qa.preferred_term and active_ingredient = qa.as_name) as cumulative_non_serious_frequency, 
                (select count(seriousnessevent) from $tableName where ptname = qa.preferred_term and active_ingredient = qa.as_name) as cumulative_total   
                from quantitative_analysis as qa join ".$tableName." as f 
                WHERE qa.as_name = f.$aiValue and f.$ptValue = qa.preferred_term and qa.as_id = $productId "
                . " AND qa.tracker_id = $trackerId "
                . " and $filterArr"
                . " GROUP BY qa.preferred_term, qa.soc_name";

        $statement2 = $connection->execute($sQuery)->getResource();
        $aResult = $statement2->fetchAll(\PDO::FETCH_ASSOC);
        $statement2->closeCursor();

        $csv_output = '';
      
        $csv_output .= 'Report Name : Quantitative Analysis';
        $csv_output .="\r\n";
        $csv_output .= 'Active Ingredient : '.$productName;
        $csv_output .="\r\n";
        $csv_output .= 'Date of Generation : '.date("d-M-Y");
        $csv_output .="\r\n";
        $csv_output .= 'Author of Generation : '.$uname;
        $csv_output .="\r\n";
            
        foreach ($hArray as $key=>$row) {
            $csv_output .= "$row" . ",";
        }
        
        $csv_output .="\r\n";

        foreach ($aResult as $res) {
            foreach ($hArray as $key => $row) {
                  $csv_output .=  '"'.$res[$key].'",'; 
            }
            $csv_output .="\r\n";
        }
            
        $fileName = "Quantitative_Analysis_".$productName."_".date("d-M-Y").".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        print $csv_output;
        die;
    }
    public function downloadPDF($productId, $trackerId, $formId, $configValue, $uname, $dateRange, $filterArr, $condition = '')
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');        
        $connection = $this->dbConnection();
        
        $query = "SELECT as_name AS product_name FROM active_substance WHERE as_id = $productId";
        $statement = $connection->execute($query)->getResource();
        $product = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (empty($product)) {
            $productName = "";
        } else {
            $productName = $product[0]['product_name'];
        }
        
        $tableName = 'form_'.$trackerId.'_'.$formId;
        $aiValue = 'active_ingredient';
        $ptValue = 'ptname';
        $socValue = 'socname';
        $ssValue = 'seriousnessevent';
        $asValue = 'as_name';
        $nvlValue = 'nvl';
        $dateValue = 'initialreceiptdate';
        $idValue = 'id';
        if (count($configValue) > 0) {
            $socValue = isset($configValue[0]['socname']) ? $configValue[0]['socname'] : 'socname';
            $ptValue = isset($configValue[0]['ptname']) ? $configValue[0]['ptname'] : 'ptname';
            $ssValue = isset($configValue[0]['seriousnessevent']) ? $configValue[0]['seriousnessevent'] : 'seriousnessevent';
            $asValue = isset($configValue[0]['as_name']) ? $configValue[0]['as_name'] : 'as_name';
            $aiValue = isset($configValue[0]['active_ingredient']) ? $configValue[0]['active_ingredient'] : 'active_ingredient';
            $nvlValue = isset($configValue[0]['nvl']) ? $configValue[0]['nvl'] : 'nvl';
            $dateValue = isset($configValue[0]['datefield']) ? $configValue[0]['datefield'] : 'initialreceiptdate';
            $idValue = isset($configValue[0]['countfield']) ? $configValue[0]['countfield'] : 'id';
        }
        $hArray = array(
                    "soc_name"=> "SOC Name",
                    "preferred_term" => "Preferred Term" ,
                    "mc_name" => "Medical Concept" ,
                    "medical_evaluation" =>"Medical Evaluation",  
                    "priority" =>"Priority", 
                    "rationale" => "Rationale",            
                    "current_serious_frequency" =>"Serious", 
                    "current_nonserious_frequency" =>"Not Serious",
                    "current_total" =>"Total",
                    "cumulative_serious_frequency" =>"Serious",
                    "cumulative_non_serious_frequency" =>"Not Serious",
                    "cumulative_total" =>"Total", 
                    "ror_all" =>"ROR (-) All"
                );

        $sQuery = "SELECT qa.pt_id, qa.soc_name, qa.preferred_term, qa.mc_name, qa.medical_evaluation, IFNULL(qa.priority, '-') as priority, qa.ror_all, qa.rationale,
                count(case when(seriousnessevent = 'serious' and $filterArr) then 1 end) as current_serious_frequency,
                count(case when(seriousnessevent = 'not serious' and $filterArr) then 1 end) as current_nonserious_frequency,
                count(case when($filterArr) then 1 end) as current_total,
                (select count(seriousnessevent) from $tableName where seriousnessevent = 'serious' and ptname = qa.preferred_term and active_ingredient = qa.as_name) as cumulative_serious_frequency, 
                (select count(seriousnessevent) from $tableName where seriousnessevent = 'not serious' and ptname = qa.preferred_term and active_ingredient = qa.as_name) as cumulative_non_serious_frequency, 
                (select count(seriousnessevent) from $tableName where ptname = qa.preferred_term and active_ingredient = qa.as_name) as cumulative_total  
                from quantitative_analysis as qa join ".$tableName." as f 
                WHERE qa.as_name = f.$aiValue and f.$ptValue = qa.preferred_term and qa.as_id = $productId "
                . " AND qa.tracker_id = $trackerId "
                . " and $filterArr "
                . " GROUP BY qa.preferred_term, qa.soc_name";
        
        $statement2 = $connection->execute($sQuery)->getResource();
        $aResult = $statement2->fetchAll(\PDO::FETCH_ASSOC);
        $statement2->closeCursor();
            
        $fileName = "Quantitative_Analysis_".$productName."_".date("d-M-Y").".pdf";
            
        $html = "<html>
                <head>
                <meta charset='utf-8' />
                <style>
                    body {font-family: sans-serif;}
                    a {color: #000066;text-decoration: none;}
                    table {border-collapse: collapse;}
                    thead {vertical-align: bottom;text-align: center;font-weight: bold;}
                    tfoot {text-align: center;font-weight: bold;}
                    th {text-align: left;padding-left: 0.35em;padding-right: 0.35em;padding-top: 0.35em;padding-bottom: 0.35em;vertical-align: top;}
                    td {padding-left: 0.35em;padding-right: 0.35em;padding-top: 0.35em;padding-bottom: 0.35em;vertical-align: top;}
                    img {margin: 0.2em;vertical-align: middle;}
                    div { padding: 1em; }
                    .level1 { box-decoration-break: slice; }
                    .level2 { box-decoration-break: clone; }
                    .level3 { box-decoration-break: clone; }
                </style>
                </head><body>
                <table>
                <tr><td><b>Report Name</b></td><td>&nbsp;</td><td>Quantitative Analysis</td></tr>
                <tr><td><b>Active Ingredient</b></td><td>&nbsp;</td><td>".$productName."</td></tr>
                <tr><td><b>Date of Generation</b></td><td>&nbsp;</td><td>".date("d-M-Y")."</td></tr>
                <tr><td><b>Author of Generation</b></td><td>&nbsp;</td><td>".$uname."</td></tr>
                </table>
            ";
        $html .= "<table width='100%' cellspacing='0' cellpadding='5' border='1' style='border-collapse: collapse; border-spacing: 0;'>                
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th colspan='3'>Selected frequency(".$dateRange.")</th>
                        <th colspan='3'>Cumulative frequency</th>
                    </tr>
                    <tr>";
        foreach ($hArray as $key=>$row) {
            $html .= "<th>".$row."</th>";
        }
            $html .= "</tr>";
            
        foreach ($aResult as $res) {
            $html .= "<tr>";
            foreach ($hArray as $key => $row) {
                  $html .= "<td>".$res[$key]."</td>"; 
            }
                $html .= "</tr>";
        }
            $html .= "</table></body>";
            ob_clean();
            $mpdf = new \mPDF('', 'A4', '', '', 15, 15, 30, 30, '', '', '');
            $mpdf->debug = true;
            $stylesheet = file_get_contents('./public/pdf.css');
            $mpdf->WriteHTML($stylesheet, 1);
            $mpdf->defaultheaderline=1;
            $mpdf->SetHeader("Quantitative Analysis Report");
            $mpdf->defaultfooterline = 0;
            $mpdf->SetFooter('Page : {PAGENO}');
            $mpdf->WriteHTML($html);
            $mpdf->Output($fileName, "D");
            exit;
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
    
    public function updateMedicalEvaluation($ptId, $value, $reason)
    {
        $reason = date("Y-m-d H:i:s") . " : " . $reason;
        $query = "UPDATE quantitative_analysis SET medical_evaluation = '$value', rationale = CONCAT('$reason', '#', rationale) WHERE pt_id = ".$ptId;
        $connection = $this->dbConnection();
        $statement = $connection->execute($query)->getResource();    
        $result = $statement->rowCount();
        $statement->closeCursor();  
        
        return $result;
    }
    
    public function updatePriority($ptId, $value, $reason)
    {
        $reason = date("Y-m-d H:i:s") . " : " . $reason;
        $query = "UPDATE quantitative_analysis SET priority = '$value', rationale = CONCAT('$reason', '#', rationale) WHERE pt_id = ".$ptId;
        $connection = $this->dbConnection();
        $statement = $connection->execute($query)->getResource();    
        $result = $statement->rowCount();
        $statement->closeCursor();  
        
        return $result;
    }    
    
    public function getActiveIngradientNameById($activeIngradientId)
    {
        $query = "SELECT as_name 
                  FROM active_substance WHERE as_id =  $activeIngradientId";
        $connection = $this->dbConnection();
        $statement = $connection->execute($query)->getResource();
        $substanceArr = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor(); 
        
        if (count($substanceArr) > 0) {
            return $substanceArr[0]['as_name'];
        }
        
        return '';
    }      
    
    public function getDashboardById($dashboardId)
    {
         
        $connection = $this->dbConnection();
        $query = "SELECT dashboard_name,label,countQuery,listQuery,listQueryLabels,`where`,groupBy,orderBy,qualitative_query_count,formId,filters FROM  dashboard where archived = 0 AND dashboard_id= ".$dashboardId;
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
