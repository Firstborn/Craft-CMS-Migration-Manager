<?php

namespace Craft;

/**
 * Class MigrationManager_RoutesService
 */
class MigrationManager_RoutesService extends MigrationManager_BaseMigrationService
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
        // ignore incoming ids are grab all routes ids
        $ids = $this->getAllRouteIds();

        $items = array();
        foreach ($ids as $id) {

            $obj = $this->exportItem($id, $fullExport = false);
            if ($obj) {
                $items[] = $obj;
            }
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function exportItem($id, $fullExport = false)
    {
        $route = $this->getRouteById($id);

        $this->addManifest($id);

        if (!$route) {
            return false;
        }

        $newRoute = [
            'urlParts' => urlencode($route['urlParts']),
            'urlPattern' => '',
            'template' => $route['template'],
            'locale' => $route['locale'] !== null ? $route['locale'] : '',
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
        $data['urlParts'] = urldecode($data['urlParts']);
        $urlParts = json_decode($data['urlParts'], true);

        if ($data['locale'] === '') {
            $locale = null;
        } else {
            $locale = $data['locale'];
        }

        $result = craft()->routes->saveRoute($urlParts, $data['template'], null, $locale);

        return $result;
    }

    /**
     * Deletes all routes
     */
    private function deleteAllRoutes()
    {
        $ids = $this->getAllRouteIds();
        foreach ($ids as $id) {
            craft()->routes->deleteRouteById($id);
        }
    }

    /**
     * @return array
     */
    private function getAllRouteIds()
    {
        $routes = craft()->db->createCommand()
            ->select('id')
            ->from('routes')
            ->order('sortOrder')
            ->queryAll();

        $ids = array();
        foreach ($routes as $route) {
            $ids[] = $route['id'];
        }

        return $ids;
    }

    /**
     * @param int $id
     *
     * @return \CDbDataReader|mixed
     */
    private function getRouteById($id)
    {
        $route = craft()->db->createCommand()
            ->select('id, locale, urlParts, urlPattern, template')
            ->from('routes')
            ->where('id = :id', array(':id' => $id))
            ->queryRow();

        return $route;
    }
}
