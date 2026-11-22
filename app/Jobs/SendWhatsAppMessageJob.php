<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 10;

    public function __construct(
        public string $phone,
        public string $message,
        public ?int $notificationLogId = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsapp): void
    {
        $result = $whatsapp->sendText($this->phone, $this->message);

        if ($this->notificationLogId !== null) {
            NotificationLog::whereKey($this->notificationLogId)->update([
                'status' => $result['success'] ? 'sent' : 'failed',
                'error_message' => $result['success'] ? null : $result['message'],
                'sent_at' => $result['success'] ? now() : null,
            ]);
        }
    }
}
