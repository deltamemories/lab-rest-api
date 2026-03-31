<?php

namespace App\Message;

readonly class PostCreatedMessage {
    public function __construct(
        public int $postId
    )
    {}
}
