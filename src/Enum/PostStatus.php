<?php
namespace App\Enum;

enum PostStatus: string {
    case DRAFT = 'draft';
    case RELEASE = 'release';
}
