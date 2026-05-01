<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| buzzjuice.net/shared/palmier/oscillator/palmier-oscillator.php
| Palmier Oscillator Engine – as specified (2026).
|--------------------------------------------------------------------------
| - Computes Palmier, Solar, Lunar, Passover variances.
| - Smooth curve transitions, aligned to actual calendar anchors.
| - Outputs:
|   - Live endpoint (?oscillation=live, returns JSON with all values)
|   - Interactive HTML UI: Current values + 5 graphs (each with week-slider)
| - Caches last calculation in log file, reduces compute/bandwidth footprint.
| - All values to 5 decimal precision.
| - Graphs and sliders client-side; backend log for performance.
|--------------------------------------------------------------------------
*/

define('PALMIER_MAX', 8.03787);      // Max Palmier variance %
define('LUNAR_MAX',   10.98000);     // Max lunar variance %
define('SOLAR_MAX',   3.35600);      // Earth's eccentricity ~3.356%
define('LOG_FILE',    __DIR__ . '/palmier-oscillator.log');
define('LOG_INTERVAL', 12345);       // seconds between recompute/log
define('WEEKS', 52);

// -- Time & Utility
function now() { return time(); }
function day_of_year($t = null): int { return (int)date('z', $t ?? now()) + 1; }
function year_phase($t = null): float {
    $t = $t ?? now();
    $start = strtotime(date('Y-01-01 00:00:00', $t));
    $end   = strtotime(date('Y-12-31 23:59:59', $t));
    return ($t - $start) / max(1, $end - $start);
}
function smooth($x): float { return (1 - cos(M_PI * $x)) / 2; }

// -- 1. Palmier Variance (custom calendar anchors)
function palmier_variance($t = null): float {
    $t = $t ?? now();
    $d = day_of_year($t);

    // Anchors: [day-of-year => value: 0=min, 1=max]
    $anchors = [
        213 => 0,   // Aug start (Aug 1)           0%
        258 => 1,   // Mid Sep (Sep 15)            Max%
        288 => 0,   // Mid Oct (Oct 15)            0%
        319 => 1,   // Mid Nov (Nov 15)            Max%
        365 => 0,   // Dec end (Dec 31)            0%
        46  => 1,   // Mid Feb (Feb 15)            Max%
        74  => 0,   // Mid Mar (Mar 15)            0%
        105 => 1,   // Mid Apr (Apr 15)            Max%
        151 => 0,   // May end (May 31)            0%
        182 => 1    // Mid Year (July 1)           Max%
    ];
    ksort($anchors);
    $keys = array_keys($anchors);

    for ($i = 0; $i < count($keys); $i++) {
        $a = $keys[$i];
        $b = $keys[($i + 1) % count($keys)];
        $va = $anchors[$a];
        $vb = $anchors[$b];
        // wrap-through for new year
        if ($b < $a) $b += 365;
        $d_mod = $d < $a ? $d + 365 : $d;
        if ($d_mod >= $a && $d_mod <= $b) {
            $f = ($d_mod - $a) / max(1e-9, ($b - $a));
            $s = smooth($f);
            $pv = ($va + ($vb - $va) * $s) * PALMIER_MAX;
            return round($pv, 5);
        }
    }
    return 0.00000;
}

// -- 2. Solar Variance (Earth perihelion/aphelion drift)
//    Max drift ≈ 3.356% (January ~ 0%, July ~ Max)
function solar_variance($t = null): float {
    $d = day_of_year($t ?? now());
    // Perihelion Jan 3 (3rd day), aphelion July 4 (185th)
    $phase = (($d - 3 + 365) % 365) / 365;
    $w = 0.5 - 0.5 * cos(2 * M_PI * $phase);
    return round($w * SOLAR_MAX, 5);
}

// -- 3. Lunar Variance (Full/New, apogee/perigee overlap)
function lunar_variance($t = null): float {
    $t = $t ?? now();
    $days = (year_phase($t) * 365.2422);
    $synodic = 29.530588;  // mean lunar month in days
    $phase = fmod($days, $synodic) / $synodic;
    $w = 0.5 - 0.5 * cos(2 * M_PI * $phase);
    return round($w * LUNAR_MAX, 5);
}

// -- 4. Passover Variance (March 25–April 25 passover start, end can be up to May 5)
function passover_window($year): array {
    $m = $year % 19;
    $start = 84 + round(($m / 19) * 31);  // Mar 25(84) → Apr 25(115)
    $end   = 90 + round(($m / 19) * 36);  // Mar 31(90) → May 5(126)
    return [$start, $end];
}
function passover_variance($t = null): float {
    $t = $t ?? now();
    $year = (int)date('Y', $t);
    $d = day_of_year($t);
    [$start, $end] = passover_window($year);
    if ($d < $start || $d > $end) return 0.00000;
    $x = ($d - $start) / max(1, $end - $start);
    // Rises at Passover start, max at midpoint, falls at end
    return round(sin(M_PI * $x) * PALMIER_MAX, 5);
}

// -- 5. Oscillation (normalize all)
function norm($v, $max): float {
    return ($max > 0) ? min(1, $v / $max) : 0;
}
function palmier_oscillation($t = null): float {
    $lv = lunar_variance($t);
    $sv = solar_variance($t);
    $pv = palmier_variance($t);
    $psv = passover_variance($t);
    $avg = (norm($lv, LUNAR_MAX) + norm($sv, SOLAR_MAX) +
            norm($pv, PALMIER_MAX) + norm($psv, PALMIER_MAX)) / 4;
    return round($avg * PALMIER_MAX, 5);
}

// -- 6. Log: Only update if new period
function read_log() {
    if (!file_exists(LOG_FILE)) return null;
    $lines = file(LOG_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) return null;
    $last = json_decode(end($lines), true);
    if (!$last || !isset($last['timestamp'])) return null;
    if (time() - $last['timestamp'] < LOG_INTERVAL) return $last;
    return null;
}
function write_log($arr) {
    file_put_contents(LOG_FILE, json_encode($arr) . "\n", FILE_APPEND | LOCK_EX);
}
function build_data($t = null) {
    $t = $t ?? time();
    $osc = palmier_oscillation($t);
    return [
        'timestamp' => $t,
        'datetime'  => date('Y-m-d H:i:s', $t),
        'lunar_variance'    => lunar_variance($t),
        'solar_variance'    => solar_variance($t),
        'palmier_max'       => PALMIER_MAX,
        'palmier_max_index' => round(PALMIER_MAX/100, 10),
        'palmier_variance'  => palmier_variance($t),
        'passover_variance' => passover_variance($t),
        'oscillation'       => $osc,
        'oscillation_index' => round($osc/100, 10)
    ];
}

// -- 7. Check log: Load or update
$data = read_log();
if (!$data) {
    $data = build_data();
    write_log($data);
}

// -- 8. API Endpoint
if (isset($_GET['oscillation']) && $_GET['oscillation'] === 'live') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// -- 9. Generate data for graphs (week = 0-51)
function graph_points($fn) {
    $arr = [];
    for ($w = 0; $w < WEEKS; $w++) {
        $phase = ($w + 0.5) / WEEKS;
        $t = strtotime(date('Y-01-01') . " +".intval($phase*365)." days");
        $arr[] = round(call_user_func($fn, $t), 5);
    }
    return $arr;
}
$lunar_graph    = graph_points('lunar_variance');
$solar_graph    = graph_points('solar_variance');
$palmier_graph  = graph_points('palmier_variance');
$passover_graph = graph_points('passover_variance');
$osc_graph      = graph_points('palmier_oscillation');

function chart_section($label, $id, $graph, $color) {
    ?>
    <div class="chart-block" style="margin-bottom:36px">
        <b><?=$label?></b>
        <canvas id="canvas-<?=$id?>" height="105"></canvas>
        <div class="slider-block" style="display:flex;align-items:center;gap:1em;margin:8px 0 0 0;">
            <input type="range" id="slider-<?=$id?>" min="0" max="<?=WEEKS-1?>" value="<?=(int)date('W')?>" step="1" class="slider" style="width:60%;">
            <span>Week: <span id="week-<?=$id?>"><?=((int)date('W'))+1?></span></span>
            <span class="readout">Value: <span id="val-<?=$id?>"><?=number_format($graph[(int)date('W')],5)?></span>%</span>
        </div>
    </div>
    <script>
    (function(){
        var graph=<?=json_encode($graph)?>,id='<?=$id?>',color='<?=$color?>';
        var ctx=document.getElementById('canvas-'+id).getContext('2d');
        var chart=new Chart(ctx,{
            type:'line',
            data: {
                labels:[...Array(<?=WEEKS?>).keys()].map(x=>x+1),
                datasets:[{label:"<?=$label?>",data:graph,fill:false,borderColor:color,backgroundColor:color}]
            },
            options:{
                plugins:{legend:{display:false}},
                scales:{y:{beginAtZero:true}}
            }
        });
        var slider=document.getElementById('slider-'+id),
            week=document.getElementById('week-'+id),
            val=document.getElementById('val-'+id);
        slider.addEventListener('input',function(){
            week.textContent=(+this.value)+1;
            val.textContent=graph[+this.value].toFixed(5);
        });
    })();
    </script>
    <?php
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Palmier Oscillator</title>
    <meta charset="utf-8">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {background: #18192b; color: #fff; font-family: Arial, monospace;}
        h2 {color: #ffcc00;}
        .chart-block {margin-bottom:38px;}
        .slider-block {margin-bottom:18px;}
        .readout {min-width:80px;}
        a {color:#ffb849;}
        b {color:#ffe87c;}
        .slider {accent-color: #ffe87c;}
    </style>
</head>
<body>
<h2>Palmier Oscillator Engine</h2>
<p>
    <b>Current Date/Time:</b> <?=htmlspecialchars($data['datetime'])?><br>
    <b>Palmier Oscillation:</b> <?=number_format($data['oscillation'],5)?>%<br>
    Lunar: <?=number_format($data['lunar_variance'],5)?>%<br>
    Solar: <?=number_format($data['solar_variance'],5)?>%<br>
    Palmier: <?=number_format($data['palmier_variance'],5)?>%<br>
    Passover: <?=number_format($data['passover_variance'],5)?>%<br>
    <small>API endpoint: <a href="?oscillation=live">?oscillation=live</a></small>
</p>
<?php
chart_section('Lunar Cycle Variance (%)',  'lunar',    $lunar_graph,    '#66d6fa');
chart_section('Solar Cycle Variance (%)',  'solar',    $solar_graph,    '#ffb84b');
chart_section('Passover Cycle Variance (%)','passover', $passover_graph,'#6cf671');
chart_section('Palmier Variance (%)',      'palmier',  $palmier_graph,  '#f47cfa');
chart_section('Palmier Oscillation (%)',   'osc',      $osc_graph,      '#ffdc59');
?>
</body>
</html>