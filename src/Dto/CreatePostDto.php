<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use App\Enum\PostStatus;

readonly class CreatePostDto {
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 255)]
        public string $title,

        #[Assert\NotBlank]
        public string $content,

        #[Assert\Choice(callback: [PostStatus::class, 'cases'])]
        public ?PostStatus $status = null,
    )
    {}
}
