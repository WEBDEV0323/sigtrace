<?php

namespace Reports\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Mvc\Controller\AbstractActionController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Reports extends AbstractActionController
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
    public function getReportDetails($formId, $reportId) 
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('aggregate_safety_reports');
        $select->where(array('form_id'=>$formId, 'report_id'=>$reportId, 'archived'=>'No'));
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
    public function getReportData($trackerId, $formId, $reportId, $condition)
    {
        $arr['labels'] = $arr['data'] = $arr['names'] = array();
        try {
            $reportData = $this->getReportDetails($formId, $reportId);       
            $query = '';
            if (!empty($reportData)) {
                foreach ($reportData as $report) {
                    $arr['labels'] = $report['report_column_header'];
                    switch ($report['report_query_type']) {
                    case 'p':
                        $query = 'call '.$report['report_query'].'("'.$condition.'")';
                        break;
                    case 'q':
                        $report['report_where'] = isset($report['report_where'])&& $report['report_where'] != '' ? " WHERE ". $report['report_where']:" WHERE 1";
                        $query = "SELECT * FROM (".$report['report_query'].") T ".$report['report_where']." ".$condition." ".$report['report_group_by']." ".$report['report_order_by'];
                        break;
                    default:
                        break;
                    }
                }
                
                if ($query != '') {
                    $statements = $this->_adapter->createStatement($query);
                    $statements->prepare();
                    $results = $statements->execute();
                    $resultSet = new ResultSet;
                    $resultSet->initialize($results);
                    $resultSet->buffer();
                    $reportsCount = $resultSet->count();
                    if ($reportsCount > 0) {
                        $arr['data'] = $resultSet->toArray();
                        $arr['names'] = array_keys($arr['data'][0]);
                    } 
                }
            } 
        } catch(\Exception $e) {
            $arr['labels'] = $arr['data'] = $arr['names'] = array();
        } catch(\PDOException $e) {
            $arr['labels'] = $arr['data'] = $arr['names'] = array();
        }
        return $arr; 
    }
    public function downloadCSV($trackerId, $formId, $reportId, $condition, $headBreadcrumb)
    {
        set_time_limit(0);
        $data = $this->getReportData($trackerId, $formId, $reportId, $condition);
        
        $reportData = $this->getReportDetails($formId, $reportId); 
        
        $content = isset($data['labels']) && !empty($data['labels'])?$data['labels']:"";
        foreach ($data['data'] as $record) {
            $i = 0;
            $content .= " \r\n";
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
        
        $fileName = isset($reportData[0]['report_name'])? str_replace(" ", "_", $reportData[0]['report_name']).".csv":"report.csv";
        header('Content-Type: application/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        echo "\xEF\xBB\xBF";
        echo $content;
    }
    public function downloadEXCEL($trackerId, $formId, $reportId, $condition, $headBreadcrumb)
    {
        set_time_limit(0);
        $data = $this->getReportData($trackerId, $formId, $reportId, $condition);
        $reportData = $this->getReportDetails($formId, $reportId); 
        $header = (!empty($data['labels']))?array_map('trim', explode(",", $data['labels'])):array();
        $reportName = isset($reportData[0]['report_name'])? $reportData[0]['report_name']:"";   
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheetTitle = $reportName;
        $sheetTitle .= ($headBreadcrumb != "") ? " for ".substr($headBreadcrumb, 4) : "";
        $spreadsheet->getActiveSheet()->mergeCellsByColumnAndRow(1, 1, sizeof($header), 1);
        $spreadsheet->getActiveSheet()->mergeCellsByColumnAndRow(1, 2, sizeof($header), 2);
        $aColumnLenth = array();
        
        $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(1, 1, $sheetTitle);
        
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
        $col = 1;
        foreach ($header as $value) {
            $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $value);
            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($col, 1, null, null)->applyFromArray($styleArrayDataLeft);
            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($col, 2, null, null)->applyFromArray($styleArrayDataLeft);
            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($col, $row, null, null)->applyFromArray($styleArrayHeader);
            $aColumnLenth[$col] = strlen($value)+1;
            $col++;
        }
       
        $row = 4;
        foreach ($data['data'] as $record) {
            $col = 1;
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
        
        $sReportName = trim($reportData[0]['report_name']);
        $fileName = isset($sReportName)? str_replace(" ", "_", $sReportName):"report";
        $sSheetName = strlen($fileName) > 31 ? substr($fileName, 0, 31) : $fileName;
        $spreadsheet->getActiveSheet()->setTitle($sSheetName, false, false);
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=' . $fileName.".xlsx");
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
    }
}
