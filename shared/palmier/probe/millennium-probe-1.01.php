<?php
// millennium-probe.php

define('OSC_URL', 'https://buzzjuice.net/shared/palmier/oscillator/palmier-oscillator.php?oscillation=live');
define('PHASES_PER_YEAR', 5);
define('BASE_AMPLITUDE_DAYS', 74144);

function fetchOscillation() {
    $json = @file_get_contents(OSC_URL);
    $osc = 0.0;
    if ($json) {
        $d = json_decode($json, true);
        if (isset($d['oscillation'])) $osc = floatval($d['oscillation']);
    }
    return $osc;
}

function getYearBounds($ts) {
    $y = intval(gmdate("Y", $ts));
    $start = gmmktime(0,0,0,1,1,$y);
    $end   = gmmktime(0,0,0,1,1,$y+1) - 1;
    return [$start, $end];
}

function getPhaseDuration($ts) {
    list($start, $end) = getYearBounds($ts);
    return ($end - $start + 1) / PHASES_PER_YEAR;
}

function getPhaseIndex($ts) {
    list($start, $end) = getYearBounds($ts);
    $pd = getPhaseDuration($ts);
    $idx = floor(($ts - $start) / $pd);
    return max(0, min(PHASES_PER_YEAR - 1, intval($idx)));
}

function getPhaseBounds($phaseIdx, $ts) {
    list($ystart, $yend) = getYearBounds($ts);
    $pd = getPhaseDuration($ts);
    $pstart = $ystart + floor($phaseIdx * $pd);
    $pend   = $pstart + floor($pd) - 1;
    return [$pstart, $pend];
}

function probeAmplitude($osc) {
    return BASE_AMPLITUDE_DAYS * (1 + $osc / 100.0);
}

// Piecewise probe function to match requested trajectory
function probeValue($t, $pstart, $pduration, $osc) {
    $A = probeAmplitude($osc);
    $u = ($t - $pstart) / $pduration; // [0,1]
    if ($u < 0.25) { // 0→+A (first quarter)
        $x = $u / 0.25; // 0→1
        return $A * sin($x * M_PI/2);
    } elseif ($u < 0.5) { // +A→0 (second quarter)
        $x = ($u-0.25)/0.25; // 0→1
        return $A * cos($x * M_PI/2);
    } elseif ($u < 0.75) { // 0→-A (third quarter)
        $x = ($u-0.5)/0.25; // 0→1
        return -$A * sin($x * M_PI/2);
    } else { // -A→0 (fourth quarter)
        $x = ($u-0.75)/0.25; // 0→1
        return -$A * cos($x * M_PI/2);
    }
}

// Convert probe x value (in days) from "now" to ISO8601
function probeToISO($basets, $probeDays) {
    return gmdate('Y-m-d\TH:i:s\Z', intval(round($basets + $probeDays * 86400)));
}
function phaseLabel($idx) {
    $labels = [
        "Early/Mid January → Mid March",
        "Mid March → Mid/Late May",
        "Late May/Early June → End of July/Early August",
        "Mid August → Mid October",
        "Mid October → Mid/Late December"
    ];
    return $labels[$idx] ?? "Phase " . ($idx+1);
}

// ========== API endpoint for live value ==============
if (isset($_GET['position']) && $_GET['position'] === 'live') {
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json; charset=UTF-8');
    $now = time();
    $osc = fetchOscillation();
    $phaseIdx = getPhaseIndex($now);
    list($pstart, $pend) = getPhaseBounds($phaseIdx, $now);
    $pduration = $pend - $pstart + 1;

    $probe_x = probeValue($now, $pstart, $pduration, $osc);
    $probe_iso = probeToISO($now, $probe_x);

    echo json_encode([
        "timestamp_now"       => $now,
        "iso_now"             => gmdate('Y-m-d\TH:i:s\Z', $now),
        "phase_index"         => $phaseIdx + 1,
        "phase_count"         => PHASES_PER_YEAR,
        "oscillation_percent" => $osc,
        "amplitude_days"      => probeAmplitude($osc),
        "probe_value_days"    => $probe_x,
        "probe_iso"           => $probe_iso
    ]);
    exit;
}

// ========== Otherwise, render full HTML UI ==============
header("Content-Type: text/html; charset=UTF-8");

// == Phase selection handling
$osc = fetchOscillation();
$now = time();
$phaseIdx = isset($_GET['phase']) ? intval($_GET['phase']) : getPhaseIndex($now);
$phaseIdx = max(0, min(PHASES_PER_YEAR - 1, $phaseIdx));
list($pstart, $pend) = getPhaseBounds($phaseIdx, $now);
$pduration = $pend - $pstart + 1;

$phase_start_iso = gmdate('Y-m-d\TH:i:s\Z', $pstart);
$phase_end_iso   = gmdate('Y-m-d\TH:i:s\Z', $pend);

$probe_x = probeValue($now, $pstart, $pduration, $osc);
$probe_iso = probeToISO($now, $probe_x);

$PHASE_GRAPH_POINTS = [];
$PHASE_STEPS = 180;
for ($i=0; $i<=$PHASE_STEPS; ++$i) {
    $t = $pstart + intval(round($i*$pduration/$PHASE_STEPS));
    $u = ($t - $pstart) / $pduration;
    $PV = probeValue($t, $pstart, $pduration, $osc);
    $PHASE_GRAPH_POINTS[] = ['x'=>$u, 'y'=>$PV, 'date'=>gmdate('Y-m-d',$t)];
}

$YEAR_GRAPH_POINTS = [];
list($ystart, $yend) = getYearBounds($now);
$yduration = $yend - $ystart + 1;
for ($p=0; $p<PHASES_PER_YEAR; ++$p) {
    list($ps, $pe) = getPhaseBounds($p, $now);
    $pd = $pe - $ps + 1;
    for ($i=0; $i<=60; ++$i) {
        $t = $ps + intval(round($i*$pd/60));
        $xu = ($t - $ystart) / $yduration;
        $PV = probeValue($t, $ps, $pd, $osc);
        $YEAR_GRAPH_POINTS[] = ['x'=>$xu, 'y'=>$PV, 'date'=>gmdate('Y-m-d',$t)];
    }
}

$now_u = ($now - $pstart) / $pduration;
$probe_y = $probe_x;

?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Millennium Probe</title>
    <meta name="viewport" content="width=1100">
    <style>
        body { background: #131b29; color:#fff;font-family:system-ui,Arial,sans-serif;margin:0; }
        .container { max-width: 1100px; margin:40px auto 0; background:#161f34; border-radius:14px; padding:38px 36px 38px 36px; box-shadow:0 0 16px #111b2a88;}
        .header { display:flex; justify-content:space-between; gap:24px; align-items:flex-start; }
        .meta   { font-size:1rem;margin-bottom:9px; opacity:.88;}
        .main-title { font-size:2.2rem;letter-spacing:-.01em;font-weight:bold;margin-bottom:4px; }
        .phase-title {font-size:1.13rem;color:#79ecff;text-shadow:0 1px 6px #008aee1e;}
        .probeval   {font-size:1.07rem;}
        .phaserange {font-size:.97rem;margin-top:-10px;color:#ffc84c; }
        .stats {margin-bottom:10px;}
        button {
            background: linear-gradient(90deg,#005a96 0,#1994e6 95%);
            border:none;padding:12px 22px;margin:4px;
            border-radius:8px;font-size:1.03rem;font-weight:bold;
            color:#fff;cursor:pointer;box-shadow:0 2px 3px #0003;transition:.16s;
        }
        button:hover { background:#1385d8; }
        .canvas-wrap {overflow:hidden;padding:10px;background:#131f29;border-radius:14px;}
        canvas {
            background:#111a2c; display:block; margin:0 auto; border-radius:8px;
            box-shadow:0 0 10px #00244c38;
        }
        .slide-left{animation:slidel 0.36s;}
        .slide-right{animation:slider 0.36s;}
        @keyframes slidel {from{transform:translateX(-64px);opacity:0;} to{transform:translateX(0);opacity:1;}}
        @keyframes slider {from{transform:translateX(64px);opacity:0;} to{transform:translateX(0);opacity:1;}}
        .phase-label-hl {color:#fff818;}
        .stats-row {display:flex; gap:35px;flex-wrap:wrap;}
        .stat-label {color:#79ecff;margin-right:3px;}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
    <div>
        <div class="main-title">Millennium Probe Engine</div>
        <div class="phase-title">Phase <?= ($phaseIdx+1) ?> of <?= PHASES_PER_YEAR ?>
            <span class="phaserange"><?= phaseLabel($phaseIdx) ?></span>
        </div>
        <div class="meta">
            Start: <b><?= $phase_start_iso ?></b> &nbsp;&nbsp; End: <b><?= $phase_end_iso ?></b>
        </div>
        <div class="meta stats-row">
            <div><span class="stat-label">Now:</span> <?= gmdate('Y-m-d\TH:i:s\Z', $now) ?></div>
            <div><span class="stat-label">Oscillation:</span> <?= round($osc,4) ?>&#37;</div>
            <div><span class="stat-label">Amplitude:</span> <?= round(probeAmplitude($osc),3) ?> days</div>
        </div>
        <div class="meta">
            <span class="stat-label">Current Probe x-axis:</span>
            <b><?= round($probe_x,2) ?> days</b>
        </div>
        <div class="probeval">
            <span class="stat-label">Probe mapped ISO date:</span>
            <b><?= $probe_iso ?></b>
        </div>
    </div>
    <div>
        <?php if ($phaseIdx>0) { ?>
            <button onclick="shiftPhase(-1)">← Previous</button>
        <?php } ?>
        <?php if ($phaseIdx<(PHASES_PER_YEAR-1)) { ?>
            <button onclick="shiftPhase(1)">Next →</button>
        <?php } ?>
    </div>
    </div>
    <br>

    <div class="phase-label-hl">Current Phase Probe Graph</div>
    <div class="meta">
        Probe Y: +75,000 days to -75,000 days (approx. ±205 years). X axis: phase duration (<?= gmdate('Y-m-d',$pstart) ?> to <?= gmdate('Y-m-d',$pend) ?>).
    </div>
    <div class="canvas-wrap"><canvas id="phase-graph" width="980" height="310"></canvas></div>

    <br><br>

    <div class="phase-label-hl">Full Cycle: Full Year (5 Phases)</div>
    <div class="canvas-wrap"><canvas id="year-graph" width="980" height="220"></canvas></div>
    <div class="meta">X axis shows normalized year progression; Y shows mapped probe value (days, ±75,000).</div>
</div>

<script>
const PHASE_POINTS = <?= json_encode($PHASE_GRAPH_POINTS) ?>;
const YEAR_POINTS  = <?= json_encode($YEAR_GRAPH_POINTS) ?>;

function drawGraph(ctx, points, opts) {
    const w = ctx.canvas.width, h = ctx.canvas.height;
    const mL=55, mR=12, mT=20, mB=38;
    ctx.clearRect(0,0, w,h);
    ctx.strokeStyle="#398fff"; ctx.globalAlpha=1; ctx.lineWidth=1;
    ctx.beginPath();
    ctx.moveTo(mL, h-mB); ctx.lineTo(w-mR, h-mB);
    ctx.moveTo(mL, h-mB); ctx.lineTo(mL,mT); ctx.stroke();

    // Y ticks
    let stepY = 25000;
    for (let yVal=-75000; yVal<=75000; yVal+=stepY) {
      let y = mT + (h-mB-mT) * (1-(yVal+75000)/150000);
      ctx.strokeStyle="#464bea"; ctx.lineWidth=(yVal===0)?2:1; ctx.globalAlpha=(yVal===0)?0.38:0.22;
      ctx.beginPath(); ctx.moveTo(mL, y);ctx.lineTo(w-mR, y);ctx.stroke();

      ctx.globalAlpha=0.8; ctx.fillStyle="#e3f6ff"; ctx.font="12px Arial";
      ctx.fillText((yVal>0?"+":"") + yVal + "d", 9, y+4);
    }

    // X ticks/labels
    let xticks=opts.xTickCount || 5;
    let xLabels=opts.xLabels || ["Start","¼","½","¾","End"];
    let xLabY=h-mB+22;
    for (let i=0;i<xticks;++i) {
        let x = mL + (w-mL-mR)*i/(xticks-1);
        ctx.strokeStyle="#466cab"; ctx.lineWidth=1; ctx.globalAlpha=0.53;
        ctx.beginPath(); ctx.moveTo(x,h-mB); ctx.lineTo(x,h-mB+12); ctx.stroke();
        ctx.globalAlpha=0.87; ctx.fillStyle="#fff980";
        let label=xLabels[i];
        if (opts.xDateLabels && opts.xDateLabels[i]) label=opts.xDateLabels[i];
        ctx.font="13px Arial"; ctx.fillText(label, x-14, xLabY);
    }
    ctx.globalAlpha=1.0;

    // Graph path
    ctx.beginPath();
    for(let i=0; i<points.length; ++i) {
        let px = mL+(w-mL-mR)*points[i].x;
        let py = mT+(h-mB-mT)*(1-(points[i].y+75000)/150000);
        if (i===0) ctx.moveTo(px,py);
        else ctx.lineTo(px,py);
    }
    ctx.strokeStyle=opts.graphColor || "#00faff"; ctx.lineWidth=2.8; ctx.globalAlpha=0.94;
    ctx.shadowColor="#19aeea44"; ctx.shadowBlur=10;
    ctx.stroke();
    ctx.shadowBlur=0;

    // Marker for current live probe (if provided)
    if (opts && opts.showCursor && typeof opts.probeNowX === "number") {
        let px = mL+(w-mL-mR)*opts.probeNowX;
        let py = mT+(h-mB-mT)*(1-(opts.probeNowY+75000)/150000);
        ctx.beginPath();
        ctx.arc(px, py, 7, 0, 7);ctx.globalAlpha=.89; ctx.fillStyle="#ff3c99";ctx.fill();
        ctx.globalAlpha=.96; ctx.font="15px Arial";
        ctx.fillStyle="#fff";
        ctx.fillText("Live", px+11, py-14);
    }
}

window.addEventListener('DOMContentLoaded', () => {
    let canvas = document.getElementById("phase-graph");
    let dates = [PHASE_POINTS[0].date, PHASE_POINTS[Math.round(PHASE_POINTS.length/4)].date, PHASE_POINTS[Math.round(PHASE_POINTS.length/2)].date, PHASE_POINTS[Math.round(PHASE_POINTS.length*3/4)].date, PHASE_POINTS[PHASE_POINTS.length-1].date];
    drawGraph(canvas, PHASE_POINTS, {graphColor:"#04fffd", showCursor:true, probeNowX:<?=json_encode($now_u)?>, probeNowY:<?=json_encode($probe_y)?>, xDateLabels:dates});

    let ycanvas = document.getElementById("year-graph");
    drawGraph(ycanvas, YEAR_POINTS, {graphColor:"#ffe258", xTickCount:6, xLabels:["Start", "1/5", "2/5", "3/5", "4/5", "End"]});
});

function shiftPhase(direction) {
    let body = document.body;
    if (direction>0) body.classList.add("slide-right");
    else body.classList.add("slide-left");
    let nextIdx = <?=json_encode($phaseIdx)?>+direction;
    setTimeout(()=> {
        window.location.href="?phase=" + nextIdx;
    }, 180);
}
</script>
</body>
</html>