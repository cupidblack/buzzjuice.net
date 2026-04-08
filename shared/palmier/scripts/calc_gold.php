<?php
/* AI PROMPT
Thoroughly review and analyze the attached and previous responses to develop and update the historical gold calculation and display strategy for each currency.

Here is the expected functionality:
1. 'palmier/scripts/calc_gold.php' is accessed.
2. 'calc_gold.php' checks the current date and time and determines when the last log should have been made to the gold_prices log file in 'palmier/gold_prices/' folder. Logs are typically generated 4 times each day in 6 hour intervals; at 00:00, 06:00, 12:00 and 18:00. If log exists at the determined time for the last expected log, 'calc_gold.php' displays that gold price log on a UI.
3. If the expected log does not exist, 'calc_gold.php' queries the 'https://buzzjuice.net/shared/palmier/scripts/sabbatical-date.php' endpoint for the historical date.
4. 'calc_gold.php' uses the acquired historical date to check the 'palmier/currency_data' folder to identify a row in the exchange rate files that matches the acquired historical date. 

5. If there is no matching date in an exchange rate file, 'calc_gold.php' checks the exchange rate file for a prior row with the last available rate in the 'open' or 'close' column. If there is no 'last available' rate or data, '0' values or null data for all rows prior to the historical date, it means the currency was probably non-existent at that time so that currency must be skipped.

6. If a rate is found, 'calc_gold.php' checks for the next row after the acquired historical date that has an available 'open' or 'close' value. If there is no 'next' rate or data, '0' values or null data found for all rows after the historical date, it means the currency was probably discontinued so that currency must be skipped.

7. If the both previous and next values have been acquired, 'calc_gold.php' interpolates an exchange rate value for that currency. 

8. 'calc_gold.php' uses the acquired historical date to check the 'palmier/currency_data/XAU_USD.csv' file to identify a row that matches the acquired historical date.

9. If there is no matching date in the XAU_USD.csv file, 'calc_gold.php' checks the XAU_USD.csv file for a prior row with the last available rate in the 'open' or 'close' column.

10. 'calc_gold.php' then checks for the next row after the acquired historical date that has an available 'open' or 'close' value.

11. When both previous and next values have been acquired, 'calc_gold.php' interpolates an exchange rate value for that currency.

12. By this time 'calc_gold.php' now has a value for the price of gold in USD and also the price of 1usd in each currency. 'calc_gold.php' now calculates the price of gold for each currency by multiplying the acquired exchange value of a currency with the with the 'open' or 'close' value in the XAU_USD.csv file. gold in usd. This should give the price of gold in that currency. Check the calculations to confirm.

13. 'calc_gold.php' updates the missing log value in the log file in the 'palmier/gold_prices/' folder. A new gold_prices log file is created for each month to avoid large archived files.

Note the following:
1. New historical rate data may be acquired frequently and the historical rates may be updated for each currency from time to time so the strategy must be flexible enough for new or updated data to be input and easily acquired for processing. 

2. The 'calc_gold.php' file must have an endpoint that can be queried to either get the gold prices from the latest historical gold price log or process a calculation to update the historical gold_price log file then return the result.

3. 'calc_gold.php' should display:
3.i. the price of gold in usd should show at the top of the table
3.ii. a column that shows the date of the row used in a currency if a matching date with the historical date was found.
3.iii. a column that shows the currency's exchange rate value that was used from the row if a matching date with the historical date was found.
3.iv. a column that shows the previous date of the row values used if a value was interpolated
3.v. a column that shows the currency's exchange rate value that was used from the previous row if the values were interpolated.
3.vi. a column that shows the next date of the row values used if a value was interpolated
3.vii. a column that shows the currency's exchange rate value that was used from the next row if the values were interpolated.
3.viii. a column that shows the interpolated value.
3.ix. a column that shows the price of gold for that currency by multiplying the the currency's exchange rate value with the price of gold in usd that is shown at the top of the table.

4. add a final row at the end of the table that calculates the average of all the displayed Gold Price in Currency values. The value should be formatted with 4 decimal places and digits in groups of 4 separated by an '. Fo example, 123456789.012345 would be formatted as 1'2345'6789.0123. This value must be made available at an endpoint as it would be used for further calculations. This value must also be logged. Show how to call this value from an endpoint.

5. Each currency displayed in the table must be numbered and the total number of currencies in the table logged.

6. The total number of currencies must be available in the json response as 'historical_currencies_count'.

Generate the fully updated codes for all the necessary files then take me step-by-step through implementation.

<RESOURCES>>
https://github.com/cupidblack/Koware-Management/blob/main/Palmier%20Concept%20(Draft%20Notes).txt 
https://github.com/cupidblack/Koware-Management/tree/main/Palmier/Historical%20Currencies/currency%20research%20data

*/

require_once __DIR__.'/helpers.php';

$currencyDir = dirname(__DIR__).'/currency_data/';
$sabbatical = 'https://buzzjuice.net/shared/palmier/scripts/sabbatical-date.php';

$format = $_GET['format'] ?? 'html';
if (isset($_GET['avg'])) $format = 'average';

$slot = getLatest6hSlot();
$logFile = getLogFile($slot);

if (logExists($logFile, $slot)) {
    $result = readLogRow($logFile, $slot);

    // Ensure total currency count present (for legacy logs)
    if (!isset($result['historical_currencies_count'])) {
        $result['historical_currencies_count'] = isset($result['currencies']) ? count($result['currencies']) : 0;
    }

    // Ensure numbering present and always in sorted order
    $ccys = array_keys($result['currencies']);
    sort($ccys, SORT_STRING);
    $newCurrencies = [];
    $i = 1;
    foreach ($ccys as $ccy) {
        $row = $result['currencies'][$ccy];
        $row['no'] = $i;
        $newCurrencies[$ccy] = $row;
        $i++;
    }
    $result['currencies'] = $newCurrencies;

} else {
    $histDT = getPalmierHistoricalDate($sabbatical);
    $histDate = $histDT->format('Y-m-d');

    $goldData = loadCsvFull($currencyDir.'XAU_USD.csv');
    $goldMeta = findRateMeta($goldData, $histDate);
    if (!$goldMeta) die("No valid/eligible XAU data for $histDate");
    $goldUSD = $goldMeta['rate'];

    // Collect all currency results (unsorted)
    $currencies_tmp = [];
    foreach (glob($currencyDir.'USD_*.csv') as $file) {
        $ccy = strtoupper(str_replace(['USD_', '.csv'], '', basename($file)));
        $data = loadCsvFull($file);
        $meta = findRateMeta($data, $histDate);
        if (!$meta) continue;
        $rate = $meta['rate'];
        $currencies_tmp[$ccy] = [
            'type'        => $meta['type'],
            'date_used'   => $meta['date_used'] ?? '',
            'rate'        => $rate,
            'prev_date'   => $meta['prev']['date'] ?? '',
            'prev_value'  => $meta['prev']['value'] ?? '',
            'next_date'   => $meta['next']['date'] ?? '',
            'next_value'  => $meta['next']['value'] ?? '',
            'interpolated'=> ($meta['type']=='interpolated') ? $rate : '',
            'gold_price'  => $goldUSD * $rate
        ];
    }
    // Sort and number
    $ccys = array_keys($currencies_tmp);
    sort($ccys, SORT_STRING);
    $currencies = [];
    $i = 1;
    foreach ($ccys as $ccy) {
        $currencies[$ccy] = $currencies_tmp[$ccy];
        $currencies[$ccy]['no'] = $i;
        $i++;
    }
    $historical_currencies_count = count($currencies);

    // Average
    $sum = 0; $count = 0;
    foreach ($currencies as $c){ $sum += $c['gold_price']; $count++; }
    $average = ($count > 0) ? $sum / $count : 0;

    $result = [
        'timestamp'                  => $slot,
        'historical_date'            => $histDT->format(DateTime::ATOM),
        'gold_usd'                   => $goldUSD,
        'currencies'                 => $currencies,
        'historical_gold_average'    => $average,
        'historical_currencies_count'=> $historical_currencies_count
    ];
    appendLog($logFile, $result);
}

// --- Endpoints ---
if ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

if ($format === 'average') {
    $resp = [
        'timestamp'                  => $result['timestamp'],
        'historical_gold_average'                    => $result['average'],
        'formatted_average'          => formatPalmierNumber($result['average']),
        'historical_currencies_count'=> $result['historical_currencies_count'] ?? 0
    ];
    header('Content-Type: application/json');
    echo json_encode($resp, JSON_PRETTY_PRINT); exit;
}

// --- HTML TABLE UI ---
echo "<h2>Palmier Gold Prices</h2>";
echo "<b>Slot:</b> {$result['timestamp']}<br>";
echo "<b>Historical Palmier Date:</b> {$result['historical_date']}<br>";
echo "<b>Total Currencies:</b> ".($result['historical_currencies_count'] ?? 0)."<br>";

echo "<h3 style='color:gold'>Gold Price in USD: {$result['gold_usd']}</h3>";

echo "<table border=1 cellpadding=6>
<tr>
<th>No.</th>
<th>Currency</th>
<th>Date Used</th>
<th>Rate Used</th>
<th>Prev Date</th>
<th>Prev Value</th>
<th>Next Date</th>
<th>Next Value</th>
<th>Interpolated</th>
<th>Gold Price in Currency</th>
</tr>";

foreach ($result['currencies'] as $ccy => $c) {
    echo "<tr>";
    echo "<td>{$c['no']}</td>";
    echo "<td>$ccy</td>";
    echo "<td>{$c['date_used']}</td>";
    echo "<td>{$c['rate']}</td>";
    echo "<td>{$c['prev_date']}</td>";
    echo "<td>{$c['prev_value']}</td>";
    echo "<td>{$c['next_date']}</td>";
    echo "<td>{$c['next_value']}</td>";
    echo "<td>{$c['interpolated']}</td>";
    echo "<td><b>{$c['gold_price']}</b></td>";
    echo "</tr>";
}

// Show formatted average at bottom
$formattedAvg = formatPalmierNumber($result['historical_gold_average']);
echo "<tr style='font-weight:bold;background:#eaeaea'>
<td colspan=9 align=right>Average</td>
<td>{$formattedAvg}</td>
</tr>";
echo "</table>";

echo "<form method='get'>
      <input type='hidden' name='format' value='json'/>
      <button>Download JSON</button>
      </form>";
?>