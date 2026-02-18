<?php

/**
 * Migration Generator Script
 * This script reads the MySQL dump and generates Laravel migrations
 */

$sqlFile = '../accountify_db.sql';
$migrationsDir = 'database/migrations';

if (!file_exists($sqlFile)) {
    die("SQL file not found: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

// Extract all CREATE TABLE statements
preg_match_all('/CREATE TABLE `([^`]+)` \((.*?)\) ENGINE=/s', $sql, $matches, PREG_SET_ORDER);

$timestamp = '2024_01_01_';
$counter = 300000; // Start from a high number to avoid conflicts

foreach ($matches as $match) {
    $tableName = $match[1];
    $tableDefinition = $match[2];

    // Skip migrations table
    if ($tableName === 'migrations') {
        continue;
    }

    $counter++;
    $migrationName = $timestamp . $counter . '_create_' . $tableName . '_table.php';
    $migrationPath = $migrationsDir . '/' . $migrationName;

    // Skip if already exists
    if (file_exists($migrationPath)) {
        echo "Skipping existing: $tableName\n";
        continue;
    }

    $className = 'Create' . str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName))) . 'Table';

    // Parse columns
    $columns = parseColumns($tableDefinition);

    // Generate migration content
    $migration = generateMigration($className, $tableName, $columns);

    file_put_contents($migrationPath, $migration);
    echo "Created: $migrationName\n";
}

function parseColumns($tableDefinition) {
    $lines = explode("\n", $tableDefinition);
    $columns = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, 'PRIMARY KEY') !== false || strpos($line, 'KEY ') !== false ||
            strpos($line, 'UNIQUE KEY') !== false || strpos($line, 'CONSTRAINT') !== false) {
            continue;
        }

        // Remove trailing comma
        $line = rtrim($line, ',');

        if (preg_match('/^`([^`]+)`\s+(.+)$/', $line, $colMatch)) {
            $colName = $colMatch[1];
            $colDef = $colMatch[2];

            $columns[] = [
                'name' => $colName,
                'definition' => $colDef
            ];
        }
    }

    return $columns;
}

function generateMigration($className, $tableName, $columns) {
    $columnsCode = '';

    foreach ($columns as $col) {
        $name = $col['name'];
        $def = $col['definition'];

        // Skip id, created_at, updated_at as they're handled by Laravel
        if ($name === 'id') {
            continue;
        }
        if ($name === 'created_at' || $name === 'updated_at') {
            continue;
        }

        $laravelColumn = convertToLaravelColumn($name, $def);
        if ($laravelColumn) {
            $columnsCode .= "            $laravelColumn\n";
        }
    }

    return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('$tableName', function (Blueprint \$table) {
            \$table->id();
$columnsCode            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('$tableName');
    }
};

PHP;
}

function convertToLaravelColumn($name, $definition) {
    $def = strtolower($definition);
    $nullable = strpos($def, 'null') !== false && strpos($def, 'not null') === false;
    $default = null;

    // Extract default value
    if (preg_match('/default\s+([^\s,]+)/i', $definition, $defaultMatch)) {
        $default = trim($defaultMatch[1], "'\"");
        if ($default === 'NULL') {
            $default = null;
        } elseif ($default === 'current_timestamp()') {
            $default = null; // Handle separately
        }
    }

    $column = '';

    // Determine column type
    if (strpos($def, 'bigint') !== false) {
        if (strpos($def, 'unsigned') !== false) {
            $column = "\$table->unsignedBigInteger('$name')";
        } else {
            $column = "\$table->bigInteger('$name')";
        }
    } elseif (strpos($def, 'int') !== false) {
        if (strpos($def, 'unsigned') !== false) {
            $column = "\$table->unsignedInteger('$name')";
        } else {
            $column = "\$table->integer('$name')";
        }
    } elseif (preg_match('/varchar\((\d+)\)/', $def, $match)) {
        $length = $match[1];
        $column = "\$table->string('$name', $length)";
    } elseif (preg_match('/decimal\((\d+),\s*(\d+)\)/', $def, $match)) {
        $total = $match[1];
        $places = $match[2];
        $column = "\$table->decimal('$name', $total, $places)";
    } elseif (strpos($def, 'text') !== false || strpos($def, 'longtext') !== false) {
        $column = "\$table->text('$name')";
    } elseif (strpos($def, 'date') !== false && strpos($def, 'datetime') === false) {
        $column = "\$table->date('$name')";
    } elseif (strpos($def, 'datetime') !== false) {
        $column = "\$table->dateTime('$name')";
    } elseif (strpos($def, 'timestamp') !== false) {
        $column = "\$table->timestamp('$name')";
    } else {
        $column = "\$table->string('$name')";
    }

    // Add modifiers
    if ($nullable) {
        $column .= '->nullable()';
    }

    if ($default !== null && $default !== '') {
        if (is_numeric($default)) {
            $column .= "->default($default)";
        } else {
            $column .= "->default('$default')";
        }
    }

    $column .= ';';

    return $column;
}

echo "\nMigration generation complete!\n";
