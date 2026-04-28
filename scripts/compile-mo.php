<?php
/**
 * Compilation minimaliste .po → .mo (format GNU gettext binaire).
 * Usage : php scripts/compile-mo.php
 */

$poFile = __DIR__ . '/../locale/textdomain/fr_FR/LC_MESSAGES/meeting-outlines.po';
$moFile = __DIR__ . '/../locale/textdomain/fr_FR/LC_MESSAGES/meeting-outlines.mo';

$po = file_get_contents($poFile);
if ($po === false) {
    fwrite(STDERR, "Impossible de lire : $poFile\n");
    exit(1);
}

// Extraction des paires msgid / msgstr (une seule ligne par chaîne)
preg_match_all('/^msgid "(.*)"\s*\nmsgstr "(.*)"$/m', $po, $matches, PREG_SET_ORDER);

$entries = [];
foreach ($matches as $m) {
    $id  = stripcslashes($m[1]);
    $str = stripcslashes($m[2]);
    if ($id !== '') {
        $entries[$id] = $str;
    }
}

echo count($entries) . " entrées trouvées.\n";

// Vérification des nouvelles chaînes
foreach (['Bible Readings'] as $key) {
    echo $key . ' : ' . (isset($entries[$key]) ? '"' . $entries[$key] . '"' : 'MANQUANT') . "\n";
}

// Génération du fichier .mo (format GNU MO)
// Référence : https://www.gnu.org/software/gettext/manual/html_node/MO-Files.html
ksort($entries);

$numStrings  = count($entries);
$originals   = array_keys($entries);
$translations = array_values($entries);

$headerSize  = 28;               // 7 × 4 octets
$tableSize   = $numStrings * 8;  // 2 tables (orig + trad) × N × 8 octets
$strOffset   = $headerSize + 2 * $tableSize;

$origTable  = '';
$tranTable  = '';
$origData   = '';
$tranData   = '';
$oOff = $strOffset;
$tOff = $strOffset;

// Passe 1 : calculer les offsets originaux
$oOffsets = [];
foreach ($originals as $str) {
    $len = strlen($str);
    $oOffsets[] = [$len, $oOff];
    $origData .= $str . "\0";
    $oOff += $len + 1;
}
// Passe 2 : offsets traductions (après les originaux)
$tBase = $oOff;
$tOff  = $tBase;
$tOffsets = [];
foreach ($translations as $str) {
    $len = strlen($str);
    $tOffsets[] = [$len, $tOff];
    $tranData .= $str . "\0";
    $tOff += $len + 1;
}

// Construction des tables
foreach ($oOffsets as [$len, $off]) {
    $origTable .= pack('VV', $len, $off);
}
foreach ($tOffsets as [$len, $off]) {
    $tranTable .= pack('VV', $len, $off);
}

// En-tête MO
$magic        = pack('V', 0x950412de);  // little-endian
$revision     = pack('V', 0);
$numStr       = pack('V', $numStrings);
$origOffset   = pack('V', $headerSize);
$tranOffset   = pack('V', $headerSize + $tableSize);
$hashSize     = pack('V', 0);
$hashOffset   = pack('V', $headerSize + 2 * $tableSize);

$mo  = $magic . $revision . $numStr . $origOffset . $tranOffset . $hashSize . $hashOffset;
$mo .= $origTable . $tranTable . $origData . $tranData;

file_put_contents($moFile, $mo);
echo "Fichier .mo généré : $moFile\n";
