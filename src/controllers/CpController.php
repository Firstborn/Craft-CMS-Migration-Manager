<?php

namespace firstborn\migrationmanager\controllers;

use Craft;
use craft\web\Controller;


/**
 * Class MigrationManagerController
 */
class CpController extends Controller
{

    /**
     * Index redirects to creation page
     */


    public function actionIndex()
    {
        return $this->renderTemplate('migrationmanager/index');
    }



    /**
     * Shows migrations
     *
     * @throws HttpException
     */
    public function actionMigrations()
    {
        $migrator = Craft::$app->getContentMigrator();
        $pending = $migrator->getNewMigrations();
        $applied = $migrator->getMigrationHistory();
        return $this->renderTemplate('migrationmanager/migrations', array('pending' => $pending, 'applied' => $applied));
    }



    /**
     * @throws HttpException
     */
    public function actionLogs()
    {
        $path = Craft::$app->path->getLogPath().'migrationmanager.log';
        $contents = IOHelper::getFileContents($path);
        $requests = explode('******************************************************************************************************', $contents);
        $logEntries = array();

        foreach ($requests as $request) {
            $logChunks = preg_split('/^(\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}) \[(.*?)\] \[(.*?)\] /m', $request, null, PREG_SPLIT_DELIM_CAPTURE);

            // Ignore the first chunk
            array_shift($logChunks);

            // Loop through them
            $totalChunks = count($logChunks);
            for ($i = 0; $i < $totalChunks; $i += 4) {
                $logEntryModel = new LogEntryModel();

                $logEntryModel->dateTime = DateTime::createFromFormat('Y/m/d H:i:s', $logChunks[$i]);
                $logEntryModel->level = $logChunks[$i + 1];
                $logEntryModel->category = $logChunks[$i + 2];

                $message = $logChunks[$i + 3];
                $message = str_replace('[Forced]', '', $message);
                $rowContents = explode("\n", $message);

                $logEntryModel->message = $rowContents[0];

                array_unshift($logEntries, $logEntryModel);
            }
        }

        $this->renderTemplate('migrationmanager/log', array(
            'logEntries' => $logEntries,
        ));
    }

    /**
     * @throws HttpException
     */
    public function actionCreateGlobalsContentMigration()
    {
        $this->requirePostRequest();
        Craft::$app->userSession->requireAdmin();

        $globalSet = new GlobalSetModel();

        // Set the simple stuff
        $globalSet->id = Craft::$app->request->getPost('setId');
        $globalSet->name = Craft::$app->request->getPost('name');
        $globalSet->handle = Craft::$app->request->getPost('handle');

        // Set the field layout
        $fieldLayout = Craft::$app->fields->assembleLayoutFromPost();
        $fieldLayout->type = ElementType::GlobalSet;
        $globalSet->setFieldLayout($fieldLayout);

        $params['global'] = array(Craft::$app->request->getPost('setId'));

        if (Craft::$app->migrationManager_migrations->createContentMigration($params)) {
            Craft::$app->userSession->setNotice(Craft::t('Migration created.'));
        } else {
            Craft::$app->userSession->setError(Craft::t('Could not create migration, check log tab for errors.'));
        }

        $this->redirectToPostedUrl($globalSet);
    }
}
