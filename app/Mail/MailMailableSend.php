<?php

namespace App\Mail;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;

class MailMailableSend extends Mailable
{
    use Queueable, SerializesModels;

    public $mailable;

    public $data;

    public $templateData;

    public $type;

    /**
     * Create a new message instance.
     */
    public function __construct($mailable, $data, $type = '')
    {
        $this->mailable = $mailable ?? '';
        $this->data = $data;
        $this->type = $type;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    // public function build()
    // {
    //     $this->templateData = $this->mailable->defaultNotificationTemplateMap->template_detail;

    //     foreach ($this->data as $key => $value) {
    //         $this->templateData = str_replace('[[ '.$key.' ]]', $this->data[$key], $this->templateData);
    //     }

    //     $message = $this->markdown('mail.markdown');

    //     return $message; //Send mail
    // }

    public function content()
    {
		$this->templateData = (string) $this->mailable->template_detail;

        foreach ($this->data as $key => $value) {
			// Only replace with scalar or stringable values to avoid type errors
			if (is_scalar($value)) {
				$replacement = (string) $value;
			} elseif (is_object($value) && method_exists($value, '__toString')) {
				$replacement = (string) $value;
			} else {
				continue;
			}

			$this->templateData = str_replace('[[ '.$key.' ]]', $replacement, $this->templateData);
        }

        return new Content(
			markdown: 'mail.markdown',
			with: ['templateData' => $this->templateData]
        );
    }

    public function attachments()
    {
        $files = [];
		if($this->type == 'complete_booking') {
			try {
				$template = setting('template') ?: 'template1';
				$viewName = "mail.invoice-templates.".$template;

				if (!view()->exists($viewName)) {
					$viewName = "mail.invoice-templates.template1";
				}

				$html = view($viewName, ['data' => $this->data])->render();
				$pdf = Pdf::loadHTML($html);

				$files[0] = Attachment::fromData(function() use($pdf) {
					return $pdf->output();
				}, 'Invoice.pdf')->withMime('application/pdf');
			} catch (\Throwable $e) {
				\Log::error('Invoice PDF generation failed: '.$e->getMessage());
			}
        }

        return $files;
    }
}
