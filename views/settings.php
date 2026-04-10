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
                        <?= gettext('Meeting Outlines') ?>
                    </a>
                </li>
                <li class="breadcrumb-item active"><?= gettext('Meeting Settings') ?></li>
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
                <i class="fa-solid fa-microphone me-2"></i><?= gettext('Preachers') ?>
            </h3>
        </div>
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-6 mb-3">
                    <label for="preachers_group_id" class="form-label">
                        <?= gettext('Group linked to Preachers') ?>
                    </label>
                    <select id="preachers_group_id" name="preachers_group_id" class="form-select">
                        <option value="0"><?= gettext('— No group selected —') ?></option>
                        <?php foreach ($allGroups as $group): ?>
                        <option value="<?= (int) $group['Id'] ?>"
                            <?= (int) $group['Id'] === $preachersGroupId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($group['Name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        <?= gettext('Members of this group will appear in the Preacher dropdown when creating a service.') ?>
                        <?php if (empty($allGroups)): ?>
                        <br><span class="text-warning">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <?= gettext('No groups found.') ?>
                            <a href="<?= SystemURLs::getRootPath() ?>/v2/groups/list"><?= gettext('Create a group first.') ?></a>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($preachersGroupId > 0 && !empty($preachersMembers)): ?>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?= gettext('Members in this group') ?></label>
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
                <i class="fa-solid fa-users me-2"></i><?= gettext('Responsibles') ?>
            </h3>
        </div>
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-6 mb-3">
                    <label for="responsibles_group_id" class="form-label">
                        <?= gettext('Group linked to Responsibles') ?>
                    </label>
                    <select id="responsibles_group_id" name="responsibles_group_id" class="form-select">
                        <option value="0"><?= gettext('— No group selected —') ?></option>
                        <?php foreach ($allGroups as $group): ?>
                        <option value="<?= (int) $group['Id'] ?>"
                            <?= (int) $group['Id'] === $responsiblesGroupId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($group['Name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        <?= gettext('Members of this group will appear in the Responsible dropdown for each item in the order of service.') ?>
                    </div>
                </div>
                <?php if ($responsiblesGroupId > 0 && !empty($responsiblesMembers)): ?>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?= gettext('Members in this group') ?></label>
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
                <i class="fa-solid fa-book-open me-2"></i><?= gettext('Bible Version') ?>
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label for="bible_version" class="form-label">
                        <?= gettext('Default Bible version') ?>
                    </label>
                    <select id="bible_version" name="bible_version" class="form-select">
                        <?php foreach ($bibleVersions as $v): ?>
                        <option value="<?= htmlspecialchars($v['code']) ?>"
                            <?= $v['code'] === $currentBibleVersion ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['name']) ?>
                            <?= $v['local'] ? ' (' . gettext('built-in') . ')' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        <?= gettext('This version is shown in the print view and used for Bible reading references.') ?>
                    </div>
                </div>
                <div class="col-md-7 mb-3">
                    <label class="form-label"><?= gettext('Available versions') ?></label>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?= gettext('Code') ?></th>
                                    <th><?= gettext('Name') ?></th>
                                    <th><?= gettext('Language') ?></th>
                                    <th><?= gettext('Built-in') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bibleVersions as $v): ?>
                                <tr <?= $v['code'] === $currentBibleVersion ? 'class="table-primary"' : '' ?>>
                                    <td><code><?= htmlspecialchars($v['code']) ?></code></td>
                                    <td><?= htmlspecialchars($v['name']) ?></td>
                                    <td><?= htmlspecialchars(strtoupper($v['lang'])) ?></td>
                                    <td class="text-center">
                                        <?php if ($v['local']): ?>
                                        <i class="fa-solid fa-check text-success" title="<?= gettext('Built-in') ?>"></i>
                                        <?php else: ?>
                                        <span class="text-muted">—</span>
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
            <i class="fa-solid fa-floppy-disk me-1"></i><?= gettext('Save Settings') ?>
        </button>
        <a href="<?= SystemURLs::getRootPath() ?>/plugins/meeting-outlines/services"
           class="btn btn-secondary ms-auto">
            <?= gettext('Back to Meeting Outlines') ?>
        </a>
    </div>

</form>

<?php require SystemURLs::getDocumentRoot() . '/Include/Footer.php'; ?>
