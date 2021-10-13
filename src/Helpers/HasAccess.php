<?php

namespace Concrete5GraphqlWebsocket\Helpers;

defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\User\Group\Group;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Support\Facade\Application as App;

class HasAccess
{
    public static function checkByGroup($context, $groups = [])
    {
        $user = $context['user'];
        $userId = (int) $user->uID;
        if ($userId && $userId > 0) {
            if ($userId === 1) {
                return true;
            }

            foreach ($groups as $group) {
                if (in_array($group, $user->uGroupsPath)) {
                    return true;
                };
            }
        } else if ($userId === 0 && count($groups) === 0) {
            return true;
        }

        return false;
    }

    public static function check($context, $zone)
    {
        $config = App::make('config');
        $zoneGroups = (array) $config->get($zone);
        if (count($zoneGroups) > 0) {
            return self::checkByGroup($context, $zoneGroups);
        } else {
            return self::checkByGroup($context);
        }
    }
}
