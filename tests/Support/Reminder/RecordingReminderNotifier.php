<?php

declare(strict_types=1);

namespace App\Tests\Support\Reminder;

use App\Domain\Reminder\ReminderLog;
use App\Domain\Reminder\ReminderNotifier;

final class RecordingReminderNotifier implements ReminderNotifier
{
    /** @var list<ReminderLog> */
    public array $sent = [];

    public function send(ReminderLog $reminder): void
    {
        $this->sent[] = $reminder;
    }
}
