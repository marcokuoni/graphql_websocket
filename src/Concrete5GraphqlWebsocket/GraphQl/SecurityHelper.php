<?php
namespace Concrete5GraphqlWebsocket\GraphQl;

defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\Support\Facade\Facade;

use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use GraphQL\Validator\Rules\DisableIntrospection;
use GraphQL\Validator\DocumentValidator;

class SecurityHelper
{
    public static function setSecurities()
    { 
        $app = Facade::getFacadeApplication();

        $maxQueryComplexity = (int)$app->make('config')->get('concrete.graphql.max_query_complexity');
        if($maxQueryComplexity > 0) {
            self::setQueryComplexityAnalysis($maxQueryComplexity);
        }

        $maxDepth = (int)$app->make('config')->get('concrete.graphql.max_query_depth');
        if($maxDepth > 0) {
            self::setLimitingQueryDepth($maxDepth);
        }

        if ((bool)$app->make('config')->get('concrete.graphql.disabling_introspection')) {
            self::setDisablingIntrospection();
        }
    }

    private static function setQueryComplexityAnalysis($maxQueryComplexity = 100)
    {
        $rule = new QueryComplexity($maxQueryComplexity);
        DocumentValidator::addRule($rule);
    }

    private static function setLimitingQueryDepth($maxDepth = 10)
    {
        $rule = new QueryDepth($maxDepth);
        DocumentValidator::addRule($rule);
    }

    private static function setDisablingIntrospection()
    {
        DocumentValidator::addRule(new DisableIntrospection());
    }
}
