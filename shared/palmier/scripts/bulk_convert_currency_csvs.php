<?php
$currencyDir = __DIR__ . '/../currency_data/';
$oldDir = $currencyDir . 'old/';
if (!is_dir($oldDir)) mkdir($oldDir, 0755, true);

// Function to clean numeric values
$cleanNumber = function($val) {
    $val = str_replace([',','"'], '', $val);
    return is_numeric($val) ? floatval($val) : null;
};

foreach (glob($currencyDir . '*.csv') as $file) {
    if (strpos($file, '/old/') !== false) continue; // skip archived
    $basename = basename($file);

    // Standardize file names
    $cleanName = null;
    if (preg_match('/^USD_([A-Z]+).*\.csv$/i', $basename, $m)) {
        $cleanName = "USD_" . strtoupper($m[1]) . ".csv";
    } elseif (preg_match('/^XAU_USD.*\.csv$/i', $basename)) {
        $cleanName = "XAU_USD.csv";
    } else {
        $cleanName = preg_replace(['/Historical Data/i','/\s+/','/[^A-Z0-9_\.]/i'],['','',''],$basename);
        if (preg_match('/USD_([A-Z]+)\.csv/i', $cleanName, $mm))
            $cleanName = "USD_".strtoupper($mm[1]).".csv";
    }
    $outFile = $currencyDir . $cleanName;

    echo "Checking $basename → $cleanName ...\n";

    // Read CSV and remove BOM
    $lines = file($file);
    if (!$lines || count($lines) < 2) { echo "  Skipping (empty)\n"; continue; }
    $lines[0] = preg_replace('/^\x{FEFF}/u', '', $lines[0]);
    $rows = array_map('str_getcsv', $lines);
    $header = array_map('strtolower', array_map('trim', $rows[0]));
    $dataRows = array_slice($rows,1);

    // Skip already normalized files
    if ($header === ['date','open','high','low','close'] && $cleanName === $basename) {
        echo "  Already normalized\n";
        continue;
    }

    // Identify column indices
    $iDate = array_search('date',$header);
    $iOpen = array_search('open',$header);
    $iHigh= array_search('high',$header);
    $iLow = array_search('low',$header);
    $iClose = array_search('close',$header);
    if ($iClose === false) $iClose = array_search('price',$header);

    // Detect dual-rate columns like USD/GHC or USD/GHS
    $usdIndex = false;
    foreach ($header as $idx=>$name) {
        if (in_array($name, ['usd/ghc','usd/ghs'])) { $usdIndex = $idx; break; }
    }

    $newRows = [['Date','Open','High','Low','Close']];

    foreach($dataRows as $r) {
        $dateRaw = $iDate!==false && isset($r[$iDate]) ? trim($r[$iDate]) : null;
        if (!$dateRaw) continue;

        // Normalize date to YYYY-MM-DD
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dateRaw)) {
            // Detect MM/DD/YYYY or DD/MM/YYYY format
            $parts = explode('/',$dateRaw);
            $month = $parts[0]; $day = $parts[1]; $year = $parts[2];
            $date = "$year-$month-$day";
        } else {
            $date = date('Y-m-d', strtotime($dateRaw));
        }
        if (!$date) continue;

        // Determine Close
        $close = $iClose!==false && isset($r[$iClose]) ? $cleanNumber($r[$iClose]) : null;
        if (($close===null || $close==0.0) && $usdIndex!==false) $close = isset($r[$usdIndex]) ? $cleanNumber($r[$usdIndex]) : null;
        if ($close===null || $close==0.0) continue;

        $open  = $iOpen!==false && isset($r[$iOpen]) && $cleanNumber($r[$iOpen])!==null ? $cleanNumber($r[$iOpen]) : $close;
        $high  = $iHigh!==false && isset($r[$iHigh]) && $cleanNumber($r[$iHigh])!==null ? $cleanNumber($r[$iHigh]) : $close;
        $low   = $iLow !==false && isset($r[$iLow])  && $cleanNumber($r[$iLow]) !==null ? $cleanNumber($r[$iLow])  : $close;

        $newRows[] = [$date,$open,$high,$low,$close];
    }

    if (count($newRows) <= 1) { echo "  No valid rows, skipping\n"; continue; }

    // Save temporary file
    $tmpFile = $currencyDir . '__tmp_' . $cleanName;
    $fout = fopen($tmpFile, 'w');
    foreach ($newRows as $row) fputcsv($fout, $row);
    fclose($fout);

    // Archive original
    $archive = $oldDir.$basename; $i=2;
    while(file_exists($archive)){
        $archive = $oldDir.pathinfo($basename,PATHINFO_FILENAME)."_bak{$i}.".pathinfo($basename,PATHINFO_EXTENSION);
        $i++;
    }
    rename($file, $archive);

    // Move temp CSV to main folder
    rename($tmpFile, $outFile);
    echo "  ✔ Converted and saved as $cleanName (original moved to /old/)\n";
}

echo "✅ Bulk CSV conversion complete.\n";