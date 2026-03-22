<?php

namespace App\Security\Voter;

use App\Entity\Comment;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class CommentVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        $supportsAttribute = in_array($attribute, ['COMMENT_EDIT', 'COMMENT_DELETE', 'COMMENT_VIEW']);

        $supportsSubject = $subject instanceof Comment;

        return $supportsAttribute && $supportsSubject;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Comment $comment */
        $comment = $subject;

        return match($attribute) {
            'COMMENT_EDIT', 'COMMENT_DELETE', 'COMMENT_VIEW' => $this->canManage($comment, $user),
            default => false,
        };
    }

    private function canManage(Comment $comment, User $user) {
        return $user === $comment->getAuthor();
    }
}
