<?php

namespace Nwogu\SmoothMigration\Helpers;

class Constants
{

    const SMOOTH_SCHEMA_FILE = "Schema";

    const SMOOTH_SCHEMA_FOLDER = "schema";

    const SMOOTH_SERIALIZER_FOLDER = "serializers";

    //Actions

    const TABLE_RENAME_UP_ACTION = "tableRenameUp";

    const TABLE_RENAME_DOWN_ACTION = "tableRenameDown";

    const COLUMN_RENAME_UP_ACTION = "columnRenameUp";

    const COLUMN_RENAME_DOWN_ACTION = "columnRenameDown";

    const DEF_CHANGE_UP_ACTION = "defChangeUp";

    const DEF_CHANGE_DOWN_ACTION = "defChangeDown";

    const COLUMN_DROP_UP_ACTION = "columnDropUp";

    const COLUMN_DROP_DOWN_ACTION = "columnDropDown";

    const COLUMN_ADD_UP_ACTION = "columnAddUp";

    const COLUMN_ADD_DOWN_ACTION = "columnAddDown";

    const FOREIGN_DROP_UP_ACTION = "dropForeignUp";

    const FOREIGN_DROP_DOWN_ACTION = "dropForeignDown";

    const FOREIGN_ADD_UP_ACTION = "addForeignUp";

    const FOREIGN_ADD_DOWN_ACTION = "addForeignDown";

    const DROP_PRIMARY_UP_ACTION = "dropPrimaryUp";

    const DROP_PRIMARY_DOWN_ACTION = "dropPrimaryDown";

    const DROP_UNIQUE_UP_ACTION = "dropUniqueUp";

    const DROP_UNIQUE_DOWN_ACTION = "dropUniqueDown";

    const DROP_INDEX_UP_ACTION = "dropIndexUp";

    const DROP_INDEX_DOWN_ACTION = "dropIndexDown";

    const DROP_MORPH_UP_ACTION = "dropMorphUp";

    const DROP_MORPH_DOWN_ACTION = "dropMorphDown";

    //End Actions

    const TABLE_CHANGE_LOG = "table";

    const SCHEMA_CHANGE_LOG = "schema";

    const SCHEMA_CREATE_ACTION = "create";

    const SCHEMA_UPDATE_ACTION = "update";

    const FOREIGN_VALUES = [
        "foreign", "references", "on"
    ];

    const SOFT_DELETE = "softDeletes";

    const MORPHS = "morphs";

    const REMEMBER_TOKEN = "remeberToken";

    const TIMESTAMP = "timestamps";

    const ALPHABETS = [
        "A", "B", "C", "D", "E", "F", "G", "H", "I", "J",
        "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T",
        "U", "V", "W", "X", "Y", "Z"
    ];

}