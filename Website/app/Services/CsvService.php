<?php
declare(strict_types=1);

namespace App\Services;

final class CsvService
{
    public static function parse(string $filePath): array
    {
        $required = ['lot_code', 'produce_type', 'origin_region', 'harvest_date', 'grade1_count', 'grade2_count', 'defect_count'];
        $rows = [];
        $errors = [];

        if (!is_file($filePath)) {
            return ['rows' => [], 'errors' => [['line' => 0, 'error' => 'File not found']]];
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            return ['rows' => [], 'errors' => [['line' => 0, 'error' => 'Cannot open file']]];
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return ['rows' => [], 'errors' => [['line' => 0, 'error' => 'Missing CSV header']]];
        }

        $header = array_map('trim', $header);
        foreach ($required as $field) {
            if (!in_array($field, $header, true)) {
                fclose($handle);
                return ['rows' => [], 'errors' => [['line' => 0, 'error' => 'Missing required column: ' . $field]]];
            }
        }

        $line = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            $item = [];
            foreach ($header as $i => $key) {
                $item[$key] = $data[$i] ?? '';
            }
            $item['__line'] = $line;

            foreach ($required as $field) {
                if (trim((string) ($item[$field] ?? '')) === '') {
                    $errors[] = ['line' => $line, 'error' => "{$field} is required"];
                }
            }

            if (!is_numeric($item['grade1_count']) || !is_numeric($item['grade2_count']) || !is_numeric($item['defect_count'])) {
                $errors[] = ['line' => $line, 'error' => 'grade counts must be numeric'];
            }

            if (strtotime((string) $item['harvest_date']) === false) {
                $errors[] = ['line' => $line, 'error' => 'invalid harvest_date'];
            }

            $rows[] = $item;
        }

        fclose($handle);

        return ['rows' => $rows, 'errors' => $errors];
    }
}
