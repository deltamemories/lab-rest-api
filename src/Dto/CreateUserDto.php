<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateUserDto {
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[Assert\Email()]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $password
    )
    {}
}
