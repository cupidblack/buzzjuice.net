<?php

require_once __DIR__.'/gold_calc_helpers.php';

$currencyDir = dirname(__DIR__).'/currency_data/';
$sabbatical  = 'https://buzzjuice.net/shared/palmier/calendar/sabbatical-date.php';

$format = $_GET['format'] ?? 'html';
$force  = isset($_GET['force']);

$slot    = getLatest6hSlot();
$logFile = getLogFile($slot);

/**
 * =============================== 
 * 1. CACHE: Load if present 
 * ===============================
 */
if (!$force && logExists($logFile, $slot)) {

    $result = readLogRow($logFile, $slot);

    // Renormalize for new schema fields
    $currencies = $result['currencies'] ?? [];
    ksort($currencies, SORT_STRING);
    $i = 1;
    foreach ($currencies as &$row) {
        $row['no'] = $i++;
    }
    unset($row);

    $result['currencies'] = $currencies;
    $result['historical_currencies_count'] = count($currencies);

} else {
/**
 * ===============================
 * 2. ENGINE: Recompute 
 * ===============================
 */

    // 2.1 Date resolution
    $histDT   = getPalmierHistoricalDate($sabbatical);
    $histDate = $histDT->format('Y-m-d');

    // 2.2 Gold rate (XAU/USD)
    $goldData = loadCsvFull($currencyDir.'XAU_USD.csv');
    $goldMeta = findRateMeta($goldData, $histDate);

    if (!$goldMeta) {
        die("Missing gold anchor (XAU/USD) for {$histDate}");
    }

    $goldUSD = $goldMeta['rate'];

    // 2.3 FX: collect currencies, always include USD
    $currencies = [];

    foreach (glob($currencyDir.'USD_*.csv') as $file) {
        $ccy = strtoupper(str_replace(['USD_', '.csv'], '', basename($file)));

        $data = loadCsvFull($file);
        $meta = findRateMeta($data, $histDate);

        if (!$meta && $ccy !== 'USD') continue;

        $rate = ($ccy === 'USD') ? 1.0 : $meta['rate'];

        $currencies[$ccy] = [
            'date_used'    => $meta['date_used'] ?? $histDate,
            'rate'         => $rate,
            'prev_date'    => $meta['prev']['date'] ?? '',
            'prev_value'   => $meta['prev']['value'] ?? '',
            'next_date'    => $meta['next']['date'] ?? '',
            'next_value'   => $meta['next']['value'] ?? '',
            'interpolated' => ($meta['type'] ?? '') === 'interpolated' ? $rate : '',
            'gold_price'   => $rate * $goldUSD
        ];
    }
    if (!isset($currencies['USD'])) {
        $currencies['USD'] = [  // defensive (should always exist)
            'date_used'    => $histDate,
            'rate'         => 1.0,
            'prev_date'    => '',
            'prev_value'   => '',
            'next_date'    => '',
            'next_value'   => '',
            'interpolated' => '',
            'gold_price'   => $goldUSD
        ];
    }

    ksort($currencies, SORT_STRING);
    $i = 1;
    foreach ($currencies as &$row) {
        $row['no'] = $i++;
    }
    unset($row);

    $sum = 0;
    foreach ($currencies as $c) {
        $sum += $c['gold_price'];
    }
    $count   = count($currencies);
    $average = $count ? $sum / $count : 0;

    $result = [
        'timestamp'                   => $slot,
        'historical_date'             => $histDT->format(DateTime::ATOM),
        'gold_usd'                    => $goldUSD,
        'currencies'                  => $currencies,
        'historical_gold_average'     => $average,
        'historical_currencies_count' => $count
    ];

    appendLog($logFile, $result);
}

/**
 * ===============================
 * 3. API ENDPOINTS 
 * ===============================
 */

if ($format === 'json') {
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}
if ($format === 'average') {
    header('Content-Type: application/json');
    echo json_encode([
        'timestamp'                   => $result['timestamp'],
        'historical_gold_average'     => $result['historical_gold_average'],
        'formatted_average'           => formatPalmierNumber($result['historical_gold_average']),
        'historical_currencies_count' => $result['historical_currencies_count']
    ], JSON_PRETTY_PRINT);
    exit;
}

/**
 * ===============================
 * 4. HTML TABLE OUTPUT 
 * ===============================
 */

 
echo "<h2>Palmier Gold Prices</h2>";
echo "<b>Slot:</b> {$result['timestamp']}<br>";
echo "<b>Historical Palmier Date:</b> {$result['historical_date']}<br>";
echo "<b>Total Currencies:</b> {$result['historical_currencies_count']}<br>";

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
    echo "<td>{$ccy}</td>";
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
echo "<tr style='font-weight:bold;background:#eaeaea'>
<td colspan=9 align=right>Average</td>
<td>" . formatPalmierNumber($result['historical_gold_average']) . "</td>
</tr>";
echo "</table>";

echo "<form method='get'><input type='hidden' name='format' value='json'/><button>Download JSON</button></form>";
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Palmier Gold Prices</title>
</html>