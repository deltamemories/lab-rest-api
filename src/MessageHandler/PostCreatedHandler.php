<?php

namespace App\MessageHandler;

use App\Message\PostCreatedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PostCreatedHandler {
    public function __construct(
        private LoggerInterface $logger
    )
    {}

    public function __invoke(
        PostCreatedMessage $message
    )
    {
        $this->logger->info("Entity Post created with id: {$message->postId}");
    }
}
