<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Profile;

use FGTCLB\AcademicPersons\Domain\Model\Address;
use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Email;
use FGTCLB\AcademicPersons\Domain\Model\PhoneNumber;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Repository\AddressRepository;
use FGTCLB\AcademicPersons\Domain\Repository\EmailRepository;
use FGTCLB\AcademicPersons\Domain\Repository\PhoneNumberRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Contracts\Service\Attribute\Required;

#[Autoconfigure(public: true, shared: true)]
final class ProfileFactory extends AbstractProfileFactory
{
    protected AddressRepository $addressRepository;
    protected EmailRepository $emailRepository;
    protected PhoneNumberRepository $phoneNumberRepository;

    #[Required]
    public function injectAddressRepository(AddressRepository $addressRepository): void
    {
        $this->addressRepository = $addressRepository;
    }

    #[Required]
    public function injectEmailRepository(EmailRepository $emailRepository): void
    {
        $this->emailRepository = $emailRepository;
    }

    #[Required]
    public function injectPhoneNumberRepository(PhoneNumberRepository $phoneNumberRepository): void
    {
        $this->phoneNumberRepository = $phoneNumberRepository;
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    protected function createProfileFromFrontendUser(array $frontendUserData): Profile
    {
        $pid = (int)$frontendUserData['pid'];

        if ($pid < 0) {
            throw new \InvalidArgumentException(
                'The PID must be a positive integer.',
                1627471234
            );
        }
        /** @var int<0, max> $pid */
        $profile = new Profile();
        $profile->setPid($pid);
        $this->applyProfileData($frontendUserData, $profile);

        $importIdentifier = sprintf('%s:%s', 'fe_users', $frontendUserData['uid']);
        $contract = new Contract();
        $contract->setImportIdentifier($importIdentifier);
        $contract->setPid($pid);
        $profile->getContracts()->attach($contract);
        $this->applyContractData($frontendUserData, $contract, $pid);

        return $profile;
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    protected function updateProfileFromFrontendUser(array $frontendUserData, Profile $profile): void
    {
        /** @var int<0, max> $pid */
        $pid = (int)$frontendUserData['pid'];
        $this->applyProfileData($frontendUserData, $profile);
        $importIdentifier = sprintf('%s:%s', 'fe_users', $frontendUserData['uid']);
        $contracts = $profile->getContracts();
        $contract = null;
        foreach ($contracts as $checkContract) {
            if ($checkContract->getImportIdentifier() === $importIdentifier) {
                $contract = $checkContract;
                break;
            }
        }
        if ($contract !== null
            && empty($frontendUserData['address'])
            && empty($frontendUserData['zip'])
            && empty($frontendUserData['city'])
            && empty($frontendUserData['country'])
            && empty($frontendUserData['email'])
            && empty($frontendUserData['phone'])
            && empty($frontendUserData['fax'])
        ) {
            // No contract data, remove previous attached contract.
            // Note that $contracts->detach() would only remove the relation and making the record
            // orphan (unconnected) and removing (deleting) it is used keep the database clean
            // and allows to use the history to restore them.
            $this->persistenceManager->remove($contract);
            return;
        }
        if ($contract === null
            && empty($frontendUserData['address'])
            && empty($frontendUserData['zip'])
            && empty($frontendUserData['city'])
            && empty($frontendUserData['country'])
            && empty($frontendUserData['email'])
            && empty($frontendUserData['phone'])
            && empty($frontendUserData['fax'])
        ) {
            // No contract and no contract data, nothing to do.
            return;
        }
        if ($contract === null) {
            $contract = new Contract();
            $contract->setImportIdentifier($importIdentifier);
            $contract->setPid($pid);
            $profile->getContracts()->attach($contract);
        }
        $this->applyContractData($frontendUserData, $contract, $pid);
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    private function applyProfileData(array $frontendUserData, Profile $profile): void
    {
        $importIdentifier = sprintf('%s:%s', 'fe_users', $frontendUserData['uid']);
        $profile->setImportIdentifier($importIdentifier);
        $profile->setTitle((string)($frontendUserData['title'] ?? ''));
        $profile->setFirstName((string)($frontendUserData['first_name'] ?? ''));
        $profile->setMiddleName((string)($frontendUserData['middle_name'] ?? ''));
        $profile->setLastName((string)($frontendUserData['last_name'] ?? ''));
        $profile->setWebsite((string)($frontendUserData['www'] ?? ''));
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     * @param int<0, max> $pid
     */
    private function applyContractData(array $frontendUserData, Contract $contract, int $pid): void
    {
        $this->applyPhysicalAddress($frontendUserData, $contract, $pid);
        $this->applyEmailAddress($frontendUserData, $contract, $pid);
        $this->applyPhoneNumber($frontendUserData, $contract, $pid, 'phone', 'telephone');
        $this->applyPhoneNumber($frontendUserData, $contract, $pid, 'fax', 'fax');
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     * @param int<0, max> $pid
     */
    private function applyPhysicalAddress(array $frontendUserData, Contract $contract, int $pid): void
    {
        $importIdentifier = sprintf('%s:%s', 'fe_users', $frontendUserData['uid']);
        // Match including hidden records, otherwise a record hidden in the frontend would not be
        // found and a duplicate would be created on the next synchronization. The visibility status
        // is intentionally never changed here, so manually hidden records stay hidden.
        $address = null;
        $contractUid = (int)$contract->getUid();
        if ($contractUid > 0) {
            foreach ($this->addressRepository->findByContractIncludingHidden($contractUid) as $checkAddress) {
                if ($checkAddress->getImportIdentifier() === $importIdentifier) {
                    $address = $checkAddress;
                    break;
                }
            }
        }
        $userAddress = (string)($frontendUserData['address'] ?? '');
        $userZip = (string)($frontendUserData['zip'] ?? '');
        $userCity = (string)($frontendUserData['city'] ?? '');
        $userCountry = (string)($frontendUserData['country'] ?? '');
        if ($address !== null
            && empty($userAddress)
            && empty($userZip)
            && empty($userCity)
            && empty($userCountry)
        ) {
            // No address data, remove previous attached address.
            // Note that $addresses->detach() would only remove the relation and making the record
            // orphan (unconnected) and removing (deleting) it is used keep the database clean
            // and allows to use the history to restore them.
            $this->persistenceManager->remove($address);
            return;
        }
        if ($address === null
            && empty($userAddress)
            && empty($userZip)
            && empty($userCity)
            && empty($userCountry)
        ) {
            // No contract and no address data, nothing to do.
            return;
        }
        $isNewAddress = $address === null;
        if ($isNewAddress) {
            // No address yet but address data exists, create new address and attach it.
            $address = new Address();
            $address->setPid($pid);
            $address->setImportIdentifier($importIdentifier);
            $contract->getPhysicalAddresses()->attach($address);
        }
        $address->setStreet($userAddress);
        $address->setZip($userZip);
        $address->setCity($userCity);
        $address->setCountry($userCountry);
        if (!$isNewAddress) {
            // Existing record was matched via the repository (outside the profile aggregate),
            // so its changes have to be persisted explicitly.
            $this->addressRepository->update($address);
        }
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     * @param int<0, max> $pid
     */
    private function applyEmailAddress(array $frontendUserData, Contract $contract, int $pid): void
    {
        $importIdentifier = sprintf('%s:%s', 'fe_users', $frontendUserData['uid']);
        // Match including hidden records, otherwise a record hidden in the frontend would not be
        // found and a duplicate would be created on the next synchronization. The visibility status
        // is intentionally never changed here, so manually hidden records stay hidden.
        $email = null;
        $contractUid = (int)$contract->getUid();
        if ($contractUid > 0) {
            foreach ($this->emailRepository->findByContractIncludingHidden($contractUid) as $checkEmail) {
                if ($checkEmail->getImportIdentifier() === $importIdentifier) {
                    $email = $checkEmail;
                    break;
                }
            }
        }
        if (empty($frontendUserData['email']) && $email !== null) {
            // Email address no longer set, remove previous imported email address to clean it up.
            // Note that $emails->detach() would only remove the relation and making the record
            // orphan (unconnected) and removing (deleting) it is used keep the database clean
            // and allows to use the history to restore them.
            $this->persistenceManager->remove($email);
            return;
        }
        if (empty($frontendUserData['email']) && $email === null) {
            // No email address and no email record, nothing to do.
            return;
        }
        $isNewEmail = $email === null;
        if ($isNewEmail) {
            // No email record yet but email address exists, create new email record and attach it.
            $email = new Email();
            $email->setPid($pid);
            $email->setImportIdentifier($importIdentifier);
            $contract->getEmailAddresses()->attach($email);
        }
        $email->setEmail((string)($frontendUserData['email']));
        if (!$isNewEmail) {
            // Existing record was matched via the repository (outside the profile aggregate),
            // so its changes have to be persisted explicitly.
            $this->emailRepository->update($email);
        }
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     * @param int<0, max> $pid
     */
    private function applyPhoneNumber(
        array $frontendUserData,
        Contract $contract,
        int $pid,
        string $type,
        string $dataKey
    ): void {
        $importIdentifier = sprintf('%s:%s:%s', $type, 'fe_users', $frontendUserData['uid']);
        // Match including hidden records, otherwise a record hidden in the frontend would not be
        // found and a duplicate would be created on the next synchronization. The visibility status
        // is intentionally never changed here, so manually hidden records stay hidden.
        $phoneNumber = null;
        $contractUid = (int)$contract->getUid();
        if ($contractUid > 0) {
            foreach ($this->phoneNumberRepository->findByContractIncludingHidden($contractUid) as $checkPhoneNumber) {
                if ($checkPhoneNumber->getType() === $type && $checkPhoneNumber->getImportIdentifier() === $importIdentifier) {
                    $phoneNumber = $checkPhoneNumber;
                    break;
                }
            }
        }
        if (empty($frontendUserData[$dataKey]) && $phoneNumber !== null) {
            // PhoneNumber<type> no longer set, remove previous imported PhoneNumber<type> to clean it up.
            // Note that $phoneNumbers->detach() would only remove the relation and making the record
            // orphan (unconnected) and removing (deleting) it is used keep the database clean
            // and allows to use the history to restore them.
            $this->persistenceManager->remove($phoneNumber);
            return;
        }
        if (empty($frontendUserData[$dataKey]) && $phoneNumber === null) {
            // No PhoneNumber<type> and no PhoneNumber<type> record, nothing to do.
            return;
        }
        $isNewPhoneNumber = $phoneNumber === null;
        if ($isNewPhoneNumber) {
            // No PhoneNumber<type> record yet but PhoneNumber<type> exists, create new PhoneNumber<type> record and attach it.
            $phoneNumber = new PhoneNumber();
            $phoneNumber->setPid($pid);
            $phoneNumber->setType($type);
            $phoneNumber->setImportIdentifier($importIdentifier);
            $contract->getPhoneNumbers()->attach($phoneNumber);
        }
        $phoneNumber->setPhoneNumber((string)($frontendUserData[$dataKey]));
        if (!$isNewPhoneNumber) {
            // Existing record was matched via the repository (outside the profile aggregate),
            // so its changes have to be persisted explicitly.
            $this->phoneNumberRepository->update($phoneNumber);
        }
    }
}
