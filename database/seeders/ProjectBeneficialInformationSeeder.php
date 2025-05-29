<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Project;

class ProjectBeneficialInformationSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::all();

        if ($projects->isEmpty()) {
            $this->command->warn('Tidak ada project ditemukan.');
            return;
        }

        // Data dummy manual
        $personNames   = ['Andi Pratama', 'Budi Cahyo', 'Citra Ananda', 'Dewi Sari'];
        $addresses     = ['Jl. Merdeka No.1', 'Jl. Sudirman No.5', 'Jl. Thamrin No.10', 'Jl. Gatot Subroto No.20'];
        $phones        = ['081234567890', '082345678901', '083456789012', '084567890123'];
        $needsList     = [
            'Butuh bantuan logistik', 
            'Membutuhkan dana pendidikan',
            'Perlu dukungan medis',
            'Butuh renovasi rumah'
        ];
        $relations     = ['diri-sendiri', 'keluarga', 'teman', 'organisasi', 'lainnya'];
        $orgNames      = ['Yayasan Peduli', 'Komunitas Bangun', 'PT. Sejahtera', 'CV. Maju Bersama'];
        $orgRegs       = ['ORG-12345', 'ORG-67890', 'ORG-54321', 'ORG-09876'];

        foreach ($projects as $project) {
            // Pilih tipe beneficiary
            $type = ['perorangan', 'lembaga'][array_rand([0,1])];

            if ($type === 'perorangan') {
                $idxName   = array_rand($personNames);
                $idxRel    = array_rand($relations);
                $benefRel  = $relations[$idxRel];
                DB::table('project_beneficial_informations')->insert([
                    'project_beneficial_information_id' => Str::uuid(),
                    'project_id'                       => $project->project_id,
                    'beneficiary_type'                 => 'perorangan',
                    'beneficiary_name'                 => $personNames[$idxName],
                    'beneficiary_nik'                  => (string) rand(1000000000, 9999999999),
                    'beneficiary_address'              => $addresses[array_rand($addresses)],
                    'beneficiary_phone'                => $phones[array_rand($phones)],
                    'beneficiary_needs'                => $needsList[array_rand($needsList)],
                    'organization_name'                => null,
                    'organization_reg_number'          => null,
                    'organization_address'             => null,
                    'organization_pic'                 => null,
                    'organization_phone'               => null,
                    'beneficiary_relation'             => $benefRel,
                    'beneficiary_relation_other'       => $benefRel === 'lainnya' ? 'Relawan lokal' : null,
                    'beneficiary_file_path'            => null,
                    'beneficiary_file_name'            => null,
                    'created_at'                       => now(),
                    'updated_at'                       => now(),
                ]);
            } else {
                $idxOrg = array_rand($orgNames);
                $idxRel = array_rand($relations);
                $benefRel = $relations[$idxRel];
                DB::table('project_beneficial_informations')->insert([
                    'project_beneficial_information_id' => Str::uuid(),
                    'project_id'                       => $project->project_id,
                    'beneficiary_type'                 => 'lembaga',
                    'beneficiary_name'                 => null,
                    'beneficiary_nik'                  => null,
                    'beneficiary_address'              => null,
                    'beneficiary_phone'                => null,
                    'beneficiary_needs'                => $needsList[array_rand($needsList)],
                    'organization_name'                => $orgNames[$idxOrg],
                    'organization_reg_number'          => $orgRegs[array_rand($orgRegs)],
                    'organization_address'             => $addresses[array_rand($addresses)],
                    'organization_pic'                 => $personNames[array_rand($personNames)],
                    'organization_phone'               => $phones[array_rand($phones)],
                    'beneficiary_relation'             => $benefRel,
                    'beneficiary_relation_other'       => $benefRel === 'lainnya' ? 'Relawan lokal' : null,
                    'beneficiary_file_path'            => null,
                    'beneficiary_file_name'            => null,
                    'created_at'                       => now(),
                    'updated_at'                       => now(),
                ]);
            }
        }
    }
}
