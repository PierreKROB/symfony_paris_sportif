<?php

namespace App\Security\Voter;

use App\Entity\SportEvent;
use App\Entity\User;
use App\Service\SportEventService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SportEventVoter extends Voter
{
    const PUBLISH = 'EVENT_PUBLISH';
    const CLOSE   = 'EVENT_CLOSE';
    const RESULT  = 'EVENT_RESULT';
    const CANCEL  = 'EVENT_CANCEL';
    const DELETE  = 'EVENT_DELETE';

    public function __construct(private SportEventService $sportEventService) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::PUBLISH, self::CLOSE, self::RESULT, self::CANCEL, self::DELETE])
            && $subject instanceof SportEvent;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) return false;

        $isManager = in_array('ROLE_MANAGER', $token->getRoleNames())
                  || in_array('ROLE_ADMIN',   $token->getRoleNames());

        if (!$isManager) return false;

        /** @var SportEvent $event */
        $event = $subject;

        return match($attribute) {
            self::PUBLISH => $event->getStatus() === SportEvent::STATUS_DRAFT,
            self::CLOSE   => $event->getStatus() === SportEvent::STATUS_PUBLISHED,
            self::RESULT  => $event->getStatus() === SportEvent::STATUS_CLOSED,
            self::CANCEL  => in_array($event->getStatus(), [SportEvent::STATUS_DRAFT, SportEvent::STATUS_PUBLISHED]),
            self::DELETE  => $this->sportEventService->canDelete($event),
            default       => false,
        };
    }
}
