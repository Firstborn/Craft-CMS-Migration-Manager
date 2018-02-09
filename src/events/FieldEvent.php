<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace firstborn\migrationmanager\events;

use craft\base\Field;
use craft\events\CancelableEvent;
use yii\base\Component;

/**
 * Asset event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FieldEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var Field|null The field model associated with the event.
     */
    public $field;

    /**
     * @var The value/settings associated with the field.
     */
    public $value;

    /**
     * @var Component|null The parent model associated with the field.
     */
    public $parent;


}
