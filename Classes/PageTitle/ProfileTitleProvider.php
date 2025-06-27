<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\PageTitle;

use FGTCLB\AcademicPersons\Controller\ProfileController;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Event\ProfileTitlePlaceholderReplacementEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\PageTitle\AbstractPageTitleProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Concrete page title provider implementation based on {@see AbstractPageTitleProvider},
 * providing simplified `setTitle()` method and a advanced `setFromProfile()` method to
 * allow format based setting possible.
 *
 * Used in {@see ProfileController::detailAction()} to set page title for displaced profile.
 */
final class ProfileTitleProvider extends AbstractPageTitleProvider
{
    public const DETAIL_PAGE_TITLE_FORMAT = '%%TITLE%% %%FIRST_NAME%% %%MIDDLE_NAME%% %%LAST_NAME%%';

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Set page title based on Profile values using the specified format,
     * supporting placeholder with syntax `%%UPPER_CASED_PROFILE_FIELD%%`
     * converted to camel-cased model getter `getUpperCasedProfileField()`
     * to retrieve replacement value.
     *
     * Note: Non-existing model getter will be not replaced and does not throw or log any errors.
     */
    public function setFromProfile(Profile $profile, string $format = self::DETAIL_PAGE_TITLE_FORMAT): void
    {
        // replace all `%%` surrounded values with model fields
        $title = (string)preg_replace_callback(
            pattern: '/%%([\w\d]+)%%/',
            callback: function (array $matches) use ($profile): string {
                $originalPlaceholder = $matches[1];
                $placeholder = $matches[1];
                $placeholder = $this->profileGetterPlaceholderReplacement($profile, $placeholder);
                $placeholder = $this->dispatchProfileTitleTagPlaceholderReplacementEvent($profile, $originalPlaceholder, $placeholder);
                return ($originalPlaceholder === $placeholder)
                    // no replacement processed, return full placeholder with surrounding percent
                    // signs to indicate unreplaced value
                    ? $matches[0]
                    // placeholder modified, return it
                    : $placeholder;
            },
            subject: $format,
        );
        // remove all leading and tailing spaces
        $title = trim($title, ' ');
        // ensure keeping only single spaces in content (replacing multi spaces with single space)
        $title = (string)preg_replace('/[[:blank:]]+/', ' ', $title);
        $this->setTitle($title);
    }

    private function profileGetterPlaceholderReplacement(Profile $profile, string $placeholder): string
    {
        $getterName = 'get' . str_replace('_', '', ucwords(mb_strtolower($placeholder), '_'));
        return method_exists($profile, $getterName)
            ? trim($profile->{$getterName}(), ' ')
            : $placeholder;
    }

    private function dispatchProfileTitleTagPlaceholderReplacementEvent(
        Profile $profile,
        string $placeholder,
        string $replacement,
    ): string {
        /** @var ProfileTitlePlaceholderReplacementEvent $event */
        $event = $this->getEventDispatcher()->dispatch(new ProfileTitlePlaceholderReplacementEvent(
            profile: $profile,
            placeholder: $placeholder,
            replacement: $replacement,
        ));
        return $event->getReplacement();
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return GeneralUtility::makeInstance(EventDispatcherInterface::class);
    }
}
