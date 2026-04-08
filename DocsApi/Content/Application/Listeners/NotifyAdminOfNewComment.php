<?php

namespace Src\Content\Application\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Src\AdminArea\Domain\Models\Admin;
use Src\Content\Application\Events\CommentCreated;
use Src\Content\Application\Mail\NewCommentAdminMail;

class NotifyAdminOfNewComment implements ShouldQueue
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
        $admins = Admin::query()
            ->whereNotNull('email')
            ->get(['email']);

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new NewCommentAdminMail($event->comment));
        }
    }
}
