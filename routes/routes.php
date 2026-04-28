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
        'sPageTitle' => dgettext('meeting-outlines', 'Meeting Outlines'),
        'services'  => $plugin->getServices(),
        'serviceTypes' => $plugin->getServiceTypes(),
        'statusLabels' => MeetingOutlinesPlugin::getStatusLabels(),
    ]);
})->add(AdminRoleAuthMiddleware::class);

// Formulaire création
$app->get('/meeting-outlines/services/new', function (Request $request, Response $response) use ($plugin): Response {
    $renderer = new PhpRenderer(__DIR__ . '/../views/');

    return $renderer->render($response, 'edit.php', [
        'sRootPath'          => SystemURLs::getRootPath(),
        'sPageTitle'         => dgettext('meeting-outlines', 'Add Meeting'),
        'service'            => null,
        'items'              => [],
        'serviceTypes'       => $plugin->getServiceTypes(),
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
        'sPageTitle'         => dgettext('meeting-outlines', 'Edit Meeting'),
        'service'            => $service,
        'items'              => $plugin->getServiceItems((int) $args['id']),
        'serviceTypes'       => $plugin->getServiceTypes(),
        'itemTypes'          => MeetingOutlinesPlugin::getItemTypes(),
        'statusLabels'       => MeetingOutlinesPlugin::getStatusLabels(),
        'preachersMembers'   => $plugin->getGroupMembers($plugin->getPreachersGroupId()),
        'responsiblesMembers'=> $plugin->getGroupMembers($plugin->getResponsiblesGroupId()),
        'bibleStructure'     => $plugin->getBibleStructure(),
        'plugin'             => $plugin,
    ]);
})->add(AdminRoleAuthMiddleware::class);

// Vue impression
$app->get('/meeting-outlines/services/{id:[0-9]+}/print', function (Request $request, Response $response, array $args) use ($plugin): Response {
    $service = $plugin->getService((int) $args['id']);

    if ($service === null) {
        return $response->withStatus(404);
    }

    $items = $plugin->getServiceItems((int) $args['id']);

    // Pré-calcul des textes bibliques pour les lectures (dégradation gracieuse si absent)
    $bibleVerses = [];
    foreach ($items as $item) {
        if ($item['item_type'] === 'bible_reading'
            && !empty($item['bible_book'])
            && !empty($item['bible_chapter'])
            && !empty($item['bible_verse_start'])
        ) {
            $verses = $plugin->getBibleVerses(
                (int) $item['bible_book'],
                (int) $item['bible_chapter'],
                (int) $item['bible_verse_start'],
                !empty($item['bible_verse_end']) ? (int) $item['bible_verse_end'] : null
            );
            if ($verses !== null) {
                $bibleVerses[$item['id']] = $verses;
            }
        }
    }

    $renderer = new PhpRenderer(__DIR__ . '/../views/');

    return $renderer->render($response, 'print.php', [
        'sRootPath'    => SystemURLs::getRootPath(),
        'sPageTitle'   => dgettext('meeting-outlines', 'Meeting Outline'),
        'service'      => $service,
        'items'        => $items,
        'serviceTypes' => $plugin->getServiceTypes(),
        'itemTypes'    => MeetingOutlinesPlugin::getItemTypes(),
        'plugin'       => $plugin,
        'bibleVerses'  => $bibleVerses,
    ]);
})->add(AdminRoleAuthMiddleware::class);

// Page réglages — GET
$app->get('/meeting-outlines/settings', function (Request $request, Response $response) use ($plugin): Response {
    $renderer = new PhpRenderer(__DIR__ . '/../views/');

    return $renderer->render($response, 'settings.php', [
        'sRootPath'           => SystemURLs::getRootPath(),
        'sPageTitle'          => dgettext('meeting-outlines', 'Meeting Settings'),
        'allGroups'           => $plugin->getAllGroups(),
        'preachersGroupId'    => $plugin->getPreachersGroupId(),
        'responsiblesGroupId' => $plugin->getResponsiblesGroupId(),
        'preachersMembers'    => $plugin->getGroupMembers($plugin->getPreachersGroupId()),
        'responsiblesMembers' => $plugin->getGroupMembers($plugin->getResponsiblesGroupId()),
        'bibleVersions'       => $plugin->getBibleVersions(),
        'currentBibleVersion' => $plugin->getBibleVersion(),
        'bibleStructure'      => $plugin->getBibleStructure(),
        'allServiceTypes'     => $plugin->getAllServiceTypes(),
        'successMessage'      => '',
        'needsDownload'       => false,
    ]);
})->add(AdminRoleAuthMiddleware::class);

// Page réglages — POST
$app->post('/meeting-outlines/settings', function (Request $request, Response $response) use ($plugin): Response {
    $data = $request->getParsedBody();
    $plugin->saveSettings($data);

    $currentVersion = $plugin->getBibleVersion();
    $needsDownload  = !$plugin->isBibleVersionAvailable($currentVersion);

    $renderer = new PhpRenderer(__DIR__ . '/../views/');

    return $renderer->render($response, 'settings.php', [
        'sRootPath'           => SystemURLs::getRootPath(),
        'sPageTitle'          => dgettext('meeting-outlines', 'Meeting Settings'),
        'allGroups'           => $plugin->getAllGroups(),
        'preachersGroupId'    => $plugin->getPreachersGroupId(),
        'responsiblesGroupId' => $plugin->getResponsiblesGroupId(),
        'preachersMembers'    => $plugin->getGroupMembers($plugin->getPreachersGroupId()),
        'responsiblesMembers' => $plugin->getGroupMembers($plugin->getResponsiblesGroupId()),
        'bibleVersions'       => $plugin->getBibleVersions(),
        'currentBibleVersion' => $currentVersion,
        'bibleStructure'      => $plugin->getBibleStructure(),
        'allServiceTypes'     => $plugin->getAllServiceTypes(),
        'successMessage'      => dgettext('meeting-outlines', 'Settings saved successfully.'),
        'needsDownload'       => $needsDownload,
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
            $errors[] = dgettext('meeting-outlines', 'Meeting date is required.');
        }
        if (empty(trim($data['title'] ?? ''))) {
            $errors[] = dgettext('meeting-outlines', 'Title is required.');
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
                'message' => dgettext('meeting-outlines', 'Meeting saved successfully.'),
                'id'      => $id,
            ]);
        } catch (\Throwable $e) {
            return SlimUtils::renderErrorJSON($response, dgettext('meeting-outlines', 'Failed to save meeting.'), [], 500, $e, $request);
        }
    });

    // PUT /plugins/meeting-outlines/api/services/{id} — mettre à jour
    $group->put('/services/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($plugin): Response {
        $data   = $request->getParsedBody();
        $errors = [];

        if (empty(trim($data['date'] ?? ''))) {
            $errors[] = dgettext('meeting-outlines', 'Meeting date is required.');
        }
        if (empty(trim($data['title'] ?? ''))) {
            $errors[] = dgettext('meeting-outlines', 'Title is required.');
        }

        if (!empty($errors)) {
            return SlimUtils::renderJSON($response, ['success' => false, 'errors' => $errors], 400);
        }

        $service = $plugin->getService((int) $args['id']);
        if ($service === null) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => dgettext('meeting-outlines', 'Meeting not found.')], 404);
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
                'message' => dgettext('meeting-outlines', 'Meeting saved successfully.'),
            ]);
        } catch (\Throwable $e) {
            return SlimUtils::renderErrorJSON($response, dgettext('meeting-outlines', 'Failed to save meeting.'), [], 500, $e, $request);
        }
    });

    // POST /plugins/meeting-outlines/api/service-types — créer un type personnalisé
    $group->post('/service-types', function (Request $request, Response $response) use ($plugin): Response {
        $data  = $request->getParsedBody();
        $label = trim($data['label'] ?? '');

        if ($label === '') {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => dgettext('meeting-outlines', 'Label is required.')], 400);
        }

        try {
            $slug = $plugin->createServiceType($label);

            return SlimUtils::renderJSON($response, [
                'success' => true,
                'message' => dgettext('meeting-outlines', 'Meeting type added.'),
                'slug'    => $slug,
                'label'   => $label,
            ]);
        } catch (\Throwable $e) {
            return SlimUtils::renderErrorJSON($response, $e->getMessage(), [], 400, $e, $request);
        }
    });

    // DELETE /plugins/meeting-outlines/api/service-types/{slug}
    $group->delete('/service-types/{slug}', function (Request $request, Response $response, array $args) use ($plugin): Response {
        $slug = $args['slug'];

        try {
            $plugin->deleteServiceType($slug);

            return SlimUtils::renderJSON($response, [
                'success' => true,
                'message' => dgettext('meeting-outlines', 'Meeting type deleted.'),
            ]);
        } catch (\Throwable $e) {
            return SlimUtils::renderErrorJSON($response, $e->getMessage(), [], 400, $e, $request);
        }
    });

    // POST /plugins/meeting-outlines/api/services/{id}/notify — envoyer le déroulement par e-mail
    $group->post('/services/{id:[0-9]+}/notify', function (Request $request, Response $response, array $args) use ($plugin): Response {
        $service = $plugin->getService((int) $args['id']);
        if ($service === null) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => dgettext('meeting-outlines', 'Meeting not found.')], 404);
        }

        try {
            $result = $plugin->sendServiceNotification((int) $args['id']);

            return SlimUtils::renderJSON($response, array_merge(['success' => true], $result));
        } catch (\Throwable $e) {
            return SlimUtils::renderErrorJSON($response, dgettext('meeting-outlines', 'Failed to send notification.'), [], 500, $e, $request);
        }
    });

    // POST /plugins/meeting-outlines/api/services/{id}/duplicate
    $group->post('/services/{id:[0-9]+}/duplicate', function (Request $request, Response $response, array $args) use ($plugin): Response {
        $service = $plugin->getService((int) $args['id']);
        if ($service === null) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => dgettext('meeting-outlines', 'Meeting not found.')], 404);
        }

        try {
            $newId = $plugin->duplicateService((int) $args['id']);

            return SlimUtils::renderJSON($response, [
                'success' => true,
                'message' => dgettext('meeting-outlines', 'Meeting duplicated.'),
                'id'      => $newId,
            ]);
        } catch (\Throwable $e) {
            return SlimUtils::renderErrorJSON($response, dgettext('meeting-outlines', 'Failed to duplicate meeting.'), [], 500, $e, $request);
        }
    });

    // DELETE /plugins/meeting-outlines/api/services/{id}
    $group->delete('/services/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($plugin): Response {
        $service = $plugin->getService((int) $args['id']);
        if ($service === null) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => dgettext('meeting-outlines', 'Meeting not found.')], 404);
        }

        try {
            $plugin->deleteService((int) $args['id']);

            return SlimUtils::renderJSON($response, [
                'success' => true,
                'message' => dgettext('meeting-outlines', 'Meeting deleted.'),
            ]);
        } catch (\Throwable $e) {
            return SlimUtils::renderErrorJSON($response, dgettext('meeting-outlines', 'Failed to delete meeting.'), [], 500, $e, $request);
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
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => dgettext('meeting-outlines', 'Meeting not found.')], 404);
        }

        $data   = $request->getParsedBody();
        $errors = [];

        if (empty(trim($data['title'] ?? ''))) {
            $errors[] = dgettext('meeting-outlines', 'Item title is required.');
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
                'message' => dgettext('meeting-outlines', 'Item added.'),
                'item'    => $item,
            ]);
        } catch (\Throwable $e) {
            return SlimUtils::renderErrorJSON($response, dgettext('meeting-outlines', 'Failed to add item.'), [], 500, $e, $request);
        }
    });

    // PUT /plugins/meeting-outlines/api/items/{id}
    $group->put('/items/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($plugin): Response {
        $item = $plugin->getItem((int) $args['id']);
        if ($item === null) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => dgettext('meeting-outlines', 'Item not found.')], 404);
        }

        $data   = $request->getParsedBody();
        $errors = [];

        if (empty(trim($data['title'] ?? ''))) {
            $errors[] = dgettext('meeting-outlines', 'Item title is required.');
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
                'message' => dgettext('meeting-outlines', 'Item updated.'),
                'item'    => $plugin->getItem((int) $args['id']),
            ]);
        } catch (\Throwable $e) {
            return SlimUtils::renderErrorJSON($response, dgettext('meeting-outlines', 'Failed to update item.'), [], 500, $e, $request);
        }
    });

    // DELETE /plugins/meeting-outlines/api/items/{id}
    $group->delete('/items/{id:[0-9]+}', function (Request $request, Response $response, array $args) use ($plugin): Response {
        $item = $plugin->getItem((int) $args['id']);
        if ($item === null) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => dgettext('meeting-outlines', 'Item not found.')], 404);
        }

        try {
            $plugin->deleteItem((int) $args['id']);

            return SlimUtils::renderJSON($response, [
                'success' => true,
                'message' => dgettext('meeting-outlines', 'Item deleted.'),
            ]);
        } catch (\Throwable $e) {
            return SlimUtils::renderErrorJSON($response, dgettext('meeting-outlines', 'Failed to delete item.'), [], 500, $e, $request);
        }
    });

    // POST /plugins/meeting-outlines/api/bible-texts/{version}/{book_nr} — télécharge un livre biblique
    $group->post('/bible-texts/{version}/{book_nr:[0-9]+}', function (Request $request, Response $response, array $args) use ($plugin): Response {
        $versionCode = strtoupper((string) $args['version']);
        $bookNr      = (int) $args['book_nr'];

        if ($bookNr < 1 || $bookNr > 66) {
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => 'Numéro de livre invalide.'], 400);
        }

        try {
            $result = $plugin->downloadBibleBook($versionCode, $bookNr);
            return SlimUtils::renderJSON($response, $result, $result['success'] ? 200 : 500);
        } catch (\Throwable $e) {
            return SlimUtils::renderErrorJSON($response, 'Échec du téléchargement.', [], 500, $e, $request);
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
            return SlimUtils::renderJSON($response, ['success' => false, 'message' => dgettext('meeting-outlines', 'Invalid data.')], 400);
        }

        $ok = $plugin->reorderItems($serviceId, $ids);

        return SlimUtils::renderJSON($response, ['success' => $ok]);
    });

})->add(AdminRoleAuthMiddleware::class);
