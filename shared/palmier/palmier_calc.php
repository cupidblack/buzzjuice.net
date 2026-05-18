<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('UTC');

// --------------------------------------------------
// CONFIGURATION
// --------------------------------------------------
define('WOOCS_API', 'https://buzzjuice.net/wp-json/woocs/v3/currency');
define('BASE_CONFIG_API', 'https://buzzjuice.net/?palmier=base_currency');
define('ALPHA_API', 'https://www.alphavantage.co/query');
define('ALPHA_KEY', 'UE68Z2WQ6DPIGUKI');
define('TWELVE_API', 'https://api.twelvedata.com');
define('TWELVE_KEY', '28e163b39b84419d91a65ecb60baf47c');
define('EXCHANGE_API_BASE', 'https://v6.exchangerate-api.com/v6/fb224de680c5c7c273169a3a/latest/');
define('HISTORICAL_API','https://buzzjuice.net/shared/palmier/gold_calc/gold_calc.php?format=json');
define('OSC_URL','https://buzzjuice.net/shared/palmier/oscillator/palmier-oscillator.php?oscillation=live');
define('LOG_DIR', __DIR__.'/logs/palmier-rates/');
define('LOG_PREFIX', 'palmier_');
define('LOG_INTERVAL', 12345);
define('MAX_LOG_SIZE', 524288);

// --------------------------------------------------
// HELPERS
// --------------------------------------------------
function fetch_json($url, $timeout=20){
    $context = stream_context_create([
        'http' => [
            'timeout' => $timeout,
            'ignore_errors' => true,
            'header' => "Cache-Control: no-cache\r\nPragma: no-cache\r\n"
        ]
    ]);
    $raw = @file_get_contents($url, false, $context);
    if(!$raw) return null;
    $json = json_decode($raw, true);
    return is_array($json) ? $json : null;
}

function normalize_rate($value) {
    return (is_numeric($value) && $value > 0) ? (float)$value : null;
}

function formatPalmierNumber($num) {
    $num = number_format((float)$num, 4, '.', '');
    $parts = explode('.', $num);
    $int = strrev(implode("'", str_split(strrev($parts[0]), 4)));
    return $int . '.' . $parts[1];
}

// --------------------------------------------------
// LOGGING
// --------------------------------------------------
function getPalmierSlot() {
    $now = time();
    $dow = gmdate('w', $now);
    $hour = (int)gmdate('H', $now);
    $suspend = ($dow == 6 && $hour >= 12) || ($dow == 0 && $hour < 12);
    if ($suspend) {
        return gmdate('Y-m-d H:i:00', strtotime('last Saturday 12:00 UTC', $now));
    }
    return gmdate('Y-m-d H:i:00', floor($now/LOG_INTERVAL)*LOG_INTERVAL);
}
function getLogFilePath($slot) {
    $year = gmdate('Y', strtotime($slot));
    $base = LOG_DIR . LOG_PREFIX . $year . '.log';
    if (!file_exists($base)) return $base;
    if (filesize($base) >= MAX_LOG_SIZE) return LOG_DIR . LOG_PREFIX . $year . '_' . time() . '.log';
    return $base;
}
function readLog($slot) {
    if (!is_dir(LOG_DIR)) return null;
    $files = glob(LOG_DIR . '*.log');
    rsort($files);
    foreach ($files as $file) {
        foreach (@file($file) as $line) {
            $row = json_decode($line, true);
            if (isset($row['timestamp']) && $row['timestamp'] === $slot) return $row;
        }
    }
    return null;
}
function appendLog($data) {
    if (!is_dir(LOG_DIR)) mkdir(LOG_DIR, 0755, true);
    file_put_contents(getLogFilePath($data['timestamp']), json_encode($data) . PHP_EOL, FILE_APPEND);
}

// --------------------------------------------------
// BASE CURRENCY
// --------------------------------------------------
static $base_currency_cache = null;
function getBaseCurrency() {
    global $base_currency_cache;
    if ($base_currency_cache) return $base_currency_cache;
    $json = fetch_json(BASE_CONFIG_API);
    $base_currency_cache = strtoupper(trim(
        $json['fox_currency'] ??
        $json['base_currency'] ??
        'USD'
    ));
    return $base_currency_cache;
}

// --------------------------------------------------
// VERIFIED REGISTRY
// --------------------------------------------------
function getVerifiedRegistry(){
    $woocs = fetch_json(WOOCS_API);
    if (!$woocs || !is_array($woocs)) exit('WOOCS unavailable');
    $verified = [];
    foreach ($woocs as $currency => $meta) {
        $currency = strtoupper(trim($currency));
        if ($currency) $verified[] = $currency;
    }
    sort($verified);
    return [
        'verified' => $verified,
        'woocs' => $woocs
    ];
}

// --------------------------------------------------
// GOLD
// --------------------------------------------------
function getGoldUSD(){
    $alpha = fetch_json(
        ALPHA_API . '?function=GOLD_SILVER_SPOT&symbol=GOLD&apikey=' . ALPHA_KEY
    );
    if ($alpha && isset($alpha['price'])) {
        $gold = normalize_rate($alpha['price']);
        if ($gold) return $gold;
    }
    $td = fetch_json(
        TWELVE_API . '/price?symbol=XAU/USD&apikey=' . TWELVE_KEY
    );
    if ($td && isset($td['price'])) {
        $gold = normalize_rate($td['price']);
        if ($gold) return $gold;
    }
    return null;
}

// --------------------------------------------------
// FX RESOLUTION
// --------------------------------------------------
function resolve_fx_rate($currency, $woocs, $exchangeRates, $baseCurrency){
    $currency = strtoupper($currency);

    // WOOCS first
    $woocsRate = normalize_rate($woocs[$currency]['rate'] ?? null);
    if ($woocsRate) return ['rate' => $woocsRate, 'source' => 'woocs'];

    // AlphaVantage
    $alpha = fetch_json(
        ALPHA_API . '?function=CURRENCY_EXCHANGE_RATE&from_currency=' . $baseCurrency . '&to_currency=' . $currency . '&apikey=' . ALPHA_KEY
    );
    $alphaRate = normalize_rate($alpha['Realtime Currency Exchange Rate']['5. Exchange Rate'] ?? null);
    if ($alphaRate) return ['rate' => $alphaRate, 'source' => 'alphavantage'];

    // TwelveData
    $td = fetch_json(
        TWELVE_API . '/exchange_rate?symbol=' . $baseCurrency . '/' . $currency . '&apikey=' . TWELVE_KEY
    );
    $tdRate = normalize_rate($td['rate'] ?? null);
    if ($tdRate) return ['rate' => $tdRate, 'source' => 'twelvedata'];

    // ExchangeRate-API
    $exRate = normalize_rate($exchangeRates[$currency] ?? null);
    if ($exRate) return ['rate' => $exRate, 'source' => 'exchangerate-api'];

    // Fallback
    return ['rate' => 'no-rate', 'source' => 'none'];
}

// --------------------------------------------------
// MAIN ENGINE
// --------------------------------------------------
$slot = getPalmierSlot();
$data = readLog($slot);

if (!$data) {
    $baseCurrency = getBaseCurrency();
    $registry = getVerifiedRegistry();
    $verified = $registry['verified'];
    $woocs = $registry['woocs'];
    $exchangeAPI = fetch_json(EXCHANGE_API_BASE . $baseCurrency);
    $exchangeRates = $exchangeAPI['conversion_rates'] ?? [];
    $goldUSD = getGoldUSD();
    if (!$goldUSD || $goldUSD <= 0) exit('Gold unavailable');
    $usdResolved = resolve_fx_rate('USD', $woocs, $exchangeRates, $baseCurrency);
    $usdRate = $usdResolved['rate'];
    if (!$usdRate || $usdRate === 'no-rate' || !is_numeric($usdRate) || $usdRate <= 0) exit('USD normalization unavailable');
    $goldBase = $goldUSD / $usdRate;

    $live = [];
    $sumGoldBase = 0;
    $sumGoldDisplayed = 0;
    $index = 1;
    foreach ($verified as $currency) {
        $resolved = resolve_fx_rate($currency, $woocs, $exchangeRates, $baseCurrency);
        $rate = $resolved['rate'];
        if (!$rate || $rate === 'no-rate' || !is_numeric($rate) || $rate <= 0) continue;
        $goldPrice = $goldBase * $rate;
        if (!is_numeric($goldPrice) || $goldPrice <= 0 || is_nan($goldPrice) || is_infinite($goldPrice)) continue;
        $live[$currency] = [
            'no' => $index++,
            'currency' => $currency,
            'rate_base' => (float)$rate,
            'rate_source' => $resolved['source'],
            'gold_price' => (float)$goldPrice,
            'gold_price_formatted' => number_format($goldPrice, 4, '.', '')
        ];
        $sumGoldDisplayed += $goldPrice;
        $sumGoldBase += ($goldPrice / $rate);
    }
    $liveCount = count($live);

    // -------- MEAN CALCULATIONS ---------
    $liveGoldAverageDisplayed = $liveCount ? $sumGoldDisplayed / $liveCount : 0; // For display only
    $liveGoldAverageBase = $liveCount ? $sumGoldBase / $liveCount : 0; // For palmier mixing only

    $hist = fetch_json(HISTORICAL_API);
    $histAvg = (float)($hist['historical_gold_average'] ?? 0);
    $histCount = (int)($hist['historical_currencies_count'] ?? 0);
    $osc = fetch_json(OSC_URL);
    $oscillation = ((float)($osc['palmier_max_index'] ?? 0)) - (2 * ((float)($osc['oscillation_index'] ?? 0)));
    $totalCount = $histCount + $liveCount;
    $palmier = $totalCount ? ((($histAvg*$histCount)+($liveGoldAverageDisplayed*$liveCount))*(1+$oscillation))/$totalCount : 0;

    foreach ($live as &$row) {
        $goldPrice = $row['gold_price'];
        $row['palmier_value'] = $goldPrice > 0 ? ($palmier/$goldPrice) : 0;
        $row['palmier_value_formatted'] = '₱' . formatPalmierNumber($row['palmier_value']);
    } unset($row);

    $data = [
        'timestamp' => $slot,
        'base_currency' => $baseCurrency,
        'gold_usd' => $goldUSD,
        'gold_base' => $goldBase,
        'usd_rate' => $usdRate,
        'verified_currency_commodity' => $verified,
        'live_currencies' => $live,
        'live_currencies_count' => $liveCount,
        // Table display
        'live_gold_average' => $liveGoldAverageDisplayed,
        'live_gold_average_formatted' => formatPalmierNumber($liveGoldAverageDisplayed),
        // Palmier logic
        'live_gold_average_for_palmier' => $liveGoldAverageBase,
        'historical_gold_average' => $histAvg,
        'historical_currencies_count' => $histCount,
        'osc' => $oscillation,
        'palmier_value' => $palmier,
        'palmier_value_formatted' => '₱' . formatPalmierNumber($palmier),
        'palmier_value_normalized' => number_format($palmier, 4, '.', ''),
    ];
    appendLog($data);
}

// --------------------------------------------------
// UI PROJECTION LAYER (dropdown rebasing)
// --------------------------------------------------
$ref = strtoupper(trim($_GET['ref'] ?? $data['base_currency']));
if (!isset($data['live_currencies'][$ref])) $ref = $data['base_currency'];
$refRate = $data['live_currencies'][$ref]['rate_base'] ?? 1;
foreach ($data['live_currencies'] as &$row) {
    $row['rate_display'] = ($refRate > 0) ? ($row['rate_base'] / $refRate) : 0;
} unset($row);

// --------------------------------------------------
// ENDPOINTS/API
// --------------------------------------------------
if (isset($_GET['endpoint'])) {
    header('Content-Type: application/json');
    if ($_GET['endpoint'] === 'palmier') {
        echo json_encode([
            'formatted' => $data['palmier_value_formatted'],
            'normalized' => $data['palmier_value_normalized']
        ]);
        exit;
    }
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// --------------------------------------------------
// HTML UI
// --------------------------------------------------
?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"/>
<title>Palmier Live Engine</title>
<style>
body{font-family:Arial,sans-serif;background:#fafafa;padding:20px;}
table{border-collapse:collapse;width:100%;}
th,td{padding:10px;border:1px solid #ddd;}
th{background:#ececec;}
tr:nth-child(even){background:#f8f8f8;}
select{font-size:16px;padding:8px;}
</style>
</head>
<body>
<h2>Palmier Live Engine</h2>
<h3 style="color:gold">
Gold Price (USD): <?=number_format($data['gold_usd'],4)?>
</h3>
<h3 style="color:gold">
Gold Price (<?=$data['base_currency']?>): <?=number_format($data['gold_base'],4)?>
</h3>
<h1 style="color:green">
XAUoz = <?=$data['palmier_value_formatted']?>
</h1>
<b>Oscillation:</b> <?=$data['osc']?><br>
<b>Live Currencies:</b> <?=$data['live_currencies_count']?><br>
<b>Historical Currencies:</b> <?=$data['historical_currencies_count']?><br>
<b>Live Gold Average:</b> <?=$data['live_gold_average_formatted']?><br>
<b>Historical Avg:</b> <?=number_format($data['historical_gold_average'],4)?><br>
<b>Palmier Normalized:</b> <?=$data['palmier_value_normalized']?><br>
<br>
<label><b>Reference Currency:</b></label>
<select id="refCurrency">
<?php foreach ($data['verified_currency_commodity'] as $currency): ?>
    <option value="<?=$currency?>" <?=$currency==$ref?'selected':''?>><?=$currency?></option>
<?php endforeach; ?>
</select>
<br><br>
<table>
<thead>
<tr>
<th>No.</th>
<th>Currency</th>
<th>Exchange Rate (vs <?=$ref?>)</th>
<th>Gold Price</th>
<th>₱ per Currency Unit</th>
<th>FX Source</th>
</tr>
</thead>
<tbody id="tableBody">
<?php foreach ($data['verified_currency_commodity'] as $currency): ?>
<?php if(!isset($data['live_currencies'][$currency])) continue; $row=$data['live_currencies'][$currency]; ?>
<tr>
<td><?=$row['no']?></td>
<td><?=$currency?></td>
<td><?=number_format($row['rate_display'], 8)?></td>
<td><?=number_format($row['gold_price'], 4)?></td>
<td><?=$row['palmier_value_formatted']?></td>
<td><?=$row['rate_source']?></td>
</tr>
<?php endforeach; ?>
<tr style="font-weight:bold;background:#eee">
<td colspan="4" align="right">Live Gold Average</td>
<td colspan="2"><?=$data['live_gold_average_formatted']?></td>
</tr>
</tbody>
</table>
<script>
document.getElementById('refCurrency').addEventListener('change', function(){
    const ref = this.value;
    fetch('?endpoint=json&ref='+encodeURIComponent(ref))
    .then(r=>r.json())
    .then(data=>{
        const refRate=data.live_currencies[ref].rate_base;
        let html='';
        for(const currency of data.verified_currency_commodity){
            if(!(currency in data.live_currencies))continue;
            const row=data.live_currencies[currency];
            const exchange=refRate>0?row.rate_base/refRate:0;
            html+=`
            <tr>
                <td>${row.no}</td>
                <td>${currency}</td>
                <td>${exchange.toFixed(8)}</td>
                <td>${Number(row.gold_price).toFixed(4)}</td>
                <td>${row.palmier_value_formatted}</td>
                <td>${row.rate_source}</td>
            </tr>
            `;
        }
        html+=`<tr style="font-weight:bold;background:#eee"><td colspan="4" align="right">Live Gold Average</td><td colspan="2">${data.live_gold_average_formatted}</td></tr>`;
        document.getElementById('tableBody').innerHTML=html;
    });
});
</script>
<br>
<form method="get">
<input type="hidden" name="endpoint" value="json"/>
<button>Download JSON</button>
</form>
<br>
<form method="get">
<input type="hidden" name="endpoint" value="palmier"/>
<button>Get Palmier Value</button>
</form>
</body>
</html>