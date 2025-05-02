<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WilayahSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = storage_path('app/private/file/kode_wilayah.csv');

        if (!file_exists($filePath)) {
            $this->command->error("File kode_wilayah.csv tidak ditemukan!");
            return;
        }

        $file = fopen($filePath, 'r');
        $header = fgetcsv($file);

        $provinsiData = [];
        $kabupatenData = [];
        $kecamatanData = [];
        $desaData = [];

        while (($row = fgetcsv($file)) !== false) {
            $provinsiData[$row[0]] = [
                'kode_provinsi' => $row[0],
                'nama_provinsi' => $row[1],
            ];

            $kabupatenData[$row[2]] = [
                'kode_kabupaten' => $row[2],
                'nama_kabupaten' => $row[3],
                'kode_provinsi' => $row[0],
            ];

            $kecamatanData[$row[4]] = [
                'kode_kecamatan' => $row[4],
                'nama_kecamatan' => $row[5],
                'kode_kabupaten' => $row[2],
            ];

            $desaData[$row[6]] = [
                'kode_desa' => $row[6],
                'nama_desa' => $row[7],
                'kode_kecamatan' => $row[4],
            ];
        }

        fclose($file);

        // Optional: hapus semua dulu biar nggak ganda (jika bukan update)
        // DB::table('provinsis')->truncate();
        // DB::table('kabupatens')->truncate();
        // DB::table('kecamatans')->truncate();
        // DB::table('desas')->truncate();

        // Insert in batch
        $this->batchUpsert('provinsis', array_values($provinsiData), 'kode_provinsi');
        $this->batchUpsert('kabupatens', array_values($kabupatenData), 'kode_kabupaten');
        $this->batchUpsert('kecamatans', array_values($kecamatanData), 'kode_kecamatan');
        $this->batchUpsert('desas', array_values($desaData), 'kode_desa');

        $this->command->info('Data wilayah berhasil dimasukkan!');
    }

    private function batchUpsert($table, $data, $uniqueKey, $batchSize = 1000)
    {
        foreach (array_chunk($data, $batchSize) as $batch) {
            DB::table($table)->upsert($batch, [$uniqueKey]);
        }
    }
}
