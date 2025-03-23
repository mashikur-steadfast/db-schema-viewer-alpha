<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class DBSchemaController extends Controller
{
    public function show()
    {
        // Get all tables in the database
        $tables = DB::select('SHOW TABLES');

        // Prepare schema data
        $schema = [];
        foreach ($tables as $table) {
            $tableName = reset($table); // Get the table name
            $columns = Schema::getColumnListing($tableName); // Get columns
            $columnDetails = [];

            // Get column types and other details
            foreach ($columns as $column) {
                $columnType = DB::getSchemaBuilder()->getColumnType($tableName, $column);
                $columnDetails[] = [
                    'name' => $column,
                    'type' => $columnType,
                ];
            }

            // Get relationships (if any)
            $relationships = $this->getTableRelationships($tableName);

            $schema[$tableName] = [
                'columns' => $columnDetails,
                'relationships' => $relationships,
            ];
        }

        // Pass the schema data to the view
        return view('schema-viewer', compact('schema'));
    }

    /**
     * Get relationships for a table (if any).
     *
     * @param string $tableName
     * @return array
     */
    protected function getTableRelationships($tableName)
    {
        // This is a basic implementation. You can extend it to detect relationships.
        // For example, check for foreign keys or use Eloquent model relationships.
        $relationships = [];

        // Example: Detect foreign keys
        $foreignKeys = DB::select("
            SELECT
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE
                TABLE_NAME = '$tableName'
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        foreach ($foreignKeys as $foreignKey) {
            $relationships[] = [
                'type' => 'belongsTo', // Assuming it's a belongsTo relationship
                'related_table' => $foreignKey->REFERENCED_TABLE_NAME,
                'foreign_key' => $foreignKey->COLUMN_NAME,
                'references' => $foreignKey->REFERENCED_COLUMN_NAME,
            ];
        }

        return $relationships;
    }
}
