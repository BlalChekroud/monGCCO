<?php

namespace App\Exporter;

use App\Enum\ExportFormat;
use App\Factory\ExcelWriterFactory;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;

class ExcelOpenSpoutExporter implements ExporterInterface
{
    public function __construct(private readonly ExcelWriterFactory $factory) {}

    public function export(array $columnNmaes, array $data, ExportFormat $format, string $fileName): \SplFileInfo
    {
        $filePath = "{$fileName}.{$format->extension()}";

        $rows = [Row::fromValues($columnNmaes, (new Style())->setFontBold())];
        
        foreach ($data as $row) {
            $values = array_map('strip_tags', $row);
            $rows[] = Row::fromValues($values);
        }

        $writer = $this->factory->fromFormat($format);
        $writer->openToBrowser($filePath);
        $writer->addRows($rows);
        $writer->close();

        return new \SplFileInfo($filePath);
    }
}