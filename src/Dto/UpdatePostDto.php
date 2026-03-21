<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use App\Enum\PostStatus;

readonly class UpdatePostDto {
    public function __construct(
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(min: 3, max: 255)]
        public ?string $title = null,

        #[Assert\NotBlank(allowNull: true)]
        public ?string $content = null,

        #[Assert\Choice(callback: [PostStatus::class, 'cases'])]
        public ?PostStatus $status = null,
    )
    {}
}
