<?php

namespace Concrete5GraphqlWebsocket;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Support\Facade\Facade;
use Concrete5GraphqlWebsocket\PackageHelpers;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\DisableIntrospection;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;

class SecurityHelper
{
    public static function setSecurities()
    {
        $app = Facade::getFacadeApplication();
        $config = PackageHelpers::getFileConfig($app);

        $maxQueryComplexity = (int) $config->get('graphql.max_query_complexity');
        if ($maxQueryComplexity > 0) {
            self::setQueryComplexityAnalysis($maxQueryComplexity);
        } else {
            self::removeQueryComplexityAnalysis($maxQueryComplexity);
        }

        $maxDepth = (int) $config->get('graphql.max_query_depth');
        if ($maxDepth > 0) {
            self::setLimitingQueryDepth($maxDepth);
        } else {
            self::removeLimitingQueryDepth();
        }

        if ((bool) $config->get('graphql.disabling_introspection')) {
            self::setDisablingIntrospection();
        } else {
            self::removeDisablingIntrospection();
        }
    }

    private static function setQueryComplexityAnalysis($maxQueryComplexity = 100)
    {
        $rule = new QueryComplexity($maxQueryComplexity);
        DocumentValidator::addRule($rule);
    }

    private static function removeQueryComplexityAnalysis()
    {
        DocumentValidator::addRule(new QueryComplexity(QueryComplexity::DISABLED));
    }

    private static function setLimitingQueryDepth($maxDepth = 10)
    {
        $rule = new QueryDepth($maxDepth);
        DocumentValidator::addRule($rule);
    }

    private static function removeLimitingQueryDepth()
    {
        DocumentValidator::addRule(new QueryDepth(QueryDepth::DISABLED));
    }

    private static function setDisablingIntrospection()
    {
        DocumentValidator::addRule(new DisableIntrospection());
    }

    private static function removeDisablingIntrospection()
    {
        DocumentValidator::addRule(new DisableIntrospection(DisableIntrospection::DISABLED));
    }
}
