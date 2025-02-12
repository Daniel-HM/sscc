<?php

namespace App\Http\Controllers;

use App\Models\Pakbonnen;
use Carbon\Carbon;
use Exception;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


use Webklex\IMAP\Facades\Client;

class MailboxController extends Controller
{

    public function __construct(private readonly PakbonController $pakbonController, private readonly CsvController $csvController)
    {
    }

    /**
     * @throws Exception
     */
    public function checkMailbox()
    {

        try {
            // Connect to the mailbox
            $client = Client::account('default');
            $client->connect();

            // Access the inbox
            $inbox = $client->getFolder('INBOX');

            // Retrieve emails
            $messages = $inbox->query()->all()->get();


            if (count($messages) > 0) {
                Log::info('Retrieved messages from the inbox.', ['message_count' => count($messages)]);
                // Process messages, store attachments, convert .xlsx to .csv
                $this->processMessages($messages);
            } else {
                Log::info('No messages from the inbox, skipping.', ['message_count' => count($messages)]);
            }
        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('An error occurred while checking the mailbox.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }


        // Check if pakbon files have moved, make a note in DB

        return app(Pipeline::class)
            ->send(new \stdClass())
            ->through([
                function($passable, $next) {
                    $this->pakbonController->checkForPakbonFiles(); // Just execute
                    return $next($passable); // Always continue
                },
                function($passable, $next) {
                    $this->csvController->convertXlsxToCsv();
                    return $next($passable);
                },
                function($passable, $next) {
                    $this->csvController->processCsvFiles();
                    return $next($passable);
                },
                function($passable, $next) {
                    $this->pakbonController->moveProcessedFilesToArchive();
                    return $next($passable);
                }
            ])
            ->thenReturn();
    }

    protected function processMessages($messages)
    {
        Log::info('Processing messages.', ['message_count' => count($messages)]);
        foreach ($messages as $message) {
            try {
                $from = $message->getFrom()[0]->mail;
                if ($this->isTrustedEmail($from) && $message->hasAttachments()) {
                    $this->handleTrustedEmail($message);
                }
            } catch (Exception $e) {
                // Log errors for individual message processing
                Log::error('Error processing message.', [
                    'error' => $e->getMessage(),
                    'message_id' => $message->getId(),
                ]);
            }

        }
    }

    protected function handleTrustedEmail($message): void
    {
        try {
            // Store attachments and handle the message
            if ($this->storeAttachments($message->getAttachments())) {
                $this->setAsSeen($message);
                $this->moveMessage($message, env('IMAP_DONE_FOLDER'));
                Log::info('Trusted email processed successfully.', [
                    'message_id' => $message->getId(),
                    'attachments_count' => $message->getAttachments()->count(),
                ]);
            }
        } catch (Exception $e) {
            // Log errors during trusted email handling
            Log::error('Error handling trusted email.');
        }
    }


    protected function isTrustedEmail($from): bool
    {
        return $from == env('TRUSTED_EMAIL_ADDRESS') or $from == env('TRUSTED_EMAIL_ADDRESS2');
    }

    /**
     *  Store attachments, convert .xlsx to .csv
     * */
    private function storeAttachments($attachments): bool
    {
        foreach ($attachments as $attachment) {
            $fileName = $attachment->getName();

            // Check if the filename matches the criteria
            if ($this->isValidAttachment($fileName)) {
                // Clean the filename and extract the base name
                $cleanedFilename = $this->cleanFileName($fileName); // name.ext
                $baseName = $this->extractBaseName($cleanedFilename); // name

                // Define the directory path
                $directory = $baseName;

                // Check if entry already exists
                if (Pakbonnen::where('naam', $baseName)->count() === 0) {
                    // Create directory if it does not exist
                    $this->ensureDirectoryExists($directory);
                    if (!Storage::disk('local')->exists($directory . '/' . $cleanedFilename)) {
                        $this->saveAttachment($directory, $cleanedFilename, $attachment->getContent());
                        Log::info('File ' . $fileName . ' saved to ' . $directory);
                    } else {
                        Log::info('File ' . $fileName . ' already exists.');
                    }
                } else {
                    Log::info('Pakbon ' . $baseName . ' already exists, skipping');
                }
            }
        }
        Log::info('Successfully saved attachments.');
        return true;
    }


    /**
     * Check if the attachment filename is valid.
     */
    private function isValidAttachment($fileName): bool
    {
        return preg_match('/SPS-\d+ uitgebreid\.xlsx/', $fileName) || preg_match('/SPS-\d+\.pdf/', $fileName);
    }


    /**
     * Remove ' uitgebreid' from the filename.
     */
    private function cleanFileName($fileName): string
    {
        return str_replace(' uitgebreid', '', $fileName);
    }

    /**
     * Extract the base name (e.g., 'SPS-00145524') from the filename.
     */
    private function extractBaseName($fileName): string
    {
        return preg_replace('/\.(xlsx|pdf)$/', '', $fileName);
    }

    /**
     * Ensure the directory exists.
     */
    private function ensureDirectoryExists($directory): void
    {
        if (!Storage::disk('local')->exists($directory)) {
            Storage::disk('local')->makeDirectory($directory);
        }
    }

    /**
     * Save the attachment to the specified directory.
     */
    private function saveAttachment($directory, $fileName, $content): bool
    {
        try {
            Storage::disk('local')->put($directory . '/' . $fileName, $content);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to save attachment: ' . $e->getMessage());
            return false;
        }
    }

    private function setAsSeen($message): void
    {
        $message->setFlag('Seen');
    }

    private function moveMessage($message, $destination): void
    {
        try {
            $message->move($destination);
            Log::info('Message with subject ' . $message->getSubject()[0] . ' from ' . $message->getFrom()[0]->mail . ' has been moved to ' . $destination);
        } catch (Exception $e) {
            Log::error('Something went wrong', [
                'error' => $e->getMessage(),
            ]);
        }
    }


}
