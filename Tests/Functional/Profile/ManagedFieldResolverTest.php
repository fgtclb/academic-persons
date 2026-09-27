<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Profile;

use FGTCLB\AcademicPersons\Domain\Model\Address;
use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Email;
use FGTCLB\AcademicPersons\Domain\Model\PhoneNumber;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Profile\ManagedFieldResolver;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

/**
 * Which fields of a stored person record a synchronisation owns. The fixture
 * extension `test_managed_fields` manages the profile title and website, the
 * contract position, the e-mail address and the phone number type, and
 * nothing of the physical addresses.
 *
 * The records are read from the database as they are stored, the shape the
 * import writer will hand in. The backend form hands in its own row, which
 * `ManagedFieldsReadOnlyTest` covers.
 */
final class ManagedFieldResolverTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/test-managed-fields';
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ManagedFields/records.csv');
    }

    /**
     * @return \Generator<string, array{string, int, list<string>}>
     */
    public static function records(): \Generator
    {
        yield 'a synchronised profile' => ['tx_academicpersons_domain_model_profile', 1, ['title', 'website']];
        yield 'a profile excluded from the synchronisation' => ['tx_academicpersons_domain_model_profile', 2, []];
        yield 'a hidden synchronised profile' => ['tx_academicpersons_domain_model_profile', 3, ['title', 'website']];
        yield 'a profile an editor created' => ['tx_academicpersons_domain_model_profile', 4, []];
        yield 'the translation of a synchronised profile' => ['tx_academicpersons_domain_model_profile', 5, []];
        yield 'a synchronised contract' => ['tx_academicpersons_domain_model_contract', 1, ['position']];
        yield 'a contract an editor added to a synchronised profile' => ['tx_academicpersons_domain_model_contract', 2, []];
        yield 'a synchronised contract of an excluded profile' => ['tx_academicpersons_domain_model_contract', 3, []];
        yield 'a synchronised contract of a hidden profile' => ['tx_academicpersons_domain_model_contract', 4, ['position']];
        yield 'the translation of a synchronised contract' => ['tx_academicpersons_domain_model_contract', 5, []];
        yield 'a synchronised e-mail address, two levels below its profile' => ['tx_academicpersons_domain_model_email', 1, ['email']];
        yield 'an e-mail address an editor added to a synchronised contract' => ['tx_academicpersons_domain_model_email', 2, []];
        yield 'a synchronised e-mail address of an excluded profile' => ['tx_academicpersons_domain_model_email', 3, []];
        yield 'a synchronised phone number, its field named by its property' => ['tx_academicpersons_domain_model_phone_number', 1, ['type']];
        yield 'a synchronised address of a record type that manages nothing' => ['tx_academicpersons_domain_model_address', 1, []];
    }

    /**
     * @param list<string> $expectedColumns
     */
    #[DataProvider('records')]
    #[Test]
    public function theManagedColumnsOfARecord(string $tableName, int $uid, array $expectedColumns): void
    {
        $this->assertSame(
            $expectedColumns,
            $this->get(ManagedFieldResolver::class)->getManagedColumns($tableName, $this->fetchRow($tableName, $uid)),
        );
    }

    /**
     * @return \Generator<string, array{class-string<Profile|Contract|Address|Email|PhoneNumber>, string, int, list<string>}>
     */
    public static function models(): \Generator
    {
        $profile = 'tx_academicpersons_domain_model_profile';
        $contract = 'tx_academicpersons_domain_model_contract';
        $email = 'tx_academicpersons_domain_model_email';
        yield 'a synchronised profile' => [Profile::class, $profile, 1, ['title', 'website']];
        yield 'a profile excluded from the synchronisation' => [Profile::class, $profile, 2, []];
        yield 'a profile an editor created' => [Profile::class, $profile, 4, []];
        yield 'the translation of a synchronised profile' => [Profile::class, $profile, 5, []];
        yield 'a synchronised contract' => [Contract::class, $contract, 1, ['position']];
        yield 'a contract an editor added to a synchronised profile' => [Contract::class, $contract, 2, []];
        yield 'a synchronised contract of an excluded profile' => [Contract::class, $contract, 3, []];
        yield 'a synchronised contract of a hidden profile' => [Contract::class, $contract, 4, ['position']];
        yield 'the translation of a synchronised contract' => [Contract::class, $contract, 5, []];
        yield 'a synchronised e-mail address' => [Email::class, $email, 1, ['email']];
        yield 'an e-mail address an editor added to a synchronised contract' => [Email::class, $email, 2, []];
        yield 'a synchronised e-mail address of an excluded profile' => [Email::class, $email, 3, []];
        yield 'a synchronised phone number' => [PhoneNumber::class, 'tx_academicpersons_domain_model_phone_number', 1, ['type']];
        yield 'a synchronised address of a record type that manages nothing' => [Address::class, 'tx_academicpersons_domain_model_address', 1, []];
    }

    /**
     * The frontend editor asks with the model it writes, mapped from the row
     * as Extbase maps it, and gets property names.
     *
     * @param class-string<Profile|Contract|Address|Email|PhoneNumber> $className
     * @param list<string> $expectedProperties
     */
    #[DataProvider('models')]
    #[Test]
    public function theManagedPropertiesOfAModel(string $className, string $tableName, int $uid, array $expectedProperties): void
    {
        $models = $this->get(DataMapper::class)->map($className, [$this->fetchRow($tableName, $uid)]);
        $this->assertCount(1, $models);
        $this->assertInstanceOf($className, $models[0]);

        $this->assertSame($expectedProperties, $this->get(ManagedFieldResolver::class)->getManagedProperties($models[0]));
    }

    /**
     * Models overlaid in another language, the way Extbase hands them to the
     * editor of a translated site language: the uid of the default-language
     * record and the language of the translation.
     *
     * @return \Generator<string, array{class-string<Profile|Contract|Email|PhoneNumber>, int, list<string>, list<string>}>
     */
    public static function overlays(): \Generator
    {
        // The last two are the managed properties of the translation, and those
        // of its default-language record.
        yield 'a synchronised profile, its website shared by all languages' => [Profile::class, 1, ['website'], ['title', 'website']];
        yield 'a profile excluded from the synchronisation' => [Profile::class, 2, [], []];
        yield 'a synchronised contract, its position translatable' => [Contract::class, 1, [], ['position']];
        yield 'a synchronised contract of an excluded profile' => [Contract::class, 3, [], []];
        yield 'a synchronised e-mail address, its address translatable' => [Email::class, 1, [], ['email']];
        yield 'an e-mail address an editor added' => [Email::class, 2, [], []];
        yield 'a synchronised phone number, its type shared by all languages' => [PhoneNumber::class, 1, ['type'], ['type']];
    }

    /**
     * @param class-string<Profile|Contract|Email|PhoneNumber> $className
     * @param list<string> $expectedOfTranslation
     * @param list<string> $expectedOfDefaultLanguage
     */
    #[DataProvider('overlays')]
    #[Test]
    public function theManagedPropertiesOfAnOverlay(
        string $className,
        int $defaultLanguageUid,
        array $expectedOfTranslation,
        array $expectedOfDefaultLanguage,
    ): void {
        $overlay = new $className();
        $overlay->_setProperty('uid', $defaultLanguageUid);
        $overlay->_setProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID, 1);
        $resolver = $this->get(ManagedFieldResolver::class);

        $this->assertSame($expectedOfTranslation, $resolver->getManagedProperties($overlay));
        $this->assertSame($expectedOfDefaultLanguage, $resolver->getManagedPropertiesOfDefaultLanguage($overlay));
    }

    #[Test]
    public function aTableOutsideTheMapHasNoManagedColumn(): void
    {
        $this->assertSame(
            [],
            $this->get(ManagedFieldResolver::class)->getManagedColumns(
                'pages',
                ['uid' => 20, 'import_identifier' => 'fe_users:1', 'sys_language_uid' => 0],
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchRow(string $tableName, int $uid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $row = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid)))
            ->executeQuery()
            ->fetchAssociative();
        $this->assertIsArray($row);
        return $row;
    }
}
