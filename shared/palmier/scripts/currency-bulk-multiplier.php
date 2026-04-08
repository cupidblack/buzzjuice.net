<?php
// Path to the original CSV file
$inputFile = __DIR__ . '/../currency_data/USD_MRO.csv';
// Path for the updated CSV
$outputFile = __DIR__ . '/../currency_data/USD_MRO_multiplied.csv';

// Open the input CSV
if (!file_exists($inputFile)) {
    exit("Input CSV file not found at $inputFile");
}

$inputHandle = fopen($inputFile, 'r');
if (!$inputHandle) {
    exit("Failed to open input CSV file.");
}

// Open the output CSV
$outputHandle = fopen($outputFile, 'w');
if (!$outputHandle) {
    exit("Failed to open output CSV file.");
}

// Read and process each line
$header = fgetcsv($inputHandle); // Read header
fputcsv($outputHandle, $header);  // Write header to output

while (($row = fgetcsv($inputHandle)) !== false) {
    // Multiply numeric values (columns Open, High, Low, Close) by 10
    for ($i = 1; $i < count($row); $i++) {
        if (is_numeric($row[$i])) {
            $row[$i] = $row[$i] * 10;
        }
    }
    fputcsv($outputHandle, $row);
}

// Close file handles
fclose($inputHandle);
fclose($outputHandle);

echo "CSV processing complete. Output saved to $outputFile\n";
?>