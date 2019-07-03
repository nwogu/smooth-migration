<?php

namespace Nwogu\SmoothMigration\Helpers;

const SMOOTH_SCHEMA_FILE = "Schema";

const SMOOTH_SCHEMA_FOLDER = "schema";

const SMOOTH_SERIALIZER_FOLDER = "serializers";

const SCHEMA_DEFAULTS = [
    "table", "runFirst", "autoIncrement"
];

const TABLE_RENAME_ACTION = "tableRename";

const COLUMN_RENAME_ACTION = "columnRename";

const DEF_CHANGE_ACTION = "defChange";

const COLUMN_DROP_ACTION = "columnDrop";

const COLUMN_ADD_ACTION = "columnAdd";

const FOREIGN_DROP_ACTION = "dropForeign";

const TABLE_CHANGE_LOG = "table";

const SCHEMA_CHANGE_LOG = "schema";

const SCHEMA_CREATE_ACTION = "create";

const SCHEMA_UPDATE_ACTION = "update";

const FOREIGN_VALUES = [
    "foreign", "references", "on"
];