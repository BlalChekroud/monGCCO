<?php

namespace App\Exporter;

use App\Enum\ExportFormat;

interface ExporterInterface
{
    public function export(array $columnNmaes, array $data, ExportFormat $format, string $fileName): \SplFileInfo;
}