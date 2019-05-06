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

class SchemaBuilder
{
    protected static $schemafiles = array();

    protected static $schema = array();
    protected static $resolver = array();
    protected static $basePath = DIR_CONFIG_SITE . '/graphql';
    protected static $mergeSchemaFileName = 'merged_schema.gql';

    public static function registerSchemaFileForMerge(string $schemafile)
    {
        self::$schemafiles[] = $schemafile;

        if ((bool)Facade::getFacadeApplication()->make('config')->get('concrete.cache.graphql_dev_mode')) {
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
            foreach (self::$schemafiles as $filepath) {
                $schemaFile = file_get_contents($filepath);
                $schema = array();


                preg_match_all('/type\s\w*\s{/', $schemaFile, $schema);
                if (count($schema) > 0) {
                    $schema = $schema[0]; //preg_match_all creates an array in an array
                    $newSchema = array();

                    foreach ($schema as $type) {
                        if ($type !== '') {
                            if (
                                $type !== 'type Query {' &&
                                $type !== 'type Mutation {' &&
                                $type !== 'type Subscription {'
                            ) {
                                foreach ($newSchema as $newType => $value) {
                                    if ($type === $newType) {
                                        throw new \Exception(t('Over all GraphQl types in your file:') . ' ' . $filepath . ' ' . t('exists two times the same type: ') . ' ' . $newType);
                                    }
                                }
                            }
                            $newSchema[$type] = '';
                        }
                    }
                    $schema = $newSchema;
                }
                $currentType = '';
                $values = '';
                $recorder = false;

                foreach (preg_split("/((\r?\n)|(\r\n?))/", $schemaFile) as $line) {
                    if ($line != '') {
                        if (preg_match('/}/', $line) != 0) {
                            $recorder = false;
                            $schema[$currentType] = $values;
                        }
                        if ($recorder) {
                            $values .= '    ' . $line . "\r\n";
                        }
                        foreach ($schema as $type => $emptyValues) {
                            if ($type != '' && strpos($line, $type) !== false) {
                                $recorder = true;
                                $values = '';
                                $currentType = $type;
                                break;
                            }
                        }
                    }
                }

                foreach ($schema as $newType => $newValues) {
                    if (
                        $newType === 'type Query {' ||
                        $newType === 'type Mutation {' ||
                        $newType === 'type Subscription {'
                    ) {
                        $oldValue = '';
                        foreach (self::$schema as $type => $value) {
                            if ($newType === $type) {
                                $oldValue = $value;
                            }
                        }
                        self::$schema[$newType] = $newValues . ($oldValue ? $oldValue : '');
                    } else {
                        foreach (self::$schema as $type => $value) {
                            if ($newType === $type) {
                                throw new \Exception(t('Over all GraphQl schema files exists two times the same type:') . ' ' . $newType . ' ' . t('second time found in') . ' ' . $filepath);
                            }
                        }
                        self::$schema[$newType] = $newValues;
                    }
                }
            }

            self::buildSchemaFile();
        }
    }

    public static function hasSchema()
    {
        if ((bool)Facade::getFacadeApplication()->make('config')->get('concrete.cache.graphql_dev_mode')) {
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
                if ((bool)Facade::getFacadeApplication()->make('config')->get('concrete.cache.graphql_dev_mode')) {
                    self::refreshSchemaMerge();
                }
            }

            if ($typeDefs != '') {
                if ((bool)Facade::getFacadeApplication()->make('config')->get('concrete.cache.graphql_dev_mode')) {
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

        foreach (self::$schema as $type => $values) {
            $schemaFile .= $type . "\r\n";
            if (is_array($values)) {
                foreach ($values as $value) {
                    $schemaFile .= $value;
                }
            } else {
                $schemaFile .= $values;
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
