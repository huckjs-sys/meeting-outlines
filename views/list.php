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
                <li class="breadcrumb-item active"><?= gettext('Meeting Outlines') ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0">
            <i class="fa-solid fa-list-ol me-2"></i><?= gettext('Meetings') ?>
        </h3>
        <a href="<?= SystemURLs::getRootPath() ?>/plugins/meeting-outlines/services/new"
           class="btn btn-success btn-sm">
            <i class="fa-solid fa-plus me-1"></i><?= gettext('Add Meeting') ?>
        </a>
    </div>
    <div class="table-responsive">
        <table id="services-table" class="table table-vcenter table-hover card-table">
            <thead>
                <tr>
                    <th><?= gettext('Date') ?></th>
                    <th><?= gettext('Title') ?></th>
                    <th><?= gettext('Type') ?></th>
                    <th><?= gettext('Preacher') ?></th>
                    <th><?= gettext('President') ?></th>
                    <th><?= gettext('Status') ?></th>
                    <th class="text-center"><?= gettext('Items') ?></th>
                    <th class="no-export w-1"><?= gettext('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $s): ?>
                <tr>
                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($s['date']))) ?></td>
                    <td><?= htmlspecialchars($s['title']) ?></td>
                    <td><?= htmlspecialchars($serviceTypes[$s['type']] ?? $s['type']) ?></td>
                    <td><?= htmlspecialchars($s['preacher_display'] ?? '') ?></td>
                    <td><?= htmlspecialchars($s['president_display'] ?? '') ?></td>
                    <td>
                        <?php if ($s['status'] === 'published'): ?>
                            <span class="badge bg-success"><?= gettext('Published') ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= gettext('Draft') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= (int) $s['item_count'] ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= SystemURLs::getRootPath() ?>/plugins/meeting-outlines/services/<?= $s['id'] ?>/edit"
                               class="btn btn-primary" title="<?= gettext('Edit Service') ?>">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="<?= SystemURLs::getRootPath() ?>/plugins/meeting-outlines/services/<?= $s['id'] ?>/print"
                               class="btn btn-info" target="_blank" title="<?= gettext('Print Order') ?>">
                                <i class="fa-solid fa-print"></i>
                            </a>
                            <button type="button" class="btn btn-danger btn-delete"
                                    data-id="<?= $s['id'] ?>"
                                    data-title="<?= htmlspecialchars($s['title']) ?>"
                                    title="<?= gettext('Delete Service') ?>">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const ROOT_PATH = <?= json_encode(SystemURLs::getRootPath()) ?>;
const LABELS = {
    confirmDelete: <?= json_encode(gettext('Are you sure you want to delete this meeting?')) ?>,
    deleted:       <?= json_encode(gettext('Meeting deleted.')) ?>,
    error:         <?= json_encode(gettext('An error occurred. Please try again.')) ?>,
};

document.addEventListener('DOMContentLoaded', function () {
    // DataTables — on étend la config globale CRM (language fr-FR + layout)
    if (typeof $.fn.DataTable !== 'undefined') {
        var dtConfig = $.extend(true, {}, window.CRM.plugin.dataTable, {
            order: [[0, 'desc']],
            columnDefs: [{ orderable: false, targets: [7] }],
            language: $.extend(true, {}, (window.CRM.plugin.dataTable.language || {}), {
                emptyTable: <?= json_encode(gettext('No services found.')) ?>,
            }),
        });
        $('#services-table').DataTable(dtConfig);
    }

    // Suppression
    document.querySelectorAll('.btn-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id    = this.dataset.id;
            const title = this.dataset.title;

            if (!confirm(LABELS.confirmDelete + '\n"' + title + '"')) {
                return;
            }

            fetch(ROOT_PATH + '/plugins/meeting-outlines/api/services/' + id, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || LABELS.error);
                }
            })
            .catch(function () { alert(LABELS.error); });
        });
    });
});
</script>

<?php require SystemURLs::getDocumentRoot() . '/Include/Footer.php'; ?>
