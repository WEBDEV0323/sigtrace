<?php
namespace LitTrace\Article\Service;
class ImportService
{
    public function get2DArrayFromCsv($file, $delimiter,$fieldDataTypeMapper)
    {
        if (!file_exists($file)) { 
            return "File not found. Make sure you specified the correct path.\n"; 
            exit; 
        }
        if (($handle = fopen($file, "r")) !== false) {
            $i = 0;
            while (($lineArray = fgetcsv($handle, 10000, ',')) !== false) {
                if (array_filter($lineArray)) {
                    for ($j = 0; $j < count($lineArray); $j++) {
                        $csv[$i][$j] = str_replace(array("\r\n", "\n\r", "\n", "\r","\xEF\xBB\xBF"), '', trim($lineArray[$j]));                        
                    }
                    $i++;
                }            
            }
            fclose($handle);
        } else {
            return "Error opening data file.\n"; 
             exit; 
        }

        array_walk(
            $csv, function (&$a) use ($csv) {
                $a = array_combine($csv[0], $a);
            }
        );
        array_shift($csv); // remove column header
        return $csv;
    }


    public function computeDuplicateCheckArrayData($dupArray,$dataArray)
    {  
        if (empty($dupArray)) {
            return $dataArray;
        }      
        array_walk(
            $dataArray, function (&$item) use ($dupArray) {
                
                $item = array_intersect_key($item, $dupArray);
            }
        );    
           
        return $dataArray;
    }

    public function flipAndGroup($input) 
    {
        $outArr = array();
        array_walk(
            $input, function ($value, $key) use (&$outArr) {
                foreach ($value as $key1 => $value1) {
                    $outArr[$key1][] = $value1;
                }
            }
        );
        return $outArr;
    }

    public function columnForDuplicateCheck($importFieldMapping)
    {
        $duplicateCheckData=array_column($importFieldMapping, 'isRequiredForDuplicateSearch', 'db_field_name');
        $dupArray = array_filter(
            $duplicateCheckData, function ($v) {
                    return $v == 'yes';
            }, ARRAY_FILTER_USE_BOTH
        );

        return $dupArray;
    }

    public function findDuplicateDataArray($dataArray,$dupDataFromDb)
    {

        foreach ($dataArray as $csvKey => $csvData) {

            foreach ($dupDataFromDb as $dupkey => $dbData) {
                   $count=0;                                             
                foreach ($dbData as $key => $value) {
                    if (isset($csvData[$key])) {
                        if (isset($dbData[$key])) {
                            if ($dbData[$key]==$csvData[$key]) {
                                $count++;                                     
                            }
                        }
                    }
                    if ($count==count($dbData)) {
                        unset($dataArray[$csvKey]);
                    }
                }
            }
        }

        return $dataArray;
    }

    public function swapCsvColumnToDb($dataArray,$importFieldArray)
    {
        print_r ("test"); die;
        array_walk(
            $dataArray, function (&$item) use ($importFieldArray) {
                $item_new=array();
                foreach ($importFieldArray as $key => $value) { 
                    if (isset($item[$key]) && $item[$key] != '') {
                        $item_new[$value]=$item[$key];
                    } 
                }
                $item=$item_new;
            }
        );
        return $dataArray;
    }
    
    public function deDuplicateDataArray($dataArray,$dupArraydata)
    {
         $newArr=[];
        foreach ($dupArraydata as $dupkey =>$dData ) {
               $newArr[]=$dataArray[$dupkey];
        }
        return $newArr;
    }
}
