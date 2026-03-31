<?php

namespace App\Jobs;

use App\Models\Service;
use App\Models\ServiceSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class GenerateDocumentJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    protected $serviceId;
    protected $submissionId;

    public function __construct($serviceId, $submissionId)
    {
        $this->serviceId = $serviceId;
        $this->submissionId = $submissionId;
    }

    public function handle()
    {
        $service = Service::findOrFail($this->serviceId);
        $submission = ServiceSubmission::findOrFail($this->submissionId);

        $templatePath = storage_path('app/public/' . $service->document);

        if (!file_exists($templatePath)) {
            return;
        }

        $tempPath = storage_path('app/public/temp_' . time() . '.docx');
        copy($templatePath, $tempPath);

        $zip = new ZipArchive;
        if ($zip->open($tempPath) === true) {

            $xml = $zip->getFromName('word/document.xml');

            foreach ($submission->data as $key => $value) {
                $value = is_array($value) ? implode(', ', $value) : $value;
                $xml = str_replace('{' . $key . '}', $value, $xml);
            }

            $zip->addFromString('word/document.xml', $xml);
            $zip->close();

            $fileName = 'doc_' . time() . '_' . $submission->id . '.docx';
            $newPath = 'customer_documents/' . $fileName;
            $fullPath = storage_path('app/public/' . $newPath);

            if (!file_exists(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0775, true);
            }

            rename($tempPath, $fullPath);

            $submission->update([
                'document' => $newPath
            ]);
        }
    }
}
