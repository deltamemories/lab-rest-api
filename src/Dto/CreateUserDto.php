<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

readonly class CreateUserDto {
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[Assert\Email()]
        #[OA\Property(example: 'testuser@gmail.com')]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        #[OA\Property(example: 'strongpass')]
        public string $password
    )
    {}
}
