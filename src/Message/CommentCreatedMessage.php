<?php

namespace App\Message;


readonly class CommentCreatedMessage {
    public function __construct(
        public int $commentId
    )
    {}
}