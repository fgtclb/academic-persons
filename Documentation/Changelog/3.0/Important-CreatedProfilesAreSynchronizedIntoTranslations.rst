.. _important-created-profiles-are-synchronized-into-translations:

================================================
Important: A created profile is translated again
================================================

Description
===========

:php:`Domain\Model\Profile::getIsTranslation()` answered by comparing two
Extbase properties with each other:

..  code-block:: php

    return $this->_localizedUid !== $this->uid;

Only :php:`Persistence\Generic\Mapper\DataMapper` writes those, and only while
it hydrates a database row. A profile that was built in PHP and then persisted
never passes through it - :php:`Persistence\Generic\Backend::insertObject()`
assigns `uid` and nothing else - so it holds a `uid` and an unset localized
uid, and the strict comparison answered "is a translation".

:php:`Profile\AbstractProfileFactory::createProfileForUser()` builds exactly
such an object: it persists the profile and announces it through
:php:`Event\AfterProfileUpdateEvent` without ever reading it back.

The method now asks the question the record answers itself. A profile that was
never persisted is not a translation of anything, and a persisted one is a
translation when the language it was read in is a translation language:

..  code-block:: php

    if ($this->_isNew()) {
        return false;
    }
    return $this->getLanguageUid() > 0;

That also settles `sys_language_uid = -1`. A record kept in all languages is
not a translation either, which the uid comparison could not express.

Impact
======

**A created profile is synchronized into its translations again.** The
:php:`Event\AfterProfileUpdateEvent` listener of EXT:academic_persons_edit
skips a profile that reports itself as a translation, because synchronization
runs from the default language record only. A profile created by
:bash:`academic:createprofiles`, or on frontend user login, was caught by that
gate and never translated, no matter how ``profile.allowedLanguages`` was
configured.

Profiles created before this change stay untranslated until something announces
them again. A run of :bash:`academic:updateprofiles` does that, and it was
never affected by the defect, because it reads its profiles through the
repository and they are therefore mapped.

The answer is unchanged for every profile that came out of the data mapper,
which is every profile the plugins and the backend work with. Only a profile
built in PHP is answered differently, and only one that carries a translation
language without a translation parent - a record Extbase cannot create, because
:php:`Persistence\Generic\Backend::insertObject()` writes `l10n_parent` as `0`
on every insert.

Affected Installations
======================

Every installation that enables the translation synchronization through the
``profile.allowedLanguages`` setting of EXT:academic_persons_edit and creates
profiles automatically, whether through the command or on frontend user login.
Installations that leave that setting empty see no change.

Installations calling :php:`Domain\Model\Profile::getIsTranslation()` from own
code get the corrected answer for an unpersisted and for a freshly persisted
profile as well.

.. index:: CLI, Database, Localization, PHP-API, ext:academic_persons
