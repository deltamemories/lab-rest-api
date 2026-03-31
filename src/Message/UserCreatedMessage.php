<?php

namespace App\Message;

readonly class UserCreatedMessage {
    public function __construct(
        public int $usertId
    )
    {}
}