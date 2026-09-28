<?php

namespace App\Services;

use ZipArchive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use RuntimeException;

class DatabaseCsvService
{
    /**
     * Export all tables to CSV and pack into a ZIP.
     */
    public function exportToZip(string $zipPath): void
    {
        $tables = $this->getAllTables();
        $tempDir = storage_path('app/temp_csv_export_' . uniqid());
        File::makeDirectory($tempDir);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Gagal membuat file ZIP.");
        }

        foreach ($tables as $table) {
            if (in_array($table, ['migrations', 'password_reset_tokens'])) {
                continue;
            }

            $csvPath = $tempDir . '/' . $table . '.csv';
            $this->exportTableToCsv($table, $csvPath);
            $zip->addFile($csvPath, $table . '.csv');
        }

        $zip->close();
        File::deleteDirectory($tempDir);
    }

    /**
     * Restore all tables from a CSV ZIP file.
     */
    public function restoreFromZip(string $zipPath): void
    {
        $tempDir = storage_path('app/temp_csv_import_' . uniqid());
        File::makeDirectory($tempDir);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException("Gagal membuka file ZIP.");
        }

        $zip->extractTo($tempDir);
        $zip->close();

        $files = File::files($tempDir);
        if (empty($files)) {
            File::deleteDirectory($tempDir);
            throw new RuntimeException("File ZIP kosong atau format tidak valid.");
        }

        DB::beginTransaction();
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            foreach ($files as $file) {
                if ($file->getExtension() !== 'csv') {
                    continue;
                }

                $tableName = $file->getFilenameWithoutExtension();
                // Pastikan tabel ada di database saat ini
                if (!in_array($tableName, $this->getAllTables())) {
                    continue;
                }

                $this->importTableFromCsv($tableName, $file->getRealPath());
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            DB::commit();
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            DB::rollBack();
            File::deleteDirectory($tempDir);
            throw new RuntimeException("Gagal memulihkan dari CSV: " . $e->getMessage());
        }

        File::deleteDirectory($tempDir);
    }

    private function getAllTables(): array
    {
        $tables = DB::select('SHOW TABLES');
        return array_map('current', json_decode(json_encode($tables), true));
    }

    private function exportTableToCsv(string $table, string $path): void
    {
        $writer = new CsvWriter();
        $writer->openToFile($path);

        $columns = DB::getSchemaBuilder()->getColumnListing($table);
        $writer->addRow(Row::fromValues($columns));

        $orderByColumn = !empty($columns) ? $columns[0] : 'id';
        DB::table($table)->orderBy($orderByColumn)->chunk(500, function ($rows) use ($writer) {
            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues(array_values((array)$row)));
            }
        });

        $writer->close();
    }

    private function importTableFromCsv(string $table, string $path): void
    {
        $reader = new CsvReader();
        $reader->open($path);

        // Truncate table first
        DB::table($table)->truncate();

        $columns = [];
        $batch = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                $cells = $row->toArray();

                // Header
                if ($rowIndex === 1) {
                    $columns = $cells;
                    continue;
                }

                // Data
                if (empty($columns) || count($cells) !== count($columns)) {
                    continue;
                }

                $data = array_combine($columns, $cells);
                // Ganti string kosong dengan null untuk konsistensi database
                foreach ($data as $key => $value) {
                    if ($value === '') {
                        $data[$key] = null;
                    }
                }

                $batch[] = $data;

                if (count($batch) >= 500) {
                    DB::table($table)->insert($batch);
                    $batch = [];
                }
            }
        }

        if (count($batch) > 0) {
            DB::table($table)->insert($batch);
        }

        $reader->close();
    }
}
