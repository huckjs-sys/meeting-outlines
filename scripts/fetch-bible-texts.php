#!/usr/bin/env php
<?php
/**
 * Télécharge les textes bibliques depuis api.getbible.net et les convertit
 * au format compact utilisé par le plugin meeting-outlines.
 *
 * Usage (depuis la racine du plugin) :
 *   php scripts/fetch-bible-texts.php           # toutes les versions
 *   php scripts/fetch-bible-texts.php LSG KJV   # versions spécifiques
 *
 * Résultat : data/text/{VERSION}/{BOOKCODE}.json
 * Format   : {"1": ["verset1", "verset2", ...], "2": [...]}
 *
 * Toutes les versions téléchargées sont dans le domaine public.
 */

declare(strict_types=1);

// ------------------------------------------------------------------
// Configuration
// ------------------------------------------------------------------

$VERSIONS = [
    'LSG' => 'ls1910',   // Louis Segond 1910 (français)
    'FRD' => 'darby',    // Darby 1885 (français)
    'KJV' => 'kjv',      // King James 1611 (anglais)
    'ASV' => 'asv',      // American Standard 1901 (anglais)
];

$API_BASE     = 'https://api.getbible.net/v2';
$DELAY_MS     = 250;   // délai entre chaque requête (ms) — soyons polis
$TIMEOUT_SEC  = 30;

// ------------------------------------------------------------------
// Chemins
// ------------------------------------------------------------------

$pluginDir    = dirname(__DIR__);
$dataDir      = $pluginDir . '/data';
$textDir      = $dataDir . '/text';
$structureFile = $dataDir . '/bible-structure.json';

if (!file_exists($structureFile)) {
    fwrite(STDERR, "Erreur : fichier introuvable : {$structureFile}\n");
    exit(1);
}

$structure = json_decode(file_get_contents($structureFile), true);
if (!is_array($structure) || empty($structure['books'])) {
    fwrite(STDERR, "Erreur : bible-structure.json invalide.\n");
    exit(1);
}

$books = $structure['books']; // [{num, code, fr, en, t, ch}, ...]

// ------------------------------------------------------------------
// Sélection des versions à télécharger
// ------------------------------------------------------------------

$requested = array_slice($argv, 1);
if (!empty($requested)) {
    $requested = array_map('strtoupper', $requested);
    $unknown   = array_diff($requested, array_keys($VERSIONS));
    if (!empty($unknown)) {
        fwrite(STDERR, "Version(s) inconnue(s) : " . implode(', ', $unknown) . "\n");
        fwrite(STDERR, "Versions disponibles : " . implode(', ', array_keys($VERSIONS)) . "\n");
        exit(1);
    }
    $VERSIONS = array_intersect_key($VERSIONS, array_flip($requested));
}

// ------------------------------------------------------------------
// Téléchargement
// ------------------------------------------------------------------

foreach ($VERSIONS as $versionCode => $getbibleAbbrev) {
    $versionDir = $textDir . '/' . $versionCode;
    if (!is_dir($versionDir) && !mkdir($versionDir, 0755, true)) {
        fwrite(STDERR, "Impossible de créer le dossier : {$versionDir}\n");
        continue;
    }

    echo "\n=== {$versionCode} ({$getbibleAbbrev}) — " . count($books) . " livres ===\n";

    $errors = 0;

    foreach ($books as $book) {
        $bookNum  = (int) $book['num'];
        $bookCode = $book['code'];
        $bookName = $book['fr'];
        $outFile  = $versionDir . '/' . $bookCode . '.json';

        // Reprend là où on s'est arrêté
        if (file_exists($outFile)) {
            echo "  [{$bookCode}] déjà présent, ignoré.\n";
            continue;
        }

        $url  = "{$API_BASE}/{$getbibleAbbrev}/{$bookNum}.json";
        $data = fetchUrl($url, $TIMEOUT_SEC);

        if ($data === null) {
            fwrite(STDERR, "  ERREUR [{$bookCode}] {$bookName} — échec de téléchargement.\n");
            $errors++;
            usleep($DELAY_MS * 2000);
            continue;
        }

        $parsed = json_decode($data, true);
        if (!is_array($parsed) || !isset($parsed['chapters'])) {
            fwrite(STDERR, "  ERREUR [{$bookCode}] {$bookName} — JSON inattendu.\n");
            $errors++;
            continue;
        }

        // Conversion au format compact
        $compact = convertToCompact($parsed['chapters']);

        if (empty($compact)) {
            fwrite(STDERR, "  ERREUR [{$bookCode}] {$bookName} — aucun chapitre extrait.\n");
            $errors++;
            continue;
        }

        $json = json_encode($compact, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents($outFile, $json);

        $chapCount  = count($compact);
        $verseCount = array_sum(array_map('count', $compact));
        echo "  [{$bookCode}] {$bookName} — {$chapCount} chap., {$verseCount} v.\n";

        usleep($DELAY_MS * 1000);
    }

    if ($errors > 0) {
        echo "  {$errors} erreur(s) pour {$versionCode}. Relancez le script pour réessayer.\n";
    } else {
        echo "  {$versionCode} complet.\n";
    }
}

echo "\nTerminé.\n";

// ------------------------------------------------------------------
// Fonctions
// ------------------------------------------------------------------

/**
 * Convertit le tableau $chapters de l'API getbible.net v2 en format compact.
 *
 * API :   [{chapter:1, verses:[{verse:1, text:"..."}, ...]}, ...]
 * Résultat : {"1": ["verset1", "verset2"], "2": [...]}
 *
 * @param array $chapters
 * @return array<string, list<string>>
 */
function convertToCompact(array $chapters): array
{
    $result = [];

    foreach ($chapters as $chapterData) {
        if (!isset($chapterData['chapter'], $chapterData['verses'])) {
            continue;
        }

        $chapterNum = (string) $chapterData['chapter'];
        $verses     = [];

        foreach ($chapterData['verses'] as $verseData) {
            $verses[] = trim((string) ($verseData['text'] ?? ''));
        }

        if (!empty($verses)) {
            $result[$chapterNum] = $verses;
        }
    }

    // Tri numérique des chapitres (au cas où l'API ne les retourne pas dans l'ordre)
    uksort($result, fn($a, $b) => (int) $a <=> (int) $b);

    return $result;
}

/**
 * Télécharge une URL avec cURL et retourne le contenu ou null en cas d'erreur.
 */
function fetchUrl(string $url, int $timeoutSec): ?string
{
    if (!function_exists('curl_init')) {
        // Fallback file_get_contents si cURL absent
        $ctx = stream_context_create(['http' => ['timeout' => $timeoutSec]]);
        $body = @file_get_contents($url, false, $ctx);
        return $body !== false ? $body : null;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeoutSec,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'meeting-outlines-plugin/fetch-bible-texts',
        CURLOPT_ENCODING       => 'gzip',
    ]);

    $body    = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errNo   = curl_errno($ch);
    curl_close($ch);

    if ($errNo !== 0 || $httpCode < 200 || $httpCode >= 300 || $body === false) {
        return null;
    }

    return (string) $body;
}
