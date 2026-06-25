<?php

namespace YYFSS\Utils;

class SimpleXLSX
{
    private array $rows = [];

    public static function parse(string $filePath, ?string $originalName = null): ?self
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $ext = self::resolveExtension($filePath, $originalName);

        if ($ext === 'csv') {
            return self::parseCsv($filePath);
        }

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            return self::parseXlsx($filePath);
        }

        return null;
    }

    private static function resolveExtension(string $filePath, ?string $originalName): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($ext !== '') {
            return $ext;
        }

        if ($originalName) {
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if ($ext !== '') {
                return $ext;
            }
        }

        $head = file_get_contents($filePath, false, null, 0, 4);
        if ($head !== false && str_starts_with($head, 'PK')) {
            return 'xlsx';
        }

        return 'csv';
    }

    private static function parseCsv(string $filePath): ?self
    {
        $content = file_get_contents($filePath);
        if ($content === false || $content === '') {
            return null;
        }

        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        if (function_exists('mb_check_encoding') && !mb_check_encoding($content, 'UTF-8')) {
            $converted = @mb_convert_encoding($content, 'UTF-8', 'GB18030');
            if ($converted !== false) {
                $content = $converted;
            }
        }

        $instance = new self();
        $lines = preg_split('/\r\n|\r|\n/', $content);
        foreach ($lines as $line) {
            if ($line === '' && count($instance->rows) === 0) {
                continue;
            }
            $instance->rows[] = str_getcsv($line);
        }

        return count($instance->rows) > 0 ? $instance : null;
    }

    private static function parseXlsx(string $filePath): ?self
    {
        if (!class_exists('ZipArchive')) {
            return self::parseCsvFallback($filePath);
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return null;
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml) {
            $xml = simplexml_load_string($sharedXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string)$r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (!$sheetXml) {
            return null;
        }

        $instance = new self();
        $xml = simplexml_load_string($sheetXml);
        if (!$xml || !isset($xml->sheetData->row)) {
            return $instance;
        }

        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            $colIndex = 0;
            foreach ($row->c as $cell) {
                $ref = (string)$cell['r'];
                preg_match('/([A-Z]+)/', $ref, $m);
                $col = $m[1] ?? '';
                $targetIndex = self::colToIndex($col);

                while ($colIndex < $targetIndex) {
                    $rowData[] = '';
                    $colIndex++;
                }

                $type = (string)$cell['t'];
                $value = '';
                if ($type === 's') {
                    $idx = (int)$cell->v;
                    $value = $sharedStrings[$idx] ?? '';
                } elseif (isset($cell->v)) {
                    $value = (string)$cell->v;
                }
                $rowData[] = $value;
                $colIndex++;
            }
            $instance->rows[] = $rowData;
        }

        return $instance;
    }

    private static function colToIndex(string $col): int
    {
        $index = 0;
        $len = strlen($col);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }

    private static function parseCsvFallback(string $filePath): ?self
    {
        return null;
    }

    public function rows(): array
    {
        return $this->rows;
    }
}
