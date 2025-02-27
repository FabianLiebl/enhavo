<?php
/**
 * @author blutze-media
 * @since 2022-07-13
 */

/**
 * @author blutze-media
 * @since 2022-07-07
 */

namespace Enhavo\Bundle\UserBundle\EventListener;

use DateTime;
use Enhavo\Bundle\UserBundle\Configuration\ConfigurationProvider;
use Enhavo\Bundle\UserBundle\Event\UserEvent;
use Enhavo\Bundle\UserBundle\Exception\TooManyLoginAttemptsException;
use Enhavo\Bundle\UserBundle\Model\UserInterface;
use Enhavo\Bundle\UserBundle\User\UserManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class TooManyLoginAttemptsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserManager $userManager,
        private ConfigurationProvider $configurationProvider,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvent::PRE_AUTH => 'onPreAuth',
            UserEvent::LOGIN_FAILURE => 'onLoginFailure',
            UserEvent::LOGIN_SUCCESS => 'onLoginSuccess',
        ];
    }

    public function onLoginFailure(UserEvent $event): void
    {
        $user = $event->getUser();
        $exception = $event->getException();

        if ($user instanceof UserInterface && $exception instanceof BadCredentialsException) {
            $user->setLastFailedLoginAttempt(new DateTime());
            $user->setFailedLoginAttempts(1 + $user->getFailedLoginAttempts());
            $this->userManager->update($user);
        }
    }

    public function onPreAuth(UserEvent $event): void
    {
        $user = $event->getUser();

        if ($user instanceof UserInterface && $this->hasTooManyLoginAttempts($user)) {
            $exception = new TooManyLoginAttemptsException('Too many login attempts');
            $exception->setUser($event->getUser());
            $event->setException($exception);
            $this->userManager->resetPassword($user, $this->configurationProvider->getResetPasswordRequestConfiguration());
        }
    }

    public function onLoginSuccess(UserEvent $event): void
    {
        $user = $event->getUser();

        if ($user instanceof UserInterface) {
            $user->setLastFailedLoginAttempt(null);
            $user->setFailedLoginAttempts(0);
            $this->userManager->update($user);
        }
    }

    public function hasTooManyLoginAttempts(UserInterface $user): bool
    {
        $loginConfiguration = $this->configurationProvider->getLoginConfiguration();

        return
            $loginConfiguration->getMaxFailedLoginAttempts() &&
            $user->getFailedLoginAttempts() >= $loginConfiguration->getMaxFailedLoginAttempts()
        ;
    }
}
