<?php
/**
 * Palmier Sabbatical Date Engine (Final Production)
 */

date_default_timezone_set('UTC');

// =========================
// CONFIG
// =========================
$historicalHourMap = [
    0 => 0,   // Sunday
    1 => 14,  // Monday
    2 => 3,   // Tuesday
    3 => 17,  // Wednesday
    4 => 6,   // Thursday
    5 => 20,  // Friday
    6 => 10   // Saturday
];

$daysShort = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
$daysFull  = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$months    = ['January','February','March','April','May','June','July','August','September','October','November','December'];

// =========================
// LOGGING
// =========================
function palmier_log($msg, $data=null){
    error_log("[PALMIER] ".$msg.($data ? " | ".json_encode($data) : ""));
}

// =========================
// HELPERS
// =========================
function ordinal($n){
    if ($n > 3 && $n < 21) return $n.'th';
    switch($n % 10){
        case 1: return $n.'st';
        case 2: return $n.'nd';
        case 3: return $n.'rd';
        default: return $n.'th';
    }
}

function jubilee($year){
    $hebrew = $year + 3760;
    $raw = ($hebrew - 2503) / 50;

    $int = floor($raw);
    $dec = $raw - $int;

    $yy = floor(($dec * 100) / 2);
    return $int . "Y" . str_pad($yy,2,'0',STR_PAD_LEFT);
}

// =========================
// CORE ENGINE
// =========================
function palmier_time(){
    global $historicalHourMap;

    try{
        $now = new DateTime("now", new DateTimeZone("UTC"));
        $epoch = new DateTime("1899-12-30 00:00:00", new DateTimeZone("UTC"));

        $diffDays = ($now->getTimestamp() - $epoch->getTimestamp()) / 86400;

        $j = (((($diffDays / 365.262541748) + 1900) / 7) - 2 - 184) * 365.262541748;

        $hist = clone $epoch;
        $days = (int)round($j);

        if($days >= 0){
            $hist->modify("+$days days");
        } else {
            $hist->modify("$days days");
        }

        // 4-hour compression
        $weekday = (int)$now->format("w");
        $base = $historicalHourMap[$weekday] ?? 0;

        $secToday = ($now->format("H")*3600)+($now->format("i")*60)+$now->format("s");
        $progress = $secToday / 86400;

        $histSec = $progress * 14400; // 4 hours

        $h = floor($histSec/3600);
        $m = floor(($histSec%3600)/60);
        $s = floor($histSec%60);

        $hour = ($base + $h) % 24;

        $hist->setTime($hour,$m,$s);

        return [$now,$hist,null];

    }catch(Exception $e){
        palmier_log("ENGINE_FAIL",$e->getMessage());
        return [null,null,$e->getMessage()];
    }
}

// =========================
// API ENDPOINTS
// =========================
if(isset($_GET['endpoint'])){
    header("Content-Type: application/json");

    [$now,$hist,$err] = palmier_time();

    if($err){
        http_response_code(500);
        echo json_encode(["error"=>$err]);
        exit;
    }

    switch($_GET['endpoint']){

        case 'historical':
            echo json_encode([
                "iso"=>$hist->format(DateTime::ATOM)
            ]);
            exit;

        case 'current':
            echo json_encode([
                "iso"=>$now->format(DateTime::ATOM)
            ]);
            exit;

        case 'probe':
            try{
                $json = @file_get_contents("https://buzzjuice.net/shared/palmier/probe/millennium-probe.php?probe=now");
                echo $json ?: json_encode(["error"=>"probe unavailable"]);
            }catch(Exception $e){
                palmier_log("PROBE_FAIL",$e->getMessage());
                echo json_encode(["error"=>"probe fail"]);
            }
            exit;

        default:
            http_response_code(404);
            echo json_encode(["error"=>"invalid endpoint"]);
            exit;
    }
}

// =========================
// INITIAL RENDER
// =========================
[$now,$hist,$err] = palmier_time();

$comboDay = $daysShort[$hist->format('w')] . $daysFull[$now->format('w')];
$ordinal  = ordinal($hist->format('d'));
$month    = $months[$hist->format('n')-1];
$jub      = jubilee($now->format('Y'));
$refYear  = $hist->format('Y');

$histTime = $hist->format('H:i:s');
$currentISO = $now->format(DateTime::ATOM);

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Sabbatical Date</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body{background:#0f172a;color:#fff;font-family:Arial;margin:0;text-align:center}
.wrap{max-width:900px;margin:auto;padding:20px}

.row{display:flex;flex-wrap:wrap;justify-content:center;gap:16px}
.group{/*min-width:120px;*/ margin:5px;}

.big{font-size:2rem;font-weight:700}
.mid{font-size:1.1rem;font-family:monospace}
.label{font-size:11px;opacity:.6}

/* MOBILE */
@media(max-width:600px){
.row{flex-direction:column}
.big{font-size:1.2rem}
.mid{font-size:.9rem}
}

.sabb-date-group-row {
    flex-direction: row !important;
    display: flex;
    justify-content: center;
}
</style>
</head>

<body>
<div class="wrap">

<!-- DATE -->
<div class="row">
    <div class="group">
        <div class="big" id="day"><?=$comboDay?></div>
        <div class="label">Day</div>
    </div>
    <div class="sabb-date-group-row">
        <div class="group">
            <div class="big" id="date"><?=$ordinal?></div>
            <div class="label">Date</div>
        </div>
        <div class="group">
            <div class="big" id="month"><?=$month?></div>
            <div class="label">Month</div>
        </div>
    </div>
    <div class="sabb-date-group-row">
        <div class="group">
            <div class="big" id="jub"><?=$jub?></div>
            <div class="label">Jubilee</div>
        </div>
        <div class="group">
            <div class="big" id="year"><?=$refYear?></div>
            <div class="label">Reference</div>
        </div>
    </div>
</div>

<!-- TIME -->
<div class="row" style="margin-top:25px">
<div class="group"><div class="mid" id="hist"><?=$histTime?></div><div class="label">Local</div></div>
<div class="group"><div class="mid" id="current"><?=$currentISO?></div><div class="label">Current</div></div>
<div class="group"><div class="mid" id="probe">--</div><div class="label">Probe</div></div>
</div>

</div>

<script>
const hourMap = <?=json_encode($historicalHourMap)?>;
const daysShort = <?=json_encode($daysShort)?>;
const daysFull = <?=json_encode($daysFull)?>;
const months = <?=json_encode($months)?>;

function pad(n){
    return String(n).padStart(2,'0');
}

/* =========================
   FIXED ORDINAL (MATCHES PHP EXACTLY)
========================= */
function ordinal(n){
    n = Number(n);

    if (isNaN(n)) return "ERR";

    if (n > 3 && n < 21) return n + "th";

    switch (n % 10){
        case 1: return n + "st";
        case 2: return n + "nd";
        case 3: return n + "rd";
        default: return n + "th";
    }
}

function jubilee(y){
    let h = y + 3760;
    let r = (h - 2503) / 50;
    let i = Math.floor(r);
    let d = r - i;
    let yy = Math.floor((d * 100) / 2);
    return i + "Y" + String(yy).padStart(2,'0');
}

/* =========================
   CORE RENDER
========================= */
function render(){
    try{

        let now = new Date();

        let epoch = new Date(Date.UTC(1899,11,30));
        let diff = (now - epoch) / 86400000;

        let j = (((((diff/365.262541748)+1900)/7)-2)-184)*365.262541748;

        let hist = new Date(epoch);
        hist.setUTCDate(hist.getUTCDate() + Math.round(j));

        let d = now.getUTCDay();
        let base = hourMap[d] ?? 0;

        let sec = now.getUTCHours()*3600 + now.getUTCMinutes()*60 + now.getUTCSeconds();
        let p = sec / 86400;

        let hs = p * 14400;

        let h = Math.floor(hs/3600);
        let m = Math.floor((hs%3600)/60);
        let s = Math.floor(hs%60);

        hist.setUTCHours((base+h)%24, m, s);

        /* SAFE VALUE EXTRACTION */
        let histDate = Number(hist.getUTCDate());

        /* DOM UPDATE */
        document.getElementById("day").innerText =
            daysShort[hist.getUTCDay()] + daysFull[now.getUTCDay()];

        document.getElementById("date").innerText =
            ordinal(histDate);

        document.getElementById("month").innerText =
            months[hist.getUTCMonth()];

        document.getElementById("jub").innerText =
            jubilee(now.getUTCFullYear());

        document.getElementById("year").innerText =
            hist.getUTCFullYear();

        document.getElementById("hist").innerText =
            pad(hist.getUTCHours()) + ":" +
            pad(hist.getUTCMinutes()) + ":" +
            pad(hist.getUTCSeconds());

        document.getElementById("current").innerText =
            now.toISOString();

    }catch(e){
        console.error("Render Error:", e);
        document.getElementById("date").innerText = "ERR";
    }
}

/* =========================
   PROBE
========================= */
async function probe(){
    try{
        let r = await fetch("?endpoint=probe");
        let j = await r.json();
        document.getElementById("probe").innerText = j.probe_iso || "ERR";
    }catch{
        document.getElementById("probe").innerText = "ERR";
    }
}

/* =========================
   LOOP
========================= */
setInterval(render,1000);
setInterval(probe,20000);

render();
probe();
</script>

</body>
</html>