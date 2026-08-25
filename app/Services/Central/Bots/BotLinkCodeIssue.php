<?php

namespace App\Services\Central\Bots;

use App\Models\Central\BotLinkCode;

class BotLinkCodeIssue
{
    public function __construct(
        public readonly string $code,
        public readonly BotLinkCode $linkCode,
    ) {}
}
