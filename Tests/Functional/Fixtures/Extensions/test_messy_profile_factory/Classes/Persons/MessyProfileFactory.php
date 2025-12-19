<?php

declare(strict_types=1);

namespace TESTS\TestMessyProfileFactory\Persons;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Profile\AbstractProfileFactory;
use FGTCLB\AcademicPersons\Profile\ProfileFactory;
use FGTCLB\AcademicPersons\Profile\ProfileFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

#[Autoconfigure(public: true)]
final class MessyProfileFactory extends AbstractProfileFactory
{
    public function __construct(
        #[Autowire(service: ProfileFactory::class)]
        private readonly ProfileFactoryInterface $defaultProfileFactory,
    ) {}

    public function shouldCreateProfileForUser(FrontendUserAuthentication $frontendUserAuthentication): bool
    {
        return true;
    }

    protected function createProfileFromFrontendUser(array $frontendUserData): Profile
    {
        return (new \ReflectionMethod($this->defaultProfileFactory, 'createProfileFromFrontendUser'))
            ->invoke($this->defaultProfileFactory, $frontendUserData);
    }
}
