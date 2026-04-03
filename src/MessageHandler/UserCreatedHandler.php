<?php

namespace App\MessageHandler;


use App\Message\UserCreatedMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UserCreatedHandler {
    public function __construct(
        private LoggerInterface $logger
    )
    {}

    public function __invoke(
        UserCreatedMessage $message
    )
    {
        $this->logger->info("Entity User created with id: {$message->userId}");
    }
}
