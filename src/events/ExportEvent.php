<?php

namespace firstborn\migrationmanager\events;

use craft\base\Element;
use yii\base\Event;
use yii\base\Component;

/**
 * Migration ExportEvent class.
 *
 */
class ExportEvent extends Event
{

    // Properties
    // =========================================================================

    /**
     * @var The element model associated with the event.
     */
    public $element;

    /**
     * @var The new value/settings associated with the element.
     */
    public $value;

    /**
     * @var Component|null The parent model associated with the element.
     */
    public $parent;


}
