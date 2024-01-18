<?php
/************************
 * Created By : SivaKrishna Yammana
 */
namespace Common\Report\Model;

require './vendor/Classes/PHPExcel.php';
require './library/mpdf60/mpdf.php';

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
class Report extends AbstractActionController
{

    public $report_id;
    public $form_id;
    public $report_query;

    public $last_run_time;
    public $report_name;
    public $report_type;

    public function exchangeArray($data)
    {

        $this->report_id = (!empty($data['report_id'])) ? $data['report_id']:null;
        $this->form_id = (!empty($data['form_id'])) ? $data['form_id']:null;
        $this->report_query = (!empty($data['report_query'])) ? $data['report_query']:null;

        $this->last_run_time = (!empty($data['last_run_time'])) ? $data['last_run_time']:null;
        $this->report_name = (!empty($data['report_name'])) ? $data['report_name']:null;
        $this->report_type = (!empty($data['report_type'])) ? $data['report_type']:null;

    }
    public function dbConnection()
    {
         return $this->getServiceLocator()->get('db')->getDriver()->getConnection();
    }
    public function getHeaders($id)
    {
        $connection = $this->dbConnection();
        $query = 'SELECT * FROM report WHERE report_id = '.$id;
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        $hResult = array(); 
        if (!empty($result)) {
            $rep_header = $result[0]['report_header'];
            $statement1 = $connection->execute($rep_header)->getResource();
            $hResult = $statement1->fetchAll(\PDO::FETCH_NUM);
            $statement1->closeCursor();
        }
        //echo "<pre>"; print_r($hResult); die;
        if (!empty($hResult)) {
            return $hResult[0]; 
        }
    }
    public function getLists($trackerId, $formId, $id, $product_ids, $sDate, $eDate, $prefferedterms, /*$form_type,*/ $get)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $connection = $this->dbConnection();
        $query = 'SELECT * FROM report WHERE report_id = '.$id;
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
         
        if (!empty($result)) {
            $rep_query = $result[0]['report_query'];
            $rep_id = $result[0]['report_id'];
            $rep_group = $result[0]['query_group_by'];
            $rep_having = $result[0]['query_having'];
            $rep_order = $result[0]['query_order_by'];
            $sWhere = $result[0]['query_where'];
            $rep_type = $result[0]['report_type'];
            $rep_filter = $result[0]['custom_filter'];
            $rep_name = $result[0]['report_name'];
            $rep_user = $result[0]['custom_user'];
            $rep_columns = $result[0]['report_columns'];
            
            $query = $sLimit = $sOrder = $product = $prefferedTrms = $dec_if_pot_signal = "";
            
            $customWhere = " AND date(".$rep_filter.")";
            
            $aColumns = array();
            
            $statement = $connection->execute($rep_columns)->getResource();
            $data = $statement->fetchAll(\PDO::FETCH_CLASS);
            if (isset($data[0])) {
                $aColumns = array_keys((array)$data[0]);
            }
            
            
            /* 
             * Paging
             */
            if (isset($get['iDisplayStart']) && $get['iDisplayLength'] != '-1' ) {
                    $sLimit = "LIMIT ".$get['iDisplayStart'].",".$get['iDisplayLength'];
            }
            
            /*
            * Ordering
            */
            if ($rep_order != '') {
                $sOrder = $rep_order;  
            }
            if (isset($get['iSortCol_0']) ) {
                if ($sOrder == '') { 
                    $sOrder = " ORDER BY  ";
                }
                   //$sOrder = "ORDER BY  ";
                for ( $i=0 ; $i<intval($get['iSortingCols']); $i++ ) {
                    if ($get[ 'bSortable_'.intval($get['iSortCol_'.$i]) ] == "true" ) {
                           $sOrder .= $aColumns[ intval($get['iSortCol_'.$i]) ]."
                                           ".$get['sSortDir_'.$i].", ";
                    }
                }

                   $sOrder = substr_replace($sOrder, "", -2);
                if ($sOrder == "ORDER BY" ) {
                        $sOrder = "";
                }
            }


            /* 
            * Filtering
            * NOTE this does not match the built-in DataTables filtering which does it
            * word by word on any field. It's possible to do here, but concerned about efficiency
            * on very large tables, and MySQL's regex functionality is very limited
            */
            //$sWhere = "";
            if ($get['sSearch'] != "" ) {
                   $sWhere .= " AND (";
                for ( $i=0 ; $i<count($aColumns); $i++ ) {
                        $sWhere .= $aColumns[$i]." LIKE '%".$get['sSearch']."%' OR ";
                }
                   $sWhere = substr_replace($sWhere, "", -3);
                   $sWhere .= ')';
            }

            /* Individual column filtering */
            for ( $i=0 ; $i<count($aColumns); $i++ ) {
                if ($get['bSearchable_'.$i] == "true" && $get['sSearch_'.$i] != '' ) {
                    if ($sWhere == "" ) {
                           $sWhere = "WHERE ";
                    } else {
                           $sWhere .= " AND ";
                    }
                        $sWhere .= $aColumns[$i]." LIKE '%".$get['sSearch_'.$i]."%' ";
                }
            }
            
            if ($sDate != '' && $eDate !='') {
                $position = strpos($rep_filter, ",");
                if ($position != false) {
                     $filtr = '';
                     $filterArray = explode(",", $rep_filter);
                    foreach ($filterArray as $filter) {
                        if ($filtr == '') {
                            $filtr = $filtr." AND( DATE_FORMAT($filter,'%Y-%m-%d') BETWEEN '$sDate' AND '$eDate' "; 
                        } else {
                            $filtr = $filtr." OR DATE_FORMAT($filter,'%Y-%m-%d') BETWEEN '$sDate' AND '$eDate' ";
                        }
                    }
                     $filtr = $filtr.') ';
                    if ($rep_id == 8) {
                         $filtr.= ' OR LOWER(final_outcome)="ongoing"';
                    } 
                        
                } else {
                    if ($rep_filter != 'aerrecvdate') {
                        $filtr = " AND DATE_FORMAT($rep_filter,'%Y-%m-%d') BETWEEN "."'$sDate' AND '$eDate' ";  
                    } else {
                        $filtr = " AND str_to_date($rep_filter,'%d-%b-%Y') BETWEEN "."'$sDate' AND '$eDate' ";   
                    } 
                }
            } else {
                $filtr = "";  
            }
            $query = $rep_query." ".$sWhere." ".$filtr." ".$rep_group;
            if ($product_ids != '') {
                $product = " AND product_id IN(".trim($product_ids).") ";
            }
            
            if ($prefferedterms != '') {
                $pTerm = "SELECT fd.field_name as ptname FROM field fd 
			   LEFT JOIN workflow w ON w.workflow_id = fd.workflow_id
                           LEFT JOIN form f ON f.form_id = w.form_id
                           WHERE f.form_id = $formId AND fd.field_id IN 
                (SELECT preferred_term FROM quantitative_setting WHERE form_id = $formId)";
                
                $statement1 = $connection->execute($pTerm)->getResource();
                $ptResult = $statement1->fetchAll(\PDO::FETCH_ASSOC);
                $statement1->closeCursor();
                if (!empty($ptResult)) {
                    $prefferedTrms = " AND ".$ptResult[0]['ptname']." IN('".$prefferedterms."') "; 
                }   
            }
            if ($get['decision_if_potential_signal'] != '') {
                $dec_if_pot_signal = " AND decision_if_potential_signal = '".$get['decision_if_potential_signal']."'"; 
            }
            /*
             * SQL queries
             * Get data to display
             */
            $sQuery = "SELECT SQL_CALC_FOUND_ROWS ".str_replace(" , ", " ", implode(", ", $aColumns))."
		FROM($query $product $prefferedTrms $dec_if_pot_signal) as T  $sOrder $sLimit";
            //echo $sQuery; die;
            $statement1 = $connection->execute($sQuery)->getResource();
            $rResult = $statement1->fetchAll(\PDO::FETCH_NUM);
            $statement1->closeCursor();

            /* Data set length after filtering */
            $sQuery1 = "SELECT FOUND_ROWS() as rows";

            $statement2 = $connection->execute($sQuery1)->getResource();
            $aResultFilterTotal = $statement2->fetchAll(\PDO::FETCH_ASSOC);
            $statement2->closeCursor();

            $iFilteredTotal = $aResultFilterTotal[0]['rows'];

            /* Total data set length */
            $queryWhere = $result[0]['query_where'];
            $sQuery2 = "SELECT COUNT(*) as rows FROM ($rep_query $queryWhere $filtr $product $prefferedTrms $dec_if_pot_signal) as T";
            //print_r($sQuery2); die;
            $statement3 = $connection->execute($sQuery2)->getResource();
            $aResultTotal = $statement3->fetchAll(\PDO::FETCH_ASSOC);
            $statement3->closeCursor();

            $iTotal = $aResultTotal[0]['rows'];
            $output = array(
                    "sEcho" => intval($get['sEcho']),
                    "iTotalRecords" => $iTotal,
                    "iTotalDisplayRecords" => $iFilteredTotal,
                    "aaData" => $rResult
            );
            return $output;
        }
       
    }
    public function downloadCSV($trackerId, $formId, $id, $productIds, $sDate, $eDate, $pts, $decision_if_potential_signal)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $objPHPExcel = new \PHPExcel();
        $connection = $this->dbConnection();
        $query = 'SELECT * FROM report WHERE report_id = '.$id;
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (!empty($result)) {
            $rep_query = $result[0]['report_query'];
            $rep_id = $result[0]['report_id'];
            $rep_group = $result[0]['query_group_by'];
            $rep_having = $result[0]['query_having'];
            $rep_order = $result[0]['query_order_by'];
            $sWhere = $result[0]['query_where'];
            $rep_type = $result[0]['report_type'];
            $rep_filter = $result[0]['custom_filter'];
            $rep_name = $result[0]['report_name'];
            $rep_user = $result[0]['custom_user'];
            $rep_header = $result[0]['report_header'];
            
            $statement1 = $connection->execute($rep_header)->getResource();
            $hResult = $statement1->fetchAll(\PDO::FETCH_NUM);
            $statement1->closeCursor();
            $csv_output = '';
            foreach ($hResult[0] as $key=>$row) {
                $csv_output .= "$row" . ",";
            }
            $csv_output .="\r\n";

            if ($sDate != '' && $eDate !='') {
                $position = strpos($rep_filter, ",");
                if ($position != false) {
                     $filtr = '';
                     $filterArray = explode(",", $rep_filter);
                    foreach ($filterArray as $filter) {
                        if ($filtr == '') {
                            $filtr = $filtr." AND( DATE_FORMAT($filter,'%Y-%m-%d') BETWEEN '$sDate' AND '$eDate' "; 
                        } else {
                             $filtr = $filtr." OR DATE_FORMAT($filter,'%Y-%m-%d') BETWEEN '$sDate' AND '$eDate' ";
                        }
                    }
                     $filtr = $filtr.')';
                    if ($rep_id == 8) {
                         $filtr.= ' OR LOWER(final_outcome)="ongoing"';
                    } 
                        
                } else {
                    if ($rep_filter != 'aerrecvdate') {
                        $filtr = " AND DATE_FORMAT($rep_filter,'%Y-%m-%d') BETWEEN "."'$sDate' AND '$eDate' ";  
                    } else {
                        $filtr = " AND str_to_date($rep_filter,'%d-%b-%Y') BETWEEN "."'$sDate' AND '$eDate' ";   
                    }
                }
            } else {
                $filtr = "";  
            }
            $query = $rep_query." ".$sWhere." ".$filtr." ".$rep_group;
            
            if ($productIds != '') {
                $product = " AND product_id IN(".trim($productIds).") ";
            }
            
            if ($pts != '') {
                $pTerm = "SELECT fd.field_name as ptname FROM field fd 
			   LEFT JOIN workflow w ON w.workflow_id = fd.workflow_id
                           LEFT JOIN form f ON f.form_id = w.form_id
                           WHERE f.form_id = $formId AND fd.field_id IN 
                (SELECT preferred_term FROM quantitative_setting WHERE form_id = $formId)";
                
                $statement3 = $connection->execute($pTerm)->getResource();
                $ptResult = $statement3->fetchAll(\PDO::FETCH_ASSOC);
                $statement3->closeCursor();
                if (!empty($ptResult)) {
                    $prefferedTrms = " AND ".$ptResult[0]['ptname']." IN('".$pts."') "; 
                }   
            }
            if ($decision_if_potential_signal != '') {
                $dec_if_pot_signal = " AND decision_if_potential_signal = '".$decision_if_potential_signal."'"; 
            } else {
                $dec_if_pot_signal = '';
            }
            
            $sQuery2 = "$query $product $prefferedTrms $dec_if_pot_signal";
            $statement4 = $connection->execute($sQuery2)->getResource();
            $aResult = $statement4->fetchAll(\PDO::FETCH_NUM);
            $statement4->closeCursor();

            foreach ($aResult as $res) {
                for ($j=0; count($res) > $j; $j++) {
                    $csv_output .=  '"'.$res[$j].'",'; 
                }
                $csv_output .="\r\n";
            }
            
            $fileName = "SigTRACE_Report_".$rep_name."_From_".str_replace('/', '-', $sDate)."_to_".str_replace('/', '-', $eDate).".csv";
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="'.$fileName.'"');
            print $csv_output;
            die;
        }
    }
    public function downloadExcel($trackerId, $formId, $id, $productIds, $sDate, $eDate, $pts, $decision_if_potential_signal)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $objPHPExcel = new \PHPExcel();
        $connection = $this->dbConnection();
        $query = 'SELECT * FROM report WHERE report_id = '.$id;
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        $productNames = '';
        if (!empty($result)) {
            $rep_query = $result[0]['report_query'];
            $rep_id = $result[0]['report_id'];
            $rep_group = $result[0]['query_group_by'];
            $rep_having = $result[0]['query_having'];
            $rep_order = $result[0]['query_order_by'];
            $sWhere = $result[0]['query_where'];
            $rep_type = $result[0]['report_type'];
            $rep_filter = $result[0]['custom_filter'];
            $rep_name = $result[0]['report_name'];
            $rep_user = $result[0]['custom_user'];
            $rep_header = $result[0]['report_header'];
            
            $statement1 = $connection->execute($rep_header)->getResource();
            $hResult = $statement1->fetchAll(\PDO::FETCH_ASSOC);
            $statement1->closeCursor();
            
            $statement2 = $connection->execute($rep_query)->getResource();
            $data = $statement2->fetchAll(\PDO::FETCH_CLASS);
            $statement2->closeCursor();
            if (isset($data[0])) {
                $aColumns = array_keys((array)$data[0]);
            }
            
            
            $header = array(); $i = 0;
            foreach ($hResult[0] as $key=>$row) {
                $header[$aColumns[$i]] = $row;
                $i++;
            }
            $hArray = array(0=>$header);

            if ($sDate != '' && $eDate !='') {
                $position = strpos($rep_filter, ",");
                if ($position != false) {
                     $filtr = '';
                     $filterArray = explode(",", $rep_filter);
                    foreach ($filterArray as $filter) {
                        if ($filtr == '') {
                            $filtr = $filtr." AND( DATE_FORMAT($filter,'%Y-%m-%d') BETWEEN '$sDate' AND '$eDate' "; 
                        } else {
                             $filtr = $filtr." OR DATE_FORMAT($filter,'%Y-%m-%d') BETWEEN '$sDate' AND '$eDate' ";
                        }
                    }
                     $filtr = $filtr.')';
                    if ($rep_id == 8) {
                         $filtr.= ' OR LOWER(final_outcome)="ongoing"';
                    } 
                        
                } else {
                    if ($rep_filter != 'aerrecvdate') {
                        $filtr = " AND DATE_FORMAT($rep_filter,'%Y-%m-%d') BETWEEN "."'$sDate' AND '$eDate' ";  
                    } else {
                        $filtr = " AND str_to_date($rep_filter,'%d-%b-%Y') BETWEEN "."'$sDate' AND '$eDate' ";   
                    }  
                }
            } else {
                $filtr = "";  
            }
            $query = $rep_query." ".$sWhere." ".$filtr." ".$rep_group;
            
            if ($productIds != '') {
                $product = " AND product_id IN(".trim($productIds).") ";
            }
            
            if ($pts != '') {
                $pTerm = "SELECT fd.field_name as ptname FROM field fd 
			   LEFT JOIN workflow w ON w.workflow_id = fd.workflow_id
                           LEFT JOIN form f ON f.form_id = w.form_id
                           WHERE f.form_id = $formId AND fd.field_id IN 
                (SELECT preferred_term FROM quantitative_setting WHERE form_id = $formId)";
                
                $statement3 = $connection->execute($pTerm)->getResource();
                $ptResult = $statement3->fetchAll(\PDO::FETCH_ASSOC);
                $statement3->closeCursor();
                if (!empty($ptResult)) {
                    $prefferedTrms = " AND ".$ptResult[0]['ptname']." IN('".$pts."') "; 
                }   
            }
            if ($decision_if_potential_signal != '') {
                $dec_if_pot_signal = " AND decision_if_potential_signal = '".$decision_if_potential_signal."'"; 
            } else {
                $dec_if_pot_signal = '';
            }
            $sQuery2 = "$query $product $prefferedTrms $dec_if_pot_signal";
            $statement4 = $connection->execute($sQuery2)->getResource();
            $aResult = $statement4->fetchAll(\PDO::FETCH_ASSOC);
            $statement4->closeCursor();
            
            $excelData = array_merge($hArray, $aResult);
        
            // Set properties
            $objPHPExcel->getProperties()->setCreator("SigTRACE");        
            $objPHPExcel->getProperties()->setLastModifiedBy("SigTRACE");
            $objPHPExcel->getProperties()->setTitle("$rep_name Report");
            $objPHPExcel->getProperties()->setSubject("SigTRACE Report");
            $objPHPExcel->getProperties()->setDescription("SigTRACE Report");
            if ($productIds != '') {
                $productNamesQry = " SELECT GROUP_CONCAT(product_name) as product_name from product WHERE product_id IN(".trim($productIds).")";
                $statement5 = $connection->execute($productNamesQry)->getResource();
                $pResult = $statement5->fetchAll(\PDO::FETCH_ASSOC);
                $statement5->closeCursor();
                $productNames = $pResult[0]['product_name'];
            }
        
            $objPHPExcel->setActiveSheetIndex(0);        

            $objPHPExcel->getActiveSheet()->getStyle("B2")->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->SetCellValue('B2', 'Company : Pharma Comapy');
        
            $objPHPExcel->getActiveSheet()->getStyle("B3")->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->SetCellValue('B3', 'Report Name : '.$rep_name);
        
            if ($productNames != '') {
                $objPHPExcel->getActiveSheet()->getStyle("B4")->getFont()->setBold(true);
                $objPHPExcel->getActiveSheet()->SetCellValue('B4', "Products : $productNames"); 
            }
            if ($pts != '') {
                $objPHPExcel->getActiveSheet()->getStyle("B5")->getFont()->setBold(true);
                $objPHPExcel->getActiveSheet()->SetCellValue('B5', "Preferred Terms : ".str_replace("'", "", $pts));
            }
        
            $objPHPExcel->getActiveSheet()->getStyle("B6")->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->SetCellValue('B6', "Date From : $sDate to $eDate");
        
            $objPHPExcel->getActiveSheet()->getStyle("B7")->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->SetCellValue('B7', "Date of Generation : ".date('d-m-Y H:i:s'));
        
            $container = new Container('login');
            $userDetails = $container->user_details;
        
            $objPHPExcel->getActiveSheet()->getStyle("B8")->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->SetCellValue('B8', "Author of Generation : ".$userDetails['u_name']);

            $objPHPExcel->getActiveSheet()->getStyle("10:10")->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->fromArray($excelData, null, 'A10');
            $objPHPExcel->getActiveSheet()->setTitle(substr("$rep_name", 0, 30));
        
            // required for IE
            if (ini_get('zlib.output_compression')) { 
                ini_set('zlib.output_compression', 'Off');
            }

            $fileName  = "SigTRACE_Report_".$rep_name."_".str_replace('/', '-', $sDate)."_to_".str_replace('/', '-', $eDate).".xls";
    
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
    }
    public function downloadPDF($trackerId, $formId, $id, $productIds, $sDate, $eDate, $pts, $decision_if_potential_signal)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');
                       
        $connection = $this->dbConnection();
        $query = 'SELECT * FROM report WHERE report_id = '.$id;
        $statement = $connection->execute($query)->getResource();
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        if (!empty($result)) {
            $rep_query = $result[0]['report_query'];
            $rep_id = $result[0]['report_id'];
            $rep_group = $result[0]['query_group_by'];
            $rep_having = $result[0]['query_having'];
            $rep_order = $result[0]['query_order_by'];
            $sWhere = $result[0]['query_where'];
            $rep_type = $result[0]['report_type'];
            $rep_filter = $result[0]['custom_filter'];
            $rep_name = $result[0]['report_name'];
            $rep_user = $result[0]['custom_user'];
            $rep_header = $result[0]['report_header'];
            
            $statement1 = $connection->execute($rep_header)->getResource();
            $hResult = $statement1->fetchAll(\PDO::FETCH_ASSOC);
            $statement1->closeCursor();

            if ($sDate != '' && $eDate !='') {
                $position = strpos($rep_filter, ",");
                if ($position != false) {
                     $filtr = '';
                     $filterArray = explode(",", $rep_filter);
                    foreach ($filterArray as $filter) {
                        if ($filtr == '') {
                            $filtr = $filtr." AND( DATE_FORMAT($filter,'%Y-%m-%d') BETWEEN '$sDate' AND '$eDate' "; 
                        } else {
                             $filtr = $filtr." OR DATE_FORMAT($filter,'%Y-%m-%d') BETWEEN '$sDate' AND '$eDate' ";
                        }
                    }
                     $filtr = $filtr.')';
                    if ($rep_id == 8) {
                         $filtr.= ' OR LOWER(final_outcome)="ongoing"';
                    } 
                        
                } else {
                    if ($rep_filter != 'aerrecvdate') {
                        $filtr = " AND DATE_FORMAT($rep_filter,'%Y-%m-%d') BETWEEN "."'$sDate' AND '$eDate' ";  
                    } else {
                        $filtr = " AND str_to_date($rep_filter,'%d-%b-%Y') BETWEEN "."'$sDate' AND '$eDate' ";   
                    }  
                }
            } else {
                $filtr = "";  
            }
            $query = $rep_query." ".$sWhere." ".$filtr." ".$rep_group;
            
            if ($productIds != '') {
                $product = " AND product_id IN(".trim($productIds).") ";
            }
            
            if ($pts != '') {
                $pTerm = "SELECT fd.field_name as ptname FROM field fd 
			   LEFT JOIN workflow w ON w.workflow_id = fd.workflow_id
                           LEFT JOIN form f ON f.form_id = w.form_id
                           WHERE f.form_id = $formId AND fd.field_id IN 
                (SELECT preferred_term FROM quantitative_setting WHERE form_id = $formId)";
                
                $statement3 = $connection->execute($pTerm)->getResource();
                $ptResult = $statement3->fetchAll(\PDO::FETCH_ASSOC);
                $statement3->closeCursor();
                if (!empty($ptResult)) {
                    $prefferedTrms = " AND ".$ptResult[0]['ptname']." IN('".$pts."') "; 
                }   
            }
            if ($decision_if_potential_signal != '') {
                $dec_if_pot_signal = " AND decision_if_potential_signal = '".$decision_if_potential_signal."'"; 
            } else {
                $dec_if_pot_signal = '';
            }
            $sQuery2 = "$query $product $prefferedTrms $dec_if_pot_signal";
            $statement4 = $connection->execute($sQuery2)->getResource();
            $aResult = $statement4->fetchAll(\PDO::FETCH_NUM);
            $statement4->closeCursor();
            
            $container = new Container('login');
            $userDetails = $container->user_details;
        
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
                    </head><body>";
            
            $html .= "<table class='level1' style='margin:0 auto;'>";
            
            $html .= "<tr><td><b>Company</b></td><td>&nbsp;</td><td>Pharma Comapy</td></tr>";
            $html .= "<tr><td><b>Report Name</b></td><td>&nbsp;</td><td>".$rep_name."</td></tr>";
            $productNames = '';
            if ($productIds != '') {
                $productNamesQry = " SELECT GROUP_CONCAT(product_name) as product_name from product WHERE product_id IN(".trim($productIds).")";
                $statement5 = $connection->execute($productNamesQry)->getResource();
                $pResult = $statement5->fetchAll(\PDO::FETCH_ASSOC);
                $statement5->closeCursor();
                $productNames = $pResult[0]['product_name'];
            }
            
            if ($productNames != '') {
                $html .= "<tr><td><b>Products</b></td><td>&nbsp;</td><td>".$productNames."</td></tr>"; 
            }
            if ($pts != '') {
                $html .= "<tr><td><b>Preferred Terms</b></td><td>&nbsp;</td><td>".str_replace("'", "", $pts)."</td></tr>";
            }
            $html .= "<tr><td><b>Date From</b></td><td>&nbsp;</td><td>$sDate to $eDate</td></tr>";
            $html .= "<tr><td><b>Date of Generation</b></td><td>&nbsp;</td><td>".date("d-m-Y H:i:s")."</td></tr>";
            $html .= "<tr><td><b>Author of Generation</b></td><td>&nbsp;</td><td>".$userDetails['u_name']."</td></tr>";
            
            $html .= "</table>";
            
            $html .= "<table></div>&nbsp;</div></table><pagebreak>";
            
            $html .= "<table border='1' class='level2'><tr>";
            foreach ($hResult[0] as $key=>$row) {
                $html .= "<th>".$row."</th>";
            }
            $html .= "</tr>";
            
            foreach ($aResult as $res) {
                $html .= "<tr>";
                for ($j=0; count($res) > $j; $j++) {
                    $html .= "<td>".$res[$j]."</td>"; 
                }
                $html .= "</tr>";
            }
            $html .= "</table></body>";
            ob_clean();
            $mpdf = new \mPDF('', 'A4', '', '', 15, 15, 30, 30, '', '', 'L');
            //$mpdf = new \mPDF('utf-8', 'A4-L');
            $mpdf->debug = true;
            $mpdf->text_input_as_HTML = true; 
            $stylesheet = file_get_contents('./public/pdf.css');
            $mpdf->WriteHTML($stylesheet, 1);
            $mpdf->defaultheaderline=1;
            $mpdf->SetHeader($rep_name);
            $mpdf->defaultfooterline = 0;
            $mpdf->SetFooter('Page : {PAGENO}');
            $mpdf->WriteHTML($html);
            $mpdf->Output("SigTRACE_Report_".$rep_name."_From_".str_replace('/', '-', $sDate)."_to_".str_replace('/', '-', $eDate).".pdf", "D");
            exit;
        }
    }
}
