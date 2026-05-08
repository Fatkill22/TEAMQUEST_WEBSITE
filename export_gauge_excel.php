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

if (!class_exists('ZipArchive')) {
    die('<b>Server configuration error:</b> The PHP <code>zip</code> extension is not enabled.<br><br>
         Fix: Open <code>C:\xampp\php\php.ini</code>, find <code>;extension=zip</code>,
         remove the semicolon, save, then restart Apache in XAMPP Control Panel.');
}

// ── Master answer key ──
$master_key = [];
$res = $conn->query("SELECT question_num, correct_val FROM gauge_answers WHERE module_id = $module_id");
while ($r = $res->fetch_assoc()) $master_key[(int)$r['question_num']] = (int)$r['correct_val'];

// ── Load 3 attempts per appraiser ──
function loadAttempts($conn, string $username, int $module_id): array {
    $esc = $conn->real_escape_string($username);
    $res = $conn->query(
        "SELECT attempt_num, answers_json
           FROM gauge_attempt_details
          WHERE username = '$esc' AND module_id = $module_id
          ORDER BY attempt_num ASC LIMIT 3"
    );
    $out = [];
    while ($r = $res->fetch_assoc()) {
        $out[(int)$r['attempt_num']] = json_decode($r['answers_json'], true) ?: [];
    }
    return $out;
}

$data_a = loadAttempts($conn, $appraiser_a, $module_id);
$data_b = loadAttempts($conn, $appraiser_b, $module_id);
$data_c = loadAttempts($conn, $appraiser_c, $module_id);

// ── Copy template ──
$template = __DIR__ . DIRECTORY_SEPARATOR . 'ATTRIBUTE GR  R.xlsx';
if (!file_exists($template)) { die("Excel template not found: ATTRIBUTE GR  R.xlsx"); }
$tmp = tempnam(sys_get_temp_dir(), 'grr_') . '.xlsx';
copy($template, $tmp);

$zip = new ZipArchive();
if ($zip->open($tmp) !== true) { unlink($tmp); die("Cannot open Excel template."); }

// ── Shared helper: create a namespaced (or plain) element ──
function mkEl(DOMDocument $doc, string $ns, string $tag): DOMElement {
    return $ns ? $doc->createElementNS($ns, $tag) : $doc->createElement($tag);
}

// ════════════════════════════════════════════════════════════════
// STEP 1 — Patch workbook.xml: add fullCalcOnLoad so Excel always
//           recalculates formulas when the file is opened.
// ════════════════════════════════════════════════════════════════
$wbXml = $zip->getFromName('xl/workbook.xml');
// Simple string replace is safe here — calcPr appears exactly once
$wbXml = str_replace('<calcPr ', '<calcPr fullCalcOnLoad="1" ', $wbXml);
$zip->addFromString('xl/workbook.xml', $wbXml);

// ════════════════════════════════════════════════════════════════
// STEP 2 — Inject green + red fill styles into styles.xml
// ════════════════════════════════════════════════════════════════
$stylesDom = new DOMDocument();
$stylesDom->loadXML($zip->getFromName('xl/styles.xml'));
$sxp  = new DOMXPath($stylesDom);
$sns  = $stylesDom->documentElement->namespaceURI ?: '';

// ── Add two solid fills (appended → indices fillCount and fillCount+1) ──
$fillsEl   = $sxp->query("//*[local-name()='fills']")->item(0);
$fillCount = (int)$fillsEl->getAttribute('count');

foreach (['FF92D050' => 'green', 'FFFF4444' => 'red'] as $rgb => $_) {
    $fill = mkEl($stylesDom, $sns, 'fill');
    $pat  = mkEl($stylesDom, $sns, 'patternFill');
    $pat->setAttribute('patternType', 'solid');
    $fg = mkEl($stylesDom, $sns, 'fgColor'); $fg->setAttribute('rgb', $rgb);
    $bg = mkEl($stylesDom, $sns, 'bgColor'); $bg->setAttribute('indexed', '64');
    $pat->appendChild($fg);
    $pat->appendChild($bg);
    $fill->appendChild($pat);
    $fillsEl->appendChild($fill);
}
$fillsEl->setAttribute('count', $fillCount + 2);

// ── Add three xf entries cloned from index 159 (border=46, font=19/black,
//    center-aligned): green, red, and a reference-column style ──
// xf[159] already has applyFont="1" and fontId=19 (Arial 10, color theme=1 = black),
// so all three clones will display black text.
$xfsEl    = $sxp->query("//*[local-name()='cellXfs']")->item(0);
$xfList   = $sxp->query("//*[local-name()='cellXfs']/*[local-name()='xf']");
$xfCount  = $xfList->length;          // green=$xfCount, red=$xfCount+1, ref=$xfCount+2
$baseXf   = $xfList->item(159);

$greenXfIdx = $xfCount;
$redXfIdx   = $xfCount + 1;
$refXfIdx   = $xfCount + 2;

$greenXf = $baseXf->cloneNode(true);
$greenXf->setAttribute('fillId', $fillCount);        // new green fill
$xfsEl->appendChild($greenXf);

$redXf = $baseXf->cloneNode(true);
$redXf->setAttribute('fillId', $fillCount + 1);      // new red fill
$xfsEl->appendChild($redXf);

// Reference column: light-yellow fill (fill 3 = #FFFFCC already in template)
// keeps the same borders/font as data cells but visually distinguishes it.
$refXf = $baseXf->cloneNode(true);
$refXf->setAttribute('fillId', 3);
$xfsEl->appendChild($refXf);

$xfsEl->setAttribute('count', $xfCount + 3);

$zip->addFromString('xl/styles.xml', $stylesDom->saveXML());

// ════════════════════════════════════════════════════════════════
// STEP 3 — Modify sheet11.xml ("N PROD 1" tab)
// ════════════════════════════════════════════════════════════════
$sheetDom = new DOMDocument();
$sheetDom->loadXML($zip->getFromName('xl/worksheets/sheet11.xml'));
$xpath = new DOMXPath($sheetDom);
$ns    = $sheetDom->documentElement->namespaceURI ?: '';

// ── Strip ALL conditional formatting from the sheet.
// The template encodes per-row CF rules that colour cells based on value (0/1)
// using the historical answer key. Those CF rules override any s= cell style,
// causing the "inverted" green/red colours the user sees. Removing them lets
// our explicit fill styles on each cell take full effect.
$cfNodes = $xpath->query("//*[local-name()='conditionalFormatting']");
foreach ($cfNodes as $cfNode) {
    $cfNode->parentNode->removeChild($cfNode);
}

// Column sort order used when inserting new cells
$COL_ORDER = array_flip(array_merge(range('A', 'Z'), ['AA','AB','AC','AD','AE','AF']));

/**
 * Set a numeric cell value (and optional style index) in the sheet.
 * Creates the row/cell if absent; wipes any existing formula or cached value.
 */
function setCell(DOMDocument $doc, DOMXPath $xp, array $colOrder,
                 int $rowNum, string $col, $value, ?int $styleIdx = null): void
{
    $ns      = $doc->documentElement->namespaceURI ?: '';
    $cellRef = $col . $rowNum;

    // ── row ──
    $rowNodes = $xp->query("//*[local-name()='sheetData']/*[local-name()='row'][@r='$rowNum']");
    if ($rowNodes->length > 0) {
        $rowEl = $rowNodes->item(0);
    } else {
        $sd    = $xp->query("//*[local-name()='sheetData']")->item(0);
        $rowEl = mkEl($doc, $ns, 'row');
        $rowEl->setAttribute('r', (string)$rowNum);
        $placed = false;
        foreach ($sd->childNodes as $ch) {
            if ($ch->nodeType === XML_ELEMENT_NODE && (int)$ch->getAttribute('r') > $rowNum) {
                $sd->insertBefore($rowEl, $ch); $placed = true; break;
            }
        }
        if (!$placed) $sd->appendChild($rowEl);
    }

    // ── cell ──
    $cellNodes = $xp->query("*[local-name()='c'][@r='$cellRef']", $rowEl);
    if ($cellNodes->length > 0) {
        $cell = $cellNodes->item(0);
        if ($cell->hasAttribute('t')) $cell->removeAttribute('t');
        while ($cell->firstChild) $cell->removeChild($cell->firstChild);
    } else {
        $cell = mkEl($doc, $ns, 'c');
        $cell->setAttribute('r', $cellRef);
        $myIdx  = $colOrder[$col] ?? 0;
        $placed = false;
        foreach ($rowEl->childNodes as $ch) {
            if ($ch->nodeType !== XML_ELEMENT_NODE) continue;
            $chCol = rtrim($ch->getAttribute('r'), '0123456789');
            if (isset($colOrder[$chCol]) && $colOrder[$chCol] > $myIdx) {
                $rowEl->insertBefore($cell, $ch); $placed = true; break;
            }
        }
        if (!$placed) $rowEl->appendChild($cell);
    }

    if ($styleIdx !== null) $cell->setAttribute('s', (string)$styleIdx);

    $v = mkEl($doc, $ns, 'v');
    $v->appendChild($doc->createTextNode((string)$value));
    $cell->appendChild($v);
}

/**
 * Write a name as an inline string into an existing cell,
 * keeping whatever style (borders, font) was already on it.
 */
function setCellName(DOMDocument $doc, DOMXPath $xp, int $rowNum, string $col, string $name): void {
    $ns       = $doc->documentElement->namespaceURI ?: '';
    $cellRef  = $col . $rowNum;
    $rowNodes = $xp->query("//*[local-name()='sheetData']/*[local-name()='row'][@r='$rowNum']");
    if ($rowNodes->length === 0) return;
    $cellNodes = $xp->query("*[local-name()='c'][@r='$cellRef']", $rowNodes->item(0));
    if ($cellNodes->length === 0) return;
    $cell = $cellNodes->item(0);
    $cell->setAttribute('t', 'inlineStr');
    while ($cell->firstChild) $cell->removeChild($cell->firstChild);
    $is = mkEl($doc, $ns, 'is');
    $t  = mkEl($doc, $ns, 't');
    $t->appendChild($doc->createTextNode($name));
    $is->appendChild($t);
    $cell->appendChild($is);
}

// ── Appraiser names → K8, K9, K10 ──
setCellName($sheetDom, $xpath, 8,  'K', $appraiser_a);
setCellName($sheetDom, $xpath, 9,  'K', $appraiser_b);
setCellName($sheetDom, $xpath, 10, 'K', $appraiser_c);

// ── Data rows 15–64 (parts 1–50) ──
// Column B  = reference answer key (no colour)
// Columns C–K = appraiser trials, coloured green (match) or red (mismatch)
for ($part = 1; $part <= 50; $part++) {
    $row = $part + 14;
    $ref = $master_key[$part] ?? 0;

    setCell($sheetDom, $xpath, $COL_ORDER, $row, 'B', $ref, $refXfIdx);

    foreach ([
        'C' => $data_a[1][$part] ?? 0,
        'D' => $data_a[2][$part] ?? 0,
        'E' => $data_a[3][$part] ?? 0,
        'F' => $data_b[1][$part] ?? 0,
        'G' => $data_b[2][$part] ?? 0,
        'H' => $data_b[3][$part] ?? 0,
        'I' => $data_c[1][$part] ?? 0,
        'J' => $data_c[2][$part] ?? 0,
        'K' => $data_c[3][$part] ?? 0,
    ] as $col => $val) {
        $style = ((int)$val === $ref) ? $greenXfIdx : $redXfIdx;
        setCell($sheetDom, $xpath, $COL_ORDER, $row, $col, $val, $style);
    }
}

$zip->addFromString('xl/worksheets/sheet11.xml', $sheetDom->saveXML());
$zip->deleteName('xl/calcChain.xml'); // also remove stale calc chain as a second guarantee
$zip->close();

// ── Serve the file ──
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
