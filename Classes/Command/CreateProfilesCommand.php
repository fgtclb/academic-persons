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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

final class CreateProfilesCommand extends Command
{
    public function __construct(
        private readonly ProfileCreateCommandService $profileCreateCommandService,
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
        $includePids = $this->getPidListOption($input, 'include-pids');
        $excludePids = $this->getPidListOption($input, 'exclude-pids');
        if ($includePids === null || $excludePids === null) {
            $output->writeln('<error>--include-pids and --exclude-pids take a comma-separated list of page uids.</error>');
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

    /**
     * The same reading as `academic:cleanupprofiles`: a mistyped page list must not
     * silently widen or narrow the run, so any part that is no page uid makes the
     * list invalid.
     *
     * @return int[]|null null for a list with a part that is no page uid
     */
    private function getPidListOption(InputInterface $input, string $option): ?array
    {
        $pids = [];
        foreach (GeneralUtility::trimExplode(',', (string)$input->getOption($option), true) as $part) {
            if (!MathUtility::canBeInterpretedAsInteger($part) || (int)$part < 0) {
                return null;
            }
            $pids[] = (int)$part;
        }
        return array_values(array_unique($pids));
    }
}
