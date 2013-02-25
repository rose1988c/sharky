<?php

function table2model($table_name, $fields) {
    $schema = array();
    
    $sharding_clue  = null;
    $isolate_column = null;

    foreach ($fields as $field) {
        $name    = $field['Field'];
        $type    = model_type($field);
        $pri     = $field['Key'] == 'PRI';
        $auto    = $field['Extra'] == 'auto_increment';
        $null    = $field['Null'] == 'YES';
        $default = model_default($type, $field['Default']);
        $global_auto = false;

        try {
            $meta = json_decode($field['Comment'], true);
            if (!is_null($meta) && is_array($meta)) {
                if (isset($meta['type']))
                    $type = $meta['type'];
                if (isset($meta['sharding_clue'])) {
                    $sharding_clue = $name;
                    $pri = true;
                }
                if (isset($meta['isolate_column']))
                    $isolate_column = $name;
                if (isset($meta['global_auto_increment']))
                    $global_auto = true;
            }
        } catch (Exception $e) {}

        $d = array();
        $d['type'] = $type;
        if ($pri)
            $d['primary'] = true;
        if ($auto)
            $d['auto_increment'] = true;
        if ($global_auto)
            $d['global_auto_increment'] = true;
        if ($null)
            $d['null'] = true;
        if ($default)
            $d['default'] = $default;

        $schema[$name] = $d;
    }

    return array('schema' => $schema, 
        'sharding_clue'  => $sharding_clue, 
        'isolate_column' => $isolate_column);
}

function model_default($type, $value) {
    if ($value == 'NULL') return false;

    switch ($type) {
        case 'int': return intval($value);
        case 'float': return floatval($value);
        case 'decimal':
        case 'double': return doubleval($value);
        default:
            return $value;
    }
}

function model_type($field) {
    $mysql_type = $field['Type'];
    @list($t, $l) = explode('(', strtolower($mysql_type), 2);
	$l = intval($l);
    switch ($t) {
        case 'int':
        case 'tinyint':
        case 'smallint':
        case 'mediumint':
        case 'bigint':
			if ($l < 11)
				return 'integer';
			else
				return 'long';
        case 'float':
            return 'float';
        case 'double':
            return 'double';
        case 'decimal':
            return 'decimal';
        case 'varchar':
        case 'char':
        case 'text':
        case 'enum':
            return 'string';
        case 'date':
            return 'date';
        case 'datetime':
        case 'timestamp':
            return 'datetime';
        default:
            return $t;
    }
}

function db2models($host, $port, $username, $password, $dbname, $prefix) {
    $db = new Database($host, $port, $dbname, $username, $password);

    $rows = $db->get_array('SHOW TABLES');

    foreach ($rows as $row) {
        $table_name = $row[0];
        $fields = $db->get_rows("SHOW FULL FIELDS FROM `{$row[0]}`");
        $model = table2model($table_name, $fields);

        $object_name = guess_object_name($table_name, $prefix);

        echo "<ul>\n";
        print_model($table_name, $object_name, $model);
        echo "\n</ul>\n";
    }
}

function guess_object_name($table_name, $prefix) {
    if ($prefix && strpos($table_name, $prefix) === 0) {
        $table_name = substr($table_name, strlen($prefix));
    }
    $table_name = ucwords(str_replace('_', ' ', $table_name));
    return str_replace(' ', '', $table_name);
}

function print_model($table_name, $object_name, $model) {/*{{{*/
    $sharding_clue  = $model['sharding_clue'];
    $isolate_column = $model['isolate_column'];
    $model = $model['schema'];

    $table_type = $sharding_clue ? 'ShardedDBTable' : 'DBTable';

    if ($sharding_clue)
        $sharding_clue = " '$sharding_clue',";
    if ($isolate_column)
        $isolate_column = ", '$isolate_column'";

    echo "<li><a href=\"#\" onclick=\"document.getElementById('def_$table_name').style.display = '';return false;\">$table_name</a>\n";
    echo "<div id=\"def_$table_name\" style=\"display:none;\"><textarea rows=\"15\" cols=\"100\">";
    echo "\$GLOBALS['$object_name'] = new $table_type('$object_name', '$table_name',$sharding_clue array(\n";

    $flen = 0;
    foreach ($model as $field_name => $field) {
        if (strlen($field_name) > $flen)
            $flen = strlen($field_name);
    }

    foreach ($model as $field_name => $field) {
        $fn = str_pad("'$field_name'", $flen + 2);
        echo "        $fn => array(";
        $keys  = array_keys($field);
        $count = count($keys);
        for ($i = 0; $i < $count; $i++) {
            $k = $keys[$i];
            $v = $field[$k];
            if (is_string($v))
                echo "'$k' => '$v'";
            else if (is_bool($v))
                echo "'$k' => " . ($v ? 'true' : 'false');
            else
                echo "'$k' => $v";
            if ($i < $count - 1)
                echo ", ";
        }
        echo "),\n";
    }

    echo "    )$isolate_column);";
    echo "</textarea></div></li>";
}/*}}}*/


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $host     = param_string($_POST, 'host', false, 'localhost');
    $port     = param_int   ($_POST, 'port', false, 3306);
    $db       = param_string($_POST, 'db', true);
    $username = param_string($_POST, 'username', false, 'root');
    $password = param_string($_POST, 'password', true);
    $prefix   = param_string($_POST, 'prefix', false, '');

    db2models($host, $port, $username, $password, $db, $prefix);
?>

<?php
} else {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>Db2models</title>
    <style type="text/css" media="screen">
        li { list-style: none; width: 500px; margin-bottom: 15px; }
        label { display: block; width: 100px; display: block; float: left; }
    </style>
</head>

<body>
    <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">                        
        <ul>
            <li><label>Host: </label><input type="text" name="host" value="localhost" /></li>
            <li><label>Port: </label><input type="text" name="port" value="3306" /></li>
            <li><label>DB: </label><input type="text" name="db" value="" /></li>
            <li><label>Username: </label><input type="text" name="username" value="root" /></li>
            <li><label>Password: </label><input type="text" name="password" value="" /></li>
            <li><label>Table Prefix: </label><input type="text" name="prefix" value="" />(for object name guessing)</li>
            <li><label>&nbsp;</label><input type="submit" name="submit" value="CREATE MODELS" /></li>
        </ul>
    </form>
</body>
</html>

<?php
}
?>
