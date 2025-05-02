<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:user-status-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pengecekan Status User';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // User yang statusnya "reported" lebih dari 7 hari dan tidak memiliki reported case yang ter-checklist
        $userReported = User::with('reportedCases')
                            ->where('status', 'reported')
                            ->where('status_date', '<=', now()->subDays(7)) // Perbaikan
                            ->where(function ($query) {
                                $query->doesntHave('reportedCases') // Tidak punya reported case sama sekali
                                      ->orWhereHas('reportedCases', function ($subQuery) {
                                          $subQuery->where('checked', false); // Semua reported case memiliki checked = false
                                      });
                            })
                            ->get();

        // Update status user ke status sebelumnya
        foreach ($userReported as $user) {
            $statusBefore = $user->user_verified ? 'verified' : 'active';
            $user->update([
                'status' => $statusBefore
            ]);

            $user->syncRoles($statusBefore);
        }

        //Ambil user yang suspended date nya melebihi waktu sekarang
        $userSuspended = User::where('status', 'suspended')
                               ->whereDate('suspended_date', '<=', now())
                               ->get();

        foreach ($userSuspended as $user) {
            $statusBefore = $user->user_verified ? 'verified' : 'active';
            $user->update([
                'status' => $statusBefore
            ]);

            $user->syncRoles($statusBefore);
        }
    }
}
