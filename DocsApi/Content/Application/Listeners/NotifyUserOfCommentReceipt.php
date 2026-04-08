<?php

namespace Src\Content\Application\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Src\Content\Application\Events\CommentCreated;
use Src\Content\Application\Mail\NewCommentUserMail;

class NotifyUserOfCommentReceipt implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CommentCreated $event): void
    {
        // Notificar o autor do comentário
        Mail::to($event->comment->user->email)->send(new NewCommentUserMail($event->comment));
    }
}
