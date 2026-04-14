<?php

use ChurchCRM\dto\SystemURLs;
use ChurchCRM\Plugins\MeetingOutlines\MeetingOutlinesPlugin;
use ChurchCRM\Slim\Middleware\Request\Auth\AdminRoleAuthMiddleware;
use ChurchCRM\Slim\SlimUtils;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\PhpRenderer;

$plugin = MeetingOutlinesPlugin::getInstance();
if ($plugin === null) {
    return;
}

// ------------------------------------------------------------------
// Pages MVC
// ------------------------------------------------------------------

// Liste des cultes
$app->get('/meeting-outlines/services', function (Request $request, Response $response) use ($plugin): Response {
    $renderer = new PhpRenderer(__DIR__ . '/../views/');

    return $renderer->render($response, 'list.php', [
        'sRootPath' => SystemURLs::getRootPath(),
        'sPageTitle' => gettext('Meeting Outlines'),
        'services'  => $plugin->getServices(),
        'serviceTypes' => MeetingOutlinesPlugin::getServiceTypes(),
        'statusLabels' => MeetingOutlinesPlugin::getStatusLabels(),
    ]);
})->add(AdminRoleAuthMiddleware::class);

// Formulaire création
$app->get('/meeting-outlines/services/new', function (Request $request, Response $response) use ($plugin): Response {
    $renderer = new PhpRenderer(__DIR__ . '/../views/');

    return $renderer->render($response, 'edit.php', [
        'sRootPath'          => SystemURLs::getRootPath(),
        'sPageTitle'         => gettext('Add Meeting'),
        'service'            => null,
        'items'              => [],
        'serviceTypes'       => MeetingOutlinesPlugin::getServiceTypes(),
        'itemTypes'          => MeetingOutlinesPlugin::getItemTypes(),
        'statusLabels'       => MeetingOutlinesPlugin::getStatusLabels(),
        'preachersMembers'   => $plugin->getGroupMembers($plugin->getPreachersGroupId()),
        'responsiblesMembers'=> $plugin->getGroupMembers($plugin->getResponsiblesGroupId()),
        'bibleStructure'     => $plugin->getBibleStructure(),
        'plugin'             => $plugin,
    ]);
})->add(AdminRoleAuthMiddleware::class);

// Formulaire édition
$app->get('/meeting-outlines/services/{id:[0-9]+}/edit', function (Request $request, Response $response, array $args) use ($plugin): Response {
    $service = $plugin->getService((int) $args['id']);

    if ($service === null) {
        return $response->withStatus(404)->withHeader('Location', SystemURLs::getRootPath() . '/plugins/meeting-outlines/services');
    }

    $renderer = new PhpRenderer(__DIR__ . '/../views/');

    return $renderer->render($response, 'edit.php', [
        'sRootPath'          => SystemURLs::getRootPath(),
        'sPageTitle'         => gettext('Edit Meeting'),
        'service'            => $service,
        'items'              => $plugin->getServiceItems((int) $args['id']),
        'serviceTypes'       => MeetingOutlinesPlugin::getServiceTypes(),
        'itemTypes'          => MeetingOutlinesPlugin::getItemTypes(),
        'statusLabels'       => MeetingOutlinesPlugin::getStatusLabels(),
        'preachersMembers'   => $plugin->getGroupMembers($plugin->getPreachersGroupId()),
        'responsiblesMembers'=> $plugin->getGroupMembers($plugin->getResponsiblesGroupId()),
        'bibleStructure'     => $plugin->getBibleStructure(),
        'plugin'             => $plugin,
    ]);
})->add(AdminRoleAuthMiddleware::class);

// Vue aperçu avant impression (conservée pour consultation)
$app->get('/meeting-outlines/services/{id:[0-9]+}/print', function (Request $request, Response $response, array $args) use ($plugin): Response {
    $service = $plugin->getService((int) $args['id']);

    if ($service === null) {
        return $response->withStatus(404);
    }

    $renderer = new PhpRenderer(__DIR__ . '/../views/');

    return $renderer->render($response, 'print.php', [
        'sRootPath'    => SystemURLs::getRootPath(),
        'sPageTitle'   => gettext('Meeting Outline'),
        'service'      => $service,
        'items'        => $plugin->getServiceItems((int) $args['id']),
        'serviceTypes' => MeetingOutlinesPlugin::getServiceTypes(),
        'itemTypes'    => MeetingOutlinesPlugin::getItemTypes(),
    ]);
})->add(AdminRoleAuthMiddleware::class);

// Export PDF
$app->get('/meeting-outlines/services/{id:[0-9]+}/pdf', function (Request $request, Response $response, array $args) use ($plugin): Response {
    $service = $plugin->getService((int) $args['id']);

    if ($service === null) {
        return $response->withStatus(404);
    }

    $items        = $plugin->getServiceItems((int) $args['id']);
    $itemTypes    = MeetingOutlinesPlugin::getItemTypes();
    $serviceTypes = MeetingOutlinesPlugin::getServiceTypes();

    // Autoloader mPDF (vendoré dans lib/)
    require_once __DIR__ . '/../lib/vendor/autoload.php';

    // Répertoire temporaire dédié (doit être accessible en écriture par le serveur web)
    $tempDir = sys_get_temp_dir() . '/mpdf_meeting_outlines';
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    // --- Données ---
    $churchName   = htmlspecialchars(\ChurchCRM\dto\SystemConfig::getValue('sChurchName') ?: '');
    $serviceDate  = htmlspecialchars(date('d/m/Y', strtotime($service['date'])));
    $serviceTitle = htmlspecialchars($service['title']);
    $serviceType  = htmlspecialchars($serviceTypes[$service['type']] ?? $service['type']);
    $preacher     = htmlspecialchars($service['preacher_display'] ?? $service['preacher'] ?? '');

    $metaParts = [$serviceDate];
    if ($preacher !== '') {
        $metaParts[] = $preacher;
    }
    $metaParts[] = $serviceType;
    $metaLine = implode(' &nbsp;&middot;&nbsp; ', $metaParts);

    // --- Lignes du tableau ---
    $rowsHtml = '';
    if (empty($items)) {
        $rowsHtml = '<tr><td colspan="5" style="text-align:center;font-style:italic;color:#888;padding:20pt;">'
            . htmlspecialchars(gettext('No items added yet.'))
            . '</td></tr>';
    } else {
        foreach ($items as $i => $item) {
            $num       = $i + 1;
            $typeLabel = htmlspecialchars($itemTypes[$item['item_type']] ?? $item['item_type']);
            $title     = htmlspecialchars($item['title']);
            $desc      = !empty($item['description'])
                ? '<div class="item-desc">' . nl2br(htmlspecialchars($item['description'])) . '</div>'
                : '';
            $resp  = htmlspecialchars($item['responsible_display'] ?? $item['responsible'] ?? '');
            $dur   = $item['duration_minutes']
                ? (int) $item['duration_minutes'] . '&nbsp;' . htmlspecialchars(gettext('min'))
                : '';
            $bg    = ($i % 2 === 1) ? ' background:#fafafa;' : '';

            $rowsHtml .= '<tr style="' . $bg . '">'
                . '<td class="item-num">' . $num . '</td>'
                . '<td class="item-type">' . $typeLabel . '</td>'
                . '<td class="item-title">' . $title . $desc . '</td>'
                . '<td class="item-resp">' . $resp . '</td>'
                . '<td class="item-duration">' . $dur . '</td>'
                . '</tr>';
        }
    }

    // --- Notes ---
    $notesHtml = '';
    if (!empty($service['notes'])) {
        $notesLabel   = htmlspecialchars(gettext('Notes'));
        $notesContent = nl2br(htmlspecialchars($service['notes']));
        $notesHtml    = '<div class="notes"><h2>' . $notesLabel . '</h2><p>' . $notesContent . '</p></div>';
    }

    // --- Labels colonnes ---
    $lNum   = htmlspecialchars(gettext('#'));
    $lType  = htmlspecialchars(gettext('Item Type'));
    $lTitle = htmlspecialchars(gettext('Title'));
    $lResp  = htmlspecialchars(gettext('Responsible'));
    $lDur   = htmlspecialchars(gettext('Duration (minutes)'));

    $footerParts = array_filter([$churchName, htmlspecialchars(gettext('Meeting Outline')), $serviceDate]);
    $footerLine  = implode(' &nbsp;&middot;&nbsp; ', $footerParts);

    // --- Template HTML → mPDF ---
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>'
        . 'body        { font-family: dejavuserif; font-size: 11pt; color: #111; }'
        . 'h1          { font-size: 17pt; font-weight: bold; text-align: center; margin: 0 0 6pt; }'
        . '.meta       { text-align: center; font-size: 10pt; color: #444; margin-bottom: 6pt; }'
        . '.separator  { border-top: 2pt solid #333; margin-bottom: 14pt; }'
        . 'table       { width: 100%; border-collapse: collapse; }'
        . 'th          { background: #efefef; border: 1pt solid #aaa; padding: 5pt 8pt;'
        .               ' font-size: 9pt; text-transform: uppercase; letter-spacing: .04em; }'
        . 'td          { border: 1pt solid #ccc; padding: 6pt 8pt; vertical-align: top; }'
        . '.item-num   { width: 18pt; text-align: center; font-weight: bold; color: #555; }'
        . '.item-type  { width: 80pt; font-style: italic; color: #555; }'
        . '.item-title { font-weight: bold; }'
        . '.item-desc  { font-size: 9pt; color: #444; margin-top: 3pt; font-weight: normal; }'
        . '.item-resp  { width: 90pt; font-size: 10pt; color: #444; }'
        . '.item-duration { width: 40pt; text-align: center; font-size: 9pt; color: #666; }'
        . '.notes      { margin-top: 18pt; border-top: 1pt solid #ccc; padding-top: 10pt;'
        .               ' font-size: 9pt; color: #444; }'
        . '.notes h2   { font-size: 10pt; font-weight: bold; text-transform: uppercase;'
        .               ' letter-spacing: .04em; margin-bottom: 5pt; }'
        . '.footer     { margin-top: 20pt; border-top: 1pt solid #ccc; padding-top: 6pt;'
        .               ' font-size: 8pt; color: #999; text-align: center; }'
        . '</style></head><body>'
        . '<h1>' . $serviceTitle . '</h1>'
        . '<p class="meta">' . $metaLine . '</p>'
        . '<div class="separator"></div>'
        . '<table>'
        .   '<thead><tr>'
        .     '<th class="item-num">'      . $lNum   . '</th>'
        .     '<th class="item-type">'     . $lType  . '</th>'
        .     '<th>'                       . $lTitle . '</th>'
        .     '<th class="item-resp">'     . $lResp  . '</th>'
        .     '<th class="item-duration">' . $lDur   . '</th>'
        .   '</tr></thead>'
        .   '<tbody>' . $rowsHtml . '</tbody>'
        . '</table>'
        . $notesHtml
        . '<div class="footer">' . $footerLine . '</div>'
        . '</body></html>';

    // --- Génération PDF ---
    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4',
        'margin_top'    => 20,
        'margin_right'  => 18,
        'margin_bottom' => 20,
        'margin_left'   => 18,
        'tempDir'       => $tempDir,
        'default_font'  => 'dejavuserif',
    ]);
    $mpdf->SetTitle($service['title']);
    $mpdf->WriteHTML($html);

    $pdfContent = $mpdf->Output('', 'S');
    $filename   = 'culte-' . $service['date'] . '.pdf';

    $response->getBody()->write($pdfContent);

    return $response
        ->withHeader('Content-Type', 'application/pdf')
        ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
        ->withHeader('Cache-Control', 'private, max-age=0, must-revalidate');
})->add(AdminRoleAuthMiddleware::class);

// Page réglages — GET
$app->get('/meeting-outlines/settings', function (Request $request, Response $response) use ($plugin): Response {
    $renderer = new PhpRenderer(__DIR__ . '/../views/');

    return $renderer->render($response, 'settings.php', [
        'sRootPath'           => SystemURLs::getRootPath(),
        'sPageTitle'          => gettext('Meeting Settings'),
        'allGroups'           => $plugin->getAllGroups(),
        'preachersGroupId'    => $plugin->getPreachersGroupId(),
        'responsiblesGroupId' => $plugin->getResponsiblesGroupId(),
        'preachersMembers'    => $plugin->getGroupMembers($plugin->getPreachersGroupId()),
        'responsiblesMembers' => $plugin->getGroupMembers($plugin->getResponsiblesGroupId()),
        'bibleVersions'       => $plugin->getBibleVersions(),
        'currentBibleVersion' => $plugin->getBibleVersion(),
        'successMessage'      => '',
    ]);
})->add(AdminRoleAuthMiddleware::class);

// Page réglages — POST
$app->post('/meeting-outlines/settings', function (Request $request, Response $response) use ($plugin): Response {
    $data = $request->getParsedBody();
    $plugin->saveSettings($data);

    $renderer = new PhpRenderer(__DIR__ . '/../views/');

    return $renderer->render($response, 'settings.php', [
        'sRootPath'           => SystemURLs::getRootPath(),
        'sPageTitle'          => gettext('Meeting Settings'),
        'allGroups'           => $plugin->getAllGroups(),
        'preachersGroupId'    => $plugin->getPreachersGroupId(),
        'responsiblesGroupId' => $plugin->getResponsiblesGroupId(),
        'preachersMembers'    => $plugin->getGroupMembers($plugin->getPreachersGroupId()),
        'responsiblesMembers' => $plugin->getGroupMembers($plugin->getResponsiblesGroupId()),
        'bibleVersions'       => $plugin->getBibleVersions(),
        'currentBibleVersion' => $plugin->getBibleVersion(),
        'successMessage'      => gettext('Settings saved successfully.'),
    ]);
})->add(AdminRoleAuthMiddleware::class);

// ------------------------------------------------------------------
// API JSON — Services
// ------------------------------------------------------------------

$app->group('/meeting-outlines/api', function (RouteCollectorProxy $group) use ($plugin): void {

    // POST /plugins/meeting-outlines/api/services — créer
    $group->post('/services', function (Request $request, Response $response) use ($plugin): Response {
        $data   = $request->getParsedBody();
        $errors = [];

        if (empty(trim($data['date'] ?? ''))) {
            $errors[] = gettext('Meeting date is required.');
        }
        if (empty(trim($data['title'] ?? ''))) {
            $errors[] = gettext('Title is required.');
        }

        if (!empty($errors)) {
            return SlimUtils::renderJSON($response, ['success' => false, 'errors' => $errors], 400);
        }

        try {
            $id = $plugin->createService([
                'date'               => trim($data['date']),
                'title'              => trim($data['title']),
                'type'               => $data['type']               ?? 'sunday',
                'preacher'           => trim($data['preacher']       ?? ''),
                'preacher_person_id'  => $data['preacher_person_id']  ?? null,
                'president_person_id' => $data['president_person_id'] ?? null,
                'notes'               => trim($data['notes']          ?? ''),
                'status'              => $data['status']              ?? 'draft',
            ]);

            return SlimUtils::renderJSON($response, [
                'success' => true,
                'message' => gettext('Meeting saved successfully.'),
                'id'      => $id,
            ]);
        } catch (\Throwable $e) {
            return SlimUtils::renderErrorJSON($response, gettext('Failed to save meeting.'), [], 500, $e, $request);
        }
    });

    // PUT /plugins/meeting-outlines/api/services/{id} — mettre à jour
    $group->put('/services/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($plugin): Response {
        $data   = $request->getParsedBody();
        $errors = [];

        if (empty(trim($data['date'] ?? ''))) {
            $errors[] = gettext('Meeting date is required.');
        }
        if (empty(trim($data['title'] ?? ''))) {
            $errors[] = gettext('Title is required.');
        }

        if (!empty($errors)) {
            return SlimUtils::renderJSON($response, ['success' => false, 'errors' => $errors], 400);
        }

        $service = $plugin->getService((int) $args['id']);
        if ($service === null) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => gettext('Meeting not found.')], 404);
        }

        try {
            $plugin->updateService((int) $args['id'], [
                'date'               => trim($data['date']),
                'title'              => trim($data['title']),
                'type'               => $data['type']               ?? 'sunday',
                'preacher'           => trim($data['preacher']       ?? ''),
                'preacher_person_id'  => $data['preacher_person_id']  ?? null,
                'president_person_id' => $data['president_person_id'] ?? null,
                'notes'               => trim($data['notes']          ?? ''),
                'status'              => $data['status']              ?? 'draft',
            ]);

            return SlimUtils::renderJSON($response, [
                'success' => true,
                'message' => gettext('Meeting saved successfully.'),
            ]);
        } catch (\Throwable $e) {
            return SlimUtils::renderErrorJSON($response, gettext('Failed to save meeting.'), [], 500, $e, $request);
        }
    });

    // DELETE /plugins/meeting-outlines/api/services/{id}
    $group->delete('/services/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($plugin): Response {
        $service = $plugin->getService((int) $args['id']);
        if ($service === null) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => gettext('Meeting not found.')], 404);
        }

        try {
            $plugin->deleteService((int) $args['id']);

            return SlimUtils::renderJSON($response, [
                'success' => true,
                'message' => gettext('Meeting deleted.'),
            ]);
        } catch (\Throwable $e) {
            return SlimUtils::renderErrorJSON($response, gettext('Failed to delete meeting.'), [], 500, $e, $request);
        }
    });

    // ------------------------------------------------------------------
    // API JSON — Items
    // ------------------------------------------------------------------

    // POST /plugins/meeting-outlines/api/services/{id}/items — ajouter un élément
    $group->post('/services/{id:[0-9]+}/items', function (Request $request, Response $response, array $args) use ($plugin): Response {
        $serviceId = (int) $args['id'];
        $service   = $plugin->getService($serviceId);

        if ($service === null) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => gettext('Meeting not found.')], 404);
        }

        $data   = $request->getParsedBody();
        $errors = [];

        if (empty(trim($data['title'] ?? ''))) {
            $errors[] = gettext('Item title is required.');
        }

        if (!empty($errors)) {
            return SlimUtils::renderJSON($response, ['success' => false, 'errors' => $errors], 400);
        }

        try {
            $id   = $plugin->createItem($serviceId, [
                'item_type'             => $data['item_type']              ?? 'other',
                'title'                 => trim($data['title']),
                'description'           => trim($data['description']           ?? ''),
                'duration_minutes'      => $data['duration_minutes']           ?? null,
                'responsible'           => trim($data['responsible']           ?? ''),
                'responsible_person_id' => $data['responsible_person_id']      ?? '',
                'bible_book'            => $data['bible_book']                 ?? '',
                'bible_chapter'         => $data['bible_chapter']              ?? '',
                'bible_verse_start'     => $data['bible_verse_start']          ?? '',
                'bible_verse_end'       => $data['bible_verse_end']            ?? '',
            ]);
            $item = $plugin->getItem($id);

            return SlimUtils::renderJSON($response, [
                'success' => true,
                'message' => gettext('Item added.'),
                'item'    => $item,
            ]);
        } catch (\Throwable $e) {
            return SlimUtils::renderErrorJSON($response, gettext('Failed to add item.'), [], 500, $e, $request);
        }
    });

    // PUT /plugins/meeting-outlines/api/items/{id}
    $group->put('/items/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($plugin): Response {
        $item = $plugin->getItem((int) $args['id']);
        if ($item === null) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => gettext('Item not found.')], 404);
        }

        $data   = $request->getParsedBody();
        $errors = [];

        if (empty(trim($data['title'] ?? ''))) {
            $errors[] = gettext('Item title is required.');
        }

        if (!empty($errors)) {
            return SlimUtils::renderJSON($response, ['success' => false, 'errors' => $errors], 400);
        }

        try {
            $plugin->updateItem((int) $args['id'], [
                'item_type'             => $data['item_type']              ?? 'other',
                'title'                 => trim($data['title']),
                'description'           => trim($data['description']           ?? ''),
                'duration_minutes'      => $data['duration_minutes']           ?? null,
                'responsible'           => trim($data['responsible']           ?? ''),
                'responsible_person_id' => $data['responsible_person_id']      ?? '',
                'bible_book'            => $data['bible_book']                 ?? '',
                'bible_chapter'         => $data['bible_chapter']              ?? '',
                'bible_verse_start'     => $data['bible_verse_start']          ?? '',
                'bible_verse_end'       => $data['bible_verse_end']            ?? '',
            ]);

            return SlimUtils::renderJSON($response, [
                'success' => true,
                'message' => gettext('Item updated.'),
                'item'    => $plugin->getItem((int) $args['id']),
            ]);
        } catch (\Throwable $e) {
            return SlimUtils::renderErrorJSON($response, gettext('Failed to update item.'), [], 500, $e, $request);
        }
    });

    // DELETE /plugins/meeting-outlines/api/items/{id}
    $group->delete('/items/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($plugin): Response {
        $item = $plugin->getItem((int) $args['id']);
        if ($item === null) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => gettext('Item not found.')], 404);
        }

        try {
            $plugin->deleteItem((int) $args['id']);

            return SlimUtils::renderJSON($response, [
                'success' => true,
                'message' => gettext('Item deleted.'),
            ]);
        } catch (\Throwable $e) {
            return SlimUtils::renderErrorJSON($response, gettext('Failed to delete item.'), [], 500, $e, $request);
        }
    });

    // GET /plugins/meeting-outlines/api/groups/{id}/members — membres d'un groupe (pour mise à jour dynamique côté JS)
    $group->get('/groups/{id:[0-9]+}/members', function (Request $request, Response $response, array $args) use ($plugin): Response {
        $members = $plugin->getGroupMembers((int) $args['id']);

        return SlimUtils::renderJSON($response, ['members' => $members]);
    });

    // POST /plugins/meeting-outlines/api/services/{id}/items/reorder
    $group->post('/services/{id:[0-9]+}/items/reorder', function (Request $request, Response $response, array $args) use ($plugin): Response {
        $serviceId = (int) $args['id'];
        $data      = $request->getParsedBody();
        $ids       = $data['ids'] ?? [];

        if (!is_array($ids)) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => gettext('Invalid data.')], 400);
        }

        $ok = $plugin->reorderItems($serviceId, $ids);

        return SlimUtils::renderJSON($response, ['success' => $ok]);
    });

})->add(AdminRoleAuthMiddleware::class);
