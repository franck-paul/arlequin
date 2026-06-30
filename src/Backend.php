<?php

declare(strict_types=1);

namespace Dotclear\Plugin\arlequin;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Backend\Menus;

/**
 * @brief       arlequin backend class.
 * @ingroup     arlequin
 *
 * @author      Oleksandr Syenchuk (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Backend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem(Menus::MENU_BLOG);

        App::behavior()->addBehaviors([
            'adminDashboardFavoritesV2' => function (Favorites $favs): void {
                $favs->register(My::id(), [
                    'title'          => My::name(),
                    'url'            => My::manageUrl(),
                    'menu-icon'      => My::icon(),
                    'dashboard-icon' => My::icon(),
                    'permissions'    => App::auth()->makePermissions([
                        App::auth()::PERMISSION_CONTENT_ADMIN,
                    ]),
                ]);
            },
            'initWidgets' => Widgets::initWidgets(...),
        ]);

        return true;
    }
}
