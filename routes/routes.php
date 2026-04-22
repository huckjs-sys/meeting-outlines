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

// Vue impression
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

        $allowedTypes    = array_keys(MeetingOutlinesPlugin::getServiceTypes());
        $allowedStatuses = array_keys(MeetingOutlinesPlugin::getStatusLabels());

        if (empty(trim($data['date'] ?? ''))) {
            $errors[] = gettext('Meeting date is required.');
        }
        if (empty(trim($data['title'] ?? ''))) {
            $errors[] = gettext('Title is required.');
        }
        if (!in_array($data['type'] ?? 'sunday', $allowedTypes, true)) {
            $errors[] = gettext('Invalid meeting type.');
        }
        if (!in_array($data['status'] ?? 'draft', $allowedStatuses, true)) {
            $errors[] = gettext('Invalid status.');
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

        $allowedTypes    = array_keys(MeetingOutlinesPlugin::getServiceTypes());
        $allowedStatuses = array_keys(MeetingOutlinesPlugin::getStatusLabels());

        if (empty(trim($data['date'] ?? ''))) {
            $errors[] = gettext('Meeting date is required.');
        }
        if (empty(trim($data['title'] ?? ''))) {
            $errors[] = gettext('Title is required.');
        }
        if (!in_array($data['type'] ?? 'sunday', $allowedTypes, true)) {
            $errors[] = gettext('Invalid meeting type.');
        }
        if (!in_array($data['status'] ?? 'draft', $allowedStatuses, true)) {
            $errors[] = gettext('Invalid status.');
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

        $allowedItemTypes = array_keys(MeetingOutlinesPlugin::getItemTypes());

        if (empty(trim($data['title'] ?? ''))) {
            $errors[] = gettext('Item title is required.');
        }
        if (!in_array($data['item_type'] ?? 'other', $allowedItemTypes, true)) {
            $errors[] = gettext('Invalid item type.');
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

        $allowedItemTypes = array_keys(MeetingOutlinesPlugin::getItemTypes());

        if (empty(trim($data['title'] ?? ''))) {
            $errors[] = gettext('Item title is required.');
        }
        if (!in_array($data['item_type'] ?? 'other', $allowedItemTypes, true)) {
            $errors[] = gettext('Invalid item type.');
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
