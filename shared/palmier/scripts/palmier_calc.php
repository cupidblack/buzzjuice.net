<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('UTC');

//--- CONSTANTS
define('PALMIER_LOG_DIR', dirname(__DIR__).'/gold_prices/');
define('PALMIER_LOG_PREFIX', 'palmier_');
define('WOOCS_API', 'https://buzzjuice.net/wp-json/woocs/v3/currency');
define('ALPHA_API', 'https://www.alphavantage.co/query');
define('ALPHA_KEY', 'UE68Z2WQ6DPIGUKI');
define('TWELVE_API', 'https://api.twelvedata.com');
define('TWELVE_KEY', '28e163b39b84419d91a65ecb60baf47c');
define('EXCHANGE_API', 'https://v6.exchangerate-api.com/v6/fb224de680c5c7c273169a3a/latest/USD');
define('HISTORICAL_API', 'https://buzzjuice.net/shared/palmier/scripts/calc_gold.php?format=json');

//--- HELPERS
function fetch_json($url, $timeout=12){
    $ctx = stream_context_create(['http'=>['timeout'=>$timeout]]);
    $raw = @file_get_contents($url,false,$ctx);
    return $raw ? json_decode($raw,true) : null;
}
function formatPalmierNumber($num){
    $num = number_format($num,4,'.','');
    $parts = explode('.',$num);
    $int = strrev(implode("'",str_split(strrev($parts[0]),4)));
    return $int.'.'.$parts[1];
}
// Mon 06:00/Wed 18:00 "slots"
function getPalmierSlot(){
    $now = new DateTime('now', new DateTimeZone('UTC'));
    $mon = new DateTime('Monday this week 06:00:00', new DateTimeZone('UTC'));
    $wed = new DateTime('Wednesday this week 18:00:00', new DateTimeZone('UTC'));
    if($now >= $wed) return $wed->format('Y-m-d H:i:00');
    if($now >= $mon) return $mon->format('Y-m-d H:i:00');
    return (new DateTime('last Wednesday 18:00:00'))->format('Y-m-d H:i:00');
}
function getLogFile($slot){
    return PALMIER_LOG_DIR . PALMIER_LOG_PREFIX . substr($slot,0,4) . '.log';
}
function readLog($file,$slot){
    if(!file_exists($file)) return null;
    foreach(file($file) as $line){
        $row = json_decode($line,true);
        if(isset($row['timestamp']) && $row['timestamp']===$slot) return $row;
    }
    return null;
}
function appendLog($file,$row){
    $f = fopen($file,'a');
    fwrite($f,json_encode($row).PHP_EOL);
    fclose($f);
}

//--- MAIN PALMIER ENGINE
$slot = getPalmierSlot();
$logFile = getLogFile($slot);
$data = readLog($logFile,$slot);

if(!$data){
    // 1. Get verified currencies/commodities list
    $woocs = fetch_json(WOOCS_API);
    if(!$woocs) exit("WOOCS API unavailable");
    $verified_currency_commodity = [];
    foreach($woocs as $code=>$c){
        $verified_currency_commodity[strtoupper($code)] = true;
    }

    // 2. Fallback FX rates from ExchangeRate-API
    $exRates = [];
    $ex = fetch_json(EXCHANGE_API);
    if(isset($ex['conversion_rates'])) $exRates = $ex['conversion_rates'];

    // 3. Get gold price (USD)
    $goldUSD = null;
    $alpha = fetch_json(ALPHA_API.'?function=GOLD_SILVER_SPOT&symbol=GOLD&apikey='.ALPHA_KEY);
    if(isset($alpha['price'])) $goldUSD = (float)$alpha['price'];
    if(!$goldUSD){
        $td = fetch_json(TWELVE_API.'/price?symbol=XAU/USD&apikey='.TWELVE_KEY);
        if(isset($td['price'])) $goldUSD = (float)$td['price'];
    }
    if(!$goldUSD || $goldUSD<=0) exit("Gold price unavailable");

    // 4. Build live rates table; only verified codes
    $codes = array_keys($verified_currency_commodity);
    sort($codes);
    $live = [];
    $sum = 0;
    $i = 1;
    foreach($codes as $ccy){
        $rate = null;
        if(isset($woocs[$ccy]['rate']) && $woocs[$ccy]['rate']>0)
            $rate = (float)$woocs[$ccy]['rate'];
        elseif(isset($exRates[$ccy]))
            $rate = (float)$exRates[$ccy];
        if(!$rate || $rate<=0) continue;
        $gold_price = $goldUSD * $rate;
        $live[$ccy] = [
            'no' => $i,
            'rate' => $rate,
            'gold_price' => $gold_price // plain
        ];
        $sum += $gold_price;
        $i++;
    }
    $live_currencies_count = count($live);
    $live_gold_average = $live_currencies_count ? $sum/$live_currencies_count : 0;

    // 5. Historical data
    $hist = fetch_json(HISTORICAL_API);
    $hist_avg = $hist['historical_gold_average'] ?? 0;
    $hist_count = $hist['historical_currencies_count'] ?? 0;

    // 6. Weighted Palmier value (in case counts differ)
    $totalCount = $hist_count + $live_currencies_count;
    $palmier = $totalCount ? ($hist_avg*$hist_count + $live_gold_average*$live_currencies_count) / $totalCount : 0;

    // This is "₱ per USD"
    $palmier_per_usd = $goldUSD > 0 ? $palmier / $goldUSD : 0;

    foreach($live as $ccy=>&$row){
        // --- CORRECT: Palmier value for 1 currency unit
        $row['palmier_value'] = $row['rate'] > 0 ? round($palmier_per_usd / $row['rate'], 4) : 0;
        $row['palmier_value_formatted'] = formatPalmierNumber($row['palmier_value']);
    } unset($row);

    $data = [
        'timestamp' => $slot,
        'gold_usd' => $goldUSD,
        'verified_currency_commodity' => array_keys($verified_currency_commodity),
        'live_currencies' => $live,
        'live_currencies_count' => $live_currencies_count,
        'live_gold_average' => $live_gold_average,
        'live_gold_average_formatted' => formatPalmierNumber($live_gold_average),
        'historical_gold_average' => $hist_avg,
        'historical_currencies_count' => $hist_count,
        'palmier_value' => $palmier,
        'palmier_value_formatted' => '₱'.formatPalmierNumber($palmier),
        'palmier_value_normalized' => number_format($palmier,4,'.','')
    ];
    appendLog($logFile,$data);
}

//--- ENDPOINTS
if(isset($_GET['endpoint'])){
    header('Content-Type: application/json');
    if($_GET['endpoint']=='json'){
        echo json_encode($data, JSON_PRETTY_PRINT); exit;
    }
    if($_GET['endpoint']=='palmier'){
        echo json_encode([
            'formatted'                     => $data['palmier_value_formatted'],
            'normalized'                    => $data['palmier_value_normalized'],
            'palmier'                       => $data['palmier_value'],
            'historical_currencies_count'   => $data['historical_currencies_count'],
            'live_currencies_count'         => $data['live_currencies_count'],
            'live_currencies'               => $data['live_currencies']
        ], JSON_PRETTY_PRINT); exit;
    }
}

//--- HTML OUTPUT
echo "<h2>Palmier Live Engine</h2>";
echo "<h3 style='color:gold'>Gold Price (USD): {$data['gold_usd']}</h3>";
echo "<h1 style='color:green'>XAUoz = {$data['palmier_value_formatted']}</h1>";
echo "<b>Live Currencies:</b> {$data['live_currencies_count']}<br>";
echo "<b>Historical Currencies:</b> {$data['historical_currencies_count']}<br>";

echo "<table border=1 cellpadding=6>
<tr>
<th>No.</th>
<th>Currency</th>
<th>Rate</th>
<th>Gold Price</th>
<th>₱ per Currency Unit</th>
</tr>";
foreach($data['live_currencies'] as $ccy=>$c){
    echo "<tr>";
    echo "<td>{$c['no']}</td>";
    echo "<td>$ccy</td>";
    echo "<td>{$c['rate']}</td>";
    echo "<td>{$c['gold_price']}</td>";
    echo "<td>{$c['palmier_value_formatted']}</td>";
    echo "</tr>";
}
echo "<tr style='font-weight:bold;background:#eee'>
<td colspan=4 align=right>Live Gold Average</td>
<td>{$data['live_gold_average_formatted']}</td>
</tr>";
echo "</table>";

?>
<form method='get'>
<input type='hidden' name='endpoint' value='json'/>
<button>Download JSON</button>
</form>
<form method='get'>
<input type='hidden' name='endpoint' value='palmier'/>
<button>Get Palmier Value</button>
</form>