<?php

declare(strict_types=1);

namespace Dotclear\Plugin\arlequin;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Html;
use Dotclear\Interface\Core\BlogWorkspaceInterface;
use Exception;

/**
 * @brief       arlequin manage class.
 * @ingroup     arlequin
 *
 * @author      Oleksandr Syenchuk (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Manage
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            $settings = My::settings();

            /**
             * @var array{name?: string, s_html?: string, e_html?: string, a_html?: string} $model
             */
            $model = is_string($model = $settings->get('model')) ? json_decode($model, true) : [];

            $exclude = $settings->get('exclude');

            // initialize settings
            if ($model === [] || $exclude === null || !(isset($model['e_html']) && isset($model['a_html']) && isset($model['s_html']))) {
                $model = My::defaultModel();
                $settings->put('model', json_encode($model), BlogWorkspaceInterface::NS_STRING, 'Arlequin configuration');
                $settings->put('exclude', 'customCSS', BlogWorkspaceInterface::NS_STRING, 'Excluded themes');

                Notices::addSuccessNotice(__('Settings have been reinitialized.'));
                App::blog()->triggerBlog();
            }

            // collect settings
            if (isset($_POST['mt_action_config'])) {
                $model['e_html'] = $_POST['e_html'];
                $model['a_html'] = $_POST['a_html'];
                $model['s_html'] = $_POST['s_html'];
                $exclude         = $_POST['exclude'];
            }

            // save settings
            if (isset($_POST['mt_action_config'])) {
                $settings->put('model', json_encode($model), BlogWorkspaceInterface::NS_STRING);
                $settings->put('exclude', $exclude, BlogWorkspaceInterface::NS_STRING);

                Notices::addSuccessNotice(__('System settings have been updated.'));
                App::blog()->triggerBlog();
                My::redirect(['config' => 1]);
            }

            // restore settings
            if (isset($_POST['mt_action_restore'])) {
                $settings->drop('model');
                $settings->drop('exclude');

                Notices::addSuccessNotice(__('Settings have been reinitialized.'));
                App::blog()->triggerBlog();
                My::redirect(['restore' => 1]);
            }
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $data = [
            'msg' => [
                'predefined_models' => Html::escapeJS(__('Predefined models')),
                'select_model'      => Html::escapeJS(__('Select a model')),
                'user_defined'      => Html::escapeJS(__('User defined')),
            ],
            'models' => [],
        ];

        $models = new ArrayObject(My::distributedModels());
        App::behavior()->callBehavior('arlequinAddModels', $models);
        $models = iterator_to_array($models);

        foreach ($models as $m) {
            $m = array_merge(My::defaultModel(), $m);

            $data['models'][] = [
                'name'   => $m['name'],
                's_html' => $m['s_html'],
                'e_html' => $m['e_html'],
                'a_html' => $m['a_html'],
            ];
        }

        $settings = My::settings();

        $excluded = is_string($excluded = $settings->get('exclude')) ? $excluded : '';

        /**
         * @var array{name?: string, s_html?: string, e_html?: string, a_html?: string} $model
         */
        $model = is_string($model = $settings->get('model')) ? json_decode($model, true) : [];

        if ($model === []) {
            $model = My::defaultModel();
        }

        Page::openModule(
            My::name(),
            Page::jsJson('arlequin', $data) .
            My::jsLoad('models.js')
        );

        echo
        Page::breadcrumb([
            Html::escapeHTML(App::blog()->name()) => '',
            My::name()                            => '',
        ]) .
        Notices::getNotices() .

        (new Form(My::id() . 'form'))
            ->method('post')
            ->action(App::backend()->getPageURL())
            ->fields([
                (new Text('h4', __('Switcher display format'))),
                (new Div())
                    ->id('models'),
                (new Div())
                    ->items([
                        (new Para())
                            ->items([
                                (new Label(__('Switcher HTML code:'), Label::OUTSIDE_LABEL_BEFORE))
                                    ->for('s_html'),
                                (new Textarea('s_html', Html::escapeHTML($model['s_html'])))
                                    ->cols(60)
                                    ->rows(10),
                            ]),
                        (new Para())
                            ->items([
                                (new Label(__('Item HTML code:'), Label::OUTSIDE_LABEL_BEFORE))
                                    ->for('e_html'),
                                (new Input('e_html'))
                                    ->size(60)
                                    ->maxlength(200)
                                    ->value(Html::escapeHTML($model['e_html'])),
                            ]),
                        (new Para())
                            ->items([
                                (new Label(__('Active item HTML code:'), Label::OUTSIDE_LABEL_BEFORE))
                                    ->for('a_html'),
                                (new Input('a_html'))
                                    ->size(60)
                                    ->maxlength(200)
                                    ->value(Html::escapeHTML($model['a_html'])),
                            ]),
                    ]),
                (new Div())
                    ->items([
                        (new Para())
                            ->items([
                                (new Label(__('Excluded themes:'), Label::OUTSIDE_LABEL_BEFORE))
                                    ->for('exclude'),
                                (new Input('exclude'))
                                    ->size(60)
                                    ->maxlength(200)
                                    ->value(Html::escapeHTML($excluded)),
                            ]),
                        (new Note())
                            ->class('form-note')
                            ->text(__('Semicolon separated list of themes IDs (theme folder name). Ex: ductile;berlin')),
                    ]),
                (new Para())
                    ->separator(' ')
                    ->items([
                        (new Submit(['mt_action_config']))
                            ->value(__('Save')),
                        (new Submit(['mt_action_restore']))
                            ->value(__('Restore defaults')),
                        ... My::hiddenFields(),
                    ]),
            ])
        ->render();

        Page::helpBlock('arlequin');
        Page::closeModule();
    }
}
