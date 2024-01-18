<?php

namespace Common\Report\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;

require './library/mpdf60/mpdf.php';
use mPDF;


class Pdfcreater extends mPDF
{

    // protected $_adapter;

    /**
     * Make the Adapter object available as local protected variable
     *
     * @param Adapter $adapter - DB PDO PgSQL conn
     */
    public function __construct(Adapter $adapter)
    {
        $this->_adapter = $adapter;
    }

    public function createPDF($trackerId, $formid, $reportid, $daterange, $productids,$preferredterms)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $daterange = str_replace('_', '/', $daterange);
        $dates = explode("-", $daterange);
        $sDate = date("Y-m-d", strtotime($dates[0]));
        $eDate = date("Y-m-d", strtotime($dates[1]));
        $select->from('report_pdf');
        $select->join('report', 'report.report_id = report_pdf.report_id');
        $select->where(array('report.report_id' => $reportid));
        $newadpater = $this->_adapter;
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

        foreach ($arr as $key => $value) {
            $rep_query = $value['report_query'];
            $rep_filter = $value['custom_filter'];
            $query = 'call ' . $rep_query . '("' . $trackerId . '","' . $formid . '","'.$productids.'","' . $sDate . '","' . $eDate . '","' . $rep_filter . '","'.$preferredterms.'")';
            $statements = $this->_adapter->query($query);
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            if ($count > 0) {
                $arr3 = $resultSet->toArray();
            }
            $res2 = $arr3;
        }
        $matches = array();
        $msgs = $arr[0]['pdf_content'];
        $header = $arr[0]['pdf_header'];
        $mpdf = new \mPDF('', 'A4', '', '', 15, 15, 30, 30, '', '', '');
        $mpdf->debug = true;
        $stylesheet = file_get_contents('./public/pdf.css');
        $mpdf->WriteHTML($stylesheet, 1);
        $footer = $arr[0]['pdf_footer'];
        $msgs = explode('<nh>', $msgs);
        $mpdf->SetHeader($header);
        $mpdf->defaultfooterline = 0;
        $mpdf->SetFooter($footer . '|Confidential|');
        $qry = 'call sp_getvalueforpdf(' . $trackerId . ',' . $formid . ',' . $reportid . ',"' . $arr[0]['pdf_frontpage'] . '")';
        $statements = $this->_adapter->query($qry);
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr2 = $resultSet->toArray();
        }
        //print_r($arr2);die;

        foreach ($res2 as $result) {
            $mpdf->Ln();
            $mpdf->WriteHTML('<div style="text-align: center;width:100%;padding-top:350px;"><b>' . $arr2[0][$arr[0]['pdf_frontpage']] . '</b></div>');

            $mpdf->WriteHTML(
                '<p style="text-align: center;">
			<span style="font-size:11px;">Disclaimer</span></p>
		<p>
			<span style="font-size:11px;">The objective of this document is to explain the difference between the current safety section (ADR table) of FCM CCDS V 4.0 and the new proposed FCM CCDS V 5.0.</span></p>
		<p>
			<span style="font-size:11px;">The contents of this document describe the position of Vifor Pharma based on available information on SEP12 2014.</span></p>'
            );
            $mpdf->AddPage();
            foreach ($msgs as $msg) {
                preg_match_all('~\{{(.+?)\}}~', $msg, $matches);
                array_shift($matches);
                $fields = implode(',', $matches[0]);
                if (isset($matches[0]) && !empty($matches[0])) {
                    $qry = 'call sp_getvalueforpdf(' . $trackerId . ',' . $formid . ',' . $reportid . ',"' . $fields . '")';
                    $statements = $this->_adapter->query($qry);
                    $results = $statements->execute();
                    $resultSet = new ResultSet;
                    $resultSet->initialize($results);
                    $resultSet->buffer();
                    $count = $resultSet->count();
                    if ($count > 0) {
                        $arr1 = $resultSet->toArray();
                    }
                    foreach ($arr1 as $res) {
                        $label = $res['label'];
                        $msg = str_replace('{{' . $fields . '}}', $result[$fields], $msg);
                    }
                }
                $mpdf->Ln();
                $mpdf->WriteHTML('<b>' . $label . '</b>');
                $mpdf->WriteHTML($msg);
            }
                $mpdf->AddPage();
        }
        $mpdf->Output();
        exit;
    }

    public function createPDFback($trackerId, $formid, $reportid, $daterange)
    {
        //echo $report_id;die;
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $daterange = str_replace('_', '/', $daterange);
        $dates = explode("-", $daterange);
        $sDate = date("Y-m-d", strtotime($dates[0]));
        $eDate = date("Y-m-d", strtotime($dates[1]));
        $select->from('report_pdf');
        $select->where(array('report_id' => $reportid));
        $newadpater = $this->_adapter;
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
        $matches = array();
        $msgs = $arr[0]['pdf_content'];
        $header = $arr[0]['pdf_header'];
        $mpdf = new \mPDF('', 'A4', '', '', 15, 15, 30, 30, '', '', '');
        $mpdf->debug = true;
        $stylesheet = file_get_contents('./public/pdf.css');
        $mpdf->WriteHTML($stylesheet, 1);
        // $img=' <img src="assets/img/pdf.jpeg" alt="logo" />';
        $footer = $arr[0]['pdf_footer'];
        $msgs = explode('<nh>', $msgs);
        $mpdf->SetHeader($header);
        $mpdf->defaultfooterline = 0;
        $mpdf->SetFooter($footer . '|Confidential|');


        echo $qry = 'call sp_getvalueforpdf(' . $trackerId . ',' . $formid . ',' . $reportid . ',"' . $arr[0]['pdf_frontpage'] . '",' . $sDate . ',' . $eDate . ')';
        die;
        $statements = $this->_adapter->query($qry);
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr2 = $resultSet->toArray();
        }
        $mpdf->Ln();
        $mpdf->WriteHTML('<div style="text-align: center;width:100%;padding-top:350px;"><b>' . $arr2[0]['frontpage'] . '</b></div>');

        $mpdf->WriteHTML(
            '<p style="text-align: center;">
			<span style="font-size:11px;">Disclaimer</span></p>
		<p>
			<span style="font-size:11px;">The objective of this document is to explain the difference between the current safety section (ADR table) of FCM CCDS V 4.0 and the new proposed FCM CCDS V 5.0.</span></p>
		<p>
			<span style="font-size:11px;">The contents of this document describe the position of Vifor Pharma based on available information on SEP12 2014.</span></p>'
        );
        $mpdf->AddPage();
        foreach ($msgs as $msg) {
            preg_match_all('~\{{(.+?)\}}~', $msg, $matches);
            array_shift($matches);
            $fields = implode(',', $matches[0]);
            if (isset($matches[0]) && !empty($matches[0])) {
                $qry = 'call sp_getvalueforpdf(' . $trackerId . ',' . $formid . ',' . $reportid . ',"' . $fields . '",' . $sDate . ',' . $eDate . ')';
                $statements = $this->_adapter->query($qry);
                $results = $statements->execute();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $count = $resultSet->count();
                if ($count > 0) {
                    $arr1 = $resultSet->toArray();
                }
                foreach ($arr1 as $res) {
                    $label = $res['label'];
                    $msg = str_replace('{{' . $fields . '}}', $res[$fields], $msg);
                }
            }
            // echo $msg;die;
            $mpdf->Ln();
            $mpdf->WriteHTML('<b>' . $label . '</b>');
            $mpdf->WriteHTML($msg);
        }
        //die;
        $mpdf->Output();
        exit;
    }


}

?>
