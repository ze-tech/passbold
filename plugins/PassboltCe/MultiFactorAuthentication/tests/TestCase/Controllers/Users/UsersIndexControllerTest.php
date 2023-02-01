<?php
declare(strict_types=1);

/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SA (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SA (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.0.0
 */

namespace Passbolt\MultiFactorAuthentication\Test\TestCase\Controllers\Users;

use App\Model\Entity\Role;
use App\Test\Fixture\Alt0\GroupsUsersFixture;
use App\Test\Fixture\Base\GpgkeysFixture;
use App\Test\Fixture\Base\ProfilesFixture;
use App\Test\Fixture\Base\RolesFixture;
use App\Test\Fixture\Base\UsersFixture;
use App\Test\Lib\Model\GroupsUsersModelTrait;
use App\Test\Lib\Utility\UserAccessControlTrait;
use App\Utility\UuidFactory;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Passbolt\AccountSettings\Test\Fixture\AccountSettingsFixture;
use Passbolt\MultiFactorAuthentication\Test\Lib\MfaIntegrationTestCase;
use Passbolt\MultiFactorAuthentication\Test\Lib\MfaOrgSettingsTestTrait;
use Passbolt\MultiFactorAuthentication\Utility\MfaAccountSettings;
use Passbolt\MultiFactorAuthentication\Utility\MfaSettings;

class UsersIndexControllerTest extends MfaIntegrationTestCase
{
    use GroupsUsersModelTrait;
    use MfaOrgSettingsTestTrait;
    use UserAccessControlTrait;

    public $fixtures = [
        AccountSettingsFixture::class,
        UsersFixture::class,
        ProfilesFixture::class,
        GpgkeysFixture::class,
        RolesFixture::class,
        GroupsUsersFixture::class,
    ];

    /**
     * @return void
     */
    public function testMfaUsersIndex_ThatColumnIsMfaEnabledIsDisabledIfMfaIsDisabledForOrg()
    {
        $config = [
            MfaSettings::PROVIDERS => [
                MfaSettings::PROVIDER_DUO => false,
                MfaSettings::PROVIDER_TOTP => false,
                MfaSettings::PROVIDER_YUBIKEY => false,
            ],
        ];

        $this->mockMfaOrgSettings($config, 'configure');
        $this->authenticateAs('ada');
        $this->getJson('/users.json?contain[is_mfa_enabled]=1');
        $this->assertSuccess();
        $this->assertObjectHasAttribute('is_mfa_enabled', $this->_responseJsonBody[0]);
        $this->assertFalse($this->_responseJsonBody[0]->is_mfa_enabled);
    }

    /**
     * @return void
     */
    public function testMfaUsersIndex_UsersIndexResultsContainIsMfaEnabledPropertyWhenContainParameterHaveIsMfaEnabled()
    {
        $this->mockMfaOrgSettings($this->getMfaProvidersConfig(), 'configure');
        $this->authenticateAs('ada');
        $this->getJson('/users.json?contain[is_mfa_enabled]=1');
        $this->assertSuccess();
        $this->assertObjectHasAttribute('is_mfa_enabled', $this->_responseJsonBody[0]);
    }

    /**
     * @return void
     */
    public function testMfaUsersIndex_ThatUsersIndexResultsAreFilteredWhenFilterParameterHaveIsMfaEnabled()
    {
        $this->mockMfaOrgSettings($this->getMfaProvidersConfig(), 'configure');
        $this->mockMfaOrgSettings($this->getMfaProvidersConfig(), 'database', $this->mockUserAccessControl('admin', Role::ADMIN));

        $userId = UuidFactory::uuid('user.id.ada');
        /** @var \Passbolt\AccountSettings\Model\Table\AccountSettingsTable $accountSettings */
        $accountSettings = TableRegistry::getTableLocator()->get('Passbolt/AccountSettings.AccountSettings');
        $accountSettings->createOrUpdateSetting($userId, MfaSettings::MFA, json_encode([
            MfaSettings::PROVIDERS => [MfaSettings::PROVIDER_TOTP],
            MfaSettings::PROVIDER_TOTP => [
                MfaAccountSettings::VERIFIED => FrozenTime::now(),
                MfaAccountSettings::OTP_PROVISIONING_URI => 'http://provisioning.uri',
            ],
        ]));

        $this->authenticateAs('admin');
        $this->clearRegistry();
        $this->getJson('/users.json?filter[is-mfa-enabled]=1&contain[is_mfa_enabled]=1');
        $this->assertSuccess();
        foreach ($this->_responseJsonBody as $user) {
            $this->assertTrue($user->is_mfa_enabled, 'All users in the results should have MFA enabled.');
        }

        $this->getJson('/users.json?filter[is-mfa-enabled]=0&contain[is_mfa_enabled]=1');
        $this->assertSuccess();
        foreach ($this->_responseJsonBody as $user) {
            $this->assertFalse($user->is_mfa_enabled, 'All users in the results should have MFA disabled.');
        }
    }

    /**
     * @return void
     */
    public function testMfaUsersIndex_UsersIndexResultsAreNotFilteredWhenFilterParameterDoesNotHaveIsMfaEnabled()
    {
        $this->mockMfaOrgSettings($this->getMfaProvidersConfig(), 'configure');
        $this->mockMfaOrgSettings($this->getMfaProvidersConfig(), 'database', $this->mockUserAccessControl('admin', Role::ADMIN));

        $userId = UuidFactory::uuid('user.id.ada');
        /** @var \Passbolt\AccountSettings\Model\Table\AccountSettingsTable $accountSettings */
        $accountSettings = TableRegistry::getTableLocator()->get('Passbolt/AccountSettings.AccountSettings');
        $accountSettings->createOrUpdateSetting($userId, MfaSettings::MFA, json_encode([
            MfaSettings::PROVIDERS => [MfaSettings::PROVIDER_TOTP],
            MfaSettings::PROVIDER_TOTP => [
                MfaAccountSettings::VERIFIED => FrozenTime::now(),
                MfaAccountSettings::OTP_PROVISIONING_URI => 'http://provisioning.uri',
            ],
        ]));

        $this->authenticateAs('admin');
        $this->clearRegistry();
        $this->getJson('/users.json?contain[is_mfa_enabled]=1');
        $this->assertSuccess();
        foreach ($this->_responseJsonBody as $user) {
            $this->assertSame($user->id === $userId, $user->is_mfa_enabled);
        }
    }

    /**
     * Clear the registry because the registry load models and their behaviors available at the moment
     * and they will be reused when running the test, and Behaviors registered in the application won't be run.
     *
     * @return void
     */
    private function clearRegistry()
    {
        TableRegistry::getTableLocator()->clear();
    }

    /**
     * @return array
     */
    private function getMfaProvidersConfig()
    {
        return [
            MfaSettings::PROVIDERS => [
                MfaSettings::PROVIDER_TOTP => true,
            ],
        ];
    }
}
