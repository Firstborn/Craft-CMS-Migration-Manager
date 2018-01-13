<?php

namespace Craft;

class MigrationManager_EmailMessagesService extends MigrationManager_BaseMigrationService
{
    protected $source = 'settingsEmailMessages';
    protected $destination = 'emailMessages';

    public function export(Array $ids, $fullExport = true)
    {
        //ignore incoming ids are grab all messages
        $messages = array();
        $locales = craft()->i18n->getSiteLocaleIds();

        foreach($locales as $locale)
        {
            $localeMessages = craft()->emailMessages->getAllMessages($locale);
            foreach ($localeMessages as $message)
            {
                $this->addManifest($message->key);

                $m = craft()->emailMessages->getMessage($message->key, $locale);
                $messages[] = $this->exportItem($m);
            }
        }

        $settings = craft()->systemSettings->getSettings('email');

        return array(
            'settings' => $settings,
            'messages' => $messages);

    }

    public function exportItem($data, $fullExport = true)
    {
        $message['key'] = $data->key;
        $message['locale'] = $data->locale;
        $message['subject'] = $data->subject;
        $message['body'] = $data->body;
        return $message;
    }

    public function import(Array $data)
    {
        foreach($data['messages'] as $message)
        {
            $this->importItem($message);
        }

        if (craft()->systemSettings->saveSettings('email', $data['settings']))
        {
        } else {
            $this->addError('Could not save email settings');
        }

    }

    public function importItem(Array $data)
    {
        $msg = $this->createModel($data);
        if (craft()->emailMessages->saveMessage($msg))
        {

        }
        else
        {
            $this->addError(Craft::t('There was a problem saving an email message.'));
        }
    }

    public function createModel(Array $data)
    {
        $message = new RebrandEmailModel();
        $message->key = $data['key'];
        $message->subject = $data['subject'];
        $message->body = $data['body'];
        $message->locale = $data['locale'];

        return $message;
    }





}