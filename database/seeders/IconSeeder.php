<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class IconSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $icons = [
        //     [
        //         'icon' => 'uil uil-check-circle',
        //         'icon_name' => 'Check Circle',
        //         'icon_background' => 'bg-primary/10 text-primary',
        //     ],
        //     [
        //         'icon' => 'uil uil-edit',
        //         'icon_name' => 'Edit',
        //         'icon_background' => 'bg-warning/10 text-warning',
        //     ],
        //     [
        //         'icon' => 'uil uil-trash-alt',
        //         'icon_name' => 'Trash',
        //         'icon_background' => 'bg-danger/10 text-danger',
        //     ],
        //     [
        //         'icon' => 'uil uil-comment',
        //         'icon_name' => 'Comment',
        //         'icon_background' => 'bg-info/10 text-info',
        //     ],
        //     [
        //         'icon' => 'uil uil-user',
        //         'icon_name' => 'User',
        //         'icon_background' => 'bg-secondary/10 text-secondary',
        //     ],
        //     [
        //         'icon' => 'uil uil-folder',
        //         'icon_name' => 'Folder',
        //         'icon_background' => 'bg-gray-200 text-gray-700',
        //     ],
        //     [
        //         'icon' => 'uil uil-bell',
        //         'icon_name' => 'Notification',
        //         'icon_background' => 'bg-yellow-200 text-yellow-700',
        //     ],
        //     [
        //         'icon' => 'uil uil-star',
        //         'icon_name' => 'Star',
        //         'icon_background' => 'bg-orange-200 text-orange-700',
        //     ],
        //     [
        //         'icon' => 'uil uil-clock',
        //         'icon_name' => 'Clock',
        //         'icon_background' => 'bg-blue-200 text-blue-700',
        //     ],
        //     [
        //         'icon' => 'uil uil-heart',
        //         'icon_name' => 'Heart',
        //         'icon_background' => 'bg-red-200 text-red-700',
        //     ],
        // ];

        $icons = [
            [
                'icon' => 'uil uil-file-check',
                'icon_name' => 'Checking',
                'icon_background' => 'bg-primary/10 text-primary',
            ],
            [
                'icon' => 'uil uil-money-stack',
                'icon_name' => 'Donation',
                'icon_background' => 'bg-primary/10 text-primary',
            ],
            [
                'icon' => 'uil uil-users-alt',
                'icon_name' => 'Registration',
                'icon_background' => 'bg-primary/10 text-primary',
            ],
            [
                'icon' => 'uil uil-meeting-board',
                'icon_name' => 'Meeting',
                'icon_background' => 'bg-primary/10 text-primary',
            ],
            [
                'icon' => 'uil uil-schedule',
                'icon_name' => 'Schedule',
                'icon_background' => 'bg-primary/10 text-primary',
            ],
            [
                'icon' => 'uil uil-monitor-heart-rate',
                'icon_name' => 'Monitoring',
                'icon_background' => 'bg-primary/10 text-primary',
            ],
            [
                'icon' => 'uil uil-chart-bar',
                'icon_name' => 'Evaluation',
                'icon_background' => 'bg-primary/10 text-primary',
            ],
            [
                'icon' => 'uil uil-check-circle',
                'icon_name' => 'Done',
                'icon_background' => 'bg-primary/10 text-primary',
            ],
            [
                'icon' => 'uil uil-megaphone',
                'icon_name' => 'Campagne',
                'icon_background' => 'bg-primary/10 text-primary',
            ],
            [
                'icon' => 'uil uil-truck',
                'icon_name' => 'Preparation',
                'icon_background' => 'bg-primary/10 text-primary',
            ],
            [
                'icon' => 'uil uil-truck',
                'icon_name' => 'Cooperation',
                'icon_background' => 'bg-primary/10 text-primary',
            ],
            [
                'icon' => 'uil uil-camera',
                'icon_name' => 'Documentation',
                'icon_background' => 'bg-primary/10 text-primary',
            ],
            [
                'icon' => 'uil uil-file-edit',
                'icon_name' => 'Reporting',
                'icon_background' => 'bg-primary/10 text-primary',
            ],
            [
                'icon' => 'uil uil-shopping-cart',
                'icon_name' => 'Shopping',
                'icon_background' => 'bg-primary/10 text-primary',
            ],
            [
                'icon' => 'uil uil-play',
                'icon_name' => 'Progress',
                'icon_background' => 'bg-primary/10 text-primary',
            ],
            [
                'icon' => 'uil uil-cancel',
                'icon_name' => 'Cancel',
                'icon_background' => 'bg-primary/10 text-primary',
            ],
        ];

        foreach ($icons as $icon) {
            DB::table('icons')->insert([
                'icon_id' => Str::uuid(),
                'icon' => $icon['icon'],
                'icon_name' => $icon['icon_name'],
                'icon_background' => $icon['icon_background'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
