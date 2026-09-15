<?php

declare(strict_types=1);

namespace App\Radar\Locations;

use RuntimeException;

final class SqlLocationDatasetParser implements LocationDatasetImporter
{
    public function records(string $source): iterable
    {
        if (!is_file($source) || !is_readable($source)) throw new RuntimeException('LOCATION_SOURCE_UNREADABLE');
        $size = filesize($source);
        if ($size === false || $size > 50 * 1024 * 1024) throw new RuntimeException('LOCATION_SQL_TOO_LARGE');
        $handle = fopen($source, 'rb');
        if ($handle === false) throw new RuntimeException('LOCATION_SOURCE_UNREADABLE');
        $statement = ''; $rowNumber = 0; $table = null; $columns = null; $insertColumns = null;
        try {
            while (($line = fgets($handle)) !== false) {
                $upper = strtoupper(trim($line));
                if (preg_match('/^DROP TABLE IF EXISTS `?wilayah`?;?$/i', trim($line))) continue;
                if (preg_match('/\b(ALTER|TRIGGER|PROCEDURE|FUNCTION|LOAD\s+DATA|COPY\s+PROGRAM|DROP)\b/i', $line)) throw new RuntimeException('LOCATION_SQL_UNSUPPORTED_CONSTRUCT');
                if ($table === null && preg_match('/CREATE TABLE(?: IF NOT EXISTS)?\s+`?([a-zA-Z0-9_]+)`?/i', $line, $match)) $table = strtolower($match[1]);
                if ($insertColumns === null && preg_match('/INSERT INTO\s+`?([a-zA-Z0-9_]+)`?\s*\(([^)]+)\)/i', trim($line), $match)) {
                    if (strtolower($match[1]) !== 'wilayah') throw new RuntimeException('LOCATION_SQL_TABLE_UNSUPPORTED');
                    $insertColumns = array_map(fn (string $value): string => strtolower(trim($value, " `\t\r\n")), explode(',', $match[2]));
                    continue;
                }
                if ($insertColumns !== null && preg_match('/^VALUES\s*$/i', trim($line))) { $columns = $insertColumns; $insertColumns = null; $statement = ''; continue; }
                if ($columns !== null) {
                    $statement .= trim($line);
                    if (str_ends_with(trim($line), ';')) {
                        foreach ($this->parseValues(rtrim($statement, ';')) as $values) {
                            $rowNumber++;
                            $row = array_combine($columns, $values);
                            if (!is_array($row) || !isset($row['kode'], $row['nama'])) throw new RuntimeException('LOCATION_SQL_COLUMNS_UNSUPPORTED');
                            $level = $this->level((string) $row['kode'], (string) $row['nama']);
                            yield ['source_row' => $rowNumber, 'code' => (string) $row['kode'], 'name' => (string) $row['nama'], 'level' => $level, 'type' => 'ADM4' === $level ? 'ADM4_UNRESOLVED' : null];
                        }
                        $columns = null; $statement = '';
                    }
                }
            }
        } finally { fclose($handle); }
        if ($table !== 'wilayah') throw new RuntimeException('LOCATION_SQL_TABLE_NOT_FOUND');
    }

    /** @return array<int,array<int,string>> */
    private function parseValues(string $input): array
    {
        $rows = []; $row = []; $value = ''; $quote = false; $escape = false; $depth = 0;
        for ($i = 0, $length = strlen($input); $i < $length; $i++) {
            $char = $input[$i];
            if ($quote) {
                if ($char === "'" && !$escape) {
                    if (($input[$i + 1] ?? '') === "'") { $value .= "'"; $i++; continue; }
                    $quote = false; continue;
                }
                if ($char === '\\' && !$escape) { $escape = true; continue; }
                $value .= $char; $escape = false; continue;
            }
            if ($char === "'") { $quote = true; continue; }
            if ($char === '(') { $depth++; if ($depth === 1) { $row = []; $value = ''; } continue; }
            if ($char === ')') { if ($depth === 1) { $row[] = trim($value); $rows[] = $row; $value = ''; } $depth--; continue; }
            if ($char === ',' && $depth === 1) { $row[] = trim($value); $value = ''; continue; }
            if ($depth > 0) $value .= $char;
        }
        if ($quote || $depth !== 0) throw new RuntimeException('LOCATION_SQL_VALUES_MALFORMED');
        return $rows;
    }

    private function level(string $code, string $name): string
    {
        return match (strlen(preg_replace('/\D/', '', trim($code)) ?? '')) {
            2 => LocationLevel::PROVINCE->value,
            4 => preg_match('/^\s*KOTA(?:\s+ADMINISTRASI)?\b/i', $name) === 1 ? LocationLevel::CITY->value : (preg_match('/^\s*KABUPATEN\b/i', $name) === 1 ? LocationLevel::REGENCY->value : LocationLevel::ADM4->value),
            6 => LocationLevel::DISTRICT->value,
            10 => LocationLevel::ADM4->value,
            default => throw new RuntimeException('LOCATION_SQL_CODE_INVALID'),
        };
    }
}