<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateCommentDto {
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3)]
        public string $content,

        #[Assert\NotBlank]
        public int $post
    )
    {}
}
