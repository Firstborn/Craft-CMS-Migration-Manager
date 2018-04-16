<?php

namespace firstborn\migrationmanager\services;

use Craft;
use craft\db\Query;

class Routes extends BaseMigration
{
    /**
     * @var string
     */
    protected $source = 'settingsRoutes';

    /**
     * @var string
     */
    protected $destination = 'routes';

    /**
     * {@inheritdoc}
     */
    public function export(array $ids, $fullExport = true)
    {
        // ignore incoming ids and grab all routes ids
        $routes = $this->getDbRoutes();

        $items = array();
        foreach ($routes as $route) {
            $obj = $this->exportItem($route, $fullExport = false);
            if ($obj) {
                $items[] = $obj;
            }
        }

        $this->addManifest('all');

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function exportItem($route, $fullExport = false)
    {
        $newRoute = [
            'uriParts' => urlencode($route['uriParts']),
            'uriPattern' => $route['uriPattern'],
            'template' => $route['template'],
            'site' => $route['siteId'] !== null ? Craft::$app->sites->getSiteById($route['siteId'])->handle : '',
        ];

        return $newRoute;
    }

    /**
     * {@inheritdoc}
     */
    public function import(Array $data)
    {
        // we delete all routes first since there is no way to identify routes without the id column
        // import data is all routes from source
        $this->deleteAllRoutes();

        return parent::import($data);
    }

    /**
     * {@inheritdoc}
     */
    public function importItem(Array $data)
    {
        $data['uriParts'] = urldecode($data['uriParts']);
        $uriParts = json_decode($data['uriParts'], true);

        if ($data['site'] === '') {
            $siteId = null;
        } else {
            $siteId = Craft::$app->sites->getSiteByHandle($data['site'])->id;
        }

        $result = Craft::$app->routes->saveRoute($uriParts, $data['template'], $siteId);

        return $result;
    }

    /**
     * Deletes all routes
     */
    private function deleteAllRoutes()
    {
        $routes = $this->getDbRoutes();
        foreach ($routes as $route) {
            Craft::$app->routes->deleteRouteById($route['id']);
        }
    }

    /**
     * @param int $id
     *
     * @return \CDbDataReader|mixed
     */

    private function getDbRoutes(): array
    {

        $results = (new Query())
            ->select(['id', 'siteId', 'uriParts', 'uriPattern', 'template', 'sortOrder'])
            ->from(['{{%routes}}'])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();

        return $results;

    }
}
