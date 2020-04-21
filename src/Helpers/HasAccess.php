<?php

namespace Concrete5GraphqlWebsocket\Helpers;

defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\User\Group\Group;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Support\Facade\Application as App;

class HasAccess
{
    public static function checkByGroup($context, $groups = ['Administrators'])
    {
        $user = $context['user'];
        $userId = (int) $user->uID;
        if ($userId && $userId > 0) {
            if ($userId === 1) {
                return true;
            }

            foreach ($groups as $group) {
                $groupItem = Group::getByName($group);
                if ($groupItem && $user->inGroup($groupItem)) {
                    return true;
                };
            }
        }

        throw new UserMessageException('Access denied', 401);
    }

    public static function check($context, $zone)
    {
        $config = App::make('config');
        $zoneGroups = (array) $config->get($zone);
        return self::checkByGroup($context, $zoneGroups || []);
    }
}
