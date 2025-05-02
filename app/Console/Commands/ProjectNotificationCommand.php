<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Project;
use App\Models\Notification;
use Illuminate\Console\Command;

class ProjectNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:project-notification-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cek project yang hampir atau sudah melewati batas waktu tertentu dan kirim notifikasi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        
        // Hampir Melewati Batas Penarikan (donasi)
        $projects = Project::where('project_category', 'donation')
                            ->where('project_status', 'completed')
                            ->whereDate('completed_at', Carbon::now()->subDay())
                            ->get();

        foreach ($projects as $project) {
            Notification::create([
                'notification_title' => 'Batas Penarikan Hampir Berakhir!',
                'notification_icon' => 'ui uil-briefcase-alt',
                'notification_text' => "Batas penarikan untuk project '{$project->project_title}' hampir berakhir. Segera Lakukan Penarikan dana sebelum " . Carbon::parse($project->completed_at)->format('d M Y'),
                'notification_url' =>  "/dashboard/project/{$project->project_id}",
                'target_id' => $project->creator_id
            ]);
        } 

        // Melewati Batas Penarikan
        $expiredProjects = Project::where('project_category', 'donation')
                                    ->where('project_status', 'completed')
                                    ->whereDate('completed_at', '<', Carbon::now()->subDays(1))
                                    ->get();

        foreach ($expiredProjects as $project) {
            Notification::create([
                'notification_title' => 'Batas Penarikan Telah Lewat!',
                'notification_icon' => 'ui uil-briefcase-alt',
                'notification_text' => "Batas penarikan dana untuk project '{$project->project_title}' telah melewati batas. Hubungi Admin Untuk melakukan Penarikan",
                'notification_url' => "/dashboard/project/{$project->project_id}",
                'target_id' => $project->creator_id
            ]);
        }

        // Cek Batas Waktu LPJ
        $lpjUsers = Project::with('projectLampirans')
                            ->where('project_status', 'completed')
                            ->where(function ($query) {
                                $query->whereDate('completed_at', Carbon::now()->subDays(7))
                                    ->orWhereDate('completed_at', Carbon::now()->subDays(30));
                            })
                            ->whereDoesntHave('projectLampirans', function ($query) {
                                $query->where('tag', 'lpj'); // Hanya proyek yang punya LPJ
                            })
                            ->get();

        foreach ($lpjUsers as $project) {
            Notification::create([
                'notification_title' => 'Batas Waktu Upload LPJ Hampir Habis!',
                'notification_icon' => 'ui uil-briefcase-alt',
                'notification_text' => "Anda belum mengunggah LPJ untuk project '{$project->project_title}'. Segera Upload LPJ sampai dengan " . Carbon::parse($project->completed_at)->addDays(30)->format('d M Y'),
                'notification_url' => "/dashboard/project/{$project->project_id}",
                'target_id' => $project->creator_id
            ]);
        }

        $projectNeedReview = Project::with('projectEvaluasis')
                            ->doesntHave('projectEvaluasis') // Perbaikan whereHas()
                            ->get();

        // Ambil semua user yang memiliki role Admin (Spatie)
        $admins = User::role('admin')->pluck('user_id')->toArray(); 

        if (!empty($admins)) { // Pastikan admin tersedia sebelum array_rand
            foreach ($projectNeedReview as $project) {
                Notification::create([
                    'notification_title' => 'Batas Waktu Review!',
                    'notification_icon' => 'ui uil-briefcase-alt',
                    'notification_text' => "Anda belum melakukan review untuk project '{$project->project_title}'. Segera Lakukan Review sampai dengan " . Carbon::parse($project->created_at)->addDays(7)->format('d M Y'),
                    'notification_url' => "/dashboard/management/project",
                    'target_id' => $admins[array_rand($admins)] // Memilih admin secara acak
                ]);
            }
        }

        // Project yang belum direview selama lebih dari 30 hari
        $projectNotReviewed = Project::with('projectEvaluasis')
                                    ->doesntHave('projectEvaluasis') 
                                    ->whereDate('created_at', '<', now()->subDays(30)) // Perbaikan subDays
                                    ->get();

        // Kirim notifikasi ke pemilik project
        foreach ($projectNotReviewed as $project) {
            $project->update([
                'project_status' => 'in progress'
            ]);

            $project->refresh();

            Notification::create([
            'notification_title' => 'Status Project Diperbarui!',
            'notification_icon' => 'ui uil-briefcase-alt',
            'notification_text' => "Status project '{$project->project_title}' telah diperbarui menjadi '{$project->project_status}'. Silakan tinjau perubahan status pada halaman project untuk informasi lebih lanjut.",
            'notification_url' => "/dashboard/project/{$project->project_id}", // Perbaikan URL
            'target_id' => $project->creator_id
            ]);
        }
    }
}
