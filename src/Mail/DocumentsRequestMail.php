<?php

namespace Slsabil\ApplicationOnboarding\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentsRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $docs,
        public string $applicationFormLink,
        public ?string $note = null,
        public array $corrections = []
    ) {}

    public function build()
    {
        return $this->subject(__('Request for Missing Documents'))
            ->view('application-onboarding::emails.documents-request')
            ->with([
                'docs'               => $this->docs,
                'applicationFormLink'=> $this->applicationFormLink,
                'note'               => $this->note,
                'corrections'        => $this->corrections,
            ]);
    }
}
