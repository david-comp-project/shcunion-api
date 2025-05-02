<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerificationEmailJob implements ShouldQueue
{
    use Queueable;


    protected $user;
    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->user->notify(new VerifyEmailNotification());

    }
}
