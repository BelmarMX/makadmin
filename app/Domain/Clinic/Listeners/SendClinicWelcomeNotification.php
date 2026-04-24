<?php

namespace App\Domain\Clinic\Listeners;

use App\Domain\Clinic\Events\ClinicCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendClinicWelcomeNotification implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(ClinicCreated $event): void
    {
        // TODO task 19: send WhatsApp / email welcome to clinic contact
    }
}
