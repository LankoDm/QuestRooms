<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailables\Attachment;

class BookingConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Booking $booking)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ваше бронювання підтверджено! | OneaQuests',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.booking_confirmed',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $pdf = Pdf::loadView('emails.booking_confirmed', ['booking' => $this->booking]);
        return [
            Attachment::fromData(fn () => $pdf->output(), "Ticket_Onea_Quests_{$this->booking->id}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
