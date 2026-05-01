<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('UTC');

/**** CONSTANTS ****/
define('WOOCS_API', 'https://buzzjuice.net/wp-json/woocs/v3/currency');
define('ALPHA_API', 'https://www.alphavantage.co/query');
define('ALPHA_KEY', 'UE68Z2WQ6DPIGUKI');
define('TWELVE_API', 'https://api.twelvedata.com');
define('TWELVE_KEY', '28e163b39b84419d91a65ecb60baf47c');
define('EXCHANGE_API', 'https://v6.exchangerate-api.com/v6/fb224de680c5c7c273169a3a/latest/USD');
define('HISTORICAL_API', 'https://buzzjuice.net/shared/palmier/gold_calc/gold_calc.php?format=json');
define('OSC_URL', 'https://buzzjuice.net/shared/palmier/oscillator/palmier-oscillator.php?oscillation=live');
define('LOG_DIR', __DIR__ . '/');
define('LOG_PREFIX', 'palmier_');

/**** HELPERS ****/
function fetch_json($url, $timeout = 10) {
    $ctx = stream_context_create(['http' => ['timeout' => $timeout]]);
    $raw = @file_get_contents($url, false, $ctx);
    return $raw ? json_decode($raw, true) : null;
}
function formatPalmierNumber($num) {
    $num = number_format($num, 4, '.', '');
    $parts = explode('.', $num);
    $int = strrev(implode("'", str_split(strrev($parts[0]), 4)));
    return $int . '.' . $parts[1];
}
// Log slot time, pausing Sat 12:00 UTC – Sun 12:00 UTC
function getPalmierSlot() {
    $now = time();
    $dow = gmdate('w', $now); $hour = (int)gmdate('H', $now);
    $suspend = ($dow == 6 && $hour >= 12) || ($dow == 0 && $hour < 12) || ($dow == 6 && $hour == 12);
    if ($suspend) return gmdate('Y-m-d H:i:00', strtotime('last Saturday 12:00 UTC', $now));
    return gmdate('Y-m-d H:i:00', floor($now / 12345) * 12345);
}
function getLogFile($slot) {
    return LOG_DIR . LOG_PREFIX . date('Y', strtotime($slot)) . '.log';
}
function readLog($file, $slot) {
    if (!file_exists($file)) return null;
    foreach (file($file) as $line) {
        $row = json_decode($line, true);
        if (isset($row['timestamp']) && $row['timestamp'] === $slot) return $row;
    }
    return null;
}
function appendLog($file, $row) {
    file_put_contents($file, json_encode($row) . PHP_EOL, FILE_APPEND);
}
// Get UI reference currency
function getReferenceCurrency() {
    if (isset($_GET['ref']) && preg_match('/^[A-Z0-9_]{3,8}$/', $_GET['ref']))
        return strtoupper($_GET['ref']);
    $r = fetch_json('https://buzzjuice.net/?palmier=base_currency');
    return strtoupper($r['fox_currency'] ?? $r['base_currency'] ?? 'USD');
}
// Currency gold price consensus
function get_gold_price_consensus($currency, $goldUSD, $woocs, $exRates) {
    $c = strtoupper($currency);
    $sources = [];
    // 1. WOOCS direct (rare)
    if (isset($woocs[$c]['gold_price']) && $woocs[$c]['gold_price'] > 0) {
        $sources[] = (float)$woocs[$c]['gold_price'];
    }
    // 2. WOOCS FX-converted
    if (isset($woocs[$c]['rate']) && $woocs[$c]['rate'] > 0)
        $sources[] = $goldUSD * (float)$woocs[$c]['rate'];
    // 3. Exchangerate-API fallback
    if (isset($exRates[$c]) && $exRates[$c] > 0)
        $sources[] = $goldUSD * (float)$exRates[$c];
    return count($sources) ? array_sum($sources) / count($sources) : $goldUSD;
}

/************** ENGINE **************/

$slot = getPalmierSlot();
$logFile = getLogFile($slot);
$data = readLog($logFile, $slot);

if (!$data) {
    // 1. WOOCS
    $woocs = fetch_json(WOOCS_API);
    if (!$woocs) exit("WOOCS unavailable");
    $verified = array_map('strtoupper', array_keys($woocs));
    sort($verified);

    // 2. FX fallback
    $ex = fetch_json(EXCHANGE_API);
    $exRates = $ex['conversion_rates'] ?? [];

    // 3. Gold(USD)
    $alpha = fetch_json(ALPHA_API . '?function=GOLD_SILVER_SPOT&symbol=GOLD&apikey=' . ALPHA_KEY);
    $goldUSD = (float)($alpha['price'] ?? 0);
    if (!$goldUSD) {
        $td = fetch_json(TWELVE_API . '/price?symbol=XAU/USD&apikey=' . TWELVE_KEY);
        $goldUSD = (float)($td['price'] ?? 0);
    }
    if (!$goldUSD || $goldUSD <= 0) exit("Gold price unavailable");

    // 4. Build table: only verified, sorted
    $live = [];
    $sum = 0;
    $i = 1;
    foreach ($verified as $ccy) {
        // WOOCS, then exRates fallback
        $rate = isset($woocs[$ccy]['rate']) && $woocs[$ccy]['rate'] > 0
            ? (float)$woocs[$ccy]['rate']
            : (isset($exRates[$ccy]) ? (float)$exRates[$ccy] : null);

        if (!$rate || $rate <= 0) continue;

        $gold_price = get_gold_price_consensus($ccy, $goldUSD, $woocs, $exRates);

        $live[$ccy] = [
            'no'         => $i++,
            'rate_usd'   => $rate,
            'gold_price' => $gold_price
        ];
        $sum += $gold_price;
    }
    $live_currencies_count = count($live);
    $live_gold_average = $live_currencies_count ? $sum / $live_currencies_count : 0;

    // 5. Historical data
    $hist = fetch_json(HISTORICAL_API);
    $hist_avg = $hist['historical_gold_average'] ?? 0;
    $hist_count = $hist['historical_currencies_count'] ?? 0;

    // 6. Oscillator
    $osc = fetch_json(OSC_URL);
    $osc_val = ($osc['palmier_max_index'] ?? 0) - 2 * ($osc['oscillation_index'] ?? 0);

    // 7. Palmier, per formula
    $totalCount = $hist_count + $live_currencies_count;
    $palmier = $totalCount
        ? ((($hist_avg * $hist_count) + ($live_gold_average * $live_currencies_count)) * (1 + $osc_val)) / $totalCount
        : 0;

    // 8. ₱/currency (per a, b)
    foreach ($live as &$row) {
        $a = $row['gold_price'];
        $b = $palmier;
        $row['palmier_value'] = $a > 0 ? round($b / $a, 4) : 0;
        $row['palmier_value_formatted'] = formatPalmierNumber($row['palmier_value']);
    }
    unset($row);

    // 9. DATA LOG structure
    $data = [
        'timestamp'                   => $slot,
        'gold_usd'                    => $goldUSD,
        'verified_currency_commodity' => $verified,
        'live_currencies'             => $live,
        'live_currencies_count'       => $live_currencies_count,
        'live_gold_average'           => $live_gold_average,
        'live_gold_average_formatted' => formatPalmierNumber($live_gold_average),
        'historical_gold_average'     => $hist_avg,
        'historical_currencies_count' => $hist_count,
        'palmier_value'               => $palmier,
        'palmier_value_formatted'     => '₱' . formatPalmierNumber($palmier),
        'palmier_value_normalized'    => number_format($palmier, 4, '.', '')
    ];
    appendLog($logFile, $data);
}

/**** UI: UI-ONLY CURRENCY PROJECTION ****/
$ref = getReferenceCurrency();
if (!isset($data['live_currencies'][$ref])) $ref = 'USD';
$refRate = $data['live_currencies'][$ref]['rate_usd'];

foreach ($data['live_currencies'] as &$row) {
    $row['rate_display'] = $refRate > 0 ? $row['rate_usd'] / $refRate : 0;
}
unset($row);

/**** ENDPOINTS (AJAX/json API, Palmier endpoint) ****/
if (isset($_GET['endpoint'])) {
    header('Content-Type: application/json');
    if ($_GET['endpoint'] === 'palmier') {
        echo json_encode([
            'formatted'  => $data['palmier_value_formatted'],
            'normalized' => $data['palmier_value_normalized']
        ]);
        exit;
    }
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Palmier Live Engine</title>
    <meta charset="UTF-8"/>
    <style>
        body {font-family:sans-serif;background:#fcfcfc;}
        h2{margin-top:0}
        table{border-collapse:collapse;}
        th, td{padding:8px 12px;}
        th{background:#ececec;}
        tr:nth-child(even){background:#fafafa;}
        select{font-size:120%}
    </style>
</head>
<body>
<h2>Palmier Live Engine</h2>
<h3 style='color:gold'>Gold Price (USD): <?=number_format($data['gold_usd'], 4)?></h3>
<h1 style='color:green'>XAUoz = <?=$data['palmier_value_formatted']?></h1>
<b>Live Currencies:</b> <?=$data['live_currencies_count']?><br>
<b>Historical Currencies:</b> <?=$data['historical_currencies_count']?><br><br>

<form onsubmit="return false" style="margin-bottom:12px">
    <label for="refCurrency"><b>Reference currency:</b></label>
    <select id="refCurrency">
    <?php foreach($data['verified_currency_commodity'] as $currency): ?>
        <option value="<?=$currency?>" <?=$currency==$ref?'selected':''?>><?=$currency?></option>
    <?php endforeach; ?>
    </select>
</form>

<table border="1" cellpadding="6">
<thead>
<tr>
    <th>No.</th>
    <th>Currency</th>
    <th>Exchange Rate (vs <?=$ref?>)</th>
    <th>Gold Price</th>
    <th>₱ per Currency Unit</th>
</tr>
</thead>
<tbody id="tableBody">
<?php foreach($data['live_currencies'] as $ccy=>$row): ?>
    <tr>
        <td><?=$row['no']?></td>
        <td><?=$ccy?></td>
        <td><?=number_format($row['rate_display'],8)?></td>
        <td><?=number_format($row['gold_price'],4)?></td>
        <td><?=$row['palmier_value_formatted']?></td>
    </tr>
<?php endforeach; ?>
<tr style='font-weight:bold;background:#eee'>
    <td colspan=4 align=right>Live Gold Average</td>
    <td><?=$data['live_gold_average_formatted']?></td>
</tr>
</tbody>
</table>

<script>
document.getElementById('refCurrency').addEventListener('change', function(){
    let ref = this.value;
    fetch('?endpoint=1&ref='+ref)
    .then(r=>r.json())
    .then(data=>{
        const refRate = data.live_currencies[ref].rate_usd;
        let html = '';
        for(const ccy of data.verified_currency_commodity){
            if(!(ccy in data.live_currencies)) continue;
            const row = data.live_currencies[ccy];
            const exch = refRate > 0 ? (row.rate_usd/refRate) : 0;
            html += `
                <tr>
                    <td>${row.no}</td>
                    <td>${ccy}</td>
                    <td>${exch.toFixed(8)}</td>
                    <td>${Number(row.gold_price).toFixed(4)}</td>
                    <td>${row.palmier_value_formatted}</td>
                </tr>
            `;
        }
        html += `<tr style="font-weight:bold;background:#eee">
            <td colspan="4" align="right">Live Gold Average</td>
            <td>${data.live_gold_average_formatted}</td>
        </tr>`;
        document.getElementById('tableBody').innerHTML = html;
    });
});
</script>

<form method='get'>
    <input type='hidden' name='endpoint' value='json'/>
    <button>Download JSON</button>
</form>
<form method='get'>
    <input type='hidden' name='endpoint' value='palmier'/>
    <button>Get Palmier Value</button>
</form>

</body>
</html>