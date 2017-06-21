

## Create a field

## Update a field

## Add field to section

## Remove field from section

## Make field required

## Create a section




    /*
    $field = new FieldModel();
    $field->groupId      = $group->id;
    $field->name         = 'Plain Text';
    $field->handle       = 'plainText';
    $field->translatable = true;
    $field->type         = 'PlainText';
    $field->settings = array(
        'multiline' => true,
        'initialRows' => 4,
        'maxLength' => 200,
        'placeholder' => 'hello world'
    );
    */

    /*
    $field = new FieldModel();
    $field->groupId      = $group->id;
    $field->name         = 'Light Switch';
    $field->handle       = 'lightSwitch';
    $field->translatable = true;
    $field->type         = 'Lightswitch';
    $field->settings = array(
        'default' => true
    );
    */


    /*

    $field = new FieldModel();
    $field->id = 10;
    $field->groupId      = $group->id;
    $field->name         = 'Matrix';
    $field->handle       = 'matrix';
    $field->instructions = null;
    $field->translatable = true;
    $field->type         = 'Matrix';
    $field->settings = array(
        'blockTypes' => array(
            'new1' => array(
                'name' => 'Block Type 1',
                'handle' => 'blockType1',
                'fields' => array(
                    'new1' => array(
                        'name' => 'My Sub-Field 1',
                        'handle' => 'mySubField1',
                        'required' => true,
                        'translatable' => false,
                        'instructions' => null,
                        'type' => 'PlainText',
                        'typesettings' => array(
                            'multiline' => false,
                        )
                    ),
                    'new2' => array(
                        'name' => 'My Sub-Field 2',
                        'handle' => 'mySubField2',
                        'required' => false,
                        'translatable' => false,
                        'instructions' => null,
                        'type' => 'PlainText',
                        'typesettings' => array(
                            'multiline' => true,
                        )
                    ),
                    'new3' => array(
                        'name' => 'My Sub-Field 3',
                        'handle' => 'mySubField3',
                        'required' => false,
                        'translatable' => false,
                        'instructions' => null,
                        'type' => 'PlainText',
                        'typesettings' => array(
                            'multiline' => true,
                        )
                    ),
                )
            ),
            'new2' => array(
                'name' => 'Block Type 2',
                'handle' => 'blockType2',
                'fields' => array(
                    'new1' => array(
                        'name' => 'My Sub-Field 1a',
                        'handle' => 'mySubField1',
                        'required' => true,
                        'translatable' => false,
                        'instructions' => null,
                        'type' => 'Lightswitch',
                        'typesettings' => array(
                            'default' => true
                        )
                    ),
                    'new2' => array(
                        'name' => 'My Sub-Field 2a',
                        'handle' => 'mySubField2',
                        'required' => false,
                        'translatable' => false,
                        'instructions' => null,
                        'type' => 'PlainText',
                        'typesettings' => array(
                            'multiline' => true,
                        )
                    ),
                    'new3' => array(
                        'name' => 'My Sub-Field 3',
                        'handle' => 'mySubField3',
                        'required' => false,
                        'translatable' => false,
                        'instructions' => null,
                        'type' => 'PlainText',
                        'typesettings' => array(
                            'multiline' => true,
                        )
                    ),
                )
            )
        )
    );
    */


    /*
    $field = new FieldModel();
    $field->groupId      = $group->id;
    $field->name         = 'Asset';
    $field->handle       = 'asset';
    $field->translatable = true;
    $field->type         = 'Asset';
    $field->allowLimit = 2;
    $field->settings = array(
        'useSingleFolder'              => AttributeType::Bool,
        'defaultUploadLocationSource'  => AttributeType::Number,
        'defaultUploadLocationSubpath' => AttributeType::String,
        'singleUploadLocationSource'   => AttributeType::Number,
        'singleUploadLocationSubpath'  => AttributeType::String,
        'restrictFiles'                => AttributeType::Bool,
        'allowedKinds'                 => AttributeType::Mixed,
    }
    */

Icon Credits 
Flying Duck by Agne Alesiute from the Noun Project