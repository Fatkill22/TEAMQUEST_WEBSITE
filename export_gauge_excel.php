<?php
session_start();
include "config.php";

if (!isset($_SESSION['username']) || $_SESSION['roles'] != 'admin') { die("Access Denied"); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: admin_modules.php?tab=gauge-admin"); exit(); }

$module_id   = (int)$_POST['module_id'];
$appraiser_a = trim($_POST['appraiser_a'] ?? '');
$appraiser_b = trim($_POST['appraiser_b'] ?? '');
$appraiser_c = trim($_POST['appraiser_c'] ?? '');

if (!$appraiser_a || !$appraiser_b || !$appraiser_c) {
    header("Location: view_gauge_report.php?id=$module_id&error=1");
    exit();
}

// Load master answer key (reference column B)
$master_key = [];
$res = $conn->query("SELECT question_num, correct_val FROM gauge_answers WHERE module_id = $module_id");
while ($row = $res->fetch_assoc()) $master_key[(int)$row['question_num']] = (int)$row['correct_val'];

// Load the 3 attempts for one appraiser
function loadAttempts($conn, string $username, int $module_id): array {
    $esc = $conn->real_escape_string($username);
    $res = $conn->query(
        "SELECT attempt_num, answers_json
           FROM gauge_attempt_details
          WHERE username = '$esc' AND module_id = $module_id
          ORDER BY attempt_num ASC LIMIT 3"
    );
    $attempts = [];
    while ($row = $res->fetch_assoc()) {
        $attempts[(int)$row['attempt_num']] = json_decode($row['answers_json'], true) ?: [];
    }
    return $attempts;
}

$data_a = loadAttempts($conn, $appraiser_a, $module_id);
$data_b = loadAttempts($conn, $appraiser_b, $module_id);
$data_c = loadAttempts($conn, $appraiser_c, $module_id);

// Copy template to temp file
$template = __DIR__ . DIRECTORY_SEPARATOR . 'ATTRIBUTE GR  R.xlsx';
if (!file_exists($template)) { die("Excel template not found: ATTRIBUTE GR  R.xlsx"); }
$tmp = tempnam(sys_get_temp_dir(), 'grr_') . '.xlsx';
copy($template, $tmp);

$zip = new ZipArchive();
if ($zip->open($tmp) !== true) { unlink($tmp); die("Cannot open Excel template."); }

// ── Helper: create element, respecting the document namespace ──
function mkEl(DOMDocument $dom, string $ns, string $tag): DOMElement {
    return $ns ? $dom->createElementNS($ns, $tag) : $dom->createElement($tag);
}

// ────────────────────────────────────────────────────────────────
// STEP 1 — Inject green + red fills and matching xf entries into styles.xml
// ────────────────────────────────────────────────────────────────
$stylesXml = $zip->getFromName('xl/styles.xml');
$sdом = new DOMDocument();
$sdом->loadXML($stylesXml);
$sxp = new DOMXPath($sdом);
$sns = $sdом->documentElement->namespaceURI ?: '';

// Append two solid fills: green (match) and red (mismatch)
$fillsEl   = $sxp->query("//*[local-name()='fills']")->item(0);
$fillCount = (int)$fillsEl->getAttribute('count'); // new fills will be index $fillCount and $fillCount+1

foreach ([
    'FF92D050' => 'green',   // solid green — pass
    'FFFF4444' => 'red',     // solid red   — fail
] as $rgb => $_label) {
    $fill = mkEl($sdом, $sns, 'fill');
    $pat  = mkEl($sdом, $sns, 'patternFill'); $pat->setAttribute('patternType', 'solid');
    $fg   = mkEl($sdом, $sns, 'fgColor');    $fg->setAttribute('rgb', $rgb);
    $bg   = mkEl($sdом, $sns, 'bgColor');    $bg->setAttribute('indexed', '64');
    $pat->appendChild($fg); $pat->appendChild($bg);
    $fill->appendChild($pat);
    $fillsEl->appendChild($fill);
}
$fillsEl->setAttribute('count', $fillCount + 2);

// Derive two new xf entries by cloning the existing data-cell style (index 159)
// then swapping in the new fill IDs. Index 159 has the correct border and alignment
// for the appraiser data columns (C–K).
$xfsEl   = $sxp->query("//*[local-name()='cellXfs']")->item(0);
$xfList  = $sxp->query("//*[local-name()='cellXfs']/*[local-name()='xf']");
$xfCount = $xfList->length; // new xf indices: $xfCount (green) and $xfCount+1 (red)
$baseXf  = $xfList->item(159);

$greenXfIdx = $xfCount;
$redXfIdx   = $xfCount + 1;

$greenXf = $baseXf->cloneNode(true); $greenXf->setAttribute('fillId', $fillCount);     $xfsEl->appendChild($greenXf);
$redXf   = $baseXf->cloneNode(true); $redXf->setAttribute('fillId',   $fillCount + 1); $xfsEl->appendChild($redXf);
$xfsEl->setAttribute('count', $xfCount + 2);

$zip->addFromString('xl/styles.xml', $sdом->saveXML());

// ────────────────────────────────────────────────────────────────
// STEP 2 — Modify sheet11.xml (the "N PROD 1" tab)
// ────────────────────────────────────────────────────────────────
$xml = $zip->getFromName('xl/worksheets/sheet11.xml');
$dom = new DOMDocument();
$dom->loadXML($xml);
$xpath = new DOMXPath($dom);
$ns    = $dom->documentElement->namespaceURI ?: '';

/**
 * Find or create a numeric cell at $col$rowNum, set its value and optional style.
 * Strips any formula so our raw value is used.
 */
function setExcelCell(DOMDocument $dom, DOMXPath $xpath, int $rowNum, string $col, $value, ?int $styleIdx = null): void {
    $cellRef = $col . $rowNum;
    $ns      = $dom->documentElement->namespaceURI ?: '';
    static $colOrder = null;
    if ($colOrder === null) {
        $letters = array_merge(range('A', 'Z'), ['AA','AB','AC','AD','AE','AF']);
        $colOrder = array_flip($letters);
    }

    // Find or create row
    $rowNodes = $xpath->query("//*[local-name()='sheetData']/*[local-name()='row'][@r='$rowNum']");
    if ($rowNodes->length > 0) {
        $rowEl = $rowNodes->item(0);
    } else {
        $sheetData = $xpath->query("//*[local-name()='sheetData']")->item(0);
        $rowEl = mkEl($dom, $ns, 'row'); $rowEl->setAttribute('r', (string)$rowNum);
        $inserted = false;
        foreach ($sheetData->childNodes as $ch) {
            if ($ch->nodeType === XML_ELEMENT_NODE && (int)$ch->getAttribute('r') > $rowNum) {
                $sheetData->insertBefore($rowEl, $ch); $inserted = true; break;
            }
        }
        if (!$inserted) $sheetData->appendChild($rowEl);
    }

    // Find or create cell
    $cellNodes = $xpath->query("*[local-name()='c'][@r='$cellRef']", $rowEl);
    if ($cellNodes->length > 0) {
        $cell = $cellNodes->item(0);
        if ($cell->hasAttribute('t')) $cell->removeAttribute('t');
        while ($cell->firstChild) $cell->removeChild($cell->firstChild);
    } else {
        $cell = mkEl($dom, $ns, 'c'); $cell->setAttribute('r', $cellRef);
        $myIdx    = $colOrder[$col] ?? 0;
        $inserted = false;
        foreach ($rowEl->childNodes as $ch) {
            if ($ch->nodeType !== XML_ELEMENT_NODE) continue;
            $chCol = rtrim($ch->getAttribute('r'), '0123456789');
            if (isset($colOrder[$chCol]) && $colOrder[$chCol] > $myIdx) {
                $rowEl->insertBefore($cell, $ch); $inserted = true; break;
            }
        }
        if (!$inserted) $rowEl->appendChild($cell);
    }

    if ($styleIdx !== null) $cell->setAttribute('s', (string)$styleIdx);

    $v = mkEl($dom, $ns, 'v'); $v->appendChild($dom->createTextNode((string)$value));
    $cell->appendChild($v);
}

/**
 * Write an appraiser name as an inline string into an existing cell,
 * preserving its existing style (borders, font, etc.).
 */
function setExcelName(DOMDocument $dom, DOMXPath $xpath, int $rowNum, string $col, string $name): void {
    $cellRef  = $col . $rowNum;
    $ns       = $dom->documentElement->namespaceURI ?: '';
    $rowNodes = $xpath->query("//*[local-name()='sheetData']/*[local-name()='row'][@r='$rowNum']");
    if ($rowNodes->length === 0) return;
    $cellNodes = $xpath->query("*[local-name()='c'][@r='$cellRef']", $rowNodes->item(0));
    if ($cellNodes->length === 0) return;
    $cell = $cellNodes->item(0);
    $cell->setAttribute('t', 'inlineStr');
    while ($cell->firstChild) $cell->removeChild($cell->firstChild);
    $is = mkEl($dom, $ns, 'is');
    $t  = mkEl($dom, $ns, 't');
    $t->appendChild($dom->createTextNode($name));
    $is->appendChild($t);
    $cell->appendChild($is);
}

// Write appraiser names into K8, K9, K10
setExcelName($dom, $xpath, 8,  'K', $appraiser_a);
setExcelName($dom, $xpath, 9,  'K', $appraiser_b);
setExcelName($dom, $xpath, 10, 'K', $appraiser_c);

// Fill data rows 15–64 (parts 1–50): reference in B, trials in C–K with green/red fill
for ($part = 1; $part <= 50; $part++) {
    $row = $part + 14;
    $ref = $master_key[$part] ?? 0;

    setExcelCell($dom, $xpath, $row, 'B', $ref); // reference — no colour change

    $trials = [
        'C' => $data_a[1][$part] ?? 0,
        'D' => $data_a[2][$part] ?? 0,
        'E' => $data_a[3][$part] ?? 0,
        'F' => $data_b[1][$part] ?? 0,
        'G' => $data_b[2][$part] ?? 0,
        'H' => $data_b[3][$part] ?? 0,
        'I' => $data_c[1][$part] ?? 0,
        'J' => $data_c[2][$part] ?? 0,
        'K' => $data_c[3][$part] ?? 0,
    ];
    foreach ($trials as $col => $val) {
        $style = ((int)$val === $ref) ? $greenXfIdx : $redXfIdx;
        setExcelCell($dom, $xpath, $row, $col, $val, $style);
    }
}

$zip->addFromString('xl/worksheets/sheet11.xml', $dom->saveXML());
$zip->deleteName('xl/calcChain.xml'); // force Excel to recalculate all formulas on open
$zip->close();

// Serve the file
$mod_res   = $conn->query("SELECT title FROM modules WHERE id = $module_id");
$mod_title = ($mod_res->fetch_assoc())['title'] ?? 'GRR';
$safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $mod_title);
$filename  = 'GRR_' . $safe_name . '_' . date('Ymd') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmp));
header('Cache-Control: no-cache');
readfile($tmp);
unlink($tmp);
exit();
