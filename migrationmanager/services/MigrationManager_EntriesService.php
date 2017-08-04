<?php
namespace Craft;

class MigrationManager_EntriesService extends MigrationManager_BaseMigrationService
{

    protected $source = 'entry';
    protected $destination = 'entries';

    public function exportItem($id, $fullExport){
        $entry = craft()->entries->getEntryById($id, 'en_us');
        $content = [];

        foreach ($entry->getFieldLayout()->getFields() as $fieldModel) {
            $this->getElementContent($content, $fieldModel, $entry);
        }

        return $content;
    }


    public function importItem(Array $data)
    {
        return true;
    }

    public function createModel(Array $data)
    {
        return false;
    }

    private function getElementContent(&$content, $fieldModel, $parent)
    {
        $field = $fieldModel->getField();
        $value = $parent->getFieldValue($field->handle);

        // Fire an 'onExportField' event
        $event = new Event($this, array(
            'field' => $field,
            'parent' => $parent,
            'value' => $value
        ));

        echo 'before event';

        $this->onExportField($event);

        echo 'after event: ' .$event->params['value'] . PHP_EOL;

        if ($event->performAction == false) {
            $value = $event->params['value'];
        } else {
            switch ($field->type) {
                case 'RichText':
                    $value = $value->getRawContent();
                    break;
                case 'Matrix':
                    $model = $parent[$field->handle];
                    $value = $this->getIteratorValues($model, function ($item) {
                        $itemType = $item->getType();
                        $value = [
                            'type' => $itemType->handle,
                            'enabled' => $item->enabled,
                            'fields' => []
                        ];

                        return $value;
                    });
                    break;
                case 'SuperTable':
                    $model = $defaultParent[$field->handle];
                    $value = $this->getIteratorValues($model, function () {
                        $value = [
                            'type' => 1,
                            'fields' => []
                        ];

                        return $value;
                    });

                    break;
                default:
                    if ($field->getFieldType() instanceof BaseElementFieldType) {
                        $elements = $value->elements();

                        $value = [];
                        if ($elements) {
                            foreach ($elements as $element) {
                                switch ($element->getElementType()) {
                                    case 'Asset':
                                        $value[] = [
                                            'slug' => $element->slug,
                                            'folder' => $element->getFolder()->name,
                                            'source' => $element->getSource()->name
                                        ];
                                        break;
                                    case 'Category':

                                        $value[] = [
                                            'slug' => $element->slug,
                                            'category' => $element->getGroup()->handle
                                        ];
                                        break;
                                    default:
                                        $value[] = $element;
                                }
                            }
                        }
                    }


                    break;
            }
        }

        //echo 'field: ' . $field->handle . ' value: ' . json_encode($value) . PHP_EOL;

        $content[$field->handle] = $value;
        //return $value;
    }

    private function getIteratorValues($element, $settingsFunc)
    {
        $items = $element->getIterator();
        $value = [];

        $i = 1;
        foreach ($items as $item) {
            $itemType = $item->getType();
            $itemFields = $itemType->getFieldLayout()->getFields();
            $itemValue = $settingsFunc($item);

            $fields = [];
            foreach ($itemFields as $field) {
                $this->getElementContent($fields, $field, $item);
            }

            $itemValue['fields'] = $fields;
            $value['new' . $i] = $itemValue;
            $i++;
        }


        return $value;
    }


    /**
     * Fires an 'onExportField' event. Event handlers can prevent the default field handling by setting $event->performAction to false.
     *
     * @param Event $event
     *          $event->params['field'] - field
     *          $event->params['parent'] - field parent
     *          $event->params['value'] - current field value, change this value in the event handler to output a different value
     *
     * @return null
     */
    public function onExportField(Event $event)
    {
         $this->raiseEvent('onExportField', $event);
    }
}