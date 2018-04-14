<?php

namespace firstborn\migrationmanager\events;

use craft\events\CancelableEvent;
use yii\base\Component;

/**
 * Migration ImportEvent class.
 */
class ImportEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var Model|null The element to be imported
     */
    public $element;

    /**
     * @var Array The data used to create the element model
     */
    public $value;

    /**
     * @var Component|null The parent element associated with the element.
     */
    public $parent;

    /**
     * @var String|null the reason the event was cancelled
     */
    public $error;


}
