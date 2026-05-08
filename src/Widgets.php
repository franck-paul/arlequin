<?php

declare(strict_types=1);

namespace Dotclear\Plugin\arlequin;

use Dotclear\App;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Module\ModuleDefine;
use Dotclear\Plugin\widgets\WidgetsStack;
use Dotclear\Plugin\widgets\WidgetsElement;

/**
 * @brief       arlequin frontend class.
 * @ingroup     arlequin
 *
 * @author      Oleksandr Syenchuk (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Widgets
{
    public static function initWidgets(WidgetsStack $w): void
    {
        $w->create(
            'arlequin',
            My::name(),
            self::parseWidget(...),
            null,
            __('Theme switcher')
        )
        ->addTitle(__('Choose a theme'))
        ->addHomeOnly()
        ->addContentOnly()
        ->addClass()
        ->addOffline();
    }

    public static function parseWidget(WidgetsElement $w): string
    {
        if ($w->offline || !$w->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        $settings = My::settings();

        /**
         * @var array{name: string, s_html: string, e_html: string, a_html: string} $model
         */
        $model    = is_string($model = $settings->get('model')) ? json_decode($model, true) : My::defaultModel();
        $excluded = is_string($excluded = $settings->get('exclude')) ? explode(';', $excluded) : [];

        $themes = array_diff_key(App::themes()->getDefines(['state' => ModuleDefine::STATE_ENABLED], true), array_flip($excluded));

        if ($themes === []) {
            return '';
        }

        /**
         * @var array<string, string>
         */
        $list = [];
        foreach ($themes as $id => $module) {
            $name = isset($module['name']) && is_string($name = $module['name']) ? $name : '';
            if ($name !== '') {
                $list[$name] = $id;
            }
        }

        App::lexical()->lexicalKeySort($list, App::lexical()::PUBLIC_LOCALE);

        # Current page URL and the associated query string. Note : the URL for
        # the switcher ($s_url) is different to the URL for an item ($e_url)
        $s_url = Http::getSelfURI();
        $e_url = $s_url;

        # If theme setting is already present in URL, we will replace its value
        $replace = preg_match('/(\\?|&)theme\\=[^&]*/', $e_url);

        # URI extension to send theme setting by query string
        if ($replace) {
            $ext = '';
        } elseif (!str_contains($e_url, '?')) {
            $ext = '?theme=';
        } else {
            $ext = (str_ends_with($e_url, '?') ? '' : '&amp;') . 'theme=';
        }

        $res = '';
        foreach ($list as $id) {
            $format = $id === App::frontend()->theme ? $model['a_html'] : $model['e_html'];

            if ($replace) {
                $e_url = preg_replace(
                    '/(\\?|&)(theme\\=)([^&]*)/',
                    '$1${2}' . addcslashes((string) $id, '$\\'),
                    (string) $e_url
                );
                $val = '';
            } else {
                $val = Html::escapeHTML(rawurlencode((string) $id));
            }

            $name = isset($themes[$id]['name']) && is_string($name = $themes[$id]['name']) ? $name : '';
            $desc = isset($themes[$id]['name']) && is_string($desc = $themes[$id]['desc']) ? $desc : '';

            if ($name !== '') {
                $res .= sprintf(
                    $format,
                    $e_url,
                    $ext,
                    $val,
                    Html::escapeHTML($name),
                    Html::escapeHTML($desc),
                    Html::escapeHTML($id)
                );
            }
        }

        # Nothing to display
        if (trim($res) === '') {
            return '';
        }

        return $w->renderDiv(
            (bool) $w->content_only,
            'arlequin ' . $w->class,
            '',
            ($w->title ? $w->renderTitle(Html::escapeHTML($w->title)) : '') . sprintf($model['s_html'], $s_url, $res)
        );
    }
}
