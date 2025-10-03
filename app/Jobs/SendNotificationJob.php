<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 3;

    protected $userId;
    protected $title;
    protected $body;
    protected $data;
    protected $type;

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $title, $body, $data = [], $type = 'general')
    {
        $this->userId = $userId;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
        $this->type = $type;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $user = User::find($this->userId);

            if (!$user || !$user->notifications_enabled || !$user->fcm_token) {
                return;
            }

            $firebaseService = new FirebaseNotificationService();
            $firebaseService->sendToUser($user, $this->title, $this->body, $this->data);

            Log::info("Notification sent to user {$this->userId}: {$this->title}");
        } catch (\Exception $e) {
            Log::error("Failed to send notification to user {$this->userId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendNotificationJob failed for user {$this->userId}: " . $exception->getMessage());
    }
}
