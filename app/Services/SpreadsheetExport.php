<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class SpreadsheetExport
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<list<string>>  $rows
     */
    public function create(array $headers, iterable $rows, string $sheetName): string
    {
        $workbookPath = tempnam(sys_get_temp_dir(), 'siap-export-');
        $worksheetPath = tempnam(sys_get_temp_dir(), 'siap-sheet-');

        if ($workbookPath === false || $worksheetPath === false) {
            if ($workbookPath !== false) {
                @unlink($workbookPath);
            }

            if ($worksheetPath !== false) {
                @unlink($worksheetPath);
            }

            throw new RuntimeException('Berkas ekspor tidak dapat dibuat.');
        }

        if ($headers === []) {
            @unlink($workbookPath);
            @unlink($worksheetPath);

            throw new RuntimeException('Kolom ekspor belum ditentukan.');
        }

        $worksheet = fopen($worksheetPath, 'wb');

        if ($worksheet === false) {
            @unlink($workbookPath);
            @unlink($worksheetPath);

            throw new RuntimeException('Lembar kerja ekspor tidak dapat dibuat.');
        }

        $sheetName = mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', trim($sheetName)) ?: 'Data', 0, 31);
        $sheetRows = 0;

        try {
            fwrite($worksheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="1" width="20" customWidth="1"/><col min="2" max="'.max(2, count($headers)).'" width="26" customWidth="1"/></cols><sheetData>');
            fwrite($worksheet, $this->rowXml($headers, 1, true));
            $sheetRows = 1;

            foreach ($rows as $row) {
                $sheetRows++;
                fwrite($worksheet, $this->rowXml($row, $sheetRows));
            }

            fwrite($worksheet, '</sheetData><autoFilter ref="A1:'.$this->columnName(count($headers)).$sheetRows.'"/></worksheet>');
        } finally {
            fclose($worksheet);
        }

        $zip = new ZipArchive;

        if ($zip->open($workbookPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($workbookPath);
            @unlink($worksheetPath);

            throw new RuntimeException('Berkas Excel tidak dapat dibuat.');
        }

        $escapedSheetName = htmlspecialchars($sheetName, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.$escapedSheetName.'" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
        $zip->addFile($worksheetPath, 'xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($worksheetPath);

        return $workbookPath;
    }

    /**
     * @param  list<string>  $values
     */
    private function rowXml(array $values, int $rowNumber, bool $header = false): string
    {
        $cells = '';

        foreach ($values as $columnIndex => $value) {
            $cellReference = $this->columnName($columnIndex + 1).$rowNumber;
            $style = $header ? ' s="1"' : '';
            $text = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $preserveSpace = trim((string) $value) !== (string) $value ? ' xml:space="preserve"' : '';
            $cells .= '<c r="'.$cellReference.'" t="inlineStr"'.$style.'><is><t'.$preserveSpace.'>'.$text.'</t></is></c>';
        }

        return '<row r="'.$rowNumber.'">'.$cells.'</row>';
    }

    private function columnName(int $columnNumber): string
    {
        $name = '';

        while ($columnNumber > 0) {
            $columnNumber--;
            $name = chr(65 + ($columnNumber % 26)).$name;
            $columnNumber = intdiv($columnNumber, 26);
        }

        return $name;
    }
}
