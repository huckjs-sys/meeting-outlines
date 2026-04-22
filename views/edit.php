<?php

use ChurchCRM\dto\SystemURLs;

require SystemURLs::getDocumentRoot() . '/Include/Header.php';

$isNew     = ($service === null);
$serviceId = $isNew ? 0 : (int) $service['id'];

// Couleur de badge par type d'élément (classe CSS Bootstrap + custom)
$itemTypeColors = [
    'song'          => 'item-badge-song',
    'prayer'        => 'item-badge-prayer',
    'bible_reading' => 'item-badge-bible',
    'sermon'        => 'item-badge-sermon',
    'offering'      => 'item-badge-offering',
    'announcements' => 'item-badge-announcements',
    'communion'     => 'item-badge-communion',
    'other'         => 'item-badge-other',
];
?>

<div class="row mb-3">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 bg-light">
                <li class="breadcrumb-item">
                    <a href="<?= SystemURLs::getRootPath() ?>/v2/dashboard"><i class="fa-solid fa-home"></i></a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= SystemURLs::getRootPath() ?>/plugins/meeting-outlines/services">
                        <?= gettext('Meeting Outlines') ?>
                    </a>
                </li>
                <li class="breadcrumb-item active"><?= $sPageTitle ?></li>
            </ol>
        </nav>
    </div>
</div>

<!-- Formulaire culte -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title mb-0">
            <i class="fa-solid fa-church me-2"></i><?= $sPageTitle ?>
        </h3>
    </div>
    <div class="card-body">
        <form id="service-form" novalidate>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="svc_date" class="form-label"><?= gettext('Meeting Date') ?> <span class="text-danger">*</span></label>
                    <input type="date" id="svc_date" name="date" class="form-control" required
                           value="<?= htmlspecialchars($service['date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-5 mb-3">
                    <label for="svc_title" class="form-label"><?= gettext('Title') ?> <span class="text-danger">*</span></label>
                    <input type="text" id="svc_title" name="title" class="form-control" required
                           maxlength="200"
                           placeholder="<?= gettext('e.g., Sunday Morning Meeting') ?>"
                           value="<?= htmlspecialchars($service['title'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="svc_type" class="form-label"><?= gettext('Type') ?></label>
                    <select id="svc_type" name="type" class="form-select">
                        <?php foreach ($serviceTypes as $key => $label): ?>
                        <option value="<?= $key ?>"
                            <?= isset($service['type']) && $service['type'] === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="svc_preacher" class="form-label"><?= gettext('Preacher') ?></label>
                    <?php if (!empty($preachersMembers)): ?>
                    <select id="svc_preacher" name="preacher_person_id" class="form-select">
                        <option value=""><?= gettext('— Select a preacher —') ?></option>
                        <?php foreach ($preachersMembers as $m): ?>
                        <option value="<?= (int) $m['id'] ?>"
                            <?= isset($service['preacher_person_id']) && (int) $service['preacher_person_id'] === $m['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php else: ?>
                    <input type="text" id="svc_preacher" name="preacher" class="form-control"
                           maxlength="150"
                           placeholder="<?= gettext('No preacher group configured') ?>"
                           value="<?= htmlspecialchars($service['preacher'] ?? '') ?>">
                    <div class="form-text">
                        <a href="<?= SystemURLs::getRootPath() ?>/plugins/meeting-outlines/settings">
                            <i class="fa-solid fa-gear"></i> <?= gettext('Configure a group in Settings') ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="svc_president" class="form-label"><?= gettext('President') ?></label>
                    <?php if (!empty($responsiblesMembers)): ?>
                    <select id="svc_president" name="president_person_id" class="form-select">
                        <option value=""><?= gettext('— Select a president —') ?></option>
                        <?php foreach ($responsiblesMembers as $m): ?>
                        <option value="<?= (int) $m['id'] ?>"
                            <?= isset($service['president_person_id']) && (int) $service['president_person_id'] === $m['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php else: ?>
                    <input type="text" id="svc_president" name="president_person_id" class="form-control"
                           disabled placeholder="<?= gettext('Configure a group in Settings') ?>">
                    <div class="form-text">
                        <a href="<?= SystemURLs::getRootPath() ?>/plugins/meeting-outlines/settings">
                            <i class="fa-solid fa-gear"></i> <?= gettext('Configure a group in Settings') ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="svc_status" class="form-label"><?= gettext('Status') ?></label>
                    <select id="svc_status" name="status" class="form-select">
                        <?php foreach ($statusLabels as $key => $label): ?>
                        <option value="<?= $key ?>"
                            <?= isset($service['status']) && $service['status'] === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <!-- spacer -->
                </div>
                <div class="col-12 mb-3">
                    <label for="svc_notes" class="form-label"><?= gettext('Notes') ?></label>
                    <textarea id="svc_notes" name="notes" class="form-control" rows="2"
                              maxlength="1000"><?= htmlspecialchars($service['notes'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success" id="btn-save-service">
                    <i class="fa-solid fa-floppy-disk me-1"></i><?= gettext('Save') ?>
                </button>
                <?php if (!$isNew): ?>
                <a href="<?= SystemURLs::getRootPath() ?>/plugins/meeting-outlines/services/<?= $serviceId ?>/print"
                   class="btn btn-info" target="_blank">
                    <i class="fa-solid fa-print me-1"></i><?= gettext('Print Order') ?>
                </a>
                <?php endif; ?>
                <a href="<?= SystemURLs::getRootPath() ?>/plugins/meeting-outlines/services"
                   class="btn btn-secondary ms-auto">
                    <?= gettext('Cancel') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (!$isNew): ?>
<!-- Ordre du programme -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0">
            <i class="fa-solid fa-list-ol me-2"></i><?= gettext('Meeting Outline') ?>
        </h3>
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#itemModal"
                onclick="openItemModal(null)">
            <i class="fa-solid fa-plus me-1"></i><?= gettext('Add Item') ?>
        </button>
    </div>
    <div class="card-body p-0">
        <div id="items-empty" class="text-center text-muted py-5 <?= !empty($items) ? 'd-none' : '' ?>">
            <i class="fa-solid fa-list fa-2x mb-2"></i><br>
            <?= gettext('No items added yet.') ?>
        </div>
        <ul id="items-list" class="list-group list-group-flush">
            <?php foreach ($items as $item): ?>
            <?php
                $bibleRef = '';
                if ($item['item_type'] === 'bible_reading' && !empty($item['bible_book'])) {
                    $bibleRef = $plugin->formatBibleRef(
                        (int) $item['bible_book'],
                        (int) $item['bible_chapter'],
                        (int) $item['bible_verse_start'],
                        $item['bible_verse_end'] ? (int) $item['bible_verse_end'] : null
                    );
                }
                $responsible = $item['responsible_display'] ?? $item['responsible'] ?? '';
                $descPreview = '';
                if (!empty($item['description'])) {
                    $descPreview = mb_strlen($item['description']) > 100
                        ? mb_substr($item['description'], 0, 100) . '…'
                        : $item['description'];
                }
            ?>
            <li class="list-group-item d-flex align-items-center gap-3 py-2"
                data-id="<?= $item['id'] ?>"
                data-duration="<?= (int) ($item['duration_minutes'] ?? 0) ?>">
                <span class="drag-handle text-muted flex-shrink-0" style="cursor:grab" title="<?= gettext('Drag to reorder') ?>">
                    <i class="fa-solid fa-grip-vertical"></i>
                </span>
                <span class="badge item-type-badge flex-shrink-0 <?= $itemTypeColors[$item['item_type']] ?? 'item-badge-other' ?>">
                    <?= htmlspecialchars($itemTypes[$item['item_type']] ?? $item['item_type']) ?>
                </span>
                <div class="flex-grow-1 d-flex align-items-center flex-wrap gap-2 min-w-0">
                    <strong class="item-title"><?= htmlspecialchars($item['title']) ?></strong>
                    <?php if (!empty($bibleRef)): ?>
                    <span class="badge bg-info text-dark item-bible-ref">
                        <i class="fa-solid fa-book-open fa-xs me-1"></i><?= htmlspecialchars($bibleRef) ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($descPreview)): ?>
                    <small class="text-muted item-description"><?= htmlspecialchars($descPreview) ?></small>
                    <?php endif; ?>
                    <?php if (!empty($responsible)): ?>
                    <small class="text-muted item-responsible ms-auto">
                        <i class="fa-solid fa-user fa-xs"></i> <?= htmlspecialchars($responsible) ?>
                    </small>
                    <?php endif; ?>
                </div>
                <?php if ($item['duration_minutes']): ?>
                <small class="text-muted text-nowrap item-duration flex-shrink-0">
                    <i class="fa-regular fa-clock"></i> <?= (int) $item['duration_minutes'] ?> <?= gettext('min') ?>
                </small>
                <?php else: ?>
                <small class="text-muted text-nowrap item-duration flex-shrink-0"></small>
                <?php endif; ?>
                <div class="btn-group btn-group-sm flex-shrink-0">
                    <button type="button" class="btn btn-primary btn-edit-item"
                            data-item='<?= htmlspecialchars(json_encode($item)) ?>'>
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button type="button" class="btn btn-danger btn-delete-item"
                            data-id="<?= $item['id'] ?>">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php
        $totalDuration = array_sum(array_column($items, 'duration_minutes'));
        $itemCount     = count($items);
        ?>
        <div id="items-footer" class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light text-muted small">
            <span id="items-footer-count">
                <i class="fa-solid fa-list-ol me-1"></i>
                <?= sprintf(ngettext('%d item', '%d items', $itemCount), $itemCount) ?>
            </span>
            <span id="items-footer-duration">
                <?php if ($totalDuration > 0): ?>
                <i class="fa-regular fa-clock me-1"></i>
                <?php
                $h = intdiv($totalDuration, 60);
                $m = $totalDuration % 60;
                echo $h > 0
                    ? sprintf('%d h %02d min', $h, $m)
                    : sprintf('%d min', $m);
                ?>
                <?php endif; ?>
            </span>
        </div>
    </div>
</div>

<!-- Modal ajout/édition élément -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemModalLabel"><?= gettext('Add Item') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="item-form" novalidate>
                    <input type="hidden" id="item_id" name="id" value="">
                    <div class="mb-3">
                        <label for="item_type" class="form-label"><?= gettext('Item Type') ?></label>
                        <select id="item_type" name="item_type" class="form-select">
                            <?php foreach ($itemTypes as $key => $label): ?>
                            <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="item_title" class="form-label"><?= gettext('Title') ?> <span class="text-danger">*</span></label>
                        <input type="text" id="item_title" name="title" class="form-control" required
                               maxlength="200">
                    </div>

                    <!-- Référence biblique — visible uniquement pour bible_reading -->
                    <div id="bible-ref-fields" class="mb-3 p-3 border rounded bg-light" style="display:none">
                        <label class="form-label fw-semibold">
                            <i class="fa-solid fa-book-open me-1"></i><?= gettext('Bible Reference') ?>
                        </label>
                        <div class="row g-2">
                            <div class="col-12">
                                <label for="item_bible_book" class="form-label small"><?= gettext('Book') ?></label>
                                <select id="item_bible_book" name="bible_book" class="form-select form-select-sm">
                                    <option value=""><?= gettext('— Select a book —') ?></option>
                                    <?php
                                    $bibleBooks = $bibleStructure['books'] ?? [];
                                    $otBooks    = array_filter($bibleBooks, fn($b) => $b['t'] === 'OT');
                                    $ntBooks    = array_filter($bibleBooks, fn($b) => $b['t'] === 'NT');
                                    ?>
                                    <optgroup label="<?= gettext('Old Testament') ?>">
                                        <?php foreach ($otBooks as $book): ?>
                                        <option value="<?= $book['num'] ?>"><?= htmlspecialchars($book['fr']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="<?= gettext('New Testament') ?>">
                                        <?php foreach ($ntBooks as $book): ?>
                                        <option value="<?= $book['num'] ?>"><?= htmlspecialchars($book['fr']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="col-4">
                                <label for="item_bible_chapter" class="form-label small"><?= gettext('Chapter') ?></label>
                                <select id="item_bible_chapter" name="bible_chapter" class="form-select form-select-sm">
                                    <option value=""><?= gettext('—') ?></option>
                                </select>
                            </div>
                            <div class="col-4">
                                <label for="item_bible_verse_start" class="form-label small"><?= gettext('From verse') ?></label>
                                <select id="item_bible_verse_start" name="bible_verse_start" class="form-select form-select-sm">
                                    <option value=""><?= gettext('—') ?></option>
                                </select>
                            </div>
                            <div class="col-4">
                                <label for="item_bible_verse_end" class="form-label small"><?= gettext('To verse') ?></label>
                                <select id="item_bible_verse_end" name="bible_verse_end" class="form-select form-select-sm">
                                    <option value=""><?= gettext('—') ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="item_responsible" class="form-label"><?= gettext('Responsible') ?></label>
                        <?php if (!empty($responsiblesMembers)): ?>
                        <select id="item_responsible_id" name="responsible_person_id" class="form-select">
                            <option value=""><?= gettext('— Select a responsible —') ?></option>
                            <?php foreach ($responsiblesMembers as $m): ?>
                            <option value="<?= (int) $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <input type="text" id="item_responsible" name="responsible" class="form-control"
                               maxlength="150"
                               placeholder="<?= gettext('No responsible group configured') ?>">
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="item_duration" class="form-label"><?= gettext('Duration (minutes)') ?></label>
                        <input type="number" id="item_duration" name="duration_minutes" class="form-control"
                               min="1" max="999">
                    </div>
                    <div class="mb-3">
                        <label for="item_description" class="form-label"><?= gettext('Description') ?></label>
                        <textarea id="item_description" name="description" class="form-control"
                                  rows="3" maxlength="2000"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= gettext('Cancel') ?></button>
                <button type="button" class="btn btn-success" id="btn-save-item">
                    <i class="fa-solid fa-floppy-disk me-1"></i><?= gettext('Save') ?>
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* Indicateur visuel pendant le drag */
#items-list li.drag-ghost {
    opacity: 0.4;
    background-color: #eff6ff;
}

/* Empêche le débordement flex sur les titres longs */
#items-list .min-w-0 { min-width: 0; }
#items-list .item-title { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 30ch; }
#items-list .item-description { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 40ch; }

/* Couleurs des badges par type d'élément */
.item-badge-song          { background-color: #8b5cf6; color: #fff; } /* violet  — chant    */
.item-badge-prayer        { background-color: #06b6d4; color: #fff; } /* cyan    — prière   */
.item-badge-bible         { background-color: #3b82f6; color: #fff; } /* bleu    — lecture  */
.item-badge-sermon        { background-color: #1e293b; color: #fff; } /* marine  — sermon   */
.item-badge-offering      { background-color: #16a34a; color: #fff; } /* vert    — offrande */
.item-badge-announcements { background-color: #f59e0b; color: #1e293b; } /* ambre — annonces */
.item-badge-communion     { background-color: #dc2626; color: #fff; } /* rouge   — communion*/
.item-badge-other         { background-color: #6b7280; color: #fff; } /* gris    — autre    */
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"
        integrity="sha384-rRoXxn2yHlrZYB587Ki9RO1tONhLdM6XfORg7Rw4uwH4/Fh/5nP7IUX91bkaKUgs"
        crossorigin="anonymous"></script>
<script>
const ROOT_PATH    = <?= json_encode(SystemURLs::getRootPath()) ?>;
const SERVICE_ID   = <?= json_encode($serviceId) ?>;
const IS_NEW       = <?= json_encode($isNew) ?>;
const LABELS = {
    addItem:       <?= json_encode(gettext('Add Item')) ?>,
    editItem:      <?= json_encode(gettext('Edit Item')) ?>,
    confirmDelete: <?= json_encode(gettext('Are you sure you want to delete this item?')) ?>,
    saved:         <?= json_encode(gettext('Meeting saved successfully.')) ?>,
    itemAdded:     <?= json_encode(gettext('Item added.')) ?>,
    itemUpdated:   <?= json_encode(gettext('Item updated.')) ?>,
    itemDeleted:   <?= json_encode(gettext('Item deleted.')) ?>,
    error:         <?= json_encode(gettext('An error occurred. Please try again.')) ?>,
    minDuration:   <?= json_encode(gettext('min')) ?>,
    selectChapter: <?= json_encode(gettext('— Select a chapter —')) ?>,
    selectVerse:   <?= json_encode(gettext('— Select a verse —')) ?>,
    noVerse:       <?= json_encode(gettext('—')) ?>,
};
const ITEM_TYPES    = <?= json_encode($itemTypes) ?>;
const ITEM_TYPE_COLORS = <?= json_encode($itemTypeColors) ?>;
const BIBLE_BOOKS   = <?= json_encode($bibleStructure['books'] ?? []) ?>;
const HAS_RESPONSIBLES = <?= json_encode(!empty($responsiblesMembers)) ?>;

// ------------------------------------------------------------------
// Sélecteur de référence biblique
// ------------------------------------------------------------------
function populateSelect(sel, options, selected) {
    sel.innerHTML = '';
    options.forEach(function (opt) {
        const el = document.createElement('option');
        el.value = opt.value;
        el.textContent = opt.label;
        if (String(opt.value) === String(selected)) el.selected = true;
        sel.appendChild(el);
    });
}

function getBibleBook(bookNum) {
    return BIBLE_BOOKS.find(function (b) { return b.num === parseInt(bookNum, 10); }) || null;
}

function updateBibleChapters(bookNum, selectedChapter) {
    const book    = getBibleBook(bookNum);
    const chapSel = document.getElementById('item_bible_chapter');
    const vseSel  = document.getElementById('item_bible_verse_start');
    const veeSel  = document.getElementById('item_bible_verse_end');

    // reset verses
    populateSelect(vseSel,  [{ value: '', label: LABELS.noVerse }], '');
    populateSelect(veeSel,  [{ value: '', label: LABELS.noVerse }], '');

    if (!book) {
        populateSelect(chapSel, [{ value: '', label: LABELS.selectChapter }], '');
        return;
    }

    const opts = [{ value: '', label: LABELS.selectChapter }];
    book.ch.forEach(function (_, i) {
        opts.push({ value: i + 1, label: String(i + 1) });
    });
    populateSelect(chapSel, opts, selectedChapter || '');

    if (selectedChapter) {
        updateBibleVerses(bookNum, selectedChapter, null, null);
    }
}

function updateBibleVerses(bookNum, chapterNum, selectedStart, selectedEnd) {
    const book  = getBibleBook(bookNum);
    const vseSel = document.getElementById('item_bible_verse_start');
    const veeSel  = document.getElementById('item_bible_verse_end');

    if (!book || !chapterNum) {
        populateSelect(vseSel,  [{ value: '', label: LABELS.noVerse }], '');
        populateSelect(veeSel,  [{ value: '', label: LABELS.noVerse }], '');
        return;
    }

    const verseCount = book.ch[parseInt(chapterNum, 10) - 1] || 0;
    const startOpts  = [{ value: '', label: LABELS.selectVerse }];
    const endOpts    = [{ value: '', label: LABELS.noVerse }];

    for (let v = 1; v <= verseCount; v++) {
        startOpts.push({ value: v, label: String(v) });
        endOpts.push({ value: v, label: String(v) });
    }

    populateSelect(vseSel, startOpts, selectedStart || '');
    populateSelect(veeSel,  endOpts,   selectedEnd   || '');
}

document.addEventListener('change', function (e) {
    if (e.target.id === 'item_type') {
        const isBible = e.target.value === 'bible_reading';
        document.getElementById('bible-ref-fields').style.display = isBible ? '' : 'none';
    }
    if (e.target.id === 'item_bible_book') {
        updateBibleChapters(e.target.value, '');
    }
    if (e.target.id === 'item_bible_chapter') {
        updateBibleVerses(
            document.getElementById('item_bible_book').value,
            e.target.value,
            '',
            ''
        );
    }
});

// ------------------------------------------------------------------
// Sauvegarde du culte
// ------------------------------------------------------------------
document.getElementById('service-form').addEventListener('submit', function (e) {
    e.preventDefault();
    const form   = e.target;
    const data   = Object.fromEntries(new FormData(form));
    const method = IS_NEW ? 'POST' : 'PUT';
    const url    = ROOT_PATH + '/plugins/meeting-outlines/api/services' + (IS_NEW ? '' : '/' + SERVICE_ID);

    fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    })
    .then(function (r) { return r.json(); })
    .then(function (resp) {
        if (resp.success) {
            location.href = ROOT_PATH + '/plugins/meeting-outlines/services';
        } else {
            const msg = (resp.errors || [resp.message]).join('\n');
            alert(msg);
        }
    })
    .catch(function () { alert(LABELS.error); });
});

<?php if (!$isNew): ?>
// ------------------------------------------------------------------
// Modal élément
// ------------------------------------------------------------------
function openItemModal(itemData) {
    document.getElementById('itemModalLabel').textContent =
        itemData ? LABELS.editItem : LABELS.addItem;
    document.getElementById('item_id').value        = itemData ? itemData.id : '';
    document.getElementById('item_title').value     = itemData ? itemData.title : '';
    document.getElementById('item_duration').value  = itemData ? (itemData.duration_minutes || '') : '';
    document.getElementById('item_description').value = itemData ? (itemData.description || '') : '';

    // type — déclenche l'affichage/masquage des champs bibliques
    const typeEl = document.getElementById('item_type');
    typeEl.value = itemData ? (itemData.item_type || 'other') : 'other';
    const isBible = typeEl.value === 'bible_reading';
    document.getElementById('bible-ref-fields').style.display = isBible ? '' : 'none';

    // référence biblique
    const bookNum    = itemData ? (itemData.bible_book    || '') : '';
    const chapter    = itemData ? (itemData.bible_chapter || '') : '';
    const verseStart = itemData ? (itemData.bible_verse_start || '') : '';
    const verseEnd   = itemData ? (itemData.bible_verse_end   || '') : '';

    document.getElementById('item_bible_book').value = bookNum;
    if (bookNum) {
        updateBibleChapters(bookNum, chapter);
        if (chapter) {
            updateBibleVerses(bookNum, chapter, verseStart, verseEnd);
        }
    } else {
        updateBibleChapters('', '');
    }

    // responsible
    if (HAS_RESPONSIBLES) {
        const respSel = document.getElementById('item_responsible_id');
        if (respSel) {
            respSel.value = itemData ? (itemData.responsible_person_id || '') : '';
        }
    } else {
        const respInput = document.getElementById('item_responsible');
        if (respInput) {
            respInput.value = itemData ? (itemData.responsible || '') : '';
        }
    }
}

document.querySelectorAll('.btn-edit-item').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const item = JSON.parse(this.dataset.item);
        openItemModal(item);
        new bootstrap.Modal(document.getElementById('itemModal')).show();
    });
});

// Sauvegarde élément
document.getElementById('btn-save-item').addEventListener('click', function () {
    const title = document.getElementById('item_title').value.trim();
    if (!title) {
        document.getElementById('item_title').focus();
        return;
    }

    const id      = document.getElementById('item_id').value;
    const itemType = document.getElementById('item_type').value;
    const isBible  = itemType === 'bible_reading';

    const data = {
        item_type:             itemType,
        title:                 title,
        duration_minutes:      document.getElementById('item_duration').value || null,
        description:           document.getElementById('item_description').value.trim(),
        // responsible
        responsible:           HAS_RESPONSIBLES ? '' : (document.getElementById('item_responsible') ? document.getElementById('item_responsible').value.trim() : ''),
        responsible_person_id: HAS_RESPONSIBLES ? (document.getElementById('item_responsible_id') ? document.getElementById('item_responsible_id').value : '') : '',
        // bible
        bible_book:            isBible ? document.getElementById('item_bible_book').value        : '',
        bible_chapter:         isBible ? document.getElementById('item_bible_chapter').value      : '',
        bible_verse_start:     isBible ? document.getElementById('item_bible_verse_start').value  : '',
        bible_verse_end:       isBible ? document.getElementById('item_bible_verse_end').value    : '',
    };

    const method = id ? 'PUT' : 'POST';
    const url    = id
        ? ROOT_PATH + '/plugins/meeting-outlines/api/items/' + id
        : ROOT_PATH + '/plugins/meeting-outlines/api/services/' + SERVICE_ID + '/items';

    fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    })
    .then(function (r) { return r.json(); })
    .then(function (resp) {
        if (resp.success) {
            bootstrap.Modal.getInstance(document.getElementById('itemModal')).hide();
            refreshItemInList(resp.item, !id);
            toastSuccess(id ? LABELS.itemUpdated : LABELS.itemAdded);
        } else {
            alert((resp.errors || [resp.message]).join('\n'));
        }
    })
    .catch(function () { alert(LABELS.error); });
});

// Suppression élément
document.getElementById('items-list').addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-delete-item');
    if (!btn) return;

    if (!confirm(LABELS.confirmDelete)) return;

    const id = btn.dataset.id;
    fetch(ROOT_PATH + '/plugins/meeting-outlines/api/items/' + id, { method: 'DELETE' })
        .then(function (r) { return r.json(); })
        .then(function (resp) {
            if (resp.success) {
                const li = document.querySelector('#items-list li[data-id="' + id + '"]');
                if (li) li.remove();
                checkEmptyList();
                updateItemsFooter();
                toastSuccess(LABELS.itemDeleted);
            } else {
                alert(resp.message || LABELS.error);
            }
        })
        .catch(function () { alert(LABELS.error); });
});

// Formate une référence biblique depuis les données de l'item
function formatBibleRefJS(item) {
    if (item.item_type !== 'bible_reading' || !item.bible_book) return '';
    const book = BIBLE_BOOKS.find(function (b) { return b.num === parseInt(item.bible_book, 10); });
    if (!book) return '';
    let ref = book.fr + ' ' + item.bible_chapter + ':' + item.bible_verse_start;
    if (item.bible_verse_end && parseInt(item.bible_verse_end, 10) > parseInt(item.bible_verse_start, 10)) {
        ref += '-' + item.bible_verse_end;
    }
    return ref;
}

// Tronque un texte à max N caractères
function truncate(str, max) {
    if (!str) return '';
    return str.length > max ? str.substring(0, max) + '…' : str;
}

// Mise à jour DOM après save élément
function refreshItemInList(item, isNew) {
    const typeLabel   = ITEM_TYPES[item.item_type] || item.item_type;
    const responsible = item.responsible_display || item.responsible || '';
    const bibleRef    = formatBibleRefJS(item);
    const descPreview = truncate(item.description, 100);

    const duration = item.duration_minutes
        ? '<i class="fa-regular fa-clock"></i> ' + item.duration_minutes + ' ' + LABELS.minDuration
        : '';

    if (isNew) {
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex align-items-center gap-3 py-2';
        li.dataset.id = item.id;
        li.dataset.duration = item.duration_minutes || 0;
        li.innerHTML = buildItemHTML(item, typeLabel, responsible, bibleRef, descPreview, duration);
        const list = document.getElementById('items-list');
        list.appendChild(li);
        checkEmptyList();
    } else {
        const li = document.querySelector('#items-list li[data-id="' + item.id + '"]');
        if (li) {
            li.dataset.duration = item.duration_minutes || 0;
            li.innerHTML = buildItemHTML(item, typeLabel, responsible, bibleRef, descPreview, duration);
            // re-attach edit button listener
            const editBtn = li.querySelector('.btn-edit-item');
            if (editBtn) {
                editBtn.addEventListener('click', function () {
                    openItemModal(JSON.parse(this.dataset.item));
                    new bootstrap.Modal(document.getElementById('itemModal')).show();
                });
            }
        }
    }
    updateItemsFooter();
}

function buildItemHTML(item, typeLabel, responsible, bibleRef, descPreview, duration) {
    const bibleHtml = bibleRef
        ? '<span class="badge bg-info text-dark item-bible-ref"><i class="fa-solid fa-book-open fa-xs me-1"></i>' + escHtml(bibleRef) + '</span>'
        : '';
    const descHtml = descPreview
        ? '<small class="text-muted item-description">' + escHtml(descPreview) + '</small>'
        : '';
    const respHtml = responsible
        ? '<small class="text-muted item-responsible ms-auto"><i class="fa-solid fa-user fa-xs"></i> ' + escHtml(responsible) + '</small>'
        : '';

    const colorClass = ITEM_TYPE_COLORS[item.item_type] || 'item-badge-other';
    return '<span class="drag-handle text-muted flex-shrink-0" style="cursor:grab" title="<?= addslashes(gettext('Drag to reorder')) ?>">'
        + '<i class="fa-solid fa-grip-vertical"></i></span>'
        + '<span class="badge item-type-badge flex-shrink-0 ' + colorClass + '">' + escHtml(typeLabel) + '</span>'
        + '<div class="flex-grow-1 d-flex align-items-center flex-wrap gap-2 min-w-0">'
        + '<strong class="item-title">' + escHtml(item.title) + '</strong>'
        + bibleHtml
        + descHtml
        + respHtml
        + '</div>'
        + '<small class="text-muted text-nowrap item-duration flex-shrink-0">' + duration + '</small>'
        + '<div class="btn-group btn-group-sm flex-shrink-0">'
        + '<button type="button" class="btn btn-primary btn-edit-item" data-item=\'' + escAttr(JSON.stringify(item)) + '\'>'
        + '<i class="fa-solid fa-pen-to-square"></i></button>'
        + '<button type="button" class="btn btn-danger btn-delete-item" data-id="' + item.id + '">'
        + '<i class="fa-solid fa-trash"></i></button>'
        + '</div>';
}

function checkEmptyList() {
    const empty = document.getElementById('items-empty');
    const list  = document.getElementById('items-list');
    if (list.children.length === 0) {
        empty.classList.remove('d-none');
    } else {
        empty.classList.add('d-none');
    }
}

function updateItemsFooter() {
    const list = document.getElementById('items-list');
    if (!list) return;
    const lis = Array.from(list.querySelectorAll('li[data-id]'));
    const count = lis.length;
    const total = lis.reduce(function (sum, li) { return sum + (parseInt(li.dataset.duration, 10) || 0); }, 0);

    const countEl = document.getElementById('items-footer-count');
    if (countEl) {
        countEl.innerHTML = '<i class="fa-solid fa-list-ol me-1"></i>'
            + count + ' ' + (count > 1 ? <?= json_encode(gettext('items')) ?> : <?= json_encode(gettext('item')) ?>);
    }
    const durEl = document.getElementById('items-footer-duration');
    if (durEl) {
        if (total > 0) {
            const h = Math.floor(total / 60);
            const m = total % 60;
            durEl.innerHTML = '<i class="fa-regular fa-clock me-1"></i>'
                + (h > 0 ? h + ' h ' + String(m).padStart(2, '0') + ' min' : m + ' min');
        } else {
            durEl.innerHTML = '';
        }
    }
}

// ------------------------------------------------------------------
// Drag & drop — SortableJS
// ------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    var list = document.getElementById('items-list');
    if (!list || typeof Sortable === 'undefined') return;

    Sortable.create(list, {
        animation:  150,
        handle:     '.drag-handle',
        ghostClass: 'drag-ghost',
        onEnd: function () {
            var ids = Array.from(list.querySelectorAll('li[data-id]'))
                           .map(function (li) { return li.dataset.id; });
            fetch(ROOT_PATH + '/plugins/meeting-outlines/api/services/' + SERVICE_ID + '/items/reorder', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ ids: ids }),
            }).catch(function () {});
        },
    });
});
<?php endif; ?>

// ------------------------------------------------------------------
// Helpers
// ------------------------------------------------------------------
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}
function escAttr(str) {
    return (str || '').replace(/'/g, '&#39;').replace(/"/g, '&quot;');
}
function toastSuccess(msg) {
    if (typeof toastr !== 'undefined') {
        toastr.success(msg);
    } else {
        // fallback minimal
        const div = document.createElement('div');
        div.className = 'alert alert-success position-fixed bottom-0 end-0 m-3';
        div.style.zIndex = 9999;
        div.textContent = msg;
        document.body.appendChild(div);
        setTimeout(function () { div.remove(); }, 3000);
    }
}
</script>

<?php require SystemURLs::getDocumentRoot() . '/Include/Footer.php'; ?>
