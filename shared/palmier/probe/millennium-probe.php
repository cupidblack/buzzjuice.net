<?php
// Millennium Probe Engine 2026 – Calendar Phase, Log-First, Robust Navigation

define('OSC_URL', 'https://buzzjuice.net/shared/palmier/oscillator/palmier-oscillator.php?oscillation=live');
define('PHASES', 5);
define('BASE_DAYS', 74144.0);
define('LOG_FILE', __DIR__ . '/millennium-probe.log');
define('LOG_TTL', 12345); // seconds; log freshness before re-polling live API
define('MAX_LOG', 180);

// === PHASE BOUNDARIES (CALENDAR BASED, UTC) ===
function getPhaseBoundsTable($year) {
    return [
        [strtotime("$year-01-10 UTC"), strtotime("$year-03-15 23:59:59 UTC")], // 1
        [strtotime("$year-03-16 UTC"), strtotime("$year-05-25 23:59:59 UTC")], // 2
        [strtotime("$year-05-26 UTC"), strtotime("$year-08-05 23:59:59 UTC")], // 3
        [strtotime("$year-08-15 UTC"), strtotime("$year-10-15 23:59:59 UTC")], // 4
        [strtotime("$year-10-16 UTC"), strtotime("$year-12-20 23:59:59 UTC")], // 5
    ];
}
function getPhaseLabels() {
    return [
        "Jan 10 – Mar 15",
        "Mar 16 – May 25",
        "May 26 – Aug 5",
        "Aug 15 – Oct 15",
        "Oct 16 – Dec 20"
    ];
}
function findPhase($now) {
    $phases = getPhaseBoundsTable(gmdate("Y", $now));
    foreach ($phases as $idx => [$start, $end]) {
        if ($now >= $start && $now <= $end) return [$idx, $start, $end, gmdate("Y", $now)];
    }
    // Year wrap: before Phase 1
    if ($now < $phases[0][0]) {
        $prev = getPhaseBoundsTable(gmdate("Y", $now)-1);
        return [PHASES-1, $prev[PHASES-1][0], $prev[PHASES-1][1], gmdate("Y", $now)-1];
    }
    // After Phase 5
    $next = getPhaseBoundsTable(gmdate("Y", $now)+1);
    return [0, $next[0][0], $next[0][1], gmdate("Y", $now)+1];
}

// === LOGGING ===
function read_log() {
    if (!file_exists(LOG_FILE)) return [];
    $d = @json_decode(file_get_contents(LOG_FILE), true);
    return is_array($d) ? $d : [];
}
function write_log($log) {
    if (count($log) > MAX_LOG) $log = array_slice($log, -MAX_LOG);
    @file_put_contents(LOG_FILE, json_encode(array_values($log), JSON_PRETTY_PRINT));
}
function append_log($entry) {
    $log = read_log(); $log[] = $entry; write_log($log);
}
function recent_log($now, $phase_idx, $osc, $ttl = LOG_TTL) {
    foreach (array_reverse(read_log()) as $e) {
        if (
            isset($e['phase_idx']) && $e['phase_idx'] === $phase_idx &&
            abs($e['oscillation_index'] - $osc) < 1e-10 &&
            ($now - $e['timestamp']) < $ttl
        ) return $e;
    }
    return null;
}

// === OSCILLATOR ===
function fetchOscIndex() {
    $json = @file_get_contents(OSC_URL);
    if (!$json) return 0.0;
    $d = @json_decode($json, true);
    return isset($d['oscillation_index']) ? floatval($d['oscillation_index']) : 0.0;
}

// === PROBE FORMULA (quarter-wave, amplitude scales by osc index) ===
function amplitude($osc) {
    return BASE_DAYS * (1.0 + $osc);
}
function probe($t, $ps, $pe, $osc) {
    $A = amplitude($osc);
    $span = max(1, $pe - $ps + 1);
    $u = ($t - $ps) / $span;
    if     ($u < 0.25)  return  $A * sin(($u/0.25)*(M_PI/2));
    elseif ($u < 0.5)   return  $A * cos((($u-0.25)/0.25)*(M_PI/2));
    elseif ($u < 0.75)  return -$A * sin((($u-0.5 )/0.25)*(M_PI/2));
    else                return -$A * cos((($u-0.75)/0.25)*(M_PI/2));
}
function probeToISO($basets, $days) {
    return gmdate("Y-m-d\TH:i:s\Z", $basets + round($days*86400));
}

// === PHASE SELECTION (phase navigation, query param) ===
$now = time();
$labels = getPhaseLabels();
if (isset($_GET['phase'])) {
    $requested = intval($_GET['phase']);
    $year = gmdate("Y", $now);
    $bounds = getPhaseBoundsTable($year);
    $phase_idx = max(0, min(PHASES-1, $requested));
    list($ps, $pe) = $bounds[$phase_idx];
} else {
    list($phase_idx, $ps, $pe, $year) = findPhase($now);
}

// === MAIN PROBE LOGIC (Logging, caching) ===
$osc = fetchOscIndex();
$log = recent_log($now, $phase_idx, $osc);

if ($log) {
    $probe_val = $log['probe_value_days'];
    $probe_iso = $log['probe_iso'];
    $live_probe_timestamp = $log['timestamp'];
} else {
    $probe_val = probe($now, $ps, $pe, $osc);
    $probe_iso = probeToISO($now, $probe_val);
    $live_probe_timestamp = $now;
    append_log([
        "timestamp" => $now,
        "phase_idx" => $phase_idx,
        "phase_label" => $labels[$phase_idx],
        "phase_start" => $ps,
        "phase_end" => $pe,
        "oscillation_index" => $osc,
        "amplitude_days" => amplitude($osc),
        "probe_value_days" => $probe_val,
        "probe_iso" => $probe_iso,
        "now_iso" => gmdate('Y-m-d\TH:i:s\Z', $now)
    ]);
}

// === API ENDPOINT: ?probe=now ===
if (isset($_GET['probe']) && $_GET['probe']=='now') {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        "probe_iso" => $probe_iso,
        "now_iso" => gmdate("Y-m-d\TH:i:s\Z", $now),
        "phase_index" => $phase_idx+1,
        "phase_label" => $labels[$phase_idx],
        "phase_start_iso" => gmdate('Y-m-d\TH:i:s\Z', $ps),
        "phase_end_iso" => gmdate('Y-m-d\TH:i:s\Z', $pe),
        "oscillation_index" => $osc,
        "amplitude_days" => amplitude($osc),
        "probe_value_days" => $probe_val
    ]);
    exit;
}

// === PHASE & YEAR GRAPH POINTS (calendar-anchored sampling) ===
$PLEN = 180; // phase curve resolution
$phasePoints = [];
$span = $pe - $ps + 1;
for ($i=0; $i<=$PLEN; ++$i) {
    $t = $ps + intval($i * $span / $PLEN);
    $u = ($t - $ps) / $span;
    $phasePoints[] = [
        "x"    => $u,
        "y"    => probe($t, $ps, $pe, $osc),
        "date" => gmdate("Y-m-d", $t)
    ];
}
$now_u = ($now - $ps) / $span;

// For full year: calendar phases
$yearPoints = [];
$yphases = getPhaseBoundsTable($year);
list($ys, $ye) = [ $yphases[0][0], $yphases[PHASES-1][1] ];
$yd = $ye - $ys + 1;
foreach ($yphases as $pidx => $PH) {
    list($s2, $e2) = $PH;
    $pd2 = $e2 - $s2 + 1;
    for ($j = 0; $j <= 36; ++$j) {
        $t2 = $s2 + intval($j * $pd2 / 36);
        $xu = ($t2 - $ys) / $yd;
        $yearPoints[] = [
            "x"    => $xu,
            "y"    => probe($t2, $s2, $e2, $osc),
            "date" => gmdate("Y-m-d", $t2)
        ];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Millennium Probe</title>
<meta name="viewport" content="width=1100">
<style>
body { background: #131b29; color:#fff;font-family:system-ui,Arial,sans-serif;margin:0;}
.container { max-width: 1100px; margin:40px auto 0; background:#161f34; border-radius:14px; padding:38px 36px; box-shadow:0 0 16px #111b2a88;}
.main-title { font-size:2.18rem;letter-spacing:-.01em;font-weight:bold;margin-bottom:2px; }
.phase-title { font-size:1.12rem;color:#79ecff;text-shadow:0 1px 6px #008aee1e;}
.meta   { font-size:.97rem;margin-bottom:0px; opacity:.88;}
.probeval { font-size: 1.06rem; }
.stats-row{display:flex;gap:30px;flex-wrap:wrap;}
.stat-label{color:#79ecff;margin-right:3px;}
.phase-label-hl {color:#fff818;}
.canvas-wrap{overflow:hidden;padding:10px;background:#111b2c;border-radius:14px;}
canvas { background:#161d2d; display:block; border-radius:8px; margin:0 auto;}
button {
    background: linear-gradient(90deg,#005a96 0,#26bfff 95%);
    border:none;padding:12px 22px;margin:4px;
    border-radius:8px;font-size:1.03rem;font-weight:bold;
    color:#fff;cursor:pointer;box-shadow:0 2px 3px #0003;transition:.16s;
}
button:hover{ background:#14a9d8; }
.slide-left{animation:slidel 0.32s;}
.slide-right{animation:slider 0.32s;}
@keyframes slidel {from{transform:translateX(-64px);opacity:0;} to{transform:translateX(0);opacity:1;}}
@keyframes slider {from{transform:translateX(64px);opacity:0;} to{transform:translateX(0);opacity:1;}}
</style>
</head>
<body>
<div class="container" id="container">
    <div class="main-title">Millennium Probe Engine</div>
    <div class="phase-title">
        <b>Phase <?= ($phase_idx+1) ?> of <?= PHASES ?>:</b>
        <?= $labels[$phase_idx] ?>
    </div>
    <div class="stats-row meta">
        <div><span class="stat-label">Phase Start:</span> <?= gmdate('Y-m-d\TH:i:s\Z', $ps) ?></div>
        <div><span class="stat-label">Phase End:</span> <?= gmdate('Y-m-d\TH:i:s\Z', $pe) ?></div>
    </div>
    <div class="stats-row meta">
        <div><span class="stat-label">Now:</span> <?= gmdate('Y-m-d\TH:i:s\Z', $now) ?></div>
        <div><span class="stat-label">Oscillation Index:</span> <?= round($osc,8) ?></div>
        <div><span class="stat-label">Amplitude:</span> <?= number_format(amplitude($osc),2) ?> days (~<?= ceil(amplitude($osc)/365) ?> years)</div>
    </div>
    <div class="meta"><span class="stat-label">Current Probe x (days):</span> <b><?= round($probe_val,4) ?></b></div>
    <div class="probeval"><span class="stat-label">Probe mapped ISO date:</span> <b id="live-probe-iso"><?= $probe_iso ?></b></div>

    <?php if ($phase_idx > 0) { ?>
        <button onclick="shiftPhase(-1)">← Previous</button>
    <?php } ?>
    <?php if ($phase_idx < (PHASES-1)) { ?>
        <button onclick="shiftPhase(1)">Next →</button>
    <?php } ?>

    <br><br>
    <div class="phase-label-hl">Current Phase Probe Graph</div>
    <div class="meta">
        Y axis: ±<?= number_format(BASE_DAYS,0) ?>d &nbsp; X axis: phase days.
    </div>
    <div class="canvas-wrap"><canvas id="phase-graph" width="950" height="290"></canvas></div>
    <br>
    <div class="phase-label-hl">Full Year (all 5 Phases)</div>
    <div class="canvas-wrap"><canvas id="year-graph" width="950" height="160"></canvas></div>
    <div class="meta">X axis shows calendar-normalized year; Y shows probe value (days).</div>
</div>
<script>
const PHASE_POINTS = <?= json_encode($phasePoints) ?>;
const YEAR_POINTS  = <?= json_encode($yearPoints) ?>;
const phaseIdx = <?= $phase_idx ?>;
const PHASES = <?= PHASES ?>;
const cursorX = <?= json_encode($now_u) ?>;
const cursorY = <?= json_encode($probe_val) ?>;

function arrayMin(arr,k){ return Math.min.apply(null, arr.map(e=>e[k])); }
function arrayMax(arr,k){ return Math.max.apply(null, arr.map(e=>e[k])); }

function drawGraph(canvas, points, opts) {
    let ctx = canvas.getContext("2d");
    let W = canvas.width, H = canvas.height;
    let mL=54, mR=8, mT=18, mB=36;

    ctx.clearRect(0,0,W,H);

    // Axes
    ctx.strokeStyle="#4467bb"; ctx.lineWidth=1.5;
    ctx.beginPath(); ctx.moveTo(mL,H-mB); ctx.lineTo(W-mR,H-mB); ctx.stroke();
    ctx.beginPath(); ctx.moveTo(mL,H-mB); ctx.lineTo(mL,mT); ctx.stroke();

    // Y labels/ticks (approx ±A)
    let yA  = <?= BASE_DAYS ?> * 1.5;
    let stepY = (arrayMax(points,"y") - arrayMin(points,"y"))>140000 ? 50000 : 25000;
    for (let y=-yA; y<=+yA; y+=stepY) {
        let yy = mT + (H-mB-mT)*(1-(y+yA)/(2*yA));
        ctx.strokeStyle="#444aee"; ctx.globalAlpha=(y==0)?0.48:0.21;
        ctx.beginPath(); ctx.moveTo(mL, yy); ctx.lineTo(W-mR, yy); ctx.stroke();
        ctx.globalAlpha=0.95; ctx.fillStyle="#ffe";
        ctx.font="12px Arial";
        ctx.fillText((y>0?"+":"") + y + "d", 9, yy+4);
    }

    // X labels
    let N = opts && opts.xTickCount ? opts.xTickCount : 5;
    let steps = points.length-1;
    let q = Math.round(steps/4), q2 = Math.round(steps/2), q3 = Math.round(steps*3/4);
    let xLabels = opts && opts.xLabels ? opts.xLabels : ["Start","¼","½","¾","End"];
    let datelabels = [
        points[0].date,
        points[q].date,
        points[q2].date,
        points[q3].date,
        points[steps].date,
    ];
    if(opts && opts.xDateLabels) xLabels = opts.xDateLabels;
    else xLabels = datelabels;
    for (let i=0; i<N; ++i) {
        let x = mL + (W-mL-mR)*i/(N-1);
        ctx.strokeStyle="#4467bb"; ctx.globalAlpha=0.45;
        ctx.beginPath(); ctx.moveTo(x, H-mB); ctx.lineTo(x, H-mB+10); ctx.stroke();
        ctx.globalAlpha=1; ctx.fillStyle="#fff980"; ctx.font="13px Arial";
        ctx.fillText(xLabels[i], x-16, H-mB+24);
    }

    // Draw line
    ctx.beginPath();
    for (let i=0; i<points.length; ++i) {
        let px = mL + (W-mL-mR)*points[i].x;
        let py = mT + (H-mB-mT)*(1-(points[i].y+yA)/(2*yA));
        if(i===0) ctx.moveTo(px, py);
        else     ctx.lineTo(px, py);
    }
    ctx.strokeStyle = opts && opts.graphColor ? opts.graphColor : "#59f6fa"; ctx.lineWidth=2.3;
    ctx.globalAlpha=0.96;
    ctx.shadowColor="#19aeea52"; ctx.shadowBlur=8;
    ctx.stroke();
    ctx.shadowBlur=0;
    ctx.globalAlpha=1.0;

    // Marker for current live probe x (if present)
    if(opts && opts.showCursor && typeof opts.probeNowX === "number"){
        let px = mL + (W-mL-mR)*opts.probeNowX;
        let py = mT + (H-mB-mT)*(1-(opts.probeNowY+yA)/(2*yA));
        ctx.beginPath(); ctx.arc(px,py,7,0,7); ctx.globalAlpha=.88; ctx.fillStyle="#ff3c99"; ctx.fill();
        ctx.globalAlpha=1;
        ctx.font="15px Arial"; ctx.fillStyle="#fff";
        ctx.fillText("Live", px+12, py-12);
    }
}

window.addEventListener('DOMContentLoaded',()=>{
    let canvas1 = document.getElementById("phase-graph");
    let steps = PHASE_POINTS.length-1;
    let q = Math.round(steps/4), q2 = Math.round(steps/2), q3 = Math.round(steps*3/4);
    let dates = [
        PHASE_POINTS[0].date,
        PHASE_POINTS[q].date,
        PHASE_POINTS[q2].date,
        PHASE_POINTS[q3].date,
        PHASE_POINTS[steps].date,
    ];
    drawGraph(canvas1, PHASE_POINTS, {
        graphColor:"#31f8f8", showCursor:true,
        probeNowX:<?=json_encode($now_u)?>,
        probeNowY:<?=json_encode($probe_val)?>,
        xDateLabels:dates
    });
    let canvas2 = document.getElementById("year-graph");
    drawGraph(canvas2, YEAR_POINTS, {
        graphColor:"#ffe270",
        xTickCount:6,
        xLabels:["Start","1/5","2/5","3/5","4/5","End"]
    });
});

function shiftPhase(dir) {
    let body = document.body;
    if (dir > 0) body.classList.add("slide-right");
    else body.classList.add("slide-left");
    let next = <?= json_encode($phase_idx) ?> + dir;
    setTimeout(()=>{window.location.href="?phase="+next;},140);
}

// Live ISO updating
setInterval(()=>{
    fetch('millennium-probe.php?probe=now')
    .then(r=>r.json()).then(o=>{
        document.getElementById('live-probe-iso').textContent = o.probe_iso || "";
    });
}, 9100);
</script>
</body>
</html>