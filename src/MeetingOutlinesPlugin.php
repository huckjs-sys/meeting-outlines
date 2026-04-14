<?php

namespace ChurchCRM\Plugins\MeetingOutlines;

use ChurchCRM\Config\Menu\MenuItem;
use ChurchCRM\dto\SystemConfig;
use ChurchCRM\model\ChurchCRM\GroupQuery;
use ChurchCRM\model\ChurchCRM\Person2group2roleP2g2rQuery;
use ChurchCRM\Plugin\AbstractPlugin;
use ChurchCRM\Plugin\Hook\HookManager;
use ChurchCRM\Plugin\Hooks;
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
        return dgettext('meeting-outlines','Meeting Outlines');
    }

    public function getDescription(): string
    {
        return dgettext('meeting-outlines','Manage the outlines of church meetings.');
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
        } catch (\Throwable $e) {
            $this->log('Migration check failed: ' . $e->getMessage(), 'error');
        }
    }

    public function addWorshipMenu(array $menus): array
    {
        if (!$this->isEnabled()) {
            return $menus;
        }

        $menu = new MenuItem(dgettext('meeting-outlines','Church Meetings'), '', true, 'fa-church');
        $menu->addSubMenu(new MenuItem(
            dgettext('meeting-outlines','Meeting Outlines'),
            'plugins/meeting-outlines/services',
            true,
            'fa-list-ol'
        ));
        $menu->addSubMenu(new MenuItem(
            dgettext('meeting-outlines','Meeting Settings'),
            'plugins/meeting-outlines/settings',
            true,
            'fa-gear'
        ));

        $menus['ChurchMeetings'] = $menu;

        return $menus;
    }

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
        $this->setConfigValue('bible_version',         $data['bible_version'] ?? 'LSG');
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
        $data = json_decode(file_get_contents($path), true) ?? [];
        return $data['versions'] ?? [];
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
    // Helpers statiques
    // ------------------------------------------------------------------

    public static function getServiceTypes(): array
    {
        return [
            'sunday'  => dgettext('meeting-outlines','Sunday Meeting'),
            'prayer'  => dgettext('meeting-outlines','Prayer Meeting'),
            'special' => dgettext('meeting-outlines','Special Meeting'),
            'other'   => dgettext('meeting-outlines','Other'),
        ];
    }

    public static function getItemTypes(): array
    {
        return [
            'song'          => dgettext('meeting-outlines','Song'),
            'prayer'        => dgettext('meeting-outlines','Prayer'),
            'bible_reading' => dgettext('meeting-outlines','Bible Reading'),
            'sermon'        => dgettext('meeting-outlines','Sermon'),
            'offering'      => dgettext('meeting-outlines','Offering'),
            'announcements' => dgettext('meeting-outlines','Announcements'),
            'communion'     => dgettext('meeting-outlines','Communion'),
            'other'         => dgettext('meeting-outlines','Other'),
        ];
    }

    public static function getStatusLabels(): array
    {
        return [
            'draft'     => dgettext('meeting-outlines','Draft'),
            'published' => dgettext('meeting-outlines','Published'),
        ];
    }
}
