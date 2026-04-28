# Meeting Outlines — ChurchCRM Community Plugin

Manage the outlines of church meetings. Create, edit and print meeting programs with ordered items (songs, prayers, Bible readings, sermon, offering, etc.).

- **Plugin ID:** `meeting-outlines`
- **Type:** Community
- **Author:** Eoles Conseil
- **Minimum ChurchCRM version:** 7.1.x (Koineo fork)

---

## Features

- Create, edit and delete church meetings (date, title, type, preacher, status, notes)
- Build a program by adding ordered items (drag & drop reordering via SortableJS)
- Item types: Song, Prayer, Bible Reading, Sermon, Offering, Announcements, Communion, Other
- Each item can have a title, description, duration and a responsible person
- **Duplicate a meeting** — copies the full outline (all items) as a new draft in one click
- **Custom meeting types** — create and delete custom types from Meeting Settings; 4 built-in types (Sunday, Prayer, Special, Other) are protected and cannot be deleted
- **Email notification** — send the meeting outline to all assigned participants (preacher, president, item responsibles) who have a valid email in the CRM; requires SMTP configured in ChurchCRM
- **Preachers & Responsibles** linked to ChurchCRM group manager (dropdown selects from configured groups)
- **Bible reference selector** (book / chapter / from verse / to verse) for Bible Reading items
- **Bible versions:** LSG 1910 (built-in), Darby, Crampon, KJV, ASV — configurable from Meeting Settings
- Draft / Published status workflow
- Clean printable view (standalone page) — shows preacher, responsible person and duration per item; date localised to CRM locale
- Fully internationalised via plugin-local gettext — add any language without touching core CRM files

---

## File Structure

```
meeting-outlines/
├── README.md                       ← this file
├── plugin.json                     ← plugin manifest (id, mainClass, routesFile, hooks…)
├── help.json                       ← contextual help shown in the plugin management UI
├── data/
│   ├── bible-structure.json        ← 66 books, chapter/verse counts (OT + NT)
│   └── bible-versions.json         ← available Bible versions (LSG, FRD, FRC, KJV, ASV)
├── locale/
│   ├── textdomain/                 ← PHP gettext .mo files (plugin-local, independent of core)
│   │   └── fr_FR/LC_MESSAGES/
│   │       ├── meeting-outlines.po ← French source strings
│   │       └── meeting-outlines.mo ← compiled binary (compile with polib, see i18n section)
│   └── i18n/                       ← JS translations (flat JSON per locale, optional)
├── src/
│   └── MeetingOutlinesPlugin.php   ← main plugin class (boot, activate, uninstall, data access)
├── routes/
│   └── routes.php                  ← Slim 4 routes: MVC pages + REST API
└── views/
    ├── list.php                    ← list of meetings (DataTables) + duplicate/delete actions
    ├── edit.php                    ← create/edit a meeting + manage its outline items
    ├── settings.php                ← admin settings (groups, Bible version)
    └── print.php                   ← standalone printable meeting outline
```

---

## Installation

### 1. Copy the plugin

Drop this directory into:
```
src/plugins/community/meeting-outlines/
```

### 2. Register the config keys — REQUIRED MANUAL STEP

> **Why this step is needed:** `SystemConfig` in ChurchCRM is a closed, hardcoded registry
> of configuration keys. Any plugin that needs to persist state (including the simple
> enabled/disabled flag) must declare its keys there. Keys that are not registered cause an
> `"An invalid configuration name has been requested"` exception that silently prevents
> activation. See the comment block in `src/ChurchCRM/dto/SystemConfig.php` for the full
> explanation and the numbering convention.

Open `src/ChurchCRM/dto/SystemConfig.php` and add the following lines in the
**Community plugins** section (around line 340, after the `external-backup` block):

```php
// Meeting Outlines Plugin (community)  — slots 3100–3109
'plugin.meeting-outlines.enabled'               => new ConfigItem(3100, 'plugin.meeting-outlines.enabled',               'boolean', '0'),
'plugin.meeting-outlines.preachers_group_id'    => new ConfigItem(3101, 'plugin.meeting-outlines.preachers_group_id',    'text',    ''),
'plugin.meeting-outlines.responsibles_group_id' => new ConfigItem(3102, 'plugin.meeting-outlines.responsibles_group_id', 'text',    ''),
'plugin.meeting-outlines.bible_version'         => new ConfigItem(3103, 'plugin.meeting-outlines.bible_version',         'text',    'LSG'),
```

The next available community slot after this plugin is **3110**.

### 3. Activate the plugin

Go to **Admin → Plugins**, find **Meeting Outlines** in the *Community Plugins* section,
and click **Activate**.

`activate()` creates the two database tables automatically (see below).
`boot()` runs safe schema migrations on every request, so new columns are added
automatically even on existing installs without needing a re-activation.

---

## Database Tables

Created automatically on activation, dropped on uninstall.

### `worship_service`

| Column | Type | Description |
|---|---|---|
| `id` | INT UNSIGNED PK AUTO | |
| `date` | DATE | Date of the meeting |
| `title` | VARCHAR(200) | e.g. "Sunday Morning Meeting" |
| `type` | VARCHAR(50) | slug from `worship_service_type` (e.g. `sunday`, `prayer`, custom slugs) |
| `preacher` | VARCHAR(150) | Free-text preacher name (fallback) |
| `preacher_person_id` | INT UNSIGNED | FK to CRM person (from configured group) |
| `notes` | TEXT | Internal notes |
| `status` | ENUM | `draft` or `published` |
| `created_at` | DATETIME | Auto |
| `updated_at` | DATETIME | Auto on update |

### `worship_service_item`

| Column | Type | Description |
|---|---|---|
| `id` | INT UNSIGNED PK AUTO | |
| `service_id` | INT UNSIGNED FK | → `worship_service.id` (CASCADE DELETE) |
| `sort_order` | SMALLINT UNSIGNED | Display position (0-based) |
| `item_type` | VARCHAR(50) | `song` / `prayer` / `bible_reading` / `sermon` / `offering` / `announcements` / `communion` / `other` |
| `title` | VARCHAR(200) | Item title |
| `description` | TEXT | Notes, lyrics reference, Bible passage… |
| `duration_minutes` | SMALLINT UNSIGNED | Estimated duration (optional) |
| `responsible` | VARCHAR(150) | Free-text responsible (fallback) |
| `responsible_person_id` | INT UNSIGNED | FK to CRM person (from configured group) |
| `bible_book` | TINYINT UNSIGNED | Book number (1–66) |
| `bible_chapter` | SMALLINT UNSIGNED | Chapter number |
| `bible_verse_start` | SMALLINT UNSIGNED | Starting verse |
| `bible_verse_end` | SMALLINT UNSIGNED | Ending verse (optional) |
| `created_at` | DATETIME | Auto |

### `worship_service_type`

| Column | Type | Description |
|---|---|---|
| `slug` | VARCHAR(50) PK | Unique identifier used as `worship_service.type` value |
| `label` | VARCHAR(100) | Display label (stored in English for system types, free text for custom) |
| `is_system` | TINYINT(1) | `1` = built-in (cannot be deleted), `0` = custom |
| `sort_order` | SMALLINT UNSIGNED | Display order (system types 1–3, custom types default to 99) |

Seeded on activation with the 4 built-in types (`sunday`, `prayer`, `special`, `other`).
`runMigrations()` also creates and seeds this table idempotently so existing installs get it without re-activation.

---

## Routes

All routes are prefixed with `/plugins/` by the ChurchCRM route loader.

### MVC Pages

| Method | Path | Description | Auth |
|---|---|---|---|
| GET | `/plugins/meeting-outlines/services` | List all meetings | Admin |
| GET | `/plugins/meeting-outlines/services/new` | Create meeting form | Admin |
| GET | `/plugins/meeting-outlines/services/{id}/edit` | Edit meeting + manage items | Admin |
| GET | `/plugins/meeting-outlines/services/{id}/print` | Printable meeting outline | Admin |
| GET | `/plugins/meeting-outlines/settings` | Meeting Settings page | Admin |
| POST | `/plugins/meeting-outlines/settings` | Save settings | Admin |

### REST API (JSON)

| Method | Path | Description |
|---|---|---|
| POST | `/plugins/meeting-outlines/api/services` | Create a meeting |
| PUT | `/plugins/meeting-outlines/api/services/{id}` | Update a meeting |
| DELETE | `/plugins/meeting-outlines/api/services/{id}` | Delete a meeting (cascades items) |
| POST | `/plugins/meeting-outlines/api/services/{id}/duplicate` | Duplicate a meeting + all its items (new draft) |
| POST | `/plugins/meeting-outlines/api/services/{id}/notify` | Send outline by email to all participants with a valid email |
| POST | `/plugins/meeting-outlines/api/services/{id}/items` | Add an item |
| PUT | `/plugins/meeting-outlines/api/items/{id}` | Update an item |
| DELETE | `/plugins/meeting-outlines/api/items/{id}` | Delete an item |
| POST | `/plugins/meeting-outlines/api/services/{id}/items/reorder` | Reorder items (`{"ids":[3,1,2]}`) |
| GET | `/plugins/meeting-outlines/api/groups/{id}/members` | List members of a CRM group |
| POST | `/plugins/meeting-outlines/api/service-types` | Create a custom meeting type (`{"label":"..."}`) |
| DELETE | `/plugins/meeting-outlines/api/service-types/{slug}` | Delete a custom meeting type (blocked if in use) |

All API endpoints require `AdminRoleAuthMiddleware`.

---

## Settings (Meeting Settings page)

Accessible via **Church Meetings → Meeting Settings**.

| Setting | Description |
|---|---|
| Preachers group | CRM group whose members appear in the Preacher dropdown |
| Responsibles group | CRM group whose members appear in the Responsible dropdown per item |
| Bible version | Default version used in print view (LSG, FRD, FRC, KJV, ASV) |
| Meeting Types | Create / delete custom meeting types; 4 built-in types are read-only |

If no group is configured, the Preacher / Responsible fields fall back to a free-text input.

---

## Bible Data

Bible structure (66 books, chapter/verse counts) is stored locally in `data/bible-structure.json`
sourced from `scrollmapper/bible_databases` (MIT licence). No external API or internet access
is required at runtime.

Available versions in `data/bible-versions.json`:

| Code | Name | Built-in |
|---|---|---|
| LSG | Louis Segond (1910) | Yes |
| FRD | Darby (français, 1885) | No |
| FRC | Crampon (1923) | No |
| KJV | King James (1611) | No |
| ASV | American Standard (1901) | No |

"Built-in" means the full text is bundled with the plugin. Other versions require
an external source (future feature).

---

## Internationalisation

This plugin uses **plugin-local gettext** (PR #8657 compliance) — completely independent
of the ChurchCRM core `messages` domain and the POeditor workflow.

**Rules followed in this plugin:**
- PHP views and class methods: `dgettext('meeting-outlines', 'English string')`
- Plural forms: `dngettext('meeting-outlines', '%d item', '%d items', $count)`
- JavaScript strings: injected from PHP via `json_encode(dgettext('meeting-outlines', '...'))`
  — no client-side lookup needed

**Plural forms in the `.po` file** must use the `msgid_plural` syntax — a simple `msgid` entry
will never be picked up by `dngettext()`:

```po
# ✅ CORRECT — dngettext() will use msgstr[0] for n=1 and msgstr[1] for n>1
msgid "%d item"
msgid_plural "%d items"
msgstr[0] "%d élément"
msgstr[1] "%d éléments"

# ❌ WRONG — plural form stays in English
msgid "%d item"
msgstr "%d élément"
```

### Adding or updating a translation

1. Edit `locale/textdomain/{locale}/LC_MESSAGES/meeting-outlines.po`
2. Compile to `.mo` using [polib](https://pypi.org/project/polib/) (Python):

```python
import polib
po = polib.pofile('locale/textdomain/fr_FR/LC_MESSAGES/meeting-outlines.po', encoding='utf-8')
po.save_as_mofile('locale/textdomain/fr_FR/LC_MESSAGES/meeting-outlines.mo')
```

Or with the standard GNU gettext toolchain if available:
```bash
msgfmt locale/textdomain/fr_FR/LC_MESSAGES/meeting-outlines.po \
       -o locale/textdomain/fr_FR/LC_MESSAGES/meeting-outlines.mo
```

### Adding a new language

Create the directory structure and `.po` file:
```
locale/textdomain/{locale}/LC_MESSAGES/meeting-outlines.po
```
Translate all `msgid` entries, compile to `.mo`, and commit both files.
`PluginLocalization::bindPhpDomains()` will pick it up automatically at boot.

> **Never** add strings from this plugin to the core `locale/messages.po`
> or submit them to POeditor — they belong here only.

---

## Architecture Notes

### Data access

This plugin uses **raw PDO** via `Propel::getConnection()` rather than generated Propel
model classes. This is intentional for a community plugin: it avoids the Propel schema
compilation step and keeps the plugin fully self-contained with no generated code to
maintain. All queries are parameterised (no string interpolation of user data).

The connection object returned by `Propel::getConnection()` is a `ConnectionWrapper`
(not a raw `\PDO`). Method signatures in this plugin use `object` as the type hint
for connection parameters to remain compatible with both.

### Schema migrations in `boot()`

`boot()` calls `runMigrations()` on every request. Each call to `addColumnIfNotExists()`
queries `information_schema` and only issues an `ALTER TABLE` if the column is absent.
This is safe, idempotent, and ensures existing installs get new columns without
requiring a plugin re-activation.

### Hook: `menu.building`

`boot()` registers a filter on `Hooks::MENU_BUILDING` that injects a **Church Meetings**
top-level menu entry with **Meeting Outlines** and **Meeting Settings** sub-items.
The menu is only added when the plugin is enabled.

### Drag & drop reordering

The edit view loads **SortableJS 1.15.3** from jsDelivr CDN at render time:

```html
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
```

Sorting is constrained to the `.drag-handle` icon (grip) so that buttons and
other interactive elements inside each row do not interfere. The new order is
persisted immediately via `POST /plugins/meeting-outlines/api/services/{id}/items/reorder`.

### Edit view — item list

Each item row displays, on a single line:
**badge** (type, colour-coded) · **title** · **bible reference** (if applicable) ·
**description preview** (truncated to 100 chars) · **responsible** (pushed right).

A footer below the list shows:
- Left: item count (`n items`)
- Right: total duration of all items with a duration set (`n min` / `n h mm min`)

Both values update in real time when items are added, edited or deleted.

### Print view

`print.php` is a standalone HTML page (no ChurchCRM `Header.php` / `Footer.php`).
It includes its own `<style>` block with `@media print` rules so that the browser
print dialog produces a clean output without navigation, buttons or page chrome.

---

## Known Limitations

- No Propel model classes — raw SQL only (intentional, see above)
- Drag & drop requires internet access to load SortableJS from jsDelivr CDN
- `SystemConfig.php` must be edited manually for each new community plugin
  (architectural limitation of the current ChurchCRM version)
- Bible text is not bundled for FRD, FRC, KJV, ASV — only the reference selector works

---

## Uninstall

Disable then uninstall the plugin from **Admin → Plugins**.

`uninstall()` drops all three tables (`worship_service_item`, `worship_service`, `worship_service_type`).
**All meeting data and custom types will be permanently deleted.**

Also remove the four `plugin.meeting-outlines.*` lines from `SystemConfig.php`.
