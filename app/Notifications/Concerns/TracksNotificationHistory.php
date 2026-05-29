<?php

namespace App\Notifications\Concerns;

trait TracksNotificationHistory
{
    public ?int $notificationHistoryId = null;

    public function withHistoryId(int $historyId): static
    {
        $this->notificationHistoryId = $historyId;

        return $this;
    }
}
