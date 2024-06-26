<?php
/**
 * Created by PhpStorm.
 * User: gseidel
 * Date: 2019-02-19
 * Time: 02:14
 */

namespace Enhavo\Bundle\ResourceBundle\Action;

use Enhavo\Bundle\ResourceBundle\Model\ResourceInterface;
use Enhavo\Component\Type\FactoryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ActionManager
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $checker,
        private readonly FactoryInterface $actionFactory,
    )
    {
    }

    /**
     * @return Action[]
     */
    public function getActions(array $configuration, ResourceInterface $resource = null): array
    {
        $actions = [];
        foreach($configuration as $key => $options) {
            /** @var Action $action */
            $action = $this->actionFactory->create($options, $key);

            if (!$action->isEnabled($resource)) {
                continue;
            }

            if ($action->getPermission($resource) !== null && !$this->checker->isGranted($action->getPermission())) {
                continue;
            }

            $actions[$key] = $action;
        }

        return $actions;
    }
}
