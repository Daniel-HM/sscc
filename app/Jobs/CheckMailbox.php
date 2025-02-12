<?php
namespace App\Jobs;

use App\Http\Controllers\MailboxController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckMailbox implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1200;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mailboxController = app(MailboxController::class);
        Log::info('Starting check mailbox job.');
        $mailboxController->checkMailbox();

    }
}
