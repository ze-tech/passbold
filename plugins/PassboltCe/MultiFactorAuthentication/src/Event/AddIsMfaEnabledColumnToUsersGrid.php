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
 * @since         2.12.0
 */
namespace Passbolt\MultiFactorAuthentication\Event;

use App\Controller\Component\QueryStringComponent;
use App\Controller\Events\ControllerFindIndexOptionsBeforeMarshal;
use App\Controller\Users\UsersIndexController;
use Cake\Event\EventListenerInterface;
use Passbolt\MultiFactorAuthentication\Model\EntityMapper\User\MfaEntityMapper;
use Passbolt\MultiFactorAuthentication\Model\Query\IsMfaEnabledQueryDecorator;

class AddIsMfaEnabledColumnToUsersGrid implements EventListenerInterface
{
    /**
     * @inheritDoc
     */
    public function implementedEvents(): array
    {
        return [
            ControllerFindIndexOptionsBeforeMarshal::EVENT_NAME => 'addIsMfaEnabledColumnToUsersGrid',
        ];
    }

    /**
     * On User Index Controller, add options.
     *
     * @param \App\Controller\Events\ControllerFindIndexOptionsBeforeMarshal $event Before Marschal Event
     * @return void
     */
    public function addIsMfaEnabledColumnToUsersGrid(ControllerFindIndexOptionsBeforeMarshal $event): void
    {
        if (!$event->getController() instanceof UsersIndexController) {
            return;
        }

        $options = $event->getOptions();

        $options->allowFilter(IsMfaEnabledQueryDecorator::IS_MFA_ENABLED_FILTER_NAME);
        $options->allowContain(MfaEntityMapper::IS_MFA_ENABLED_PROPERTY);
        $options->addFilterValidator(IsMfaEnabledQueryDecorator::IS_MFA_ENABLED_FILTER_NAME, function ($value) {
            $filterName = IsMfaEnabledQueryDecorator::IS_MFA_ENABLED_FILTER_NAME;

            return QueryStringComponent::validateFilterBoolean($value, $filterName);
        });
    }
}
