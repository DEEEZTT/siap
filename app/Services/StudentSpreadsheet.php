<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class StudentSpreadsheet
{
    /** @return array<int, array{row: int, cells: array<int, string>}> */
    public function read(UploadedFile $file): array
    {
        if (strtolower($file->getClientOriginalExtension()) === 'csv') {
            return $this->readCsv($file);
        }

        return $this->readXlsx($file);
    }

    /** @return list<string> */
    public function headers(): array
    {
        return [
            'NIS',
            'Nama Lengkap',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Konsentrasi Keahlian',
            'Perusahaan PKL',
            'Mulai PKL',
            'Selesai PKL',
            'Predikat',
        ];
    }

    public function createTemplate(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'siap-pkl-');

        if ($path === false) {
            throw new \RuntimeException('Template tidak dapat dibuat.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($path);
            throw new \RuntimeException('Template Excel tidak dapat dibuat.');
        }

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Siswa PKL" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');

        $rows = [$this->headers()];

        $sheetRows = [];

        foreach ($rows as $rowNumber => $row) {
            $cells = '';

            foreach ($row as $columnNumber => $value) {
                $style = $columnNumber === 0 ? ' s="1"' : '';
                $cells .= '<c r="'.$this->columnName($columnNumber + 1).($rowNumber + 1).'" t="inlineStr"'.$style.'><is><t>'.htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</t></is></c>';
            }

            $rowNumber++;
            $rowsXml = '<row r="'.$rowNumber.'">'.$cells.'</row>';
            $sheetRows[] = $rowsXml;
        }

        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="@"/></numFmts><fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols><col min="1" max="1" style="1" width="18" customWidth="1"/></cols><sheetData>'.implode('', $sheetRows).'</sheetData></worksheet>');
        $zip->close();

        return $path;
    }

    /** @return array<int, array{row: int, cells: array<int, string>}> */
    private function readCsv(UploadedFile $file): array
    {
        $stream = fopen($file->getPathname(), 'rb');

        if ($stream === false) {
            throw ValidationException::withMessages(['file' => 'Berkas CSV tidak dapat dibaca.']);
        }

        $firstLine = fgets($stream);

        if ($firstLine === false) {
            fclose($stream);
            throw ValidationException::withMessages(['file' => 'Berkas Excel masih kosong.']);
        }

        $delimiter = ',';

        foreach (["\t", ';', ','] as $candidate) {
            if (substr_count($firstLine, $candidate) > substr_count($firstLine, $delimiter)) {
                $delimiter = $candidate;
            }
        }

        rewind($stream);
        $rows = [];
        $rowNumber = 0;

        while (($cells = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            $rowNumber++;
            $rows[] = ['row' => $rowNumber, 'cells' => array_map(static fn ($value): string => (string) ($value ?? ''), $cells)];

            if ($rowNumber > 2001) {
                fclose($stream);
                throw ValidationException::withMessages(['file' => 'Berkas hanya boleh berisi maksimal 2.000 baris data.']);
            }
        }

        fclose($stream);

        return $rows;
    }

    /** @return array<int, array{row: int, cells: array<int, string>}> */
    private function readXlsx(UploadedFile $file): array
    {
        $zip = new ZipArchive;

        if ($zip->open($file->getPathname()) !== true) {
            throw ValidationException::withMessages(['file' => 'Berkas bukan Excel .xlsx yang valid.']);
        }

        try {
            $workbook = $this->document($this->zipEntry($zip, 'xl/workbook.xml'));
            $workbookRelationships = $this->document($this->zipEntry($zip, 'xl/_rels/workbook.xml.rels'));
            $workbookXPath = new DOMXPath($workbook);
            $firstSheet = $workbookXPath->query('//*[local-name()="sheet"]')->item(0);

            if ($firstSheet === null) {
                throw ValidationException::withMessages(['file' => 'Workbook tidak memiliki lembar kerja.']);
            }

            $relationshipId = $firstSheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
            $relationshipXPath = new DOMXPath($workbookRelationships);
            $relationship = null;

            foreach ($relationshipXPath->query('//*[local-name()="Relationship"]') as $candidate) {
                if ($candidate->attributes?->getNamedItem('Id')?->nodeValue === $relationshipId) {
                    $relationship = $candidate;
                    break;
                }
            }

            if ($relationship === null) {
                throw ValidationException::withMessages(['file' => 'Lembar kerja Excel tidak dapat ditemukan.']);
            }

            $target = str_replace('\\', '/', $relationship->attributes?->getNamedItem('Target')?->nodeValue ?? '');
            $sheetPath = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.$target;
            $sheetPath = $this->normalizeZipPath($sheetPath);

            if ($sheetPath === null) {
                throw ValidationException::withMessages(['file' => 'Lokasi lembar kerja Excel tidak valid.']);
            }

            $sheet = $this->document($this->zipEntry($zip, $sheetPath));
            $sharedStrings = [];
            $sharedStringContents = $this->zipEntry($zip, 'xl/sharedStrings.xml', true);

            if ($sharedStringContents !== false) {
                $sharedStringDocument = $this->document($sharedStringContents);
                $sharedStringXPath = new DOMXPath($sharedStringDocument);

                foreach ($sharedStringXPath->query('//*[local-name()="si"]') as $sharedString) {
                    $sharedStrings[] = $sharedString->textContent;
                }
            }

            $sheetXPath = new DOMXPath($sheet);
            $rowNodes = $sheetXPath->query('//*[local-name()="sheetData"]/*[local-name()="row"]');

            if ($rowNodes->length > 2001) {
                throw ValidationException::withMessages(['file' => 'Berkas hanya boleh berisi maksimal 2.000 baris data.']);
            }

            $rows = [];

            foreach ($rowNodes as $rowIndex => $rowNode) {
                $cells = [];

                foreach ($sheetXPath->query('./*[local-name()="c"]', $rowNode) as $cell) {
                    $reference = $cell->attributes?->getNamedItem('r')?->nodeValue ?? '';

                    if (! preg_match('/^([A-Z]+)/i', $reference, $matches)) {
                        continue;
                    }

                    $columnNumber = $this->columnNumber(strtoupper($matches[1]));

                    if ($columnNumber > 50) {
                        throw ValidationException::withMessages(['file' => 'Berkas Excel maksimal berisi 50 kolom.']);
                    }

                    $valueNode = $sheetXPath->query('./*[local-name()="v"]', $cell)->item(0);
                    $cellType = $cell->attributes?->getNamedItem('t')?->nodeValue ?? '';

                    if ($cellType === 'inlineStr') {
                        $value = $sheetXPath->query('./*[local-name()="is"]', $cell)->item(0)?->textContent ?? '';
                    } else {
                        $value = $valueNode?->textContent ?? '';

                        if ($cellType === 's') {
                            $value = $sharedStrings[(int) $value] ?? '';
                        }
                    }

                    $cells[$columnNumber - 1] = $value;
                }

                if ($cells !== []) {
                    ksort($cells);
                    $actualRowNumber = (int) ($rowNode->attributes?->getNamedItem('r')?->nodeValue ?? $rowIndex + 1);
                    $rows[] = ['row' => $actualRowNumber, 'cells' => $cells];
                }
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    private function document(string|false $contents): DOMDocument
    {
        if ($contents === false || str_contains($contents, '<!DOCTYPE')) {
            throw ValidationException::withMessages(['file' => 'Struktur Excel tidak lengkap atau tidak valid.']);
        }

        $document = new DOMDocument;
        $document->resolveExternals = false;
        $document->substituteEntities = false;

        if (! $document->loadXML($contents, LIBXML_NONET | LIBXML_NOBLANKS)) {
            throw ValidationException::withMessages(['file' => 'Struktur Excel tidak dapat dibaca.']);
        }

        return $document;
    }

    private function zipEntry(ZipArchive $zip, string $name, bool $optional = false): string|false
    {
        $entry = $zip->statName($name);

        if ($entry === false) {
            return $optional ? false : throw ValidationException::withMessages(['file' => 'Struktur Excel tidak lengkap atau tidak valid.']);
        }

        if ($entry['size'] > 20 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => 'Bagian berkas Excel terlalu besar untuk diproses.']);
        }

        return $zip->getFromName($name);
    }

    private function normalizeZipPath(string $path): ?string
    {
        $parts = [];

        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..') {
                if ($parts === []) {
                    return null;
                }

                array_pop($parts);

                continue;
            }

            $parts[] = $part;
        }

        return implode('/', $parts);
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

    private function columnNumber(string $columnName): int
    {
        $number = 0;

        foreach (str_split($columnName) as $letter) {
            $number = ($number * 26) + ord($letter) - 64;
        }

        return $number;
    }
}
