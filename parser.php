<?php

if(strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
    $allFileData = [];

    $fh = fopen("files/" . $_POST['file'], "r");
    if(!$fh) {
        echo 'Set permissions so server could read file';
        die;
    }

    $allFileData = readCsv($fh);
    fclose($fh);

    //filter data from single file
    $filteredFileData = filterArray($allFileData);
    //merge it with present data and filter result
    $merged = mergeData($filteredFileData);

    echo $_POST['file'] . " - " . ($merged ? 'success' : 'fail');
    die;
}

/**
 * @param resource $fh
 * @return array parsed csv content
 */
function readCsv($fh)
{
    $dataSet = [];

    while (($data = fgetcsv($fh, 1000, ";")) !== false) {
        $data[0] = (int)$data[0];

        //to work with price as number without currency
        $data[3] = (float)$data[3];
        $dataSet[] = $data;
    }

    return $dataSet;
}

/**
 * @param array $array
 * @return array Array sorted by price in ascending order, that meet conditions: <= $totalLimit/all; <= $perIdLimit/ID;
 */
function filterArray(array $array)
{
    $perIdLimit = 20;
    $totalLimit = 1000;

    //sort data, first by ID, then by price in ascending order
    usort($array, function($a, $b) {
        return ($a[0] - $b[0]) ?: ($a[3] - $b[3]);
    });

    //number of elements in incoming array for every ID
    $countPerId = array_count_values(array_column($array, 0));

    //IDs and number of them elements that exceed per-ID limit
    $exceededId = array_filter($countPerId, function($elem) use($perIdLimit) { return $elem > $perIdLimit; });

    foreach($exceededId as $id => $countOfElements) {
        //first occurrence of ID with exceeded count
        $key = array_search($id, array_column($array, 0));

        //remove record with certain ID above allowed limit
        for($i = $key + $perIdLimit; $i < $key + $countOfElements; $i++) {
            unset($array[$i]);
        }

        //"reset" array keys to properly get first exceeded ID position
        $array = array_values($array);
    }

    //sort by price in ascending order
    usort($array, function($a, $b) {
        return $a[3] - $b[3];
    });

    return array_slice($array, 0, $totalLimit);
}

/**
 * @param array $filteredArray
 * @return boolean Is result of data merge successful or not
 */
function mergeData(array $filteredArray)
{
    $timeout = 120;

    $fh = fopen("result.csv", "r+");
    if(!$fh) {
        return false;
    }

    //if other process is merging data - wait until it finish and release lock
    $waitTime = 0;
    while (!flock($fh, LOCK_EX)) {
        $waitTime++;
        if ($waitTime > $timeout) {
            fclose($fh);

            return false;
        }
        sleep(1);
    }

    $resultData = readCsv($fh);

    ftruncate($fh, 0);
    rewind($fh);
    $mergedData = array_merge($resultData, $filteredArray);
    //filter common result and data from consecutive file
    $mergedData = filterArray($mergedData);
    foreach ($mergedData as $string) {
        fputcsv($fh, $string, ';');
    }

    //write new common result, unlock file
    flock($fh, LOCK_UN);
    fclose($fh);

    return true;
}