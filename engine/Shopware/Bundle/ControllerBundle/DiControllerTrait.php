<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Bundle\ControllerBundle;

trait DiControllerTrait
{
    public function __construct()
    {
    }

    public function initController(): void
    {
        $this->controller_name = $this->Front()->Dispatcher()->getFullControllerName($this->Request());

        Shopware()->Events()->notify(
            'Enlight_Controller_Action_Init',
            ['subject' => $this, 'request' => $this->Request(), 'response' => $this->Response()]
        );
        Shopware()->Events()->notify(
            'Enlight_Controller_Action_Init_' . $this->controller_name,
            ['subject' => $this, 'request' => $this->Request(), 'response' => $this->Response()]
        );

        if (method_exists($this, 'init')) {
            $this->init();
        }
    }

    protected function getActionArguments(string $actionMethodName): array
    {
        $controllerArray = [
            $this,
            $actionMethodName,
        ];

        $this->Request()->setAttribute('_controller', $this->Request()->getAttribute('controllerId') . ':' . $actionMethodName);

        return $this->container->get('argument_resolver')->getArguments($this->Request(), $controllerArray);
    }
}
