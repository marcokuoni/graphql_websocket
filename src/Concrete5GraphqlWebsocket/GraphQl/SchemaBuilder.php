<?php
namespace Concrete5GraphqlWebsocket\GraphQl;

defined('C5_EXECUTE') or die("Access Denied.");

use Siler\GraphQL as SilerGraphQL;

use GraphQL\Type\Schema as SchemaType;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Concrete\Core\Support\Facade\Facade;
use Concrete5GraphqlWebsocket\GraphQl\WebsocketHelpers;
use Concrete5GraphqlWebsocket\GraphQl\SecurityHelper;
use Concrete5GraphqlWebsocket\PackageHelpers;
use Ola\GraphQL\Tools\MergeSchemas;

class SchemaBuilder
{
    protected static $schemafiles = array();

    protected static $queries = array();
    protected static $mutations = array();
    protected static $subscriptions = array();
    protected static $fileLines = array();

    protected static $schema = array();
    protected static $resolver = array();
    protected static $basePath = DIR_CONFIG_SITE . '/graphql';
    protected static $mergeSchemaFileName = 'merged_schema.gql';

    public static function registerSchemaFileForMerge(string $schemafile)
    {
        self::$schemafiles[] = $schemafile;

        if ((bool)PackageHelpers::getFileConfig(Facade::getFacadeApplication())->get('graphql.graphql_dev_mode')) {
            self::refreshSchemaMerge();
        }
    }

    public static function registerResolverForMerge(array $resolver)
    {
        self::$resolver = array_merge_recursive($resolver, self::$resolver);
    }

    public static function refreshSchemaMerge()
    {
        if (count(self::$schemafiles)  > 0 && count(self::$resolver) > 0) {
            self::$schema = array();
            self::$queries = array();
            self::$mutations = array();
            self::$subscriptions = array();
            self::$fileLines = array();

            foreach (self::$schemafiles as $filepath) {
                $schemaFile = file_get_contents($filepath);

                $values = '';
                $recorder = false;
                $currentType = '';
                $newSpecialEntry = false;
                foreach (preg_split("/((\r?\n)|(\r\n?))/", $schemaFile) as $line) {
                    if ($currentType !== '' && preg_match('/}/', $line) != 0) {
                        $recorder = false;

                        switch ($currentType) {
                            case 'query':
                                self::$queries[] = $values;
                                break;
                            case 'mutation':
                                self::$mutations[] = $values;
                                break;
                            case 'subscription':
                                self::$subscriptions[] = $values;
                                break;
                        }
                        $values = '';
                        $currentType = '';
                        $newSpecialEntry = true;
                    } else {
                        if ($recorder) {
                            $values .= '    ' . $line . "\r\n";
                        } else {
                            if (strpos(strtolower($line), strtolower('type Query')) !== false) {
                                $recorder = true;
                                if ($values !== '') {
                                    self::$fileLines[] = $values;
                                }
                                $values = '';
                                $currentType = 'query';
                            } else if (strpos(strtolower($line), strtolower('type Mutation')) !== false) {
                                $recorder = true;
                                if ($values !== '') {
                                    self::$fileLines[] = $values;
                                }
                                $values = '';
                                $currentType = 'mutation';
                            } else if (strpos(strtolower($line), strtolower('type Subscription')) !== false) {
                                $recorder = true;
                                if ($values !== '') {
                                    self::$fileLines[] = $values;
                                }
                                $values = '';
                                $currentType = 'subscription';
                            } else {
                                if ((!$newSpecialEntry || $line !== '')) {
                                    $values .= $line . "\r\n";
                                }
                            }
                        }
                    }
                }
                if ($values !== '') {
                    self::$fileLines[] = $values;
                }
            }

            self::buildSchemaFile();
        }
    }

    public static function hasSchema()
    {
        if ((bool)PackageHelpers::getFileConfig(Facade::getFacadeApplication())->get('graphql.graphql_dev_mode')) {
            self::refreshSchemaMerge();
        }
        if (file_exists(self::$basePath . '/' . self::$mergeSchemaFileName)) {
            $typeDefs = file_get_contents(self::$basePath . '/' . self::$mergeSchemaFileName);
            return ($typeDefs != '' && count(self::$resolver) > 0);
        }

        return false;
    }

    public static function get()
    {
        WebsocketHelpers::setSubscriptionAt();
        SecurityHelper::setSecurities();

        if (count(self::$resolver) > 0) {
            if (file_exists(self::$basePath . '/' . self::$mergeSchemaFileName)) {
                $typeDefs = file_get_contents(self::$basePath . '/' . self::$mergeSchemaFileName);
            } else {
                if ((bool)PackageHelpers::getFileConfig(Facade::getFacadeApplication())->get('graphql.graphql_dev_mode')) {
                    self::refreshSchemaMerge();
                }
            }

            if ($typeDefs != '') {
                if ((bool)PackageHelpers::getFileConfig(Facade::getFacadeApplication())->get('graphql.graphql_dev_mode')) {
                    self::refreshSchemaMerge();
                }
                if (file_exists(self::$basePath . '/' . self::$mergeSchemaFileName)) {
                    $typeDefs = file_get_contents(self::$basePath . '/' . self::$mergeSchemaFileName);
                }
            }

            if ($typeDefs != '') {
                return SilerGraphQL\schema($typeDefs, self::$resolver);
            }
        }

        return false;
    }

    public static function deleteSchemaFile()
    {
        if (file_exists(self::$basePath . '/' . self::$mergeSchemaFileName)) {
            unlink(self::$basePath . '/' . self::$mergeSchemaFileName);
        }
    }

    private static function buildSchemaFile()
    {
        $schemaFile = '';

        if (count(self::$fileLines) > 0) {
            foreach (self::$fileLines as $fileLine) {
                $schemaFile .= $fileLine;
            }
        }

        $schemaFile .=  "\r\n";

        if (count(self::$queries) > 0) {
            $schemaFile .= 'type Query {' . "\r\n";
            foreach (self::$queries as $query) {
                $schemaFile .= $query . "\r\n";
            }
            $schemaFile .= '}' . "\r\n" . "\r\n";
        }

        if (count(self::$mutations) > 0) {
            $schemaFile .= 'type Mutation {' . "\r\n";
            foreach (self::$mutations as $mutation) {
                $schemaFile .= $mutation . "\r\n";
            }
            $schemaFile .= '}' . "\r\n" . "\r\n";
        }

        if (count(self::$subscriptions) > 0) {
            $schemaFile .= 'type Subscription {' . "\r\n";
            foreach (self::$subscriptions as $subscription) {
                $schemaFile .= $subscription . "\r\n";
            }
            $schemaFile .= '}' . "\r\n" . "\r\n";
        }
        $schemaFilePath = self::$basePath . '/' . self::$mergeSchemaFileName;
        if (!file_exists(self::$basePath)) {
            mkdir(self::$basePath, 0755, true);
        }
        file_put_contents($schemaFilePath, $schemaFile);
    }
}
