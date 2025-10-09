<?php

namespace App\Service;
use App\Entity\Subscription;
use App\Entity\User;

class SubscriptionService
{
    public function isDemo(User $user): bool
    {
        $subscriptions = $user->getSubscriptions()->toArray();

        /** @var Subscription $subscription */
        $subscription = end($subscriptions);

        if ($user->getTrialEndedAt() >= new \DateTime() OR ($subscription instanceof Subscription AND$subscription->isActive() === true)) {
            return false;
        }

        return true;
    }
}
