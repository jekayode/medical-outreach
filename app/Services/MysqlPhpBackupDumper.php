<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class MysqlPhpBackupDumper
{
    /**
     * Build a logical SQL dump using the active Laravel database connection.
     */
    public function dump(?string $connectionName = null): string
    {
        /** @var Connection $connection */
        $connection = DB::connection($connectionName);
        $database = (string) $connection->getDatabaseName();

        $lines = [
            '-- Medical Outreach database backup',
            '-- Generated: '.now()->toIso8601String(),
            '-- Database: '.$database,
            '-- Method: PHP (mysqldump unavailable)',
            '',
            'SET FOREIGN_KEY_CHECKS=0;',
            'SET NAMES utf8mb4;',
            'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";',
            '',
        ];

        $tables = $connection->select('SHOW FULL TABLES WHERE Table_type = ?', ['BASE TABLE']);
        $tableKey = 'Tables_in_'.$database;

        foreach ($tables as $tableRow) {
            $tableName = (string) ($tableRow->{$tableKey} ?? '');
            if ($tableName === '') {
                continue;
            }

            $quotedTable = $this->quoteIdentifier($tableName);
            $createRows = $connection->select('SHOW CREATE TABLE '.$quotedTable);
            $createSql = (string) ($createRows[0]->{'Create Table'} ?? '');

            if ($createSql === '') {
                throw new RuntimeException(__('Could not read schema for table :table.', ['table' => $tableName]));
            }

            $lines[] = 'DROP TABLE IF EXISTS '.$quotedTable.';';
            $lines[] = $createSql.';';
            $lines[] = '';

            $batch = [];
            foreach ($connection->table($tableName)->orderByRaw('1')->lazy(200) as $row) {
                $batch[] = (array) $row;
                if (count($batch) >= 200) {
                    array_push($lines, ...$this->insertLines($quotedTable, $batch, $connection));
                    $batch = [];
                }
            }

            if ($batch !== []) {
                array_push($lines, ...$this->insertLines($quotedTable, $batch, $connection));
            }
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<string>
     */
    private function insertLines(string $quotedTable, array $rows, Connection $connection): array
    {
        if ($rows === []) {
            return [];
        }

        $columns = array_keys($rows[0]);
        $columnList = implode(', ', array_map(fn (string $column): string => $this->quoteIdentifier($column), $columns));

        $valueGroups = [];
        foreach ($rows as $row) {
            $values = [];
            foreach ($columns as $column) {
                $values[] = $this->quoteValue($connection, $row[$column] ?? null);
            }
            $valueGroups[] = '('.implode(', ', $values).')';
        }

        return [
            'INSERT INTO '.$quotedTable.' ('.$columnList.') VALUES',
            implode(",\n", $valueGroups).';',
            '',
        ];
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    private function quoteValue(Connection $connection, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $connection->getPdo()->quote((string) $value);
    }
}
