<?php 

namespace App\Enum;

enum ExportFormat{
    case CSV;
    case XLSX;
    case ODS;

    public static function fromExtension(string $extension): ExportFormat
    {
        return match($extension) {
            'csv' => ExportFormat::CSV,
            'xlsx' => ExportFormat::XLSX,
            'ods' => ExportFormat::ODS,
        };
    }

    public function contentType(): string
    {
        return match ($this) {
            ExportFormat::CSV => 'text/csv',
            ExportFormat::XLSX => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ExportFormat::ODS => 'application/vnd.oasis.opendocument.spreadsheet',
        };
    }

    public function extension(): string
    {
        return match ($this) {
            ExportFormat::CSV => 'csv',
            ExportFormat::XLSX => 'xlsx',
            ExportFormat::ODS => 'ods',
        };
    }
}