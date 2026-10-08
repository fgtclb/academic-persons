<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Command;

use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileUpdateCommandDto;
use FGTCLB\AcademicPersons\Service\ProfileUpdateCommandService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @internal This command is for internal use and may change without notice.
 */
final class UpdateProfilesCommand extends Command
{
    public function __construct(
        private readonly ProfileUpdateCommandService $profileUpdateCommandService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command updates profiles for all frontend users that have a profile.')
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
        $updated = $this->profileUpdateCommandService->execute(new ProfileUpdateCommandDto(
            includePids: $includePids,
            excludePids: $excludePids,
        ));
        $output->writeln(sprintf('Profiles of %d frontend user(s) updated.', $updated));
        return Command::SUCCESS;
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
