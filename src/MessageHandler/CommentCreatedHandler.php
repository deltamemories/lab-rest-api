<?php

namespace App\MessageHandler;

use App\Message\CommentCreatedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CommentCreatedHandler {
    public function __construct(
        private LoggerInterface $logger
    )
    {}

    public function __invoke(CommentCreatedMessage $message) {
        $this->logger->info("Entity Comment created with id: {$message->commentId}");
    }
}