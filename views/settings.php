<?php

use ChurchCRM\dto\SystemURLs;

require SystemURLs::getDocumentRoot() . '/Include/Header.php';
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
                        <?= dgettext('meeting-outlines', 'Meeting Outlines') ?>
                    </a>
                </li>
                <li class="breadcrumb-item active"><?= dgettext('meeting-outlines', 'Meeting Settings') ?></li>
            </ol>
        </nav>
    </div>
</div>

<?php if (!empty($successMessage)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fa-solid fa-check-circle me-2"></i><?= htmlspecialchars($successMessage) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="post" action="<?= SystemURLs::getRootPath() ?>/plugins/meeting-outlines/settings">

    <!-- ================================================================
         Prédicateurs
    ================================================================ -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fa-solid fa-microphone me-2"></i><?= dgettext('meeting-outlines', 'Preachers') ?>
            </h3>
        </div>
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-6 mb-3">
                    <label for="preachers_group_id" class="form-label">
                        <?= dgettext('meeting-outlines', 'Group linked to Preachers') ?>
                    </label>
                    <select id="preachers_group_id" name="preachers_group_id" class="form-select">
                        <option value="0"><?= dgettext('meeting-outlines', '— No group selected —') ?></option>
                        <?php foreach ($allGroups as $group): ?>
                        <option value="<?= (int) $group['Id'] ?>"
                            <?= (int) $group['Id'] === $preachersGroupId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($group['Name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        <?= dgettext('meeting-outlines', 'Members of this group will appear in the Preacher dropdown when creating a service.') ?>
                        <?php if (empty($allGroups)): ?>
                        <br><span class="text-warning">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <?= dgettext('meeting-outlines', 'No groups found.') ?>
                            <a href="<?= SystemURLs::getRootPath() ?>/v2/groups/list"><?= dgettext('meeting-outlines', 'Create a group first.') ?></a>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($preachersGroupId > 0 && !empty($preachersMembers)): ?>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?= dgettext('meeting-outlines', 'Members in this group') ?></label>
                    <ul class="list-group list-group-flush border rounded" style="max-height:160px;overflow-y:auto">
                        <?php foreach ($preachersMembers as $m): ?>
                        <li class="list-group-item py-1 px-3 small">
                            <i class="fa-solid fa-user me-2 text-muted"></i><?= htmlspecialchars($m['name']) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ================================================================
         Responsables
    ================================================================ -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fa-solid fa-users me-2"></i><?= dgettext('meeting-outlines', 'Responsibles') ?>
            </h3>
        </div>
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-6 mb-3">
                    <label for="responsibles_group_id" class="form-label">
                        <?= dgettext('meeting-outlines', 'Group linked to Responsibles') ?>
                    </label>
                    <select id="responsibles_group_id" name="responsibles_group_id" class="form-select">
                        <option value="0"><?= dgettext('meeting-outlines', '— No group selected —') ?></option>
                        <?php foreach ($allGroups as $group): ?>
                        <option value="<?= (int) $group['Id'] ?>"
                            <?= (int) $group['Id'] === $responsiblesGroupId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($group['Name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        <?= dgettext('meeting-outlines', 'Members of this group will appear in the Responsible dropdown for each item in the order of service.') ?>
                    </div>
                </div>
                <?php if ($responsiblesGroupId > 0 && !empty($responsiblesMembers)): ?>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?= dgettext('meeting-outlines', 'Members in this group') ?></label>
                    <ul class="list-group list-group-flush border rounded" style="max-height:160px;overflow-y:auto">
                        <?php foreach ($responsiblesMembers as $m): ?>
                        <li class="list-group-item py-1 px-3 small">
                            <i class="fa-solid fa-user me-2 text-muted"></i><?= htmlspecialchars($m['name']) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ================================================================
         Version biblique
    ================================================================ -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fa-solid fa-book-open me-2"></i><?= dgettext('meeting-outlines', 'Bible Version') ?>
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label for="bible_version" class="form-label">
                        <?= dgettext('meeting-outlines', 'Default Bible version') ?>
                    </label>
                    <select id="bible_version" name="bible_version" class="form-select">
                        <?php foreach ($bibleVersions as $v): ?>
                        <option value="<?= htmlspecialchars($v['code']) ?>"
                            <?= $v['code'] === $currentBibleVersion ? 'selected' : '' ?>
                            data-available="<?= $v['available'] ? '1' : '0' ?>">
                            <?= htmlspecialchars($v['name']) ?>
                            <?= $v['available'] ? ' (' . dgettext('meeting-outlines', 'built-in') . ')' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        <?= dgettext('meeting-outlines', 'This version is shown in the print view and used for Bible reading references.') ?>
                    </div>
                    <div id="version-unavailable-hint" class="alert alert-warning py-2 mt-2 mb-0 small d-none">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        <?= dgettext('meeting-outlines', 'This version is not yet downloaded. Save and then download it.') ?>
                    </div>
                </div>
                <div class="col-md-7 mb-3">
                    <label class="form-label"><?= dgettext('meeting-outlines', 'Available versions') ?></label>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?= dgettext('meeting-outlines', 'Code') ?></th>
                                    <th><?= dgettext('meeting-outlines', 'Name') ?></th>
                                    <th><?= dgettext('meeting-outlines', 'Language') ?></th>
                                    <th><?= dgettext('meeting-outlines', 'Built-in') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bibleVersions as $v): ?>
                                <tr data-version="<?= htmlspecialchars($v['code']) ?>"
                                    <?= $v['code'] === $currentBibleVersion ? 'class="table-primary"' : '' ?>>
                                    <td><code><?= htmlspecialchars($v['code']) ?></code></td>
                                    <td><?= htmlspecialchars($v['name']) ?></td>
                                    <td><?= htmlspecialchars(strtoupper($v['lang'])) ?></td>
                                    <td class="text-center version-status-cell">
                                        <?php if ($v['available']): ?>
                                        <i class="fa-solid fa-check text-success version-status"
                                           title="<?= dgettext('meeting-outlines', 'Built-in') ?>"></i>
                                        <?php else: ?>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary btn-download-version"
                                                data-version="<?= htmlspecialchars($v['code']) ?>"
                                                data-name="<?= htmlspecialchars($v['name']) ?>">
                                            <i class="fa-solid fa-download me-1"></i><?= dgettext('meeting-outlines', 'Download') ?>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bouton enregistrer -->
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success">
            <i class="fa-solid fa-floppy-disk me-1"></i><?= dgettext('meeting-outlines', 'Save Settings') ?>
        </button>
        <a href="<?= SystemURLs::getRootPath() ?>/plugins/meeting-outlines/services"
           class="btn btn-secondary ms-auto">
            <?= dgettext('meeting-outlines', 'Back to Meeting Outlines') ?>
        </a>
    </div>

</form>

<?php if ($needsDownload): ?>
<div class="alert alert-warning d-flex align-items-center gap-3 mt-0 mb-4" id="needs-download-banner">
    <i class="fa-solid fa-triangle-exclamation fa-lg flex-shrink-0"></i>
    <div class="flex-grow-1">
        <strong><?= dgettext('meeting-outlines', 'Bible text not available') ?></strong><br>
        <?= sprintf(
            dgettext('meeting-outlines', 'The version "%s" has been saved but its text is not yet downloaded. Download it to display full verses in the print view.'),
            htmlspecialchars($currentBibleVersion)
        ) ?>
    </div>
    <button type="button" class="btn btn-warning btn-sm flex-shrink-0"
            id="btn-trigger-download"
            data-version="<?= htmlspecialchars($currentBibleVersion) ?>"
            data-name="<?= htmlspecialchars(array_column($bibleVersions, 'name', 'code')[$currentBibleVersion] ?? $currentBibleVersion) ?>">
        <i class="fa-solid fa-download me-1"></i><?= dgettext('meeting-outlines', 'Download now') ?>
    </button>
</div>
<?php endif; ?>

<!-- Modal téléchargement Bible -->
<div class="modal fade" id="downloadModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"
     aria-labelledby="downloadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="downloadModalLabel">
                    <i class="fa-solid fa-download me-2"></i><?= dgettext('meeting-outlines', 'Downloading Bible text') ?>
                </h5>
            </div>
            <div class="modal-body">
                <p class="mb-1" id="dl-status"><?= dgettext('meeting-outlines', 'Preparing…') ?></p>
                <div class="progress mb-2" style="height:20px">
                    <div id="dl-progress"
                         class="progress-bar progress-bar-striped progress-bar-animated"
                         role="progressbar" style="width:0%">0%</div>
                </div>
                <p class="text-muted small mb-0" id="dl-detail"></p>
            </div>
            <div class="modal-footer d-none" id="dl-footer">
                <button type="button" class="btn btn-success"
                        onclick="location.reload()"><?= dgettext('meeting-outlines', 'Close') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- ================================================================
     Types de réunion (gestion dynamique via API)
================================================================ -->
<div class="card mt-4" id="service-types-card">
    <div class="card-header">
        <h3 class="card-title mb-0">
            <i class="fa-solid fa-tags me-2"></i><?= dgettext('meeting-outlines', 'Meeting Types') ?>
        </h3>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            <?= dgettext('meeting-outlines', 'Built-in types cannot be deleted. Custom types can only be deleted if no meeting uses them.') ?>
        </p>

        <ul id="service-types-list" class="list-group list-group-flush mb-3">
            <?php foreach ($allServiceTypes as $t): ?>
            <li class="list-group-item d-flex align-items-center justify-content-between py-2"
                data-slug="<?= htmlspecialchars($t['slug']) ?>">
                <span>
                    <?= htmlspecialchars($t['label']) ?>
                    <?php if ($t['is_system']): ?>
                    <span class="badge bg-secondary ms-2 text-white"><?= dgettext('meeting-outlines', 'Built-in') ?></span>
                    <?php endif; ?>
                </span>
                <?php if (!$t['is_system']): ?>
                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-type"
                        data-slug="<?= htmlspecialchars($t['slug']) ?>"
                        data-label="<?= htmlspecialchars($t['label']) ?>">
                    <i class="fa-solid fa-trash"></i>
                </button>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="d-flex gap-2">
            <input type="text" id="new-type-label" class="form-control" style="max-width:300px"
                   maxlength="100"
                   placeholder="<?= dgettext('meeting-outlines', 'New type label…') ?>">
            <button type="button" class="btn btn-success" id="btn-add-type">
                <i class="fa-solid fa-plus me-1"></i><?= dgettext('meeting-outlines', 'Add') ?>
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const ROOT_PATH   = <?= json_encode(SystemURLs::getRootPath()) ?>;
    const BIBLE_BOOKS = <?= json_encode(array_map(
        fn($b) => ['num' => $b['num'], 'code' => $b['code'], 'fr' => $b['fr']],
        $bibleStructure['books'] ?? []
    )) ?>;
    const LABELS = {
        confirmDelete:    <?= json_encode(dgettext('meeting-outlines', 'Are you sure you want to delete this meeting type?')) ?>,
        error:            <?= json_encode(dgettext('meeting-outlines', 'An error occurred. Please try again.')) ?>,
        builtIn:          <?= json_encode(dgettext('meeting-outlines', 'Built-in')) ?>,
        dlDone:           <?= json_encode(dgettext('meeting-outlines', 'Download complete!')) ?>,
        dlErrors:         <?= json_encode(dgettext('meeting-outlines', '%d error(s). Retry by clicking Download again.')) ?>,
        dlBook:           <?= json_encode(dgettext('meeting-outlines', 'Book %1$d / %2$d')) ?>,
    };

    // ------------------------------------------------------------------
    // Téléchargement d'une version biblique (livre par livre)
    // ------------------------------------------------------------------
    async function downloadVersion(versionCode, versionName) {
        const modalEl   = document.getElementById('downloadModal');
        const statusEl  = document.getElementById('dl-status');
        const barEl     = document.getElementById('dl-progress');
        const detailEl  = document.getElementById('dl-detail');
        const footerEl  = document.getElementById('dl-footer');

        // Reset
        barEl.style.width = '0%';
        barEl.textContent = '0%';
        barEl.className   = 'progress-bar progress-bar-striped progress-bar-animated';
        footerEl.classList.add('d-none');
        statusEl.textContent = '';
        detailEl.textContent  = '';

        document.getElementById('downloadModalLabel').innerHTML =
            '<i class="fa-solid fa-download me-2"></i>' + escHtml(versionName);

        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        let errors = 0;
        const total = BIBLE_BOOKS.length;

        for (let i = 0; i < total; i++) {
            const book = BIBLE_BOOKS[i];
            const pct  = Math.round(((i + 1) / total) * 100);

            statusEl.textContent = LABELS.dlBook
                .replace('%1$d', i + 1)
                .replace('%2$d', total);
            detailEl.textContent  = book.fr;
            barEl.style.width     = pct + '%';
            barEl.textContent     = pct + '%';

            try {
                const resp = await fetch(
                    ROOT_PATH + '/plugins/meeting-outlines/api/bible-texts/' + versionCode + '/' + book.num,
                    { method: 'POST', headers: { 'Content-Type': 'application/json' } }
                );
                const data = await resp.json();
                if (!data.success && !data.skipped) {
                    errors++;
                }
            } catch (e) {
                errors++;
            }
        }

        // Résultat final
        barEl.classList.remove('progress-bar-animated');
        if (errors === 0) {
            barEl.classList.add('bg-success');
            statusEl.textContent = LABELS.dlDone;
            detailEl.textContent  = '';
            // Mettre à jour la ligne du tableau
            markVersionAvailable(versionCode);
            // Cacher la bannière si elle était affichée
            const banner = document.getElementById('needs-download-banner');
            if (banner) banner.remove();
        } else {
            barEl.classList.add('bg-warning');
            statusEl.textContent = LABELS.dlErrors.replace('%d', errors);
        }
        footerEl.classList.remove('d-none');
    }

    function markVersionAvailable(versionCode) {
        const row = document.querySelector('tr[data-version="' + versionCode + '"]');
        if (!row) return;
        const cell = row.querySelector('.version-status-cell');
        if (cell) {
            cell.innerHTML = '<i class="fa-solid fa-check text-success version-status" title="' + LABELS.builtIn + '"></i>';
        }
        // Mettre à jour le select
        const opt = document.querySelector('#bible_version option[value="' + versionCode + '"]');
        if (opt) {
            opt.dataset.available = '1';
            if (!opt.textContent.includes('(')) {
                opt.textContent += ' (' + LABELS.builtIn + ')';
            }
        }
    }

    // Boutons "Télécharger" dans le tableau
    document.querySelectorAll('.btn-download-version').forEach(function (btn) {
        btn.addEventListener('click', function () {
            downloadVersion(this.dataset.version, this.dataset.name);
        });
    });

    // Bouton "Télécharger maintenant" dans la bannière post-save
    const triggerBtn = document.getElementById('btn-trigger-download');
    if (triggerBtn) {
        triggerBtn.addEventListener('click', function () {
            downloadVersion(this.dataset.version, this.dataset.name);
        });
    }

    // Avertissement si on sélectionne une version non disponible
    document.getElementById('bible_version').addEventListener('change', function () {
        const opt  = this.options[this.selectedIndex];
        const hint = document.getElementById('version-unavailable-hint');
        if (opt && opt.dataset.available === '0') {
            hint.classList.remove('d-none');
        } else {
            hint.classList.add('d-none');
        }
    });

    // Ajouter un type
    document.getElementById('btn-add-type').addEventListener('click', function () {
        const input = document.getElementById('new-type-label');
        const label = input.value.trim();
        if (!label) { input.focus(); return; }

        fetch(ROOT_PATH + '/plugins/meeting-outlines/api/service-types', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ label: label }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.success) { alert(data.message || LABELS.error); return; }

            input.value = '';
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex align-items-center justify-content-between py-2';
            li.dataset.slug = data.slug;
            li.innerHTML = '<span>' + escHtml(data.label) + '</span>'
                + '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-type"'
                + ' data-slug="' + escAttr(data.slug) + '"'
                + ' data-label="' + escAttr(data.label) + '">'
                + '<i class="fa-solid fa-trash"></i></button>';
            document.getElementById('service-types-list').appendChild(li);
            bindDelete(li.querySelector('.btn-delete-type'));
        })
        .catch(function () { alert(LABELS.error); });
    });

    // Supprimer un type
    function bindDelete(btn) {
        btn.addEventListener('click', function () {
            const slug  = this.dataset.slug;
            const label = this.dataset.label;
            if (!confirm(LABELS.confirmDelete + '\n"' + label + '"')) return;

            fetch(ROOT_PATH + '/plugins/meeting-outlines/api/service-types/' + encodeURIComponent(slug), {
                method:  'DELETE',
                headers: { 'Content-Type': 'application/json' },
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) { alert(data.message || LABELS.error); return; }
                const li = document.querySelector('#service-types-list li[data-slug="' + slug + '"]');
                if (li) li.remove();
            })
            .catch(function () { alert(LABELS.error); });
        });
    }

    document.querySelectorAll('.btn-delete-type').forEach(bindDelete);

    function escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }
    function escAttr(str) {
        return (str || '').replace(/'/g, '&#39;').replace(/"/g, '&quot;');
    }
})();
</script>

<?php require SystemURLs::getDocumentRoot() . '/Include/Footer.php'; ?>
