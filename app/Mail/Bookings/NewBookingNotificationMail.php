<?php

namespace App\Mail\Bookings;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewBookingNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Booking $booking,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New booking awaiting payment',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.bookings.admin',
            with: [
                'booking' => $this->booking,
                'service' => $this->booking->service,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
