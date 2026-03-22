<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class UpdateCommentDto {
    public function __construct(
        #[Assert\NotBlank(allowNull: true)]
        #[Assert\Length(min: 3)]
        public ?string $content = null,

        #[Assert\NotBlank(allowNull: true)]
        public ?int $post = null
    )
    {}
}
