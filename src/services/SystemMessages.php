<?php

namespace firstborn\migrationmanager\services;

use Craft;
use craft\db\Query;
use craft\models\SystemMessage;

class SystemMessages extends BaseMigration
{
    protected $source = 'settingsSystemMessages';
    protected $destination = 'systemMessages';

    /**
     * @param array $ids
     * @param bool $fullExport
     * @return array
     */
    public function export(Array $ids, $fullExport = true)
    {
        //ignore incoming ids are grab all messages
        $messages = array();
        $systemMessages = $this->getSystemMessages();

        foreach($systemMessages as $systemMessage)
        {
            $this->addManifest($systemMessage['key']);
            $messages[] = $this->exportItem($systemMessage);
        }

        $settings = Craft::$app->systemSettings->getSettings('email');

        return array(
            'settings' => $settings,
            'messages' => $messages);
    }

    /**
     * @param int $data
     * @param bool $fullExport
     * @return int
     */
    public function exportItem($data, $fullExport = true)
    {
        return $data;
    }

    public function import(Array $data)
    {
        foreach($data['messages'] as $message)
        {
            $this->importItem($message);
        }

        if (Craft::$app->systemSettings->saveSettings('email', $data['settings']))
        {
        } else {
            $this->addError('error', 'Could not save system message settings');
        }
    }

    /**
     * @param array $data
     */
    public function importItem(Array $data)
    {
        $msg = $this->createModel($data);
        if (Craft::$app->systemMessages->saveMessage($msg, $data['language']))
        {

        }
        else
        {
            $this->addError('error', Craft::t('There was a problem saving a system message.'));
        }
    }

    /**
     * @param array $data
     * @return SystemMessage
     */
    public function createModel(Array $data)
    {
        $message = new SystemMessage();
        $message->key = $data['key'];
        $message->subject = $data['subject'];
        $message->body = $data['body'];


        return $message;
    }

    /**
     * @return array
     */
    private function getSystemMessages(): array
    {
        $results = (new Query())
            ->select(['language', 'key', 'subject', 'body'])
            ->from(['{{%systemmessages}}'])
            ->all();

        return $results;


    }





}