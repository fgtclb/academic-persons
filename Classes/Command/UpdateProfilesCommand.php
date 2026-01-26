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
        $this->profileUpdateCommandService->execute(new ProfileUpdateCommandDto(
            includePids: $this->getCommaSeparatedIntegerValueListOptionAsArrayOfIntegerValues($input, 'include-pids'),
            excludePids: $this->getCommaSeparatedIntegerValueListOptionAsArrayOfIntegerValues($input, 'exclude-pids'),
        ));
        return Command::SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param string $option
     * @return int[]
     */
    private function getCommaSeparatedIntegerValueListOptionAsArrayOfIntegerValues(InputInterface $input, string $option): array
    {
        if ($option === '') {
            return [];
        }
        $valuesStringList = $this->getOptionWithEmptyStringFallback($input, $option);
        $values = GeneralUtility::intExplode(',', $valuesStringList, true);
        $values = array_unique($values);
        $values = array_values($values);
        return $values;
    }

    private function getOptionWithEmptyStringFallback(InputInterface $input, string $option): mixed
    {
        if ($option === '' || !$input->hasOption($option)) {
            return '';
        }
        return (string)($input->getOption($option)) ?: '';
    }
}
