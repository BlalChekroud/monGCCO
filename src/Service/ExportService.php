<?php

namespace App\Service;

use App\Enum\ExportFormat;
use App\Exporter\ExcelOpenSpoutExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExportService
{
    private ExcelOpenSpoutExporter $exporter;

    public function __construct(ExcelOpenSpoutExporter $exporter)
    {
        $this->exporter = $exporter;
    }

    /**
     * Exports data to a file in the specified format.
     *
     * @param array        $columnNames Header row for the file.
     * @param array        $data        Data rows to export.
     * @param ExportFormat $format      Format for the export (CSV, XLSX, ODS).
     * @param string       $fileName    Name of the exported file.
     *
     * @return StreamedResponse Response to trigger file download.
     */
    public function export(array $columnNames, array $data, ExportFormat $format, string $fileName): StreamedResponse
    {
        return new StreamedResponse(function () use ($columnNames, $data, $format, $fileName) {
            $this->exporter->export($columnNames, $data, $format, $fileName);
        }, 200, [
            'Content-Type' => $format->contentType(),
            'Content-Disposition' => sprintf('attachment; filename="%s.%s"', $fileName, $format->extension()),
        ]);
    }
    
}