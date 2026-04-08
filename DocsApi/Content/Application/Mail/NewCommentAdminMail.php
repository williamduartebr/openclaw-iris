<?php

namespace Src\Content\Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Src\Content\Domain\Models\Comment;

class NewCommentAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Comment $comment)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Novo Comentário (Review Necessário): '.$this->comment->article->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'content::mail.new-comment-admin',
            with: [
                'approveUrl' => \Illuminate\Support\Facades\URL::signedRoute('admin.comments.review.approve', ['comment' => $this->comment->id]),
                'disapproveUrl' => \Illuminate\Support\Facades\URL::signedRoute('admin.comments.review.disapprove', ['comment' => $this->comment->id]),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
