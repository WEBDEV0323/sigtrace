<?php

namespace Common\Report\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Mvc\Controller\AbstractActionController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Session\Container\SessionContainer;

class Report extends AbstractActionController
{
    /**
     * Make the Adapter object available as local protected variable
     *
     * @param Adapter $adapter - DB PDO PgSQL conn
     */
    
    public function __construct(Adapter $adapter)
    {
        $this->_adapter = $adapter;
    }
    
    public function getReportAccessSettings($formId, $reportId, $roleId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('report_access_setting');
        $select->where(
            array('form_id' => $formId,
            'report_id' => $reportId,    
            'role_id' => $roleId, 
            'can_access' => 'Yes'
            )
        );
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $reportAccess = array();
        if ($count > 0) {
            $reportAccess = $resultSet->toArray();
        }
        return $reportAccess;
    }    
    public function getReportDownloadSettings($trackerId, $roleId, $config)
    {        
        $queryFormFields = "SELECT `action`.action_name 
        FROM controller
        JOIN action ON controller.controller_id = action.controller_id 
        JOIN role_permission ON action.action_id = role_permission.permission_id  
        WHERE controller.controller_name = '".$config[0]['report_controller']."' AND role_permission.role_id = $roleId AND role_permission.tracker_id = $trackerId";
        $statements = $this->_adapter->createStatement($queryFormFields);
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $array = array();
        if ($count > 0) {
            $array = $resultSet->toArray();
        }
        
        return $array;
    }
    public function getReportSettingsConfigs($formId) 
    {
        $socQuery = "SELECT config_value FROM config  
                     WHERE scope_level = 'Form' AND scope_id = $formId AND config_key = 'report_settings'";
        $statements = $this->_adapter->createStatement($socQuery);
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $array = array();
        $configValue = array();
        if ($count > 0) {
            $array = $resultSet->toArray();
            $configValue = json_decode($array[0]['config_value'], true);
        }
        
        return $configValue;
    }    
    public function getEditWorkflowAccessSettings($trackerId, $roleId, $config)
    {        
        $queryFormFields = "SELECT `action`.action_name 
        FROM controller
        JOIN action ON controller.controller_id = action.controller_id 
        JOIN role_permission ON action.action_id = role_permission.permission_id  
        WHERE controller.controller_name = '".$config[0]['workflow_controller']."' AND action.action_name = '" .$config[0]['editrecord_action']."' AND role_permission.role_id = $roleId AND role_permission.tracker_id = $trackerId";
        $statements = $this->_adapter->createStatement($queryFormFields);
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $array = array();
        if ($count > 0) {
            $array = $resultSet->toArray();
        }
        
        return $array;
    }    
    public function getViewWorkflowAccessSettings($trackerId, $roleId, $config)
    {        
        $queryFormFields = "SELECT `action`.action_name 
        FROM controller
        JOIN action ON controller.controller_id = action.controller_id 
        JOIN role_permission ON action.action_id = role_permission.permission_id  
        WHERE controller.controller_name = '".$config[0]['workflow_controller']."' AND action.action_name = '" .$config[0]['viewrecord_action']."' AND role_permission.role_id = $roleId AND role_permission.tracker_id = $trackerId";
        $statements = $this->_adapter->createStatement($queryFormFields);
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $array = array();
        if ($count > 0) {
            $array = $resultSet->toArray();
        }
        return $array;
    }    
    public function getReportDetails($formId, $reportId) 
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('report');
        $select->where(array('form_id'=>$formId, 'report_id'=>$reportId, 'archived'=>'No'));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $reportDetails = array();
        if ($count > 0) {
            $reportDetails = $resultSet->toArray()[0];
        }
        return $reportDetails;
    }
    public function getReportDataCount($countQuery)
    {
        $statements = $this->_adapter->createStatement($countQuery);
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultArray = $resultSet->toArray();
        $row = $resultArray[0];
        foreach ($row as $key => $val) {
            return $val;
        }
    }
    public function getReportData($formId, $reportId, $condition, $max_rows)
    {
        $arr = array();
        $arr['labels'] = $arr['data'] = $arr['names'] = array();
        $arr['report_type'] = '';
        $arr['max_count'] = 0;
        $count = 1;
        
        try {
            $report = $this->getReportDetails($formId, $reportId);  
            $query = '';
            if (!empty($report)) {    
                if (trim($report['count_query']) != '') {
                    $where = isset($report['report_where'])&& $report['report_where'] != '' ? " WHERE ". $report['report_where']:" WHERE 1";
                    $countQuery = $report['count_query'] . " " . $where . " " . $condition; 
                      
                    $count = $this->getReportDataCount($countQuery);
                }  
                if ($count < $max_rows) {
                    switch ($report['report_query_type']) {
                    case 'p':
                        $query = 'call '.$report['report_query'].'("'.$condition.'")';
                        break;
                    case 'q':
                    case 'qwl':
                        $report['report_where'] = isset($report['report_where'])&& $report['report_where'] != '' ? " WHERE ". $report['report_where']:" WHERE 1";
                        $query = "SELECT ".$report['report_columns']." FROM (".$report['report_query'].$report['report_where']." " . $condition.") T "." "." ".$report['report_group_by']." ".$report['report_order_by'];                    
                        break;
                    case 'pi':
                        $report['report_where'] = isset($report['report_where'])&& $report['report_where'] != '' ? " WHERE ". $report['report_where']:" WHERE 1";
                        $query = "SELECT ".$report['report_columns']." FROM (".$report['report_query'].$report['report_where']." " . $condition." ". $report['report_group_by'] . ") T ".$report['report_order_by'];
                        break;
                    default:
                        break;
                    }
                   
                    if ($query != '') {
                        $statements = $this->_adapter->createStatement($query);
                        $statements->prepare();
                        $results = $statements->execute();
                        $resultSet = new ResultSet;
                        $resultSet->initialize($results);
                        // $resultSet->buffer();
                        $reportsCount = $resultSet->count();
                        if ($reportsCount > 0) {
                            $arr['report_type'] = $report['report_query_type'];
                            $arr['data'] = $resultSet->toArray();
                            $arr['names'] = array_keys($arr['data'][0]);
                            $arr['labels'] = (isset($report['report_column_header']) && $report['report_column_header'] != '') ? $report['report_column_header'] : ucwords(strtolower(str_replace("_", " ", implode(", ", array_map('trim', $arr['names'])))));
                        } else {
                            $arr['labels'] = (isset($report['report_column_header']) && $report['report_column_header'] != '') ? $report['report_column_header'] : ucwords(strtolower(str_replace("_", " ", $report['report_columns']))); 
                        }
                    } else {
                        $arr['labels'] = (isset($report['report_column_header']) && $report['report_column_header'] != '') ? $report['report_column_header'] : ucwords(strtolower(str_replace("_", " ", $report['report_columns'])));
                    }  
                    $arr['max_count'] = 0;
                } else {
                    $arr['max_count'] = 1;
                    $arr['labels'] = (isset($report['report_column_header']) && $report['report_column_header'] != '') ? $report['report_column_header'] : ucwords(strtolower(str_replace("_", " ", $report['report_columns'])));
                }
            } 
        } catch(\Exception $e) {
            $arr['labels'] = $arr['data'] = $arr['names'] = array();
        } catch(\PDOException $e) {
            $arr['labels'] = $arr['data'] = $arr['names'] = array();
        }
        return $arr; 
    }
    public function downloadReport($trackerId, $formId, $reportId, $type, $condition, $headBreadcrumb, $max_rows, $filteredData, $urlquery)
    {
        $urlquery = base64_decode($urlquery);
        $urlquery = json_decode($urlquery, true);
        set_time_limit(0);
        ini_set('memory_limit', '-1');
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $userDetails = $userSession->user_details;
        if ($trackerId == 0 || $formId == 0 ) { 
            echo "Incorrect data for download";
        } else {
            if ($reportId != 0) {
                $data = $this->getReportData($formId, $reportId, $condition, $max_rows);
            } else {
                $data = $this->getnotsavedReportData($formId, $reportId, $condition, $max_rows, $urlquery);
            }
            $reportData = $this->getReportDetails($formId, $reportId);
            $reportName = isset($reportData['report_name'])? $reportData['report_name']:"Custom report";
            switch (strtolower($type)) {
            case 'csv':
                $content = "";
                foreach ($filteredData as $filter) {
                    $v = is_array($filter['value'])?implode(", ", $filter['value']):$filter['value'];
                    $content .= '"'.$filter['label'].' : '.$v.'"';
                    $content .= " \r\n";
                }
                $content .= " \r\n".isset($data['labels']) && !empty($data['labels'])?str_replace(array("\n    ","\n\r","\r\n","\r","\n","\\r","\\n","\\r\\n","\\n\\r","\s"), "", trim($data['labels'])):"";
                if ($data['max_count'] == 0) {
                    foreach ($data['data'] as $record) {
                        $i = 0;
                        $content .= " \r\n";
                        if (count($data['names']) !== count(explode(",", $data['labels'])) && in_array("id", array_values($data['names']))) {
                            $key = array_search('id', $data['names']);  
                            unset($data['names'][$key]);  
                        }
                        foreach ($data['names'] as $value) {
                            $content .= '"';
                            $regex = "/^(([0-9])|([0-2][0-9])|([3][0-1]))-(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-\d{4}$/";
                            if (preg_match($regex, $record[$value])) {
                                $content .= $record[$value];
                            } else {
                                $content .= $record[$value];
                            }
                            $content .= '"';
                            $i++;
                            $content .= (count($data['names']) !== $i)?',':'';
                        }
                    }                    
                } else {
                    $content .= " \r\n" . "Returned records greater than maximum rows to display. Please refine your filters to return less records";
                }
                $fileName = $reportName != ''? str_replace(" ", "_", $reportName).".csv":"report.csv";
                header('Content-Type: application/csv; charset=UTF-8');
                header('Content-Disposition: attachment; filename="'.$fileName.'"');
                echo "\xEF\xBB\xBF";
                echo $content;
                break;
            case 'excel':
                $header = (!empty($data['labels']))?array_map('trim', explode(",", $data['labels'])):array();
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheetTitle = $reportName;
                $sheetTitle .= ($headBreadcrumb != "") ? " for ".substr($headBreadcrumb, 4) : "";
                $spreadsheet->getActiveSheet()->mergeCellsByColumnAndRow(1, 1, sizeof($header), 1);
                $spreadsheet->getActiveSheet()->mergeCellsByColumnAndRow(1, 2, sizeof($header), 2);
                $aColumnLenth = array();

                //$spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(1, 1, $sheetTitle);

                $styleArrayHeader = [
                    'font' => [
                        'bold' => true,
                        'size' => 10,
                        'color' => [
                            'argb' => 'FFFFFF',
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'top' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                        'bottom' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                        'left' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => '007080',
                        ],
                        'endColor' => [
                            'argb' => '007080',
                        ],
                    ],
                ];

                $styleArrayDataLeft = [
                    'font' => [
                        'size' => 10,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'top' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                        'bottom' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                        'left' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ];

                $styleArrayDataCenter = [
                    'font' => [
                        'size' => 10,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'top' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                        'bottom' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                        'left' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                        'right' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ];
                $row = 3;
                $client = $this->getClientName($trackerId);
                $clientName = 'Company : '.$client['client_name'];
                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(1, $row, $clientName);
                    $row++;
                $report = 'Report Name : '.$reportName;
                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(1, $row, $report);
                    $row++;
                    $row++;
                foreach ($filteredData as $filter) {
                    $v = is_array($filter['value'])?implode(", ", $filter['value']):$filter['value'];
                    $v = !empty($v) ? $v : 'All';
                    $fltr = $filter['label'].' : '.$v;
                    $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(1, $row, $fltr);
                    $row++;
                }
                $row++;
                    date_default_timezone_set('Asia/Kolkata');
                    $time = 'Date of Generation : '.date('d-M-Y h:i:s A e');
                    $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(1, $row, $time);
                    $row++;
                    
                    $user = 'Author of Generation : '.$userDetails['email'];
                    $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(1, $row, $user);
                    $row++;
                
                $row++;
                $col = 1;
                foreach ($header as $value) {
                    $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $value);
                    $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($col, 1, null, null)->applyFromArray($styleArrayDataLeft);
                    $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($col, 2, null, null)->applyFromArray($styleArrayDataLeft);
                    $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($col, $row, null, null)->applyFromArray($styleArrayHeader);
                    $aColumnLenth[$col] = strlen($value)+1;
                    $col++;
                }

                $row++;
                if ($data['max_count'] == 0) {
                    foreach ($data['data'] as $record) {
                        $col = 1;
                        if (count($data['names']) !== count(explode(",", $data['labels'])) && in_array("id", array_values($data['names']))) {
                            $key = array_search('id', $data['names']);  
                            unset($data['names'][$key]);  
                        }
                        foreach ($data['names'] as $value) {
                            $regex = "/^(([0-9])|([0-2][0-9])|([3][0-1]))-(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)-\d{4}$/";
                            if (preg_match($regex, $record[$value])) {
                                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $record[$value]);
                                $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($col, $row, null, null)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMMYYYY);

                                $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($col, $row, null, null)->applyFromArray($styleArrayDataCenter);
                            } else {
                                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $record[$value]);
                                $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($col, $row, null, null)->applyFromArray($styleArrayDataLeft);
                            }

                            if (strlen($record[$value]) > 45) {
                                $aColumnLenth[$col] = 30;
                                $spreadsheet->getActiveSheet()->getColumnDimensionByColumn($col)->setAutoSize(false);
                                $spreadsheet->getActiveSheet()->getColumnDimensionByColumn($col)->setWidth(30);
                                $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($col, $row, null, null)->getAlignment()->setWrapText(true);
                            } else {
                                $aColumnLenth[$col] = strlen($record[$value]) > $aColumnLenth[$col] ? strlen($record[$value])+1 : $aColumnLenth[$col];
                                $spreadsheet->getActiveSheet()->getColumnDimensionByColumn($col)->setWidth($aColumnLenth[$col]);
                            }
                            $col++;
                        }
                        $row++;
                    }                            
                } else {
                    $col = 1;
                    $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col, $row, "Returned records greater than maximum rows to display. Please refine your filters to return less records");
                    $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($col, $row, null, null)->applyFromArray($styleArrayDataLeft);                    
                }
                $fileName = $reportName != ''? str_replace(" ", "_", $reportName):"report";
                $sSheetName = strlen($fileName) > 31 ? substr($fileName, 0, 31) : $fileName;
                $spreadsheet->getActiveSheet()->setTitle($sSheetName, false, false);
                $writer = new Xlsx($spreadsheet);
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment; filename=' . $fileName.".xlsx");
                header('Cache-Control: max-age=0');

                $writer->save('php://output');
                break;
            case 'pdf':
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
                $html .= "<tr><td><b>Report Name</b></td><td>&nbsp;</td><td>".$reportName."</td></tr>";
                $productNames = '';
                if ($productIds != '') {
                    $productNamesQry = " SELECT GROUP_CONCAT(product_name) as product_name from product WHERE product_id IN(".trim($productIds).")";
                    $statements = $this->_adapter->createStatement($productNamesQry);
                    $statements->prepare();
                    $results = $statements->execute();
                    $resultSet = new ResultSet;
                    $resultSet->initialize($results);
                    $resultSet->buffer();
                    $reportsCount = $resultSet->count();
                    $arr = array();
                    if ($reportsCount > 0) {
                        $arr = $resultSet->toArray()[0];
                    }
                    $productNames = isset($ar['product_name'])?$ar['product_name']:"";
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
                $header = (!empty($data['labels']))?array_map('trim', explode(",", $data['labels'])):array();
                foreach ($header as $key=>$row) {
                    $html .= "<th>".$row."</th>";
                }
                $html .= "</tr>";
                foreach ($data['data'] as $record) {
                    $html .= "<tr>";
                    foreach ($data['names'] as $value) {
                        $html .= "<td>".$record[$value]."</td>"; 
                    }
                    $html .= "</tr>";
                }
                $html .= "</table></body>";
                ob_clean();
                $mpdf = new \mPDF('', 'A4', '', '', 15, 15, 30, 30, '', '', 'L');
                $mpdf->debug = true;
                $mpdf->text_input_as_HTML = true; 
                $stylesheet = file_get_contents('./public/pdf.css');
                $mpdf->WriteHTML($stylesheet, 1);
                $mpdf->defaultheaderline=1;
                $mpdf->SetHeader($reportName);
                $mpdf->defaultfooterline = 0;
                $mpdf->SetFooter('Page : {PAGENO}');
                $mpdf->WriteHTML($html);
                $mpdf->Output("Report_".$reportName."_From_".str_replace('/', '-', $sDate)."_to_".str_replace('/', '-', $eDate).".pdf", "D");
                exit;
                    break;
            default:
                break;
                
            }
        }
    }
    
    public function getReportFilters($reportId) 
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('report');
        $select->columns(array('report_filters'));
        $select->where(array('report_id'=>$reportId, 'report_query_type'=>'vr'));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $reportDetails = array();
        if ($count > 0) {
            $reportDetails = $resultSet->toArray()[0];
        }
        return $reportDetails;
    }
    
    public function getClientName($trackerId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('client');
        $select->join('tracker', 'tracker.client_id=client.client_id', array('client_id'));
          $select->columns(array('client_name'));
        $select->where(array('tracker.tracker_id'=>$trackerId));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $reportDetails = array();
        if ($count > 0) {
            $reportDetails = $resultSet->toArray()[0];
        }
        return $reportDetails;
    }

    public function getWorkflowAndFields($trackerId, $formId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('field');
        $select->columns(array('field_id','field_name','label','workflow_id'));
        $select->join('workflow', 'workflow.workflow_id=field.workflow_id', array('workflow_name'));
        //$select->join('form', 'form.form_id=workflow.form_id');
        $select->where(array('workflow.form_id'=>$formId));
        //$select->where(array('form.tracker_id'=>$trackerId));

        $selectString = $sql->prepareStatementForSqlObject($select);
        
        $results = $selectString->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $fieldDetails = array();
        if ($count > 0) {
            $fieldDetails = $resultSet->toArray();
        }
        return $fieldDetails;
    }

    public function checkIfReportNameExist($reportname, $formid)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('report');
        $select->where(array('report_name'=>$reportname, 'form_id'=>$formid));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $reportDetails = array();
        if ($count > 0) {
            $reportDetails = $resultSet->toArray();
        }
        return $reportDetails;
    }

    public function saveNewCustomReport($formId,$reportName,$make_query,$select,$fieldHeading,$where)
    {   
        $sql = new Sql($this->_adapter);
        $insert = $sql->insert('report');
        $newData = array(
            'form_id' => $formId,
            'report_name' => $reportName,
            'report_query_type'=>'q',
            'report_query' => $make_query,
            'report_column_header' => $fieldHeading,
            'report_columns' => $select,
            'report_where' => $where,
        );
        $insert->values($newData);
        $statement = $sql->prepareStatementForSqlObject($insert);
        $results = $statement->execute($insert);
        $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();
        return $lastInsertID;
    }

    public function getReportQueriedData($query)
    {        
        $statements = $this->_adapter->createStatement($query);
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $array = array();
        if ($count > 0) {
            $array = $resultSet->toArray();
        }
        return $array;
    }
    public function getnotsavedReportData($formId, $reportId, $condition, $max_rows, $urlquery)
    {
        $arr = array();
        $arr['labels'] = $arr['data'] = $arr['names'] = array();
        $arr['report_type'] = '';
        $arr['max_count'] = 0;
        $count = 1;
        
        try {
            $report = $urlquery['query'];
            $query = '';
            if (!empty($report)) {    
                $count = 1;
                if ($count < $max_rows) {
                    $query = $urlquery['query'];
                    if ($query != '') {
                        $statements = $this->_adapter->createStatement($query);
                        $statements->prepare();
                        $results = $statements->execute();
                        $resultSet = new ResultSet;
                        $resultSet->initialize($results);
                        $reportsCount = $resultSet->count();
                        if ($reportsCount > 0) {
                            $arr['report_type'] = 'q';
                            $arr['data'] = $resultSet->toArray();
                            $arr['names'] = array_keys($arr['data'][0]);
                            $arr['labels'] = (isset($urlquery['names']) && $urlquery['names'] != '') ? $urlquery['names'] : ucwords(strtolower(str_replace("_", " ", implode(", ", array_map('trim', $arr['names'])))));
                        } else {
                            $arr['labels'] = (isset($urlquery['names']) && $urlquery['names'] != '') ? $urlquery['names'] : ucwords(strtolower(str_replace("_", " ", $urlquery['labels']))); 
                        }
                    } else {
                        $arr['labels'] = (isset($urlquery['names']) && $urlquery['names'] != '') ? $urlquery['names'] : ucwords(strtolower(str_replace("_", " ", $urlquery['labels'])));
                    }  
                    $arr['max_count'] = 0;
                } else {
                    $arr['max_count'] = 1;
                    $arr['labels'] = (isset($urlquery['names']) && $urlquery['names'] != '') ? $urlquery['names'] : ucwords(strtolower(str_replace("_", " ", $urlquery['labels'])));
                }
            }
        } catch(\Exception $e) {
            $arr['labels'] = $arr['data'] = $arr['names'] = array();
        } catch(\PDOException $e) {
            $arr['labels'] = $arr['data'] = $arr['names'] = array();
        }
        return $arr; 
    }    

}