<?php

namespace MiningManager\Services\Security;

/**
 * Makes CSV cells safe to open in Excel, LibreOffice and Google Sheets.
 *
 * A cell that starts with =, +, -, @, a tab or a carriage return is read as a
 * formula by spreadsheet programs. Names in these exports come from players
 * (event names, structure names), so one starting with "=" could
 * run a formula on the director's machine when they open the file. Such cells
 * get a leading apostrophe, which spreadsheets hide and treat as "this is
 * text". Numbers, including negative ones, are left alone.
 */
final class CsvSafe
{
    public static function cell($value)
    {
        if (!is_string($value) || $value === '' || is_numeric($value)) {
            return $value;
        }

        if (in_array($value[0], ['=', '+', '-', '@', "\t", "\r", "\n"], true)) {
            return "'" . $value;
        }

        return $value;
    }

    public static function row(array $row): array
    {
        return array_map([self::class, 'cell'], $row);
    }

    /**
     * fputcsv with every cell made safe.
     */
    public static function put($handle, $row): void
    {
        // Rows arrive as arrays, stdClass query rows or collections.
        if ($row instanceof \Illuminate\Contracts\Support\Arrayable) {
            $row = $row->toArray();
        } elseif (is_object($row)) {
            $row = get_object_vars($row);
        }

        fputcsv($handle, self::row((array) $row));
    }
}
