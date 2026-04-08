<?php
/*AI PROMPT>>

Referencing the Palmier calculation of the historical date, develop the code for a page that displays  the live historical date in the following format:

Large text {{{Abbreviated Historical Day}} {{Current Day}} {{Historical ordinal date eg. 20th}} {{Historical Month}} {{insert ((current Hebrew year)-2503)/50}} {{Historical Year}}}

Below the date show:

Smaller text {{{Current date and time: ISO 8601 Combined: eg. 2026-04-06T14:30:00Z}} {{Historical time: Hour Minute Second}} {{future date: (current day) + (74144 days), show only the year at that point as the day and month of the future date should be the same as the current day and month}}}

Note the following:
1. No space between the abbreviated historical day and the current day so the date can read as SunTuesday for example.
1.i. Show the text 'Day' below the combined historical day and the current day as a micro label.
1.ii. Show the text 'Date' below the Historical ordinal date as a micro label.
1.iii. Show the text 'Month' below the Historical Month as a micro label.
1.iv. Show the text 'Jubilee' below the modified Hebrew year as a micro label.
1.v. Show the text 'Reference' below the Historical Year as a micro label.
1.vi. Show the text 'Local' below the Historical time as a micro label.
1.vii. Show the text 'Current' below the 'Current date and time: ISO 8601 Combined' as a micro label.
1.vii. Show the text 'Future' below the 'future date' as a micro label.

2. For 'current Hebrew year/50', replace decimal with 'Y' and divide the decimal by 2 to give a format like (((current Hebrew year)-2503)/50)Y05 if the year is (((current Hebrew year)-2503)/50).1134 or (((current Hebrew year)-2503)/50)Y28 if the year is (((current Hebrew year)-2503)/50).5687.

3. Include an endpoint that can be queried and returns the historical date and time in ISO 8601 Combined format. This would be used to fetch historical currency data.

4. Include an endpoint that can be queried and returns the current date and time in ISO 8601 Combined format.

5. Referencing the 'Historical time', each current day consumes exactly (24/7) hours of the historical time. This means that
- Sunday consumes (24/7) hours of historical time starting from 00:00:00
- Monday consumes (24/7) hours of historical time continuing from Sunday
- Tuesday consumes (24/7) hours of historical time continuing from Monday
- Wednesday consumes (24/7) hours of historical time continuing from Tuesday
- Thursday consumes (24/7) hours of historical time continuing from Wednesday
- Friday consumes (24/7) hours of historical time continuing from Thursday
- Saturday consumes (24/7) hours of historical time continuing from Friday

This should be modified so that each current day consumes exactly 4 hours of the historical time with the following strategy:
- Sunday consumes 4 hours of historical time starting from 00:00:00 historical time
- Monday consumes 4 hours of historical time starting from 14:00:00 historical time
- Tuesday consumes 4 hours of historical time starting from 03:00:00 historical time
- Wednesday consumes 4 hours of historical time starting from 17:00:00 historical time
- Thursday consumes 4 hours of historical time starting from 06:00:00 historical time
- Friday consumes 4 hours of historical time starting from 20:00:00 historical time
- Saturday consumes 4 hours of historical time starting from 10:00:00 historical time

6. Implement a responsive ui for small screens so that small screens show:
Large text {{
<Day on one line: eg SunSaturday>
<Date and Month on one line: eg 20th June> <br>
<Jubilee and Reference on one line: eg 55Y27 2001>
}}

Smaller text {{
<Local on one line> <br>
<Current on one line> <br>
<Future on one line> <br>
}}

7. The page displaying the sabbatical date and time must be easily accessible to any user who taps a shared link to the page.

8. Develop it as a php file so that anyone with the link to 'https://buzzjuice.net/shared/palmier/scripts/sabbatical-date.php' can simply view the live sabbatical date automatically updating with every second..

9. The code should be compatible with the  'palmier/scripts/calc_gold.php' file to be developed next. 

10. Include extensive error control and logging for debugging purposes.

*/
?>

<?php
// sabbatical-date.php - Palmier live historical date display + API (v2026.04)
//
// --- Configuration ---
$historicalHourMap = [0=>0, 1=>14, 2=>3, 3=>17, 4=>6, 5=>20, 6=>10]; // block per weekday
$daysShort = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
$daysFull = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
$months = ["January","February","March","April","May","June","July","August","September","October","November","December"];

// --- Helper functions ---
function getOrdinal($n) {
    if ($n > 3 && $n < 21) return $n."th";
    switch ($n % 10) {
        case 1: return $n."st";
        case 2: return $n."nd";
        case 3: return $n."rd";
        default: return $n."th";
    }
}
function getModifiedHebrewYear($utcYear) {
    $hebrewYear = $utcYear + 3760;
    $jub = ($hebrewYear - 2503) / 50;
    $intPart = floor($jub);
    $decRaw = $jub - $intPart;
    $decY = floor(($decRaw * 100) / 2); // divide decimal by 2
    return $intPart . "Y" . str_pad($decY, 2, "0", STR_PAD_LEFT);
}

// --- Palmier sabbatical time calculation ---
function getPalmierTime() {
    global $historicalHourMap;
    $now = new DateTime('now', new DateTimeZone('UTC'));
    $excelEpoch = new DateTime("1899-12-30T00:00:00Z");
    $A3 = ($now->getTimestamp() - $excelEpoch->getTimestamp()) / 86400.0;
    $j = (((($A3 / 365.262541748) + 1900) / 7) - 2 - 184) * 365.262541748;
    $histDate = clone $excelEpoch;
    $histDate->add(new DateInterval('P' . intval(abs(round($j))) . 'D'));
    // If j can be negative (shouldn't, but for safety), go backward.
    if ($j < 0) $histDate->sub(new DateInterval('P' . intval(abs(round($j))) . 'D'));

    // 4-hour block mapping
    $currDay = (int)$now->format("w"); // 0=Sun
    $baseHour = $historicalHourMap[$currDay];
    $secToday = (int)$now->format("H")*3600 + (int)$now->format("i")*60 + (int)$now->format("s");
    $progress = $secToday/86400.0;
    $histSec = $progress * (4*3600);
    $h = floor($histSec/3600);
    $m = floor(($histSec%3600)/60);
    $s = floor($histSec%60);
    $histDate->setTime($baseHour+$h, $m, $s);
    return [$now, $histDate];
}

// API endpoints for PHP consumers and goldcalc
if (isset($_GET['endpoint'])) {
    list($nowDT, $histDT) = getPalmierTime();
    if ($_GET['endpoint'] === "historical") {
        header('Content-Type: application/json'); echo json_encode(["iso"=>$histDT->format(DateTime::ATOM)]); exit;
    } elseif ($_GET['endpoint'] === "current") {
        header('Content-Type: application/json'); echo json_encode(["iso"=>$nowDT->format(DateTime::ATOM)]); exit;
    }
}
// --------- Endpoints done ---------

// For display.
list($now, $hist) = getPalmierTime();
$future = clone $now; $future->modify("+74144 days"); // only need year
$jubilee = getModifiedHebrewYear((int)$now->format("Y"));
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Palmier Sabbatical Date</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body { margin:0; font-family:Arial,sans-serif; background:#0f172a; color:#fff; display:flex; justify-content:center; align-items:center; min-height:100vh; }
#box { background:#1e293b; padding:20px; border-radius:16px; width:96vw; max-width:900px; text-align:center; }
.row { display:flex; justify-content:center; flex-wrap:wrap; gap:20px; }
.item { text-align:center; }
.main { font-size:2.1em; font-weight:700; }
.micro { font-size:13px; opacity:.72; margin-top:3px;}
.datetime-row { margin-top:22px; }
.dtmain { font-family:monospace; font-size:1em;}
@media (max-width:650px){
  .row { flex-direction:column; gap:9px;}
  .main { font-size:1.2em;}
  .micro { font-size:11px;}
  #box { padding:5vw;}
}
</style>
</head>
<body>
<div id="box">
  <div class="row" id="dateRow"></div>
  <div class="row datetime-row" id="dtRow"></div>
</div>
<script>
// -- Data ported for JS live updates --
const daysShort = <?= json_encode($daysShort) ?>;
const daysFull = <?= json_encode($daysFull) ?>;
const months = <?= json_encode($months) ?>;
const historicalHourMap = <?= json_encode($historicalHourMap) ?>;
function getOrdinal(n) {
    if(n>3&&n<21) return n+"th";
    switch(n%10) {
      case 1: return n+"st"; case 2: return n+"nd"; case 3: return n+"rd"; default:return n+"th";
    }
}
// Jubilee calculation: (((current Hebrew year)-2503)/50) as X.YY, where YY = int(decimal*100/2)
function getModifiedHebrewYear(utcYear) {
    const hebrewYear = utcYear + 3760;
    const jub = (hebrewYear - 2503) / 50;
    const intPart = Math.floor(jub);
    const decRaw = jub - intPart;
    const decY = Math.floor((decRaw*100)/2);
    return intPart + "Y" + String(decY).padStart(2,'0');
}
function pad(x) { return String(x).padStart(2,'0'); }

// --- Live rendering loop ---
function renderPalmierPage() {
    const now = new Date();
    const excelEpoch = new Date(Date.UTC(1899,11,30,0,0,0));
    const diff = (now.getTime() - excelEpoch.getTime())/(1000.0*86400);
    const j = (((((diff/365.262541748)+1900)/7)-2)-184)*365.262541748;
    // Compute Palmier historical date
    let hist = new Date(excelEpoch.getTime());
    hist.setUTCDate(hist.getUTCDate() + Math.round(j)); // basic day, then override time below

    // Map current day to 4-hour
    const currDay = now.getUTCDay();
    const baseHour = historicalHourMap[currDay];
    const secToday = now.getUTCHours()*3600 + now.getUTCMinutes()*60 + now.getUTCSeconds();
    const progress = secToday / 86400.0;
    const histSec = progress * (4*3600);
    const h = Math.floor(histSec/3600), m = Math.floor((histSec%3600)/60), s = Math.floor(histSec%60);
    hist.setUTCHours(baseHour+h, m, s);

    // Values for display
    const comboDay = daysShort[hist.getUTCDay()] + daysFull[now.getUTCDay()];
    const ordDate = getOrdinal(hist.getUTCDate());
    const month = months[hist.getUTCMonth()];
    const jub = getModifiedHebrewYear(now.getUTCFullYear());
    const refY = hist.getUTCFullYear();
    const historicTime = pad(hist.getUTCHours())+":"+pad(hist.getUTCMinutes())+":"+pad(hist.getUTCSeconds());
    const currentIso = now.toISOString();
    // Future year (+74144d)
    const futureD = new Date(now);
    futureD.setUTCDate(futureD.getUTCDate()+74144);
    const futureY = futureD.getUTCFullYear();

    // Responsive: mobile
    if(window.innerWidth<650){
      document.getElementById("dateRow").innerHTML =
        `<span class="main">${comboDay}</span><br>`
        + `<span class="main">${ordDate} ${month}</span><br>`
        + `<span class="main">${jub} ${refY}</span><br>`
        + `<span class="micro">Day</span><br>`
        + `<span class="micro">Date Month</span><br>`
        + `<span class="micro">Jubilee Reference</span>`;
      document.getElementById("dtRow").innerHTML =
        `<span class="dtmain">${historicTime}</span><br><span class="micro">Local</span><br>`
        + `<span class="dtmain">${currentIso}</span><br><span class="micro">Current</span><br>`
        + `<span class="dtmain">${futureY}</span><br><span class="micro">Future</span>`;
      return;
    }
    // Desktop/tablet: grid rows
    document.getElementById("dateRow").innerHTML =
        `<div class="item"><div class="main">${comboDay}</div><div class="micro">Day</div></div>`
      + `<div class="item"><div class="main">${ordDate}</div><div class="micro">Date</div></div>`
      + `<div class="item"><div class="main">${month}</div><div class="micro">Month</div></div>`
      + `<div class="item"><div class="main">${jub}</div><div class="micro">Jubilee</div></div>`
      + `<div class="item"><div class="main">${refY}</div><div class="micro">Reference</div></div>`;
    document.getElementById("dtRow").innerHTML =
        `<div class="item"><div class="dtmain">${historicTime}</div><div class="micro">Local</div></div>`
      + `<div class="item"><div class="dtmain">${currentIso}</div><div class="micro">Current</div></div>`
      + `<div class="item"><div class="dtmain">${futureY}</div><div class="micro">Future</div></div>`;
}
setInterval(renderPalmierPage, 1000);
renderPalmierPage();
window.addEventListener('resize', renderPalmierPage);
</script>
</body>
</html>