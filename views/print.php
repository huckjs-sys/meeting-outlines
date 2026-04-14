<?php

use ChurchCRM\dto\SystemConfig;
use ChurchCRM\dto\SystemURLs;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(SystemConfig::getValue('sLanguage') ?: 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= dgettext('meeting-outlines','Meeting Outline') ?> — <?= htmlspecialchars($service['title']) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 12pt;
            color: #111;
            background: #fff;
            padding: 0;
        }

        .page {
            max-width: 680px;
            margin: 0 auto;
            padding: 30px 40px;
        }

        /* En-tête */
        .print-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 14px;
            margin-bottom: 20px;
        }
        .print-header h1 {
            font-size: 20pt;
            font-weight: bold;
            letter-spacing: .02em;
        }
        .print-header .meta {
            margin-top: 6px;
            font-size: 11pt;
            color: #444;
        }
        .print-header .meta span { margin: 0 8px; }

        /* Tableau du programme */
        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .order-table thead th {
            background: #f0f0f0;
            border: 1px solid #ccc;
            padding: 6px 10px;
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .order-table tbody td {
            border: 1px solid #ddd;
            padding: 7px 10px;
            vertical-align: top;
        }
        .order-table tbody tr:nth-child(even) td {
            background: #fafafa;
        }

        .item-num {
            width: 28px;
            text-align: center;
            font-weight: bold;
            color: #555;
        }
        .item-type-col {
            width: 110px;
            font-style: italic;
            color: #555;
        }
        .item-title-col {
            font-weight: bold;
        }
        .item-desc {
            font-size: 10pt;
            color: #444;
            margin-top: 3px;
            white-space: pre-line;
        }
        .item-responsible {
            font-size: 10pt;
            color: #555;
        }
        .item-duration-col {
            width: 60px;
            text-align: center;
            font-size: 10pt;
            color: #666;
            white-space: nowrap;
        }

        /* Message vide */
        .empty-msg {
            text-align: center;
            color: #888;
            font-style: italic;
            padding: 30px;
        }

        /* Notes du culte */
        .notes-block {
            margin-top: 24px;
            border-top: 1px solid #ccc;
            padding-top: 12px;
            font-size: 10pt;
            color: #444;
        }
        .notes-block h2 {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        /* Pied de page impression */
        .print-footer {
            margin-top: 30px;
            border-top: 1px solid #ccc;
            padding-top: 8px;
            font-size: 9pt;
            color: #888;
            text-align: center;
        }

        /* Boutons (écran seulement) */
        .screen-only {
            text-align: center;
            padding: 16px 0 8px;
        }
        .btn-pdf {
            display: inline-block;
            background: #b71c1c;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 8px 22px;
            font-size: 12pt;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-back {
            display: inline-block;
            background: #555;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            font-size: 12pt;
            cursor: pointer;
            text-decoration: none;
            margin-right: 10px;
        }
    </style>
</head>
<body>
<div class="page">

    <!-- Boutons écran -->
    <div class="screen-only">
        <a href="javascript:history.back()" class="btn-back">
            &#8592; <?= dgettext('meeting-outlines','Back') ?>
        </a>
        <a href="<?= SystemURLs::getRootPath() ?>/plugins/meeting-outlines/services/<?= (int) $service['id'] ?>/pdf"
           class="btn-pdf">
            &#128196; <?= dgettext('meeting-outlines','Download PDF') ?>
        </a>
    </div>

    <!-- En-tête -->
    <div class="print-header">
        <h1><?= htmlspecialchars($service['title']) ?></h1>
        <div class="meta">
            <span><?= htmlspecialchars(date('l, F j, Y', strtotime($service['date']))) ?></span>
            <?php if (!empty($service['preacher'])): ?>
                <span>·</span>
                <span><?= htmlspecialchars($service['preacher']) ?></span>
            <?php endif; ?>
            <span>·</span>
            <span><?= htmlspecialchars($serviceTypes[$service['type']] ?? $service['type']) ?></span>
        </div>
    </div>

    <!-- Programme -->
    <?php if (empty($items)): ?>
        <p class="empty-msg"><?= dgettext('meeting-outlines','No items added yet.') ?></p>
    <?php else: ?>
    <table class="order-table">
        <thead>
            <tr>
                <th class="item-num">#</th>
                <th><?= dgettext('meeting-outlines','Item Type') ?></th>
                <th><?= dgettext('meeting-outlines','Title') ?></th>
                <th><?= dgettext('meeting-outlines','Responsible') ?></th>
                <th><?= dgettext('meeting-outlines','Duration (minutes)') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $i => $item): ?>
            <tr>
                <td class="item-num"><?= $i + 1 ?></td>
                <td class="item-type-col">
                    <?= htmlspecialchars($itemTypes[$item['item_type']] ?? $item['item_type']) ?>
                </td>
                <td class="item-title-col">
                    <?= htmlspecialchars($item['title']) ?>
                    <?php if (!empty($item['description'])): ?>
                        <div class="item-desc"><?= htmlspecialchars($item['description']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="item-responsible">
                    <?= htmlspecialchars($item['responsible']) ?>
                </td>
                <td class="item-duration-col">
                    <?= $item['duration_minutes'] ? (int)$item['duration_minutes'] . ' ' . dgettext('meeting-outlines','min') : '' ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Notes -->
    <?php if (!empty($service['notes'])): ?>
    <div class="notes-block">
        <h2><?= dgettext('meeting-outlines','Notes') ?></h2>
        <p><?= nl2br(htmlspecialchars($service['notes'])) ?></p>
    </div>
    <?php endif; ?>

    <!-- Pied de page -->
    <div class="print-footer">
        <?= htmlspecialchars(SystemConfig::getValue('sChurchName') ?: '') ?>
        &nbsp;·&nbsp;
        <?= dgettext('meeting-outlines','Meeting Outline') ?>
        &nbsp;·&nbsp;
        <?= htmlspecialchars(date('d/m/Y', strtotime($service['date']))) ?>
    </div>

</div>
</body>
</html>
