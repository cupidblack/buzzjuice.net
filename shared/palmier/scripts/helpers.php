<?php

function getLatest6hSlot() {
    $slots = ['00:00:00','06:00:00','12:00:00','18:00:00'];
    $now = new DateTime('now', new DateTimeZone('UTC'));
    foreach(array_reverse($slots) as $slot) {
        if($now->format('H:i:s') >= $slot) return $now->format('Y-m-d').' '.$slot;
    }
    return (new DateTime('yesterday', new DateTimeZone('UTC')))->format('Y-m-d').' 18:00:00';
}

function getLogFile($slot) {
    return dirname(__DIR__).'/gold_prices/'.substr($slot,0,7).'.log';
}

function logExists($file, $slot) {
    if(!file_exists($file)) return false;
    foreach(file($file) as $line) {
        $row = json_decode($line,true);
        if(isset($row['timestamp']) && $row['timestamp']===$slot) return true;
    }
    return false;
}

function readLogRow($file, $slot) {
    foreach(file($file) as $line) {
        $row = json_decode($line,true);
        if(isset($row['timestamp']) && $row['timestamp']===$slot) return $row;
    }
    return null;
}

function appendLog($file, $row) {
    $f = fopen($file,'a');
    fwrite($f, json_encode($row).PHP_EOL);
    fclose($f);
}

function getPalmierHistoricalDate($endpoint) {
    $resp = file_get_contents($endpoint.'?endpoint=historical');
    $json = json_decode($resp,true);
    if(!isset($json['iso'])) die("Invalid sabbatical response");
    return new DateTime($json['iso'], new DateTimeZone('UTC'));
}

// Returns array: ['date1'=>[...], 'date2'=>[...]]
function loadCsvFull($file) {
    $lines = file($file);
    if(!$lines) return [];
    $lines[0] = preg_replace('/^\x{FEFF}/u','',$lines[0]);
    $rows = array_map('str_getcsv', $lines);
    $header = array_map('strtolower',$rows[0]);
    unset($rows[0]);
    $data = [];
    foreach($rows as $row) {
        $entry = array_combine($header,$row);
        if(isset($entry['date'])) $data[$entry['date']] = $entry;
    }
    ksort($data);
    return $data;
}

// Prefer 'close' if valid, fallback to 'open'
function getRate($row) {
    if(isset($row['close']) && $row['close']!='' && $row['close']!=0){
        return (float)$row['close'];
    }
    if(isset($row['open']) && $row['open']!='' && $row['open']!=0){
        return (float)$row['open'];
    }
    return null;
}

// Finds exact/interpolated rate or skips currency if ineligible
function findRateMeta($data, $targetDate){
    $dates = array_keys($data);
    $prev=null; $next=null;
    foreach($dates as $d){
        $row = $data[$d];
        $value = getRate($row);
        if($value === null) continue;
        if($d == $targetDate){
            return [
                'type'      => 'exact',
                'date_used' => $d,
                'rate'      => $value
            ];
        }
        if($d < $targetDate) $prev = ['date'=>$d, 'value'=>$value];
        if($d > $targetDate && !$next) $next = ['date'=>$d,'value'=>$value];
    }
    if(!$prev || !$next) return null;
    $t1 = strtotime($prev['date']);
    $t2 = strtotime($next['date']);
    $t  = strtotime($targetDate);
    $interp = $prev['value'] + ($next['value'] - $prev['value']) * (($t-$t1)/($t2-$t1));
    return [
        'type'      => 'interpolated',
        'date_used' => $targetDate,
        'rate'      => $interp,
        'prev'      => $prev,
        'next'      => $next
    ];
}

// Format: 1'2345'6789.0123 (groups of 4)
function formatPalmierNumber($num) {
    $num = number_format($num,4,'.','');
    $parts = explode('.',$num);
    $int = strrev(implode("'",str_split(strrev($parts[0]),4)));
    return $int.'.'.$parts[1];
}
?>