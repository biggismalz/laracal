<?php

namespace App\Mail\Bookings;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingPendingPaymentMail extends Mailable
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
            subject: 'Thanks! Your booking is almost confirmed',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.bookings.pending',
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
