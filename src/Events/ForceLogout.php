<?php

namespace HasinHayder\TyroLogin\Events;

class ForceLogout {
    public function __construct(
        public readonly int $userId
    ) {}
}
