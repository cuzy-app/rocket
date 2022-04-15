<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\rocket;


use humhub\modules\admin\permissions\ManageSettings;
use humhub\modules\admin\widgets\SettingsMenu;
use humhub\modules\rocket\components\RocketApi;
use humhub\modules\ui\menu\MenuLink;
use humhub\modules\user\models\Group;
use humhub\modules\user\models\GroupUser;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;

class Events
{
    /**
     * @param yii\base\Event $event
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public static function onSettingsMenuInit($event)
    {
        /** @var SettingsMenu $menu */
        $menu = $event->sender;

        /** @var Module $module */
        $module = Yii::$app->getModule('rocket');

        $menu->addEntry(new MenuLink([
            'label' => $module->getName(),
            'url' => $module->getConfigUrl(),
            'sortOrder' => 932,
            'isActive' => MenuLink::isActiveState('rocket', 'config'),
            'isVisible' => Yii::$app->user->can(ManageSettings::class)
        ]));
    }


    /**
     * @param $event
     * @return void
     */
    public static function onModelGroupAfterInsert($event)
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('rocket');
        if (
            empty($event->sender)
            || !$module->settings->get('syncOnGroupAdd')
        ) {
            return;
        }

        /** @var Group $group */
        $group = $event->sender;

        (new RocketApi())->createGroup($group->name);
    }


    /**
     * @param $event
     * @return void
     */
    public static function onModelGroupAfterDelete($event)
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('rocket');
        if (
            empty($event->sender)
            || !$module->settings->get('syncOnGroupDelete')
        ) {
            return;
        }

        /** @var Group $group */
        $group = $event->sender;

        (new RocketApi())->deleteGroup($group->name);
    }


    /**
     * @param $event
     * @return void
     */
    public static function onModelGroupAfterUpdate($event)
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('rocket');
        if (
            empty($event->sender)
            || !isset($event->changedAttributes)
            || !$module->settings->get('syncOnGroupRename')
        ) {
            return;
        }

        /** @var Group $group */
        $group = $event->sender;

        // Get changed attributes
        $changedAttributes = $event->changedAttributes;

        // If name has changed
        if (array_key_exists('name', $changedAttributes)) {
            (new RocketApi())->renameGroup($changedAttributes['name'], $group->name);
        }
    }


    /**
     * @param $event
     * @return void
     */
    public static function onModelGroupUserAfterInsert($event)
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('rocket');
        if (
            empty($event->sender)
            || !$module->settings->get('syncOnUserGroupAdd')
        ) {
            return;
        }

        /** @var GroupUser $groupUser */
        $groupUser = $event->sender;

        $group = $groupUser->group;
        $user = $groupUser->user;
        if ($group === null || $user === null) {
            return;
        }

        (new RocketApi())->inviteUserToGroup($user->username, $group->name);
    }


    /**
     * @param $event
     * @return void
     */
    public static function onModelGroupUserAfterDelete($event)
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('rocket');
        if (
            empty($event->sender)
            || !$module->settings->get('syncOnUserGroupRemove')
        ) {
            return;
        }

        /** @var GroupUser $groupUser */
        $groupUser = $event->sender;

        $group = $groupUser->group;
        $user = $groupUser->user;
        if ($group === null || $user === null) {
            return;
        }

        (new RocketApi())->kickUserOutOfGroup($user->username, $group->name);
    }
}