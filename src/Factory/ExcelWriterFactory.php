<?php

namespace App\Factory;

use App\Enum\ExportFormat;
use OpenSpout\Writer\CSV\Writer as CSVWriter;
use OpenSpout\Writer\ODS\Writer as ODSWriter;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter ;

class ExcelWriterFactory
{
    public function fromFormat(ExportFormat $format): WriterInterface
    {
        return match ($format) {
            ExportFormat::CSV => new CSVWriter(),
            ExportFormat::XLSX => new XLSXWriter(),
            ExportFormat::ODS => new ODSWriter(),
        };
    }
}