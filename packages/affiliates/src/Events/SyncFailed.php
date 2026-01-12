<?php

namespace Holibob\Affiliates\Events;

use App\Models\AffiliateProvider;
use App\Models\SyncLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public AffiliateProvider $affiliateProvider,
        public SyncLog $syncLog,
        public \Exception $exception
    ) {
    }
}
