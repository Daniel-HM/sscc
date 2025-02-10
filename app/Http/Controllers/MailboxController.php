<?php

namespace App\Http\Controllers;

use App\Http\Controllers\PakbonController;
use App\Models\Pakbonnen;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\FastExcel;
use Smalot\PdfParser\Parser;
use Webklex\IMAP\Facades\Client;

class MailboxController extends Controller
{

    private $directory;
    private $cleanedFilename;

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

            Log::info('Retrieved messages from the inbox.', ['message_count' => count($messages)]);

            // Process messages, store attachments, convert .xlsx to .csv
            $this->processMessages($messages);

        } catch (Exception $e) {
            // Log any unexpected errors
            Log::error('An error occurred while checking the mailbox.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
        Log::info('Successfully processed messages');
        $pakbonController = new PakbonController;
        $pakbonController->findCsvFiles();
        // return response()->json(['error' => 'An error occurred while processing the mailbox.'], 500);
        return view('mailbox');
    }

    protected function processMessages($messages)
    {
        foreach ($messages as $message) {
            try {
                $from = $message->getFrom()[0]->mail;
                $this->extractEmailData($message, $from);
                if ($this->isTrustedEmail($from) && $message->hasAttachments()) {
                    $this->handleTrustedEmail($message);
                } else {
                    $this->deleteMessage($message);
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
                $this->createPakbonEntryDB($this->getDateFromFirstPageOfPdf($this->directory, $this->cleanedFilename));
                $this->convertXlsxToCsv($this->directory, $this->cleanedFilename);

                Log::info('Trusted email processed successfully.', [
                    'message_id' => $message->getId(),
                    'attachments_count' => $message->getAttachments()->count(),
                ]);
            } else {
                $this->moveMessage($message, env('IMAP_POSSIBLE_DUPE_FOLDER'));
            }
        } catch (Exception $e) {
            // Log errors during trusted email handling
            Log::error('Error handling trusted email.', [
                'error' => $e->getMessage(),
                'message_id' => $message->getId(),
            ]);
        }
    }

    protected function extractEmailData($message, $from)
    {
        return [
            'subject' => $message->getSubject()[0],
            'from' => $from,
            'date' => Carbon::parse($message->getDate()[0])->format('d-m-Y H:i'),
            'attachments' => [],
        ];
    }

    protected function createPakbonEntryDB($date)
    {

        try {
            // Create a new Pakbon record
            Pakbonnen::create([
                'naam' => $this->directory,
                'movedToFolder' => true,
                'pakbonDatum' => Carbon::parse($date)->format('Y-m-d'),
            ]);

            Log::info('Pakbon entry created successfully.', ['directory' => $this->directory]);

        } catch (Exception $e) {
            // Log errors during Pakbon creation
            Log::error('Error creating Pakbon DB entry.', [
                'error' => $e->getMessage(),
                'directory' => $this->directory,
            ]);
        }
    }

    protected function setConvertedToTrue($entry)
    {
        try {
            Pakbonnen::where('naam', $entry)->update(['isConverted' => 1]);

            Log::info('Pakbon entry "isConverted" set to true for ' . $entry);

        } catch (Exception $e) {
            Log::error('Error updating pakbon entry - converted to true failed', [
                'error' => $e->getMessage(),
                'entry' => $entry,
            ]);
        }
    }

    protected function deleteMessage($message): void
    {
        try {
            // Delete the message
            $message->move($folder_path = "Trash");

            Log::info('Message deleted successfully.', [
                'subject' => $message->getSubject()[0],
                'from' => $message->getFrom()[0]->mail,
            ]);

        } catch (Exception $e) {
            // Log errors during message deletion
            Log::error('Error deleting message.', [
                'error' => $e->getMessage(),
                'message_id' => $message->getId(),
            ]);
        }
    }

    protected function isTrustedEmail($from)
    {
        return $from == env('TRUSTED_EMAIL_ADDRESS') or $from == env('TRUSTED_EMAIL_ADDRESS2');
    }

    /**
     *  Store attachments, convert .xlsx to .csv
     * */
    private function storeAttachments($attachments): bool
    {
        $processedAnyAttachments = false;
        $allProcessedSuccessfully = true;
        foreach ($attachments as $attachment) {
            $fileName = $attachment->getName();

            // Check if the filename matches the criteria
            if ($this->isValidAttachment($fileName)) {
                $processedAnyAttachments = true;
                // Clean the filename and extract the base name
                $cleanedFilename = $this->cleanFileName($fileName);
                $baseName = $this->extractBaseName($cleanedFilename);

                // Define the directory path
                $directory = $baseName;
                $this->directory = $directory;
                $this->cleanedFilename = $cleanedFilename;

                // Check if entry already exists
                if (Pakbonnen::where('naam', $baseName)->count() === 0) {
                    // Create directory if it does not exist
                    $this->ensureDirectoryExists($directory);
                    if (!$this->saveAttachment($directory, $cleanedFilename, $attachment->getContent())) {
                        $allProcessedSuccessfully = false;
                    }
                    Log::info('File ' . $fileName . ' saved to ' . $directory);

                } else {
                    Log::info('Pakbon ' . $baseName . ' already exists, skipping');
                }
            }
        }
        return $allProcessedSuccessfully && $processedAnyAttachments;
    }

    private function convertXlsxToCsv($directory, $file)
    {
        $baseName = $this->extractBaseName($file);
        $inputFile = storage_path('app/private/' . $directory . '/' . $baseName . '.xlsx');
        $outputFile = storage_path('app/private/' . $directory . '/' . $baseName . '.csv');

        // Read the .xlsx file into a collection
        $collection = (new FastExcel)->import($inputFile);

        // Export the collection to a .csv file
        $convert = (new FastExcel($collection))->export($outputFile);
        if ($convert) {
            $this->setConvertedToTrue($directory);
            Log::info($baseName . '.xlsx successfully converted to ' . $baseName . '.csv');
        }

    }

    /**
     * Check if the attachment filename is valid.
     */
    private function isValidAttachment($fileName)
    {
        return preg_match('/SPS-\d+ uitgebreid\.xlsx/', $fileName) || preg_match('/SPS-\d+\.pdf/', $fileName);
    }

    /**
     * Remove ' uitgebreid' from the filename.
     */
    private function cleanFileName($fileName)
    {
        return str_replace(' uitgebreid', '', $fileName);
    }

    /**
     * Extract the base name (e.g., 'SPS-00145524') from the filename.
     */
    private function extractBaseName($fileName)
    {
        return preg_replace('/\.(xlsx|pdf)$/', '', $fileName);
    }

    /**
     * Ensure the directory exists.
     */
    private function ensureDirectoryExists($directory)
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
            $message->move($folder_path = $destination);
            Log::info('Message with subject ' . $message->getSubject()[0] . ' from ' . $message->getFrom()[0]->mail . ' has been moved to ' . $destination);
        } catch (Exception $e) {
            Log::error('Something went wrong', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getDateFromFirstPageOfPdf($directory, $file)
    {
        Log::info('Retrieving date from PDF file: ' . $file);
        // Path to the PDF file
        $pdfPath = storage_path('app/private/' . $directory . '/' . $file);

        // Initialize the PDF parser
        $parser = new Parser();
        $pdf = $parser->parseFile($pdfPath);

        // Get the first page
        $pages = $pdf->getPages();
        $firstPage = $pages[0];

        // Extract text from the first page
        $text = $firstPage->getText();

        // Use a regular expression to find a date in the text
        $datePattern = '/(\d{2}-\d{2}-\d{4})\S*/';
        preg_match($datePattern, $text, $matches);

        // Check if a date was found
        if (!empty($matches)) {
            $date = $matches[1];
            return $date;
        } else {
            return false;
        }
    }

}
