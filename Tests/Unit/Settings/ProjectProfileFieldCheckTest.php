<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicBase\Settings\ValidationNormalizer;
use FGTCLB\AcademicPersons\Settings\ProfileField;
use FGTCLB\AcademicPersons\Settings\ProjectProfileFieldCheck;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Which columns a project field of the persons settings may use. The TCA handed in
 * is a profile table as the shipped TCA file and a site package leave it.
 */
final class ProjectProfileFieldCheckTest extends UnitTestCase
{
    public static function allowedColumnDataProvider(): \Generator
    {
        yield 'input' => ['input', 'text', []];
        yield 'text' => ['text', 'textarea', []];
        yield 'email, with its validator' => ['email', 'email', ['email']];
        yield 'link, with its validator' => ['link', 'text', ['url']];
        yield 'number' => ['number', 'text', ['number']];
        yield 'check, as a checkbox' => ['check', 'checkbox', []];
        yield 'a rich text renderer' => ['text', 'ckeditor', ['html']];
        yield 'a textarea renderer on an input column' => ['input', 'textarea', []];
    }

    /**
     * @param list<string> $flags
     */
    #[DataProvider('allowedColumnDataProvider')]
    #[Test]
    public function aProjectColumnWithAPlainValueIsAllowed(string $type, string $renderType, array $flags): void
    {
        $tca = $this->profileTca();
        $tca['columns']['tx_site_value'] = ['config' => ['type' => $type]];

        $this->assertNull((new ProjectProfileFieldCheck())->findProblem($this->field('siteValue', 'tx_site_value', $renderType, $flags), $tca));
    }

    public static function mismatchDataProvider(): \Generator
    {
        yield 'a check column shown as text' => [
            'check',
            'text',
            [],
            ': a column of the TCA type "check" needs the renderer "checkbox", not "text".',
        ];
        yield 'a checkbox on a text column' => [
            'input',
            'checkbox',
            [],
            ': the renderer "checkbox" needs a column of the TCA type "check", not "input".',
        ];
        yield 'rich text on an input column' => [
            'input',
            'ckeditor',
            ['html'],
            ': the renderer "ckeditor" needs a column of the TCA type "text", not "input".',
        ];
        yield 'an e-mail column without its validator' => [
            'email',
            'email',
            [],
            ': a column of the TCA type "email" needs the validator "email", which checks the value before anything is stored.',
        ];
        yield 'a link column without its validator' => [
            'link',
            'text',
            [],
            ': a column of the TCA type "link" needs the validator "url", which checks the value before anything is stored.',
        ];
    }

    /**
     * The renderer has to fit the column, and a column the DataHandler checks by
     * itself needs the validator that checks it first.
     *
     * @param list<string> $flags
     * @param non-empty-string $expectedEnd
     */
    #[DataProvider('mismatchDataProvider')]
    #[Test]
    public function aRendererOrValidatorThatDoesNotFitTheColumnIsNamed(string $type, string $renderType, array $flags, string $expectedEnd): void
    {
        $tca = $this->profileTca();
        $tca['columns']['tx_site_value'] = ['config' => ['type' => $type]];

        $problem = (new ProjectProfileFieldCheck())->findProblem($this->field('siteValue', 'tx_site_value', $renderType, $flags), $tca);

        $this->assertIsString($problem);
        $this->assertStringEndsWith($expectedEnd, $problem);
    }

    public static function refusedColumnDataProvider(): \Generator
    {
        yield 'no column named' => [
            'siteValue',
            '',
            'text',
            'The project field "profile.siteValue" of the persons settings names no column. Set its "fieldName".',
        ];
        yield 'an identifier the model has' => [
            'middleName',
            'tx_site_value',
            'text',
            ': its identifier "middleName" is a property of the profile model, name the project field differently.',
        ];
        yield 'a column the TCA does not have' => [
            'siteValue',
            'tx_site_missing',
            'text',
            ' cannot use the column "tx_site_missing" of the table "tx_academicpersons_domain_model_profile": the TCA of the table has no such column.',
        ];
        yield 'the uid' => ['siteValue', 'uid', 'text', ': it is a system column of the table.'];
        yield 'the column that hides the profile' => ['siteValue', 'hidden', 'text', ': it is a system column of the table.'];
        yield 'the language column' => ['siteValue', 'sys_language_uid', 'text', ': it is a system column of the table.'];
        yield 'the edit lock' => ['siteValue', 'tx_site_lock', 'text', ': it is a system column of the table.'];
        yield 'a workspace column' => ['siteValue', 't3ver_oid', 'text', ': it is a system column of the table.'];
        yield 'a column of the model' => [
            'siteValue',
            'first_name',
            'text',
            ': it belongs to the property "firstName" of the profile model.',
        ];
        yield 'a select column' => [
            'siteValue',
            'tx_site_select',
            'text',
            ': its TCA type "select" holds no plain value, use one of: input, text, email, link, number, check.',
        ];
        yield 'a file column' => [
            'siteValue',
            'tx_site_file',
            'text',
            ': its TCA type "file" holds no plain value, use one of: input, text, email, link, number, check.',
        ];
        yield 'a select renderer' => [
            'siteValue',
            'tx_site_value',
            'select',
            ': the renderer "select" is not available for a project field.',
        ];
        yield 'a renderer of two columns' => [
            'siteValue',
            'tx_site_value',
            'combinedLink',
            ': the renderer "combinedLink" is not available for a project field.',
        ];
    }

    /**
     * @param non-empty-string $expectedEnd
     */
    #[DataProvider('refusedColumnDataProvider')]
    #[Test]
    public function aColumnTheEditorMustNotWriteIsNamed(string $identifier, string $column, string $renderType, string $expectedEnd): void
    {
        $problem = (new ProjectProfileFieldCheck())->findProblem($this->field($identifier, $column, $renderType), $this->profileTca());

        $this->assertIsString($problem);
        $this->assertStringEndsWith($expectedEnd, $problem);
        if ($column !== '') {
            $this->assertStringStartsWith(
                sprintf('The project field "profile.%s" of the persons settings cannot use the column "%s"', $identifier, $column),
                $problem,
            );
        }
    }

    /**
     * A project that maps its column in an XCLASS of the model still declares it: the
     * shipped model is the class, and the class knows nothing of the column.
     */
    #[Test]
    public function aColumnOfAModelExtensionIsNotTheShippedModel(): void
    {
        $tca = $this->profileTca();
        $tca['columns']['name_prefix'] = ['config' => ['type' => 'input']];

        $this->assertNull((new ProjectProfileFieldCheck())->findProblem($this->field('namePrefix', 'name_prefix', 'text'), $tca));
    }

    public static function linkColumnDataProvider(): \Generator
    {
        yield 'every link type' => [['*'], true];
        yield 'pages and web addresses' => [['page', 'url'], true];
        yield 'no restriction' => [null, true];
        yield 'pages only' => [['page'], false];
        yield 'no link type at all' => [[], false];
    }

    /**
     * The editor accepts web addresses only, so a link column has to take them.
     *
     * @param list<string>|null $allowedTypes
     */
    #[DataProvider('linkColumnDataProvider')]
    #[Test]
    public function aLinkColumnHasToTakeWebAddresses(?array $allowedTypes, bool $allowed): void
    {
        $tca = $this->profileTca();
        $tca['columns']['tx_site_value'] = ['config' => array_filter(
            ['type' => 'link', 'allowedTypes' => $allowedTypes],
            static fn(mixed $value): bool => $value !== null,
        )];

        $problem = (new ProjectProfileFieldCheck())->findProblem($this->field('siteValue', 'tx_site_value', 'text', ['url']), $tca);

        if ($allowed) {
            $this->assertNull($problem);
            return;
        }
        $this->assertIsString($problem);
        $this->assertStringEndsWith(': its "allowedTypes" do not include "url", the one link type the editor accepts.', $problem);
    }

    /**
     * @param list<string> $flags
     */
    private function field(string $identifier, string $column, string $renderType, array $flags = []): ProfileField
    {
        return new ProfileField(
            identifier: $identifier,
            section: 'information',
            propertyName: $identifier,
            fieldName: $column,
            fieldType: 'input',
            renderType: $renderType,
            validation: (new ValidationNormalizer())->normalizeValidation($identifier, $flags, $column, $renderType),
            position: 0,
            custom: true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function profileTca(): array
    {
        return [
            'ctrl' => [
                'tstamp' => 'tstamp',
                'crdate' => 'crdate',
                'delete' => 'deleted',
                'languageField' => 'sys_language_uid',
                'transOrigPointerField' => 'l10n_parent',
                'editlock' => 'tx_site_lock',
                'enablecolumns' => ['disabled' => 'hidden', 'starttime' => 'starttime'],
            ],
            'columns' => [
                'uid' => ['config' => ['type' => 'passthrough']],
                'hidden' => ['config' => ['type' => 'check']],
                'sys_language_uid' => ['config' => ['type' => 'language']],
                'tx_site_lock' => ['config' => ['type' => 'check']],
                't3ver_oid' => ['config' => ['type' => 'number']],
                'first_name' => ['config' => ['type' => 'input']],
                'tx_site_value' => ['config' => ['type' => 'input']],
                'tx_site_select' => ['config' => ['type' => 'select', 'renderType' => 'selectSingle']],
                'tx_site_file' => ['config' => ['type' => 'file']],
            ],
        ];
    }
}
