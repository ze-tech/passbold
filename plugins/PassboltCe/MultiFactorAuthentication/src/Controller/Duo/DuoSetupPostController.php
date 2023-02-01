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
use App\Error\Exception\CustomValidationException;
use Cake\Http\Exception\BadRequestException;
use Passbolt\MultiFactorAuthentication\Controller\MfaSetupController;
use Passbolt\MultiFactorAuthentication\Form\MfaFormInterface;
use Passbolt\MultiFactorAuthentication\Utility\MfaSettings;

class DuoSetupPostController extends MfaSetupController
{
    /**
     * Handle Duo setup POST request
     *
     * @param \App\Authenticator\SessionIdentificationServiceInterface $sessionIdentificationService Session ID service
     * @param \Passbolt\MultiFactorAuthentication\Form\MfaFormInterface $setupForm MFA Form
     * @return void
     */
    public function post(
        SessionIdentificationServiceInterface $sessionIdentificationService,
        MfaFormInterface $setupForm
    ) {
        if ($this->request->is('json')) {
            throw new BadRequestException(__('This functionality is not available using AJAX/JSON.'));
        }
        $this->_orgAllowProviderOrFail(MfaSettings::PROVIDER_DUO);
        $this->_notAlreadySetupOrFail(MfaSettings::PROVIDER_DUO);

        /** @var \Passbolt\MultiFactorAuthentication\Form\Duo\DuoSetupForm $setupForm */
        try {
            $setupForm->execute($this->request->getData());
        } catch (CustomValidationException $exception) {
            $this->set('setupForm', $setupForm);
            $this->set('theme', $this->User->theme());
            $this->set('sigRequest', $setupForm->getSigRequest());
            $this->set('hostName', $this->mfaSettings->getOrganizationSettings()->getDuoHostname());
            $this->viewBuilder()
                ->setLayout('mfa_setup')
                ->setTemplatePath(ucfirst(MfaSettings::PROVIDER_DUO))
                ->setTemplate('setupForm');

            return;
        }
        $this->_handlePostSuccess(MfaSettings::PROVIDER_DUO, $sessionIdentificationService);
    }
}
