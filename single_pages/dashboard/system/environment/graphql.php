<?php
/* @var Concrete\Core\Form\Service\Form $form */
/* @var Concrete\Core\Html\Service\Html $html */
/* @var Concrete\Core\Page\View\PageView $this */
/* @var Concrete\Core\Validation\CSRF\Token $token */
/* @var Concrete\Core\Page\View\PageView $view */

/* @var bool $doctrine_dev_mode */
/* @var Doctrine\ORM\Mapping\Driver\AnnotationDriver[] $drivers */

defined('C5_EXECUTE') or die('Access Denied.');
use Concrete5GraphqlWebsocket\GraphQl\SchemaBuilder;

?>

<form method="post" id="entities-settings-form" action="<?= $view->action('update_entity_settings') ?>" style="position: relative">
    <?= $token->output('update_entity_settings') ?>

    <fieldset>
        <legend><?= t('GraphQL Settings') ?></legend>
        <div class="form-group">
            <label class="launch-tooltip" data-placement="right" title="<?= t('Defines whether the GraphQL schema is created on the fly. On the fly generation is active when development mode is enabled.') ?>"><?= t('GraphQL Development Mode') ?></label>
            <div class="radio">
                <label>
                    <?= $form->radio('GRAPHQL_DEV_MODE', 'yes', $graphql_dev_mode) ?>
                    <span><?= t('On - GraphQL schema will be generated on the fly. Good for development.') ?></span>
                </label>
            </div>

            <div class="radio">
                <label>
                    <?= $form->radio('GRAPHQL_DEV_MODE', 'no', !$graphql_dev_mode) ?>
                    <span><?= t('Off - GraphQL schema need to be manually generated with the button here in the footer. Helps speed up a live site.') ?></span>
                </label>
            </div>
        </div>
        <div class="form-group">
            <label class="launch-tooltip" data-placement="right" title="<?= t('This is a PHP port of Query Complexity Analysis in Sangria implementation. Complexity analysis is a separate validation rule which calculates query complexity score before execution. Every field in the query gets a default score 1 (including ObjectType nodes). Total complexity of the query is the sum of all field scores. For example, the complexity of introspection query is 109. If this score exceeds a threshold, a query is not executed and an error is returned instead.') ?>"><?= t('Query Complexity Analysis') ?></label>
            <div class="radio">
                <label>
                    <?= $form->radio('QUERY_COMPLEXITY_ANALYSIS', 'yes', $query_complexity_analysis) ?>
                    <span><?= t('On - It also disables introspection') ?></span>
                </label>
            </div>
            <div data-fields="QUERY_COMPLEXITY_ANALYSIS" style="padding-left: 30px;">
                <?php
                if (count($websocket_servers) > 0 && (int) array_values($websocket_servers)[0] > 0) {
                    ?>
                    <div class="help-block">
                        <?= t('All websocket servers have to be restarted to take effect on them') ?>
                    </div>
                <?php
            }
            ?>
                <div class="form-group">
                    <?= $form->label('MAX_QUERY_COMPLEXITY', t('Max Query Complexity')) ?>
                    <?= $form->text('MAX_QUERY_COMPLEXITY', (int) $max_query_complexity > 0 ? (int) $max_query_complexity : 100) ?>
                </div>
            </div>

            <div class="radio">
                <label>
                    <?= $form->radio('QUERY_COMPLEXITY_ANALYSIS', 'no', !$query_complexity_analysis) ?>
                    <span><?= t('Off') ?></span>
                </label>
            </div>
        </div>
        <div class="form-group">
            <label class="launch-tooltip" data-placement="right" title="<?= t('This is a PHP port of Limiting Query Depth in Sangria implementation. For example, max depth of the introspection query is 7.') ?>"><?= t('Limiting Query Depth') ?></label>
            <div class="radio">
                <label>
                    <?= $form->radio('LIMITING_QUERY_DEPTH', 'yes', $limiting_query_depth) ?>
                    <span><?= t('On - It also disables introspection') ?></span>
                </label>
            </div>
            <div data-fields="LIMITING_QUERY_DEPTH" style="padding-left: 30px;">
                <?php
                if (count($websocket_servers) > 0 && (int) array_values($websocket_servers)[0] > 0) {
                    ?>
                    <div class="help-block">
                        <?= t('All websocket servers have to be restarted to take effect on them') ?>
                    </div>
                <?php
            }
            ?>
                <div class="form-group">
                    <div class="form-group">
                        <?= $form->label('MAX_QUERY_DEPTH', t('Max Query Depth')) ?>
                        <?= $form->text('MAX_QUERY_DEPTH', (int) $max_query_depth > 0 ? (int) $max_query_depth : 10) ?>
                    </div>
                </div>
            </div>

            <div class="radio">
                <label>
                    <?= $form->radio('LIMITING_QUERY_DEPTH', 'no', !$limiting_query_depth) ?>
                    <span><?= t('Off') ?></span>
                </label>
            </div>
        </div>
        <div class="form-group">
            <label class="launch-tooltip" data-placement="right" title="<?= t('Introspection is a mechanism for fetching schema structure. It is used by tools like GraphiQL for auto-completion, query validation, etc. Introspection is enabled by default. It means that anybody can get a full description of your schema by sending a special query containing meta fields __type and __schema. If you are not planning to expose your API to the general public, it makes sense to disable this feature.') ?>"><?= t('Disabling Introspection') ?></label>
            <div class="radio">
                <label>
                    <?= $form->radio('DISABLING_INTROSPECTION', 'yes', $disabling_introspection) ?>
                    <span><?= t('On') ?></span>
                </label>
            </div>
            <div data-fields="DISABLING_INTROSPECTION" style="padding-left: 30px;">
                <?php
                if (count($websocket_servers) > 0 && (int) array_values($websocket_servers)[0] > 0) {
                    ?>
                    <div class="help-block">
                        <?= t('All websocket servers have to be restarted to take effect on them') ?>
                    </div>
                <?php
            }
            ?>
            </div>
            <div class="radio">
                <label>
                    <?= $form->radio('DISABLING_INTROSPECTION', 'no', !$disabling_introspection) ?>
                    <span><?= t('Off') ?></span>
                </label>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend><?= t('Websocket') ?></legend>
        <?php
        if (SchemaBuilder::hasSchema()) {
            ?>
            <div class="form-group">
                <div class="radio">
                    <label>
                        <?php echo $form->radio('WEBSOCKET', 'yes', $websocket_has_servers) ?>

                        <span><?php echo t('On'); ?></span>
                    </label>
                </div>
                <div class="help-block">
                    <?= t('Activate websocket server to use subscriptions. The Websocket Server needs a restart to get the newest GraphQL schema. After save you will get a button here in the footer to do that. This also disconnects all clients.') ?>
                </div>
                <div data-fields="simple" style="padding-left: 30px;">
                    <div class="servers-container">
                        <?php
                        if (count($websocket_servers) === 0) {
                            ?>
                            <div class="servers">
                                <h4><?= '1. ' . t('Server') ?></h4>
                                <div class="form-group">
                                    <?= $form->label('WEBSOCKET_PORTS[]', t('Port (default 3000 but use the one mapped on your apache server)')) ?>
                                    <?= $form->text('WEBSOCKET_PORTS[]', 3000) ?>
                                </div>
                            </div>
                        <?php
                    } else {
                        $count = 0;
                        foreach ($websocket_servers as $port => $pid) {
                            ?>
                                <div class="servers" data-pid="<?= $pid ?>">
                                    <h4><?= ($count + 1) . '. ' . t('Server') . (((int) $pid > 0) ? ' ' . t('currently running on pid:') . ' ' . $pid : '') ?></h4>
                                    <?php
                                    if ((int) $pid > 0) {
                                        ?>
                                        <a class="btn btn-danger" data-pid="<?= $pid ?>" name="stop-server" style="margin-bottom:15px;" href="javascript:void(0);"><?= t('Stop this websocket server, disconnects all clients.') ?></a>
                                    <?php
                                } else {
                                    ?>
                                        <a class="btn btn-success" data-port="<?= $port ?>" name="start-server" style="margin-bottom:15px;" href="javascript:void(0);"><?= t('Start this websocket server, disconnects all clients.') ?></a>
                                    <?php
                                }
                                ?>
                                    <a class="btn btn-danger" data-pid="<?= $pid ?>" data-port="<?= $port ?>" name="remove-server" style="margin-bottom:15px;" href="javascript:void(0);"><?= t('Remove Server') ?></a>
                                    <div class="form-group">
                                        <?= $form->label('WEBSOCKET_PORTS[]', t('Port')) ?>
                                        <?php
                                        if ((int) $pid > 0) {
                                            ?>
                                            <?= $form->hidden('WEBSOCKET_PORTS[]', (int) $port) ?>
                                            <p><?= t('Stop this websocket server to change the port %s', (int) $port) ?></p>
                                        <?php
                                    } else {
                                        ?>
                                            <?= $form->text('WEBSOCKET_PORTS[]', (int) $port) ?>
                                        <?php
                                    }
                                    ?>
                                    </div>
                                </div>
                                <?php
                                ++$count;
                            }
                        }
                        ?>
                    </div>

                    <a class="btn btn-primary" name="add-server" href="javascript:void(0);"><?= t('Add Server') ?></a>

                    <div class="form-group" style="margin-top: 30px;">
                        <label class="launch-tooltip" data-placement="right" title="<?= t('Websocket desbug modus writes echo infos in  /var/log/subscription_server.log file. Create and check persmissions for the file') ?>"><?= t('Debug Mode') ?></label>
                        <div class="radio">
                            <label>
                                <?= $form->radio('WEBSOCKET_DEBUG', 'yes', $websocket_debug) ?>
                                <span><?= t('On - Writes echo in /var/log/subscription_server.log') ?></span>
                            </label>
                        </div>

                        <div class="radio">
                            <label>
                                <?= $form->radio('WEBSOCKET_DEBUG', 'no', !$websocket_debug) ?>
                                <span><?= t('Off') ?></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        <?php
    } else {
        ?>
            <div class="help-block">
                <?= t('You can just start a websocket server if there are GraphQL Schemas defined and built. Use the "GraphQL Development Mode" or the button here in the footer to build it.') ?>
            </div>
        <?php
    }
    ?>
        <div class="form-group">
            <div class="radio">
                <label>
                    <?php echo $form->radio('WEBSOCKET', 'no', !$websocket_has_servers) ?>
                    <span><?php echo t('Off'); ?></span>
                </label>
            </div>

        </div>
    </fieldset>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <button class="pull-left btn btn-danger" name="refresh" value="1" type="submit"><?= t('Refresh GraphQL Schema') ?></button>
            <?php
            if (count($websocket_servers) > 0 && (int) array_values($websocket_servers)[0] > 0) {
                ?>
                <a class="pull-left btn btn-danger" name="restart-servers" style="margin-left: 15px;" href="javascript:void(0);"><?= t('Restart websocket servers, disconnects all clients.') ?></a>
            <?php
        }
        ?>
            <button class="pull-right btn btn-primary" type="submit"><?= t('Save') ?></button>
        </div>
    </div>

</form>


<script data-template='add-server-template' type="text/template" charset="utf-8">
    <div class="servers">
        <h4><%= count %><?= '. ' . t('Server') ?></h4>
        <a class="btn btn-danger" name="remove-new-server" style="margin-bottom:15px;" href="javascript:void(0);"><?= t('Remove Server') ?></a>
        <div class="form-group">
            <label for="WEBSOCKET_PORTS[]" class="control-label"><?= t('Port') ?></label>                                    
            <input type="text" id="WEBSOCKET_PORTS[]" name="WEBSOCKET_PORTS[]" class="form-control ccm-input-text" value="<%= port %>">
            <p><?= t('Save to start this websocket server') ?></p>
        </div>
    </div>
</script>

<script type="text/javascript">
    $(document).ready(function() {
        var
            _addServerTemplate = _.template($('script[data-template=add-server-template]').html()),
            $serversContainer = $('.servers-container'),
            $restartWebsockets = $('a[name="restart-servers"]'),
            $stopWebsocket = $('a[name="stop-server"]'),
            $startWebsocket = $('a[name="start-server"]'),
            $addServer = $('a[name="add-server"]'),
            $removeServer = $('a[name="remove-server"]'),
            tokenName = <?= json_encode($token::DEFAULT_TOKEN_NAME) ?>,
            actions = <?= json_encode([
                            'restartWebsockets' => [
                                'url' => (string) $view->action('restartWebsocketServer'),
                                'token' => $token->generate('ccm-restart_websockets'),
                            ],
                            'stopWebsocket' => [
                                'url' => (string) $view->action('stopWebsocketServer'),
                                'token' => $token->generate('ccm-stop_websocket'),
                            ],
                            'startWebsocket' => [
                                'url' => (string) $view->action('startWebsocketServer'),
                                'token' => $token->generate('ccm-start_websocket'),
                            ],
                            'removeWebsocket' => [
                                'url' => (string) $view->action('removeWebsocketServer'),
                                'token' => $token->generate('ccm-remove_websocket'),
                            ],
                        ]) ?>;

        function ajax(which, data, onSuccess, onError) {
            data[tokenName] = actions[which].token;
            $.ajax({
                data: data,
                dataType: 'json',
                method: 'POST',
                url: actions[which].url
            }).done(function() {
                onSuccess();
            }).fail(function(xhr, status, error) {
                var msg = error;
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error.message || xhr.responseJSON.error;
                }
                window.alert(msg);
                onError();
            });
        }

        $restartWebsockets.click(function() {
            if (confirm("<?= t('Are you sure, that you want to restart all websocket servers?') ?>")) {
                var $servers = $serversContainer.find('.servers'),
                    pids = [];
                $(document.body).css({
                    'cursor': 'wait'
                });

                $servers.each(function() {
                    pids.push($(this).data('pid'));
                })

                ajax(
                    'restartWebsockets', {
                        pids: pids
                    },
                    function() {
                        //give the webservers a chance to start without reload
                        setTimeout(function() {
                                window.location.reload();
                            },
                            1000);
                    },
                    function() {}
                );
            }
        });

        $stopWebsocket.click(function() {
            if (confirm("<?= t('Are you sure, that you want to stop this websocket server?') ?>")) {
                var pids = [];
                pids.push($(this).data('pid'))
                $(document.body).css({
                    'cursor': 'wait'
                });

                ajax(
                    'stopWebsocket', {
                        pids: pids
                    },
                    function() {
                        //give the webservers a chance to start without reload
                        setTimeout(function() {
                                window.location.reload();
                            },
                            1000);
                    },
                    function() {}
                );
            }
        });

        $startWebsocket.click(function() {
            var ports = [];
            ports.push($(this).data('port'))
            $(document.body).css({
                'cursor': 'wait'
            });

            ajax(
                'startWebsocket', {
                    ports: ports
                },
                function() {
                    //give the webservers a chance to start without reload
                    setTimeout(function() {
                            window.location.reload();
                        },
                        1000);
                },
                function() {}
            );
        });

        $removeServer.click(function() {
            if (confirm("<?= t('Are you sure, that you want to remove this websocket server?') ?>")) {
                $(document.body).css({
                    'cursor': 'wait'
                });
                ajax(
                    'removeWebsocket', {
                        pid: $(this).data('pid'),
                        port: $(this).data('port')
                    },
                    function() {
                        //give the webservers a chance to start without reload
                        setTimeout(function() {
                                window.location.reload();
                            },
                            1000);
                    },
                    function() {}
                );
            }
        });

        $addServer.click(function() {
            var count = $serversContainer.find('.servers').length + 1;

            $serversContainer.append(
                _addServerTemplate({
                    'count': count,
                    'port': (3000 + (count - 1))
                })
            );

            var $removeNewServer = $('a[name="remove-new-server"]');
            $removeNewServer.off('click');
            $removeNewServer.on('click', function() {
                if (confirm("<?= t('Are you sure, that you want to remove this websocket server?') ?>")) {
                    $(this).parent().remove();
                }
            });
        });

        $('input[name^="WEBSOCKET_PORTS"]').keyup(function() {
            var $startServerButton = $(this).parent().parent().find('a[name="start-server"]');
            if ($startServerButton.length > 0) {
                $startServerButton.remove();
                $(this).parent().append('<p><?= t('Save to start this websocket server') ?></p>');
            }
        });

        $('input[name=WEBSOCKET]').change(function() {
            var $selected = $('input[name=WEBSOCKET]:checked');
            if ($selected.val() === 'yes') {
                $('div[data-fields=simple]').show();
            } else {
                $('div[data-fields=simple]').hide();
            }
        });

        $('input[name=WEBSOCKET]:checked').trigger('change');

        $('input[name=QUERY_COMPLEXITY_ANALYSIS]').change(function() {
            var $selected = $('input[name=QUERY_COMPLEXITY_ANALYSIS]:checked');
            if ($selected.val() === 'yes') {
                $('div[data-fields=QUERY_COMPLEXITY_ANALYSIS]').show();
            } else {
                $('div[data-fields=QUERY_COMPLEXITY_ANALYSIS]').hide();
            }
        });

        $('input[name=QUERY_COMPLEXITY_ANALYSIS]:checked').trigger('change');

        $('input[name=LIMITING_QUERY_DEPTH]').change(function() {
            var $selected = $('input[name=LIMITING_QUERY_DEPTH]:checked');
            if ($selected.val() === 'yes') {
                $('div[data-fields=LIMITING_QUERY_DEPTH]').show();
            } else {
                $('div[data-fields=LIMITING_QUERY_DEPTH]').hide();
            }
        });

        $('input[name=LIMITING_QUERY_DEPTH]:checked').trigger('change');

        $('input[name=DISABLING_INTROSPECTION]').change(function() {
            var $selected = $('input[name=DISABLING_INTROSPECTION]:checked');
            if ($selected.val() === 'yes') {
                $('div[data-fields=DISABLING_INTROSPECTION]').show();
            } else {
                $('div[data-fields=DISABLING_INTROSPECTION]').hide();
            }
        });

        $('input[name=DISABLING_INTROSPECTION]:checked').trigger('change');
    });
</script>