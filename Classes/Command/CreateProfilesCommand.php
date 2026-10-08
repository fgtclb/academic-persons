<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Command;

use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileCreateCommandDto;
use FGTCLB\AcademicPersons\Service\ProfileCreateCommandService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

final class CreateProfilesCommand extends Command
{
    public function __construct(
        private readonly ProfileCreateCommandService $profileCreateCommandService,
        private readonly PageListParser $pageListParser,
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command create profiles for all frontend users that do not have a profile yet but should have one.')
            ->addOption(
                'exclude-pids',
                'e',
                InputOption::VALUE_REQUIRED,
                'Comma-separated list of PIDs to exclude',
                null
            )
            ->addOption(
                'include-pids',
                'i',
                InputOption::VALUE_REQUIRED,
                'Comma-separated list of PIDs to include',
                null
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $includePids = $this->pageListParser->parse($input->getOption('include-pids'));
        $excludePids = $this->pageListParser->parse($input->getOption('exclude-pids'));
        if ($includePids === null || $excludePids === null) {
            $output->writeln('<error>' . PageListParser::ERROR_MESSAGE . '</error>');
            return Command::INVALID;
        }
        $created = $this->profileCreateCommandService->execute(
            new ProfileCreateCommandDto(
                includePids: $includePids,
                excludePids: $excludePids,
            ),
        );
        $output->writeln(sprintf('%d profile(s) created.', $created));
        if ($created === 0 && !$this->isAutoCreateProfilesEnabled()) {
            $output->writeln(
                'The automatic profile creation is disabled, so the default profile factory creates no profile.'
                . ' Enable profile.autoCreateProfiles in the extension configuration of academic_persons.'
                . ' A profile factory chosen through the ChooseProfileFactoryEvent may ignore the option.'
            );
        }
        return Command::SUCCESS;
    }

    private function isAutoCreateProfilesEnabled(): bool
    {
        try {
            return (int)$this->extensionConfiguration->get('academic_persons', 'profile/autoCreateProfiles') !== 0;
        } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException) {
            return false;
        }
    }
}
