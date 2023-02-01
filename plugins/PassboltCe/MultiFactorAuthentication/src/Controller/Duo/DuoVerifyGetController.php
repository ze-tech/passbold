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
 * @since         2.5.0
 */
namespace Passbolt\MultiFactorAuthentication\Controller\Duo;

use App\Authenticator\SessionIdentificationServiceInterface;
use Cake\Http\Exception\BadRequestException;
use Passbolt\MultiFactorAuthentication\Controller\MfaVerifyController;
use Passbolt\MultiFactorAuthentication\Form\MfaFormInterface;
use Passbolt\MultiFactorAuthentication\Utility\MfaSettings;

class DuoVerifyGetController extends MfaVerifyController
{
    /**
     * Duo Verify Get
     *
     * @param \App\Authenticator\SessionIdentificationServiceInterface $sessionIdentificationService session ID service
     * @param \Passbolt\MultiFactorAuthentication\Form\MfaFormInterface $verifyForm MFA Form
     * @throws \Cake\Http\Exception\InternalErrorException if there is no MFA settings for the user
     * @throws \Cake\Http\Exception\BadRequestException if valid Verification token is already present in cookie
     * @throws \Cake\Http\Exception\BadRequestException if there is no MFA settings for this provider
     * @return void
     */
    public function get(
        SessionIdentificationServiceInterface $sessionIdentificationService,
        MfaFormInterface $verifyForm
    ) {
        if ($this->request->is('json')) {
            throw new BadRequestException(__('This functionality is not available using AJAX/JSON.'));
        }
        $this->_handleVerifiedNotRequired($sessionIdentificationService);
        $this->_handleInvalidSettings(MfaSettings::PROVIDER_DUO);

        /** @var \Passbolt\MultiFactorAuthentication\Form\Duo\DuoVerifyForm $verifyForm */
        $this->set('sigRequest', $verifyForm->getSigRequest());
        $this->set('hostName', $this->mfaSettings->getOrganizationSettings()->getDuoHostname());
        $this->set('verifyForm', $verifyForm);
        $this->set('providers', $this->mfaSettings->getEnabledProviders());
        $this->set('theme', $this->User->theme());
        $this->viewBuilder()
            ->setLayout('mfa_verify')
            ->setTemplatePath(ucfirst(MfaSettings::PROVIDER_DUO))
            ->setTemplate('verifyForm');
    }
}
