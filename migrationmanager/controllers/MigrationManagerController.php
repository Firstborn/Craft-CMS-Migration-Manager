<?php

namespace Craft;

/**
 * Class MigrationManagerController
 */
class MigrationManagerController extends BaseController
{
    /**
     * Index redirects to creation page
     */
    public function actionIndex()
    {
        $pendingMigrations = count(craft()->migrationManager_migrations->getNewMigrations());
        if ($pendingMigrations > 0){
            $this->redirect('migrationmanager/pending');
        } else {
            $this->redirect('migrationmanager/create');
        }
    }

    /**
     * The export creation page controller
     *
     * @throws HttpException
     */
    public function actionCreate()
    {
        $this->renderTemplate('migrationManager/create');
    }

    /**
     * @throws HttpException
     */
    public function actionCreateMigration()
    {
        // Prevent GET Requests
        $this->requirePostRequest();
        $post = craft()->request->getPost();

        if (craft()->migrationManager_migrations->createSettingMigration($post)) {
            craft()->userSession->setNotice(Craft::t('Migration created.'));
        } else {
            craft()->userSession->setError(Craft::t('Could not create migration, check log tab for errors.'));
        }

        $this->redirectToPostedUrl();
    }

    /**
     * Shows pending migrations
     *
     * @throws HttpException
     */
    public function actionPending()
    {
        $this->renderTemplate('migrationManager/pending');
    }

    /**
     * Shows applied migrations
     *
     * @throws HttpException
     */
    public function actionApplied()
    {
        $this->renderTemplate('migrationManager/applied');
    }

    /**
     * @throws HttpException
     */
    public function actionLogs()
    {
        $path = craft()->path->getLogPath().'migrationmanager.log';
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
        craft()->userSession->requireAdmin();

        $globalSet = new GlobalSetModel();

        // Set the simple stuff
        $globalSet->id = craft()->request->getPost('setId');
        $globalSet->name = craft()->request->getPost('name');
        $globalSet->handle = craft()->request->getPost('handle');

        // Set the field layout
        $fieldLayout = craft()->fields->assembleLayoutFromPost();
        $fieldLayout->type = ElementType::GlobalSet;
        $globalSet->setFieldLayout($fieldLayout);

        $params['global'] = array(craft()->request->getPost('setId'));

        if (craft()->migrationManager_migrations->createContentMigration($params)) {
            craft()->userSession->setNotice(Craft::t('Migration created.'));
        } else {
            craft()->userSession->setError(Craft::t('Could not create migration, check log tab for errors.'));
        }

        $this->redirectToPostedUrl($globalSet);
    }
}
