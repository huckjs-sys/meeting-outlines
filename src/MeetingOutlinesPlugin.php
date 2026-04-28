<?php

namespace ChurchCRM\Plugins\MeetingOutlines;

use ChurchCRM\Config\Menu\MenuItem;
use ChurchCRM\dto\ChurchMetaData;
use ChurchCRM\dto\SystemConfig;
use ChurchCRM\dto\SystemURLs;
use ChurchCRM\model\ChurchCRM\GroupQuery;
use ChurchCRM\model\ChurchCRM\Person2group2roleP2g2rQuery;
use ChurchCRM\Plugin\AbstractPlugin;
use ChurchCRM\Plugin\Hook\HookManager;
use ChurchCRM\Plugin\Hooks;
use PHPMailer\PHPMailer\PHPMailer;
use Propel\Runtime\Propel;

class MeetingOutlinesPlugin extends AbstractPlugin
{
    private static ?MeetingOutlinesPlugin $instance = null;

    public function __construct(string $basePath = '')
    {
        parent::__construct($basePath);
        self::$instance = $this;
    }

    public static function getInstance(): ?MeetingOutlinesPlugin
    {
        return self::$instance;
    }

    public function getId(): string
    {
        return 'meeting-outlines';
    }

    public function getName(): string
    {
        return dgettext('meeting-outlines', 'Meeting Outlines');
    }

    public function getDescription(): string
    {
        return dgettext('meeting-outlines', 'Manage the outlines of church meetings.');
    }

    public function boot(): void
    {
        HookManager::addFilter(Hooks::MENU_BUILDING, [$this, 'addWorshipMenu'], 10);
        $this->runMigrations();
    }

    /**
     * Idempotent schema migrations — safe to run on every boot.
     * Adds new columns that may be missing on installs activated before V2.
     */
    private function runMigrations(): void
    {
        try {
            $con = Propel::getConnection();
            $this->addColumnIfNotExists($con, 'worship_service',      'preacher_person_id',   'INT UNSIGNED DEFAULT NULL');
            $this->addColumnIfNotExists($con, 'worship_service',      'president_person_id',  'INT UNSIGNED DEFAULT NULL');
            $this->addColumnIfNotExists($con, 'worship_service_item', 'responsible_person_id','INT UNSIGNED DEFAULT NULL');
            $this->addColumnIfNotExists($con, 'worship_service_item', 'bible_book',            'TINYINT UNSIGNED DEFAULT NULL');
            $this->addColumnIfNotExists($con, 'worship_service_item', 'bible_chapter',         'SMALLINT UNSIGNED DEFAULT NULL');
            $this->addColumnIfNotExists($con, 'worship_service_item', 'bible_verse_start',     'SMALLINT UNSIGNED DEFAULT NULL');
            $this->addColumnIfNotExists($con, 'worship_service_item', 'bible_verse_end',       'SMALLINT UNSIGNED DEFAULT NULL');
            $this->createServiceTypeTableIfNotExists($con);
        } catch (\Throwable $e) {
            $this->log('Migration check failed: ' . $e->getMessage(), 'error');
        }
    }

    public function addWorshipMenu(array $menus): array
    {
        if (!$this->isEnabled()) {
            return $menus;
        }

        $menu = new MenuItem(dgettext('meeting-outlines', 'Church Meetings'), '', true, 'fa-church');
        $menu->addSubMenu(new MenuItem(
            dgettext('meeting-outlines', 'Meeting Outlines'),
            'plugins/meeting-outlines/services',
            true,
            'fa-list-ol'
        ));
        $menu->addSubMenu(new MenuItem(
            dgettext('meeting-outlines', 'Meeting Settings'),
            'plugins/meeting-outlines/settings',
            true,
            'fa-gear'
        ));

        $menus['ChurchMeetings'] = $menu;

        return $menus;
    }

    /** Slugs des types système — utilisés pour dgettext() au lieu du libellé stocké. */
    private const SYSTEM_TYPE_KEYS = [
        'sunday'  => 'Sunday Meeting',
        'prayer'  => 'Prayer Meeting',
        'special' => 'Special Meeting',
        'other'   => 'Other',
    ];

    public function activate(): void
    {
        $con = Propel::getConnection();

        $con->exec("
            CREATE TABLE IF NOT EXISTS `worship_service` (
                `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `date`                DATE NOT NULL,
                `title`               VARCHAR(200) NOT NULL DEFAULT '',
                `type`                VARCHAR(50)  NOT NULL DEFAULT 'sunday',
                `preacher`            VARCHAR(150) NOT NULL DEFAULT '',
                `preacher_person_id`  INT UNSIGNED DEFAULT NULL,
                `notes`               TEXT,
                `status`              ENUM('draft','published') NOT NULL DEFAULT 'draft',
                `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_date` (`date`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $con->exec("
            CREATE TABLE IF NOT EXISTS `worship_service_item` (
                `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `service_id`          INT UNSIGNED NOT NULL,
                `sort_order`          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                `item_type`           VARCHAR(50) NOT NULL DEFAULT 'other',
                `title`               VARCHAR(200) NOT NULL DEFAULT '',
                `description`         TEXT,
                `duration_minutes`    SMALLINT UNSIGNED DEFAULT NULL,
                `responsible`         VARCHAR(150) NOT NULL DEFAULT '',
                `responsible_person_id` INT UNSIGNED DEFAULT NULL,
                `bible_book`          TINYINT UNSIGNED DEFAULT NULL,
                `bible_chapter`       SMALLINT UNSIGNED DEFAULT NULL,
                `bible_verse_start`   SMALLINT UNSIGNED DEFAULT NULL,
                `bible_verse_end`     SMALLINT UNSIGNED DEFAULT NULL,
                `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_service` (`service_id`),
                KEY `idx_order` (`service_id`, `sort_order`),
                CONSTRAINT `fk_wsi_service`
                    FOREIGN KEY (`service_id`) REFERENCES `worship_service` (`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Migration douce : ajout des colonnes si elles n'existent pas encore
        // (pour les installations existantes qui avaient l'ancienne structure)
        $this->addColumnIfNotExists($con, 'worship_service',      'preacher_person_id',    'INT UNSIGNED DEFAULT NULL');
        $this->addColumnIfNotExists($con, 'worship_service_item', 'responsible_person_id', 'INT UNSIGNED DEFAULT NULL');
        $this->addColumnIfNotExists($con, 'worship_service_item', 'bible_book',            'TINYINT UNSIGNED DEFAULT NULL');
        $this->addColumnIfNotExists($con, 'worship_service_item', 'bible_chapter',         'SMALLINT UNSIGNED DEFAULT NULL');
        $this->addColumnIfNotExists($con, 'worship_service_item', 'bible_verse_start',     'SMALLINT UNSIGNED DEFAULT NULL');
        $this->addColumnIfNotExists($con, 'worship_service_item', 'bible_verse_end',       'SMALLINT UNSIGNED DEFAULT NULL');

        $this->createServiceTypeTableIfNotExists($con);

        $this->log('Worship Order plugin activated — tables ready');
    }

    private function addColumnIfNotExists(object $con, string $table, string $column, string $definition): void
    {
        $stmt = $con->prepare("
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = :table
              AND COLUMN_NAME  = :column
        ");
        $stmt->execute([':table' => $table, ':column' => $column]);

        if ((int) $stmt->fetchColumn() === 0) {
            $con->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            $this->log("Added column {$table}.{$column}");
        }
    }

    public function deactivate(): void {}

    public function uninstall(): void
    {
        $con = Propel::getConnection();
        $con->exec('DROP TABLE IF EXISTS `worship_service_item`');
        $con->exec('DROP TABLE IF EXISTS `worship_service`');
        $con->exec('DROP TABLE IF EXISTS `worship_service_type`');
        $this->log('Worship Order plugin uninstalled — tables dropped');
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function getConfigurationError(): ?string
    {
        return null;
    }

    // ------------------------------------------------------------------
    // Réglages
    // ------------------------------------------------------------------

    public function getPreachersGroupId(): int
    {
        return (int) $this->getConfigValue('preachers_group_id');
    }

    public function getResponsiblesGroupId(): int
    {
        return (int) $this->getConfigValue('responsibles_group_id');
    }

    public function getBibleVersion(): string
    {
        $v = $this->getConfigValue('bible_version');
        return $v ?: 'LSG';
    }

    public function saveSettings(array $data): void
    {
        $this->setConfigValue('preachers_group_id',    (string) ((int) ($data['preachers_group_id']    ?? 0)));
        $this->setConfigValue('responsibles_group_id', (string) ((int) ($data['responsibles_group_id'] ?? 0)));

        $version       = $data['bible_version'] ?? 'LSG';
        $validVersions = array_column($this->getBibleVersions(), 'code');
        if (!in_array($version, $validVersions, true)) {
            $version = 'LSG';
        }
        $this->setConfigValue('bible_version', $version);
    }

    // ------------------------------------------------------------------
    // Groupes ChurchCRM
    // ------------------------------------------------------------------

    public function getAllGroups(): array
    {
        return GroupQuery::create()
            ->orderByName()
            ->find()
            ->toArray();
    }

    public function getGroupMembers(int $groupId): array
    {
        if ($groupId <= 0) {
            return [];
        }

        $memberships = Person2group2roleP2g2rQuery::create()
            ->joinWithPerson()
            ->findByGroupId($groupId);

        $members = [];
        foreach ($memberships as $m) {
            $person = $m->getPerson();
            if ($person === null) {
                continue;
            }
            $members[] = [
                'id'   => $person->getId(),
                'name' => $person->getLastName() . ', ' . $person->getFirstName(),
            ];
        }

        usort($members, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $members;
    }

    // ------------------------------------------------------------------
    // Bible
    // ------------------------------------------------------------------

    public function getBibleStructure(): array
    {
        $path = $this->basePath . '/data/bible-structure.json';
        if (!file_exists($path)) {
            return [];
        }
        return json_decode(file_get_contents($path), true) ?? [];
    }

    public function getBibleVersions(): array
    {
        $path = $this->basePath . '/data/bible-versions.json';
        if (!file_exists($path)) {
            return [];
        }
        $data     = json_decode(file_get_contents($path), true) ?? [];
        $versions = $data['versions'] ?? [];

        foreach ($versions as &$v) {
            $v['available'] = $this->isBibleVersionAvailable($v['code']);
        }
        unset($v);

        return $versions;
    }

    public function isBibleVersionAvailable(string $versionCode): bool
    {
        if (empty($versionCode)) {
            return false;
        }
        $dir = $this->basePath . '/data/text/' . $versionCode;
        if (!is_dir($dir)) {
            return false;
        }
        $files = glob($dir . '/*.json');
        return !empty($files);
    }

    /**
     * Télécharge un livre depuis api.getbible.net et le sauvegarde au format compact.
     *
     * @return array{success:bool, book?:string, skipped?:bool, message?:string}
     */
    public function downloadBibleBook(string $versionCode, int $bookNum): array
    {
        // Trouver l'abréviation getbible pour cette version
        $versions      = $this->getBibleVersions();
        $getbibleAbbrev = null;
        foreach ($versions as $v) {
            if ($v['code'] === $versionCode) {
                $getbibleAbbrev = $v['getbible'] ?? null;
                break;
            }
        }
        if ($getbibleAbbrev === null) {
            return ['success' => false, 'message' => 'Version inconnue : ' . $versionCode];
        }

        // Trouver le code du livre (ex. GEN, EXO…)
        $structure = $this->getBibleStructure();
        $bookCode  = null;
        foreach ($structure['books'] ?? [] as $book) {
            if ((int) $book['num'] === $bookNum) {
                $bookCode = $book['code'];
                break;
            }
        }
        if ($bookCode === null) {
            return ['success' => false, 'message' => 'Livre inconnu : ' . $bookNum];
        }

        // Créer le répertoire de sortie
        $textDir = $this->basePath . '/data/text/' . $versionCode;
        if (!is_dir($textDir) && !@mkdir($textDir, 0755, true)) {
            return ['success' => false, 'message' => 'Impossible de créer le répertoire.'];
        }

        $outFile = $textDir . '/' . $bookCode . '.json';

        // Déjà téléchargé → rien à faire
        if (file_exists($outFile)) {
            return ['success' => true, 'book' => $bookCode, 'skipped' => true];
        }

        // Télécharger depuis getbible.net
        $url  = 'https://api.getbible.net/v2/' . $getbibleAbbrev . '/' . $bookNum . '.json';
        $body = $this->fetchUrlContent($url);
        if ($body === null) {
            return ['success' => false, 'message' => 'Échec du téléchargement pour ' . $bookCode];
        }

        $parsed = json_decode($body, true);
        if (!is_array($parsed) || !isset($parsed['chapters'])) {
            return ['success' => false, 'message' => 'JSON inattendu pour ' . $bookCode];
        }

        $compact = $this->convertBibleToCompact($parsed['chapters']);
        if (empty($compact)) {
            return ['success' => false, 'message' => 'Aucun chapitre extrait pour ' . $bookCode];
        }

        file_put_contents($outFile, json_encode($compact, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return ['success' => true, 'book' => $bookCode];
    }

    /**
     * Convertit le tableau chapters de l'API getbible.net v2 en format compact.
     * Entrée  : [{chapter:1, verses:[{verse:1, text:"…"}, …]}, …]
     * Sortie  : {"1": ["verset1", "verset2"], "2": […]}
     *
     * @param array $chapters
     * @return array<string, list<string>>
     */
    private function convertBibleToCompact(array $chapters): array
    {
        $result = [];
        foreach ($chapters as $chapterData) {
            if (!isset($chapterData['chapter'], $chapterData['verses'])) {
                continue;
            }
            $chapterNum = (string) $chapterData['chapter'];
            $verses     = [];
            foreach ($chapterData['verses'] as $v) {
                $verses[] = trim((string) ($v['text'] ?? ''));
            }
            if (!empty($verses)) {
                $result[$chapterNum] = $verses;
            }
        }
        uksort($result, fn($a, $b) => (int) $a <=> (int) $b);
        return $result;
    }

    /**
     * Télécharge une URL et retourne le contenu, ou null en cas d'erreur.
     */
    private function fetchUrlContent(string $url, int $timeoutSec = 30): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $timeoutSec,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT      => 'meeting-outlines-plugin/1.0',
                CURLOPT_ENCODING       => 'gzip',
            ]);
            $body   = curl_exec($ch);
            $code   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errNo  = curl_errno($ch);
            curl_close($ch);

            if ($errNo !== 0 || $code < 200 || $code >= 300 || $body === false) {
                return null;
            }
            return (string) $body;
        }

        // Fallback sans cURL
        $ctx  = stream_context_create(['http' => ['timeout' => $timeoutSec]]);
        $body = @file_get_contents($url, false, $ctx);
        return $body !== false ? $body : null;
    }

    /**
     * Retourne les versets demandés depuis le fichier texte local de la version configurée.
     *
     * Le fichier attendu : data/text/{VERSION}/{BOOKCODE}.json
     * Format compact     : {"1": ["verset1", "verset2", …], "2": […]}
     *
     * @return array<int, string>|null  [numéroVerset => texte] ou null si le fichier n'existe pas
     */
    public function getBibleVerses(int $bookNum, int $chapter, int $verseStart, ?int $verseEnd): ?array
    {
        $version   = $this->getBibleVersion();
        $structure = $this->getBibleStructure();

        $bookCode = null;
        foreach ($structure['books'] ?? [] as $book) {
            if ((int) $book['num'] === $bookNum) {
                $bookCode = $book['code'];
                break;
            }
        }
        if ($bookCode === null) {
            return null;
        }

        $path = $this->basePath . '/data/text/' . $version . '/' . $bookCode . '.json';
        if (!file_exists($path)) {
            return null;
        }

        $chapterMap = json_decode((string) file_get_contents($path), true);
        if (!is_array($chapterMap)) {
            return null;
        }

        $verses = $chapterMap[(string) $chapter] ?? null;
        if (!is_array($verses)) {
            return null;
        }

        $end    = $verseEnd ?? $verseStart;
        $result = [];
        for ($v = $verseStart; $v <= $end; $v++) {
            $idx = $v - 1;
            if (!array_key_exists($idx, $verses)) {
                break;
            }
            $result[$v] = $verses[$idx];
        }

        return $result ?: null;
    }

    public function formatBibleRef(int $bookNum, int $chapter, int $verseStart, ?int $verseEnd, string $lang = 'fr'): string
    {
        $structure = $this->getBibleStructure();
        $bookName  = '';
        foreach ($structure['books'] ?? [] as $book) {
            if ((int) $book['num'] === $bookNum) {
                $bookName = $lang === 'fr' ? $book['fr'] : $book['en'];
                break;
            }
        }

        if (empty($bookName)) {
            return '';
        }

        $ref = "{$bookName} {$chapter}:{$verseStart}";
        if ($verseEnd && $verseEnd > $verseStart) {
            $ref .= "-{$verseEnd}";
        }

        return $ref;
    }

    // ------------------------------------------------------------------
    // Services
    // ------------------------------------------------------------------

    public function getServices(int $limit = 50, int $offset = 0): array
    {
        $con  = Propel::getConnection();
        $stmt = $con->prepare('
            SELECT s.*,
                   COUNT(i.id) AS item_count,
                   COALESCE(
                       CONCAT(pp.per_LastName, \', \', pp.per_FirstName),
                       NULLIF(s.preacher, \'\')
                   ) AS preacher_display,
                   CONCAT(pr.per_LastName, \', \', pr.per_FirstName) AS president_display
            FROM   worship_service s
            LEFT   JOIN worship_service_item i  ON i.service_id  = s.id
            LEFT   JOIN person_per            pp ON pp.per_ID     = s.preacher_person_id
            LEFT   JOIN person_per            pr ON pr.per_ID     = s.president_person_id
            GROUP  BY s.id
            ORDER  BY s.date DESC
            LIMIT  :limit OFFSET :offset
        ');
        $stmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getService(int $id): ?array
    {
        $con  = Propel::getConnection();
        $stmt = $con->prepare('
            SELECT s.*,
                   COALESCE(
                       CONCAT(pp.per_LastName, \', \', pp.per_FirstName),
                       NULLIF(s.preacher, \'\')
                   ) AS preacher_display,
                   CONCAT(pr.per_LastName, \', \', pr.per_FirstName) AS president_display
            FROM   worship_service s
            LEFT   JOIN person_per pp ON pp.per_ID = s.preacher_person_id
            LEFT   JOIN person_per pr ON pr.per_ID = s.president_person_id
            WHERE  s.id = :id
        ');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function getServiceItems(int $serviceId): array
    {
        $con  = Propel::getConnection();
        $stmt = $con->prepare('
            SELECT i.*,
                   COALESCE(
                       CONCAT(p.per_LastName, \', \', p.per_FirstName),
                       NULLIF(i.responsible, \'\')
                   ) AS responsible_display
            FROM   worship_service_item i
            LEFT   JOIN person_per p ON p.per_ID = i.responsible_person_id
            WHERE  i.service_id = :service_id
            ORDER  BY i.sort_order ASC, i.id ASC
        ');
        $stmt->execute([':service_id' => $serviceId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createService(array $data): int
    {
        $con  = Propel::getConnection();
        $stmt = $con->prepare('
            INSERT INTO worship_service (date, title, type, preacher, preacher_person_id, president_person_id, notes, status)
            VALUES (:date, :title, :type, :preacher, :preacher_person_id, :president_person_id, :notes, :status)
        ');
        $preacherPersonId  = ($data['preacher_person_id']  ?? '') !== '' ? (int) $data['preacher_person_id']  : null;
        $presidentPersonId = ($data['president_person_id'] ?? '') !== '' ? (int) $data['president_person_id'] : null;
        $stmt->execute([
            ':date'                => $data['date'],
            ':title'               => $data['title'],
            ':type'                => $data['type']     ?? 'sunday',
            ':preacher'            => $data['preacher'] ?? '',
            ':preacher_person_id'  => $preacherPersonId,
            ':president_person_id' => $presidentPersonId,
            ':notes'               => $data['notes']    ?? null,
            ':status'              => $data['status']   ?? 'draft',
        ]);

        return (int) $con->lastInsertId();
    }

    public function updateService(int $id, array $data): bool
    {
        $con  = Propel::getConnection();
        $stmt = $con->prepare('
            UPDATE worship_service
            SET date = :date, title = :title, type = :type,
                preacher = :preacher, preacher_person_id = :preacher_person_id,
                president_person_id = :president_person_id,
                notes = :notes, status = :status
            WHERE id = :id
        ');

        $preacherPersonId  = ($data['preacher_person_id']  ?? '') !== '' ? (int) $data['preacher_person_id']  : null;
        $presidentPersonId = ($data['president_person_id'] ?? '') !== '' ? (int) $data['president_person_id'] : null;
        return $stmt->execute([
            ':id'                  => $id,
            ':date'                => $data['date'],
            ':title'               => $data['title'],
            ':type'                => $data['type']     ?? 'sunday',
            ':preacher'            => $data['preacher'] ?? '',
            ':preacher_person_id'  => $preacherPersonId,
            ':president_person_id' => $presidentPersonId,
            ':notes'               => $data['notes']    ?? null,
            ':status'              => $data['status']   ?? 'draft',
        ]);
    }

    public function deleteService(int $id): bool
    {
        $con  = Propel::getConnection();
        $stmt = $con->prepare('DELETE FROM worship_service WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    public function duplicateService(int $id): int
    {
        $con = Propel::getConnection();

        $stmt = $con->prepare('SELECT * FROM worship_service WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $source = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$source) {
            throw new \RuntimeException('Meeting not found: ' . $id);
        }

        $stmt = $con->prepare('
            INSERT INTO worship_service
                (date, title, type, preacher, preacher_person_id, president_person_id, notes, status)
            VALUES
                (:date, :title, :type, :preacher, :preacher_person_id, :president_person_id, :notes, \'draft\')
        ');
        $stmt->execute([
            ':date'                => $source['date'],
            ':title'               => dgettext('meeting-outlines', 'Copy of') . ' ' . $source['title'],
            ':type'                => $source['type'],
            ':preacher'            => $source['preacher'],
            ':preacher_person_id'  => $source['preacher_person_id'],
            ':president_person_id' => $source['president_person_id'],
            ':notes'               => $source['notes'],
        ]);

        $newId = (int) $con->lastInsertId();

        $items = $con->prepare('
            SELECT item_type, title, description, duration_minutes, responsible,
                   responsible_person_id, bible_book, bible_chapter, bible_verse_start, bible_verse_end
            FROM worship_service_item
            WHERE service_id = :service_id
            ORDER BY sort_order ASC, id ASC
        ');
        $items->execute([':service_id' => $id]);

        $insert = $con->prepare('
            INSERT INTO worship_service_item
                (service_id, sort_order, item_type, title, description, duration_minutes,
                 responsible, responsible_person_id, bible_book, bible_chapter, bible_verse_start, bible_verse_end)
            VALUES
                (:service_id, :sort_order, :item_type, :title, :description, :duration_minutes,
                 :responsible, :responsible_person_id, :bible_book, :bible_chapter, :bible_verse_start, :bible_verse_end)
        ');

        foreach ($items->fetchAll(\PDO::FETCH_ASSOC) as $pos => $item) {
            $insert->execute([
                ':service_id'           => $newId,
                ':sort_order'           => $pos,
                ':item_type'            => $item['item_type'],
                ':title'                => $item['title'],
                ':description'          => $item['description'],
                ':duration_minutes'     => $item['duration_minutes'],
                ':responsible'          => $item['responsible'],
                ':responsible_person_id'=> $item['responsible_person_id'],
                ':bible_book'           => $item['bible_book'],
                ':bible_chapter'        => $item['bible_chapter'],
                ':bible_verse_start'    => $item['bible_verse_start'],
                ':bible_verse_end'      => $item['bible_verse_end'],
            ]);
        }

        return $newId;
    }

    public function createItem(int $serviceId, array $data): int
    {
        $con  = Propel::getConnection();
        $stmt = $con->prepare('
            SELECT COALESCE(MAX(sort_order), -1) + 1
            FROM worship_service_item WHERE service_id = :service_id
        ');
        $stmt->execute([':service_id' => $serviceId]);
        $nextOrder = (int) $stmt->fetchColumn();

        $stmt = $con->prepare('
            INSERT INTO worship_service_item
                (service_id, sort_order, item_type, title, description,
                 duration_minutes, responsible, responsible_person_id,
                 bible_book, bible_chapter, bible_verse_start, bible_verse_end)
            VALUES
                (:service_id, :sort_order, :item_type, :title, :description,
                 :duration_minutes, :responsible, :responsible_person_id,
                 :bible_book, :bible_chapter, :bible_verse_start, :bible_verse_end)
        ');
        $stmt->execute($this->buildItemParams($serviceId, $nextOrder, $data));

        return (int) $con->lastInsertId();
    }

    public function updateItem(int $id, array $data): bool
    {
        $con  = Propel::getConnection();
        $stmt = $con->prepare('
            UPDATE worship_service_item
            SET item_type = :item_type, title = :title, description = :description,
                duration_minutes = :duration_minutes, responsible = :responsible,
                responsible_person_id = :responsible_person_id,
                bible_book = :bible_book, bible_chapter = :bible_chapter,
                bible_verse_start = :bible_verse_start, bible_verse_end = :bible_verse_end
            WHERE id = :id
        ');
        $params = $this->buildItemParams(null, null, $data);
        $params[':id'] = $id;

        return $stmt->execute($params);
    }

    private function buildItemParams(?int $serviceId, ?int $sortOrder, array $data): array
    {
        $params = [
            ':item_type'            => $data['item_type']              ?? 'other',
            ':title'                => $data['title']                  ?? '',
            ':description'          => $data['description']            ?: null,
            ':duration_minutes'     => ($data['duration_minutes'] !== '' && $data['duration_minutes'] !== null)
                                           ? (int) $data['duration_minutes'] : null,
            ':responsible'          => $data['responsible']            ?? '',
            ':responsible_person_id'=> ($data['responsible_person_id'] ?? '') !== ''
                                           ? (int) $data['responsible_person_id'] : null,
            ':bible_book'           => ($data['bible_book'] ?? '') !== ''
                                           ? (int) $data['bible_book'] : null,
            ':bible_chapter'        => ($data['bible_chapter'] ?? '') !== ''
                                           ? (int) $data['bible_chapter'] : null,
            ':bible_verse_start'    => ($data['bible_verse_start'] ?? '') !== ''
                                           ? (int) $data['bible_verse_start'] : null,
            ':bible_verse_end'      => ($data['bible_verse_end'] ?? '') !== ''
                                           ? (int) $data['bible_verse_end'] : null,
        ];

        if ($serviceId !== null) $params[':service_id']  = $serviceId;
        if ($sortOrder  !== null) $params[':sort_order']  = $sortOrder;

        return $params;
    }

    public function deleteItem(int $id): bool
    {
        $con  = Propel::getConnection();
        $stmt = $con->prepare('DELETE FROM worship_service_item WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    public function reorderItems(int $serviceId, array $orderedIds): bool
    {
        $con = Propel::getConnection();
        $con->beginTransaction();
        try {
            $stmt = $con->prepare('
                UPDATE worship_service_item SET sort_order = :sort_order
                WHERE id = :id AND service_id = :service_id
            ');
            foreach ($orderedIds as $position => $itemId) {
                $stmt->execute([
                    ':sort_order'  => $position,
                    ':id'          => (int) $itemId,
                    ':service_id'  => $serviceId,
                ]);
            }
            $con->commit();
            return true;
        } catch (\Throwable $e) {
            $con->rollBack();
            $this->log('reorderItems failed: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    public function getItem(int $id): ?array
    {
        $con  = Propel::getConnection();
        $stmt = $con->prepare('
            SELECT i.*,
                   COALESCE(
                       CONCAT(p.per_LastName, \', \', p.per_FirstName),
                       NULLIF(i.responsible, \'\')
                   ) AS responsible_display
            FROM   worship_service_item i
            LEFT   JOIN person_per p ON p.per_ID = i.responsible_person_id
            WHERE  i.id = :id
        ');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    // ------------------------------------------------------------------
    // Notification par email
    // ------------------------------------------------------------------

    /**
     * Retourne les personnes uniques impliquées dans un culte (prédicateur,
     * président, responsables d'éléments) qui ont une adresse email.
     *
     * @return array<int, array{id:int, name:string, email:string, roles:string[]}>
     */
    public function getServiceParticipantsWithEmail(int $serviceId): array
    {
        $con     = Propel::getConnection();
        $service = $this->getService($serviceId);

        if (!$service) {
            return [];
        }

        // Collecte des IDs avec leur(s) rôle(s)
        $personRoles = [];

        $addRole = function (int $personId, string $role) use (&$personRoles): void {
            if ($personId <= 0) return;
            if (!isset($personRoles[$personId])) {
                $personRoles[$personId] = [];
            }
            if (!in_array($role, $personRoles[$personId], true)) {
                $personRoles[$personId][] = $role;
            }
        };

        $addRole((int) ($service['preacher_person_id'] ?? 0),  dgettext('meeting-outlines', 'Preacher'));
        $addRole((int) ($service['president_person_id'] ?? 0), dgettext('meeting-outlines', 'President'));

        $items = $this->getServiceItems($serviceId);
        foreach ($items as $item) {
            $addRole((int) ($item['responsible_person_id'] ?? 0), dgettext('meeting-outlines', 'Responsible'));
        }

        if (empty($personRoles)) {
            return [];
        }

        $ids      = array_keys($personRoles);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt     = $con->prepare("
            SELECT per_ID, per_FirstName, per_LastName, per_email
            FROM   person_per
            WHERE  per_ID IN ({$placeholders})
              AND  per_email IS NOT NULL
              AND  per_email <> ''
        ");
        $stmt->execute($ids);

        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $id = (int) $row['per_ID'];
            $result[] = [
                'id'    => $id,
                'name'  => $row['per_LastName'] . ', ' . $row['per_FirstName'],
                'email' => $row['per_email'],
                'roles' => $personRoles[$id] ?? [],
            ];
        }

        return $result;
    }

    /**
     * Envoie le déroulement du culte par email à tous les participants identifiés.
     *
     * @return array{sent:int, skipped:int, errors:string[]}
     */
    public function sendServiceNotification(int $serviceId): array
    {
        if (!SystemConfig::hasValidMailServerSettings()) {
            throw new \RuntimeException(dgettext('meeting-outlines', 'SMTP is not configured.'));
        }

        $service = $this->getService($serviceId);
        if (!$service) {
            throw new \RuntimeException(dgettext('meeting-outlines', 'Meeting not found.'));
        }

        $items = $this->getServiceItems($serviceId);

        // Compte les personnes assignées (avec ou sans email) pour le calcul des ignorés
        $allPersonIds = array_filter(array_unique([
            (int) ($service['preacher_person_id']  ?? 0),
            (int) ($service['president_person_id'] ?? 0),
            ...array_map(fn($i) => (int) ($i['responsible_person_id'] ?? 0), $items),
        ]), fn($id) => $id > 0);

        $participants = $this->getServiceParticipantsWithEmail($serviceId);

        $locale = SystemConfig::getValue('sLanguage') ?: 'en_US';
        if (class_exists('IntlDateFormatter')) {
            $fmt       = new \IntlDateFormatter($locale, \IntlDateFormatter::FULL, \IntlDateFormatter::NONE);
            $dateLabel = $fmt->format(strtotime($service['date']));
        } else {
            $dateLabel = date('d/m/Y', strtotime($service['date']));
        }

        $subject = sprintf(
            '[%s] %s — %s',
            ChurchMetaData::getChurchName(),
            htmlspecialchars($service['title']),
            $dateLabel
        );

        $html = $this->buildNotificationHtml($service, $items, $dateLabel);

        $sent    = 0;
        $skipped = max(0, count($allPersonIds) - count($participants));
        $errors  = [];

        foreach ($participants as $p) {
            try {
                $mail = new PHPMailer(true);
                $mail->IsSMTP();
                $mail->CharSet   = 'UTF-8';
                $mail->Timeout   = SystemConfig::getIntValue('iSMTPTimeout');
                $mail->Host      = SystemConfig::getValue('sSMTPHost');
                $mail->SMTPAutoTLS = SystemConfig::getBooleanValue('bPHPMailerAutoTLS');
                $mail->SMTPSecure  = SystemConfig::getValue('sPHPMailerSMTPSecure');
                $mail->SMTPDebug   = 0;

                if (SystemConfig::getBooleanValue('bSMTPAuth')) {
                    $mail->SMTPAuth  = true;
                    $mail->Username  = SystemConfig::getValue('sSMTPUser');
                    $mail->Password  = SystemConfig::getValue('sSMTPPass');
                }

                $mail->setFrom(ChurchMetaData::getChurchEmail(), ChurchMetaData::getChurchName());
                $mail->addAddress($p['email'], $p['name']);
                $mail->Subject = $subject;
                $mail->isHTML(true);
                $mail->Body    = $html;
                $mail->AltBody = strip_tags(str_replace(['<tr>', '<td>', '</td>'], ["\n", ' | ', ''], $html));
                $mail->send();
                $sent++;
            } catch (\Throwable $e) {
                $errors[] = $p['name'] . ': ' . $e->getMessage();
            }
        }

        return ['sent' => $sent, 'skipped' => $skipped, 'errors' => $errors];
    }

    private function buildNotificationHtml(array $service, array $items, string $dateLabel): string
    {
        $itemTypes    = self::getItemTypes();
        $churchName   = htmlspecialchars(ChurchMetaData::getChurchName());
        $title        = htmlspecialchars($service['title']);
        $preacher     = htmlspecialchars($service['preacher_display'] ?? $service['preacher'] ?? '');
        $printUrl     = SystemURLs::getURL() . '/plugins/meeting-outlines/services/' . (int) $service['id'] . '/print';

        $rows = '';
        foreach ($items as $i => $item) {
            $type        = htmlspecialchars($itemTypes[$item['item_type']] ?? $item['item_type']);
            $itemTitle   = htmlspecialchars($item['title']);
            $responsible = htmlspecialchars($item['responsible_display'] ?? $item['responsible'] ?? '');
            $duration    = $item['duration_minutes'] ? (int) $item['duration_minutes'] . ' min' : '';
            $bg          = ($i % 2 === 0) ? '#ffffff' : '#f9f9f9';

            $rows .= "
            <tr style=\"background:{$bg}\">
                <td style=\"padding:6px 10px;border:1px solid #ddd;color:#555;font-style:italic\">{$type}</td>
                <td style=\"padding:6px 10px;border:1px solid #ddd;font-weight:bold\">{$itemTitle}</td>
                <td style=\"padding:6px 10px;border:1px solid #ddd;color:#444\">{$responsible}</td>
                <td style=\"padding:6px 10px;border:1px solid #ddd;text-align:center;color:#666\">{$duration}</td>
            </tr>";
        }

        $typeHeader        = htmlspecialchars(dgettext('meeting-outlines', 'Item Type'));
        $titleHeader       = htmlspecialchars(dgettext('meeting-outlines', 'Title'));
        $responsibleHeader = htmlspecialchars(dgettext('meeting-outlines', 'Responsible'));
        $durationHeader    = htmlspecialchars(dgettext('meeting-outlines', 'Duration (minutes)'));
        $printLabel        = htmlspecialchars(dgettext('meeting-outlines', 'Print Order'));

        return "<!DOCTYPE html>
<html>
<head><meta charset=\"UTF-8\"></head>
<body style=\"font-family:Arial,sans-serif;font-size:13px;color:#222;margin:0;padding:20px\">
  <div style=\"max-width:600px;margin:0 auto\">
    <p style=\"color:#888;font-size:11px;margin-bottom:4px\">{$churchName}</p>
    <h2 style=\"margin:0 0 4px\">{$title}</h2>
    <p style=\"margin:0 0 16px;color:#555\">{$dateLabel}" . ($preacher ? " &nbsp;·&nbsp; {$preacher}" : '') . "</p>
    <table style=\"width:100%;border-collapse:collapse\">
      <thead>
        <tr style=\"background:#f0f0f0\">
          <th style=\"padding:6px 10px;border:1px solid #ccc;text-align:left\">{$typeHeader}</th>
          <th style=\"padding:6px 10px;border:1px solid #ccc;text-align:left\">{$titleHeader}</th>
          <th style=\"padding:6px 10px;border:1px solid #ccc;text-align:left\">{$responsibleHeader}</th>
          <th style=\"padding:6px 10px;border:1px solid #ccc;text-align:center\">{$durationHeader}</th>
        </tr>
      </thead>
      <tbody>{$rows}</tbody>
    </table>
    <p style=\"margin-top:16px\">
      <a href=\"{$printUrl}\" style=\"color:#2e7d32\">{$printLabel}</a>
    </p>
  </div>
</body>
</html>";
    }

    // ------------------------------------------------------------------
    // Helpers statiques
    // ------------------------------------------------------------------

    /**
     * Retourne les types de réunion sous la forme slug => label traduit.
     * Lit la table worship_service_type ; tombe en arrière sur les défauts si la table n'existe pas.
     */
    public function getServiceTypes(): array
    {
        try {
            $con  = Propel::getConnection();
            $stmt = $con->prepare('SELECT slug, label, is_system FROM worship_service_type ORDER BY is_system DESC, sort_order ASC, label ASC');
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return $this->getDefaultServiceTypes();
            }

            $result = [];
            foreach ($rows as $row) {
                $key = $row['slug'];
                if ((int) $row['is_system'] && isset(self::SYSTEM_TYPE_KEYS[$key])) {
                    $result[$key] = dgettext('meeting-outlines', self::SYSTEM_TYPE_KEYS[$key]);
                } else {
                    $result[$key] = $row['label'];
                }
            }

            return $result;
        } catch (\Throwable $e) {
            return $this->getDefaultServiceTypes();
        }
    }

    /**
     * Retourne tous les types avec leurs métadonnées (slug, label, is_system) — pour la page Paramètres.
     *
     * @return array<int, array{slug:string, label:string, is_system:int}>
     */
    public function getAllServiceTypes(): array
    {
        try {
            $con  = Propel::getConnection();
            $stmt = $con->prepare('SELECT slug, label, is_system FROM worship_service_type ORDER BY is_system DESC, sort_order ASC, label ASC');
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as &$row) {
                if ((int) $row['is_system'] && isset(self::SYSTEM_TYPE_KEYS[$row['slug']])) {
                    $row['label'] = dgettext('meeting-outlines', self::SYSTEM_TYPE_KEYS[$row['slug']]);
                }
            }

            return $rows;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Crée un type de réunion personnalisé. Retourne le slug généré.
     */
    public function createServiceType(string $label): string
    {
        $label = trim($label);
        if ($label === '') {
            throw new \InvalidArgumentException(dgettext('meeting-outlines', 'Label is required.'));
        }

        $con  = Propel::getConnection();
        $slug = $this->uniqueServiceTypeSlug($con, $label);

        $stmt = $con->prepare('INSERT INTO worship_service_type (slug, label, is_system, sort_order) VALUES (?, ?, 0, 99)');
        $stmt->execute([$slug, $label]);

        return $slug;
    }

    /**
     * Supprime un type de réunion personnalisé.
     * Lance une exception si le type est système ou s'il est utilisé par au moins une réunion.
     */
    public function deleteServiceType(string $slug): void
    {
        $con = Propel::getConnection();

        $stmt = $con->prepare('SELECT is_system FROM worship_service_type WHERE slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            throw new \RuntimeException(dgettext('meeting-outlines', 'Meeting type not found.'));
        }
        if ((int) $row['is_system']) {
            throw new \RuntimeException(dgettext('meeting-outlines', 'Built-in types cannot be deleted.'));
        }

        $stmt = $con->prepare('SELECT COUNT(*) FROM worship_service WHERE type = ?');
        $stmt->execute([$slug]);
        if ((int) $stmt->fetchColumn() > 0) {
            throw new \RuntimeException(dgettext('meeting-outlines', 'This meeting type is in use and cannot be deleted.'));
        }

        $stmt = $con->prepare('DELETE FROM worship_service_type WHERE slug = ?');
        $stmt->execute([$slug]);
    }

    private function getDefaultServiceTypes(): array
    {
        return [
            'sunday'  => dgettext('meeting-outlines', 'Sunday Meeting'),
            'prayer'  => dgettext('meeting-outlines', 'Prayer Meeting'),
            'special' => dgettext('meeting-outlines', 'Special Meeting'),
            'other'   => dgettext('meeting-outlines', 'Other'),
        ];
    }

    private function createServiceTypeTableIfNotExists(object $con): void
    {
        $con->exec("
            CREATE TABLE IF NOT EXISTS `worship_service_type` (
                `slug`       VARCHAR(50)          NOT NULL,
                `label`      VARCHAR(100)         NOT NULL DEFAULT '',
                `is_system`  TINYINT(1)           NOT NULL DEFAULT 0,
                `sort_order` SMALLINT UNSIGNED    NOT NULL DEFAULT 99,
                PRIMARY KEY (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Insère les types système s'ils n'existent pas encore (idempotent)
        $stmt = $con->prepare('INSERT IGNORE INTO worship_service_type (slug, label, is_system, sort_order) VALUES (?, ?, 1, ?)');
        foreach ([
            ['sunday',  'Sunday Meeting',  1],
            ['prayer',  'Prayer Meeting',  2],
            ['special', 'Special Meeting', 3],
            ['other',   'Other',           99],
        ] as $row) {
            $stmt->execute($row);
        }
    }

    private function uniqueServiceTypeSlug(object $con, string $label): string
    {
        $base = preg_replace('/[^a-z0-9]+/', '_', mb_strtolower($label));
        $base = trim($base ?? '', '_');
        $base = $base !== '' ? $base : 'type';
        $slug = $base;
        $i    = 2;
        $chk  = $con->prepare('SELECT COUNT(*) FROM worship_service_type WHERE slug = ?');

        while (true) {
            $chk->execute([$slug]);
            if ((int) $chk->fetchColumn() === 0) {
                break;
            }
            $slug = $base . '_' . $i++;
        }

        return $slug;
    }

    public static function getItemTypes(): array
    {
        return [
            'song'          => dgettext('meeting-outlines', 'Song'),
            'prayer'        => dgettext('meeting-outlines', 'Prayer'),
            'bible_reading' => dgettext('meeting-outlines', 'Bible Reading'),
            'sermon'        => dgettext('meeting-outlines', 'Sermon'),
            'offering'      => dgettext('meeting-outlines', 'Offering'),
            'announcements' => dgettext('meeting-outlines', 'Announcements'),
            'communion'     => dgettext('meeting-outlines', 'Communion'),
            'other'         => dgettext('meeting-outlines', 'Other'),
        ];
    }

    public static function getStatusLabels(): array
    {
        return [
            'draft'     => dgettext('meeting-outlines', 'Draft'),
            'published' => dgettext('meeting-outlines', 'Published'),
        ];
    }
}
