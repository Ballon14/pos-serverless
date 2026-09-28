<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Ifsnop\Mysqldump\Mysqldump;
use Exception;

class SettingController extends Controller
{
    public function index()
    {
        $settings = \App\Models\Setting::all()->pluck('value', 'key')->toArray();
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'enable_multi_tier_grosir' => 'nullable|boolean',
        ]);

        \App\Models\Setting::set('enable_multi_tier_grosir', $request->has('enable_multi_tier_grosir') ? '1' : '0');

        app(ActivityLogger::class)->log('settings.update', 'Memperbarui pengaturan sistem.');

        return redirect()->route('settings.index')->with('success', 'Pengaturan berhasil diperbarui.');
    }

    public function backup()
    {
        try {
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');

            // Generate filename
            $filename = 'backup_tokombaemi_' . date('Ymd_His') . '.sql';
            $path = storage_path('app/' . $filename);

            // Dump database
            $dump = new Mysqldump("mysql:host={$host};dbname={$database}", $username, $password);
            $dump->start($path);

            app(ActivityLogger::class)->log('settings.backup', 'Melakukan backup seluruh database.');

            return response()->download($path)->deleteFileAfterSend(true);
        } catch (Exception $e) {
            return redirect()->route('settings.index')->with('error', 'Gagal membackup database: ' . $e->getMessage());
        }
    }

    public function restore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimetypes:text/plain,application/sql,text/x-sql|max:51200', // 50MB max
        ], [
            'backup_file.required' => 'File backup harus diunggah.',
            'backup_file.mimetypes' => 'File harus berupa file .sql',
        ]);

        try {
            $file = $request->file('backup_file');
            $sql = file_get_contents($file->getRealPath());

            // Matikan pengecekan foreign key sementara agar proses restore lancar
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::unprepared($sql);
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            app(ActivityLogger::class)->log('settings.restore', 'Melakukan restore seluruh database.');

            return redirect()->route('settings.index')->with('success', 'Database berhasil dipulihkan.');
        } catch (Exception $e) {
            // Pastikan foreign key nyala kembali jika gagal
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return redirect()->route('settings.index')->with('error', 'Gagal memulihkan database: ' . $e->getMessage());
        }
    }

    public function backupCsv(\App\Services\DatabaseCsvService $csvService)
    {
        try {
            $filename = 'backup_tokombaemi_csv_' . date('Ymd_His') . '.zip';
            $path = storage_path('app/' . $filename);

            $csvService->exportToZip($path);

            app(ActivityLogger::class)->log('settings.backup-csv', 'Melakukan backup seluruh database ke format CSV (ZIP).');

            return response()->download($path)->deleteFileAfterSend(true);
        } catch (Exception $e) {
            return redirect()->route('settings.index')->with('error', 'Gagal membackup database ke CSV: ' . $e->getMessage());
        }
    }

    public function restoreCsv(Request $request, \App\Services\DatabaseCsvService $csvService)
    {
        $request->validate([
            'backup_zip' => 'required|file|mimes:zip|max:51200', // 50MB max
        ], [
            'backup_zip.required' => 'File backup CSV (.zip) harus diunggah.',
            'backup_zip.mimes' => 'File harus berupa arsip .zip',
        ]);

        try {
            $file = $request->file('backup_zip');
            
            $csvService->restoreFromZip($file->getRealPath());

            app(ActivityLogger::class)->log('settings.restore-csv', 'Melakukan restore seluruh database dari CSV (ZIP).');

            return redirect()->route('settings.index')->with('success', 'Database berhasil dipulihkan dari CSV.');
        } catch (Exception $e) {
            return redirect()->route('settings.index')->with('error', 'Gagal memulihkan database dari CSV: ' . $e->getMessage());
        }
    }
}
