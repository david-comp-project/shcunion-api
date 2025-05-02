<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [

            'Pendidikan',
            'Lingkungan',
            'Sosial',
            'Ekonomi',
            'Budaya',
            'Olahraga',
            'Seni',
            'Teknologi',
            'Pariwisata',
            'Hewan',
            'Pertanian',
            'Perikanan',
            'Pangan',
            'Kerajinan',
            'Kewirausahaan',
            'Kesejahteraan',
            'Kebudayaan',
            'Kesehatan',

        ];

        foreach ($tags as $tag) {
            Tag::create([
                'tag_name' => $tag,
                'tag_slug' => Str::slug($tag),
                'tag_color' => '#' . substr(md5($tag), 0, 6)
            ]);
        }
    }
}
