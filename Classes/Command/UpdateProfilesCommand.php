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

/**
 * @internal This command is for internal use and may change without notice.
 */
final class UpdateProfilesCommand extends Command
{
    public function __construct(
        private readonly ProfileUpdateCommandService $profileUpdateCommandService,
        private readonly PageListParser $pageListParser,
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
        $includePids = $this->pageListParser->parse($input->getOption('include-pids'));
        $excludePids = $this->pageListParser->parse($input->getOption('exclude-pids'));
        if ($includePids === null || $excludePids === null) {
            $output->writeln('<error>' . PageListParser::ERROR_MESSAGE . '</error>');
            return Command::INVALID;
        }
        $updated = $this->profileUpdateCommandService->execute(new ProfileUpdateCommandDto(
            includePids: $includePids,
            excludePids: $excludePids,
        ));
        $output->writeln(sprintf('Profiles of %d frontend user(s) updated.', $updated));
        return Command::SUCCESS;
    }
}
