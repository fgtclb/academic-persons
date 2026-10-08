<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Command;

use FGTCLB\AcademicPersons\Domain\Model\Dto\ProfileCleanupCandidate;
use FGTCLB\AcademicPersons\Provider\InactiveFrontendUserProfileProvider;
use FGTCLB\AcademicPersons\Service\DataHandlerExecutionContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hides or deletes the profiles whose linked frontend users are all disabled, past
 * their end time or deleted. The selection is {@see InactiveFrontendUserProfileProvider}.
 *
 * Every write is a DataHandler run of its own for one profile, acting as a synthetic
 * admin in the live workspace: `hidden` is `l10n_mode => exclude`, so the DataHandler
 * carries it into every translation, and a delete takes the translations and the
 * contracts with it. The history and the cache clearing follow as for a backend save.
 * A DataHandler save is not announced on this branch, so a hidden profile is not
 * either.
 *
 * The command never shows a profile again, and a profile that is hidden already is
 * not written a second time.
 *
 * @internal This command is for internal use and may change without notice.
 */
final class CleanupProfilesCommand extends Command
{
    private const TABLE = 'tx_academicpersons_domain_model_profile';

    private const DISABLED_ACTIONS = ['hide', 'keep'];
    private const DELETED_ACTIONS = ['delete', 'hide', 'keep'];

    public function __construct(
        private readonly InactiveFrontendUserProfileProvider $profileProvider,
        private readonly DataHandlerExecutionContext $executionContext,
        private readonly PageListParser $pageListParser,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
                'Looks at the profiles the synchronisation manages. Hides those whose frontend users are all'
                . ' disabled or past their end time, and deletes those whose frontend users are all deleted.'
                . ' Profiles excluded from the synchronisation, profiles without a frontend user, profiles linked'
                . ' to another login and profiles with an active frontend user are never touched.'
                . ' A hidden profile is never shown again by this command. Run it with --dry-run first.'
            )
            ->addOption(
                'disabled',
                null,
                InputOption::VALUE_REQUIRED,
                'What happens to a profile whose frontend users are all disabled or past their end time: hide or keep',
                'hide'
            )
            ->addOption(
                'deleted',
                null,
                InputOption::VALUE_REQUIRED,
                'What happens to a profile whose frontend users are all deleted: delete, hide or keep',
                'delete'
            )
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
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'List the profiles that would be hidden or deleted, and write nothing'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $disabledAction = (string)$input->getOption('disabled');
        $deletedAction = (string)$input->getOption('deleted');
        if (!in_array($disabledAction, self::DISABLED_ACTIONS, true)) {
            $output->writeln(sprintf('<error>--disabled must be one of: %s.</error>', implode(', ', self::DISABLED_ACTIONS)));
            return Command::INVALID;
        }
        if (!in_array($deletedAction, self::DELETED_ACTIONS, true)) {
            $output->writeln(sprintf('<error>--deleted must be one of: %s.</error>', implode(', ', self::DELETED_ACTIONS)));
            return Command::INVALID;
        }
        $includePids = $this->pageListParser->parse($input->getOption('include-pids'));
        $excludePids = $this->pageListParser->parse($input->getOption('exclude-pids'));
        if ($includePids === null || $excludePids === null) {
            $output->writeln('<error>' . PageListParser::ERROR_MESSAGE . '</error>');
            return Command::INVALID;
        }
        $dryRun = (bool)$input->getOption('dry-run');

        $candidates = $this->profileProvider->findCandidates($includePids, $excludePids);
        $changed = 0;
        $failed = 0;
        foreach ($candidates as $candidate) {
            $action = $candidate->allFrontendUsersDeleted ? $deletedAction : $disabledAction;
            if ($action === 'keep' || ($action === 'hide' && $candidate->hidden)) {
                continue;
            }
            if ($dryRun) {
                $output->writeln(sprintf(
                    '%s: would be %s',
                    $this->describe($candidate),
                    $action === 'delete' ? 'deleted' : 'hidden',
                ));
                $changed++;
                continue;
            }
            $errors = $action === 'delete' ? $this->delete($candidate->uid) : $this->hide($candidate->uid);
            if ($errors !== []) {
                $output->writeln(sprintf(
                    '<error>%s: not %s. %s</error>',
                    $this->describe($candidate),
                    $action === 'delete' ? 'deleted' : 'hidden',
                    OutputFormatter::escape(implode(' ', $errors)),
                ));
                $failed++;
                continue;
            }
            $output->writeln(sprintf('%s: %s', $this->describe($candidate), $action === 'delete' ? 'deleted' : 'hidden'));
            $changed++;
        }

        if ($dryRun) {
            $output->writeln(sprintf('Dry run: %d profile(s) would change. Nothing was written.', $changed));
            return Command::SUCCESS;
        }
        $output->writeln(sprintf('%d profile(s) changed.', $changed));
        if ($failed > 0) {
            $output->writeln(sprintf('<error>%d profile(s) could not be changed.</error>', $failed));
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

    /**
     * @return list<string> the errors the DataHandler reported
     */
    private function hide(int $profileUid): array
    {
        return $this->runDataHandler([self::TABLE => [$profileUid => ['hidden' => 1]]], []);
    }

    /**
     * @return list<string> the errors the DataHandler reported
     */
    private function delete(int $profileUid): array
    {
        return $this->runDataHandler([], [self::TABLE => [$profileUid => ['delete' => 1]]]);
    }

    /**
     * @param array<string, array<int, array<string, int>>> $dataMap
     * @param array<string, array<int, array<string, int>>> $commandMap
     * @return list<string>
     */
    private function runDataHandler(array $dataMap, array $commandMap): array
    {
        $errors = [];
        $this->executionContext->runAsLiveBackendUser(
            function (BackendUserAuthentication $backendUser) use ($dataMap, $commandMap, &$errors): void {
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start($dataMap, $commandMap, $backendUser);
                $dataHandler->process_datamap();
                $dataHandler->process_cmdmap();
                $errors = array_values(array_map('strval', $dataHandler->errorLog));
            },
        );
        return $errors;
    }

    private function describe(ProfileCleanupCandidate $candidate): string
    {
        return $candidate->label === ''
            ? sprintf('Profile %d', $candidate->uid)
            : sprintf('Profile %d "%s"', $candidate->uid, OutputFormatter::escape($candidate->label));
    }
}
