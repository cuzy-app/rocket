<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\rocket;

use humhub\commands\CronController;
use humhub\modules\rocket\jobs\AddMissingRolesAndMembersToRocket;
use humhub\modules\rocket\jobs\AddMissingSpaceMembersToRocket;
use humhub\modules\rocket\jobs\removeChannelsFromSpaceSettings;
use humhub\modules\rocket\jobs\SendApiRequest;
use humhub\modules\user\models\Group;
use humhub\modules\user\models\GroupUser;
use humhub\modules\user\models\User;
use Throwable;
use Yii;

class Events
{
    /**
     * @param $event
     * @return void
     * @throws Throwable
     */
    public static function onCronDailyRun($event)
    {
        if (!Yii::$app->getModule('rocket')) {
            return;
        }

        /** @var CronController $controller */
        $controller = $event->sender;
        $controller->stdout("Rocket.chat module: Adding to jobs Rocket.chat synchronization with the API ");

        Yii::$app->queue->push(new AddMissingRolesAndMembersToRocket());
        Yii::$app->queue->push(new AddMissingSpaceMembersToRocket());
        Yii::$app->queue->push(new removeChannelsFromSpaceSettings());
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

        Yii::$app->queue->push(new SendApiRequest([
            'method' => 'createRole',
            'arguments' => [$group->name]
        ]));
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

        Yii::$app->queue->push(new SendApiRequest([
            'method' => 'deleteRole',
            'arguments' => [$group->name]
        ]));
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
            Yii::$app->queue->push(new SendApiRequest([
                'method' => 'renameRole',
                'arguments' => [$changedAttributes['name'], $group->name]
            ]));
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
        if ($group === null || $user === null || !$user->isActive()) {
            return;
        }

        Yii::$app->queue->push(new SendApiRequest([
            'method' => 'addUserToRole',
            'arguments' => [$user->id, $group->name]
        ]));
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

        Yii::$app->queue->push(new SendApiRequest([
            'method' => 'removeUserFromRole',
            'arguments' => [$user->id, $group->name]
        ]));
    }

    /**
     * For `add_to_group_when_added_to_related_space` setting
     * @param $event
     */
    public static function onModelSpaceMembershipMemberAdded($event)
    {
        if (empty($event)) {
            return;
        }

        // Get HumHub user and space
        $membership = $event; // not $event->sender as it is executed by queue/run
        /** @var User $user */
        $user = $membership->user;
        /** @var \humhub\modules\space\models\Space $space */
        $space = $membership->space;

        /** @var Module $module */
        $module = Yii::$app->getModule('rocket');
        $settings = $module->settings->space($space);
        $membersSyncRocketChannels = (array)$settings->getSerialized('membersSyncRocketChannels');
        $membersSyncRocketGroups = (array)$settings->getSerialized('membersSyncRocketGroups');

        foreach ($membersSyncRocketChannels as $rocketChannelId) {
            Yii::$app->queue->push(new SendApiRequest([
                'method' => 'inviteUserToChannel',
                'arguments' => [$user->id, $rocketChannelId]
            ]));
        }
        foreach ($membersSyncRocketGroups as $rocketGroupId) {
            Yii::$app->queue->push(new SendApiRequest([
                'method' => 'inviteUserToGroup',
                'arguments' => [$user->id, $rocketGroupId]
            ]));
        }
    }


    /**
     * For `remove_from_group_when_removed_from_related_space` setting
     * @param $event
     */
    public static function onModelSpaceMembershipMemberRemoved($event)
    {
        if (empty($event)) {
            return;
        }

        // Get HumHub user and space
        $membership = $event; // not $event->sender as it is executed by queue/run
        /** @var User $user */
        $user = $membership->user;
        /** @var \humhub\modules\space\models\Space $space */
        $space = $membership->space;

        /** @var Module $module */
        $module = Yii::$app->getModule('rocket');
        $settings = $module->settings->space($space);
        $membersSyncRocketChannels = (array)$settings->getSerialized('membersSyncRocketChannels');
        $membersSyncRocketGroups = (array)$settings->getSerialized('membersSyncRocketGroups');

        foreach ($membersSyncRocketChannels as $rocketChannelId) {
            Yii::$app->queue->push(new SendApiRequest([
                'method' => 'kickUserOutOfChannel',
                'arguments' => [$user->id, $rocketChannelId]
            ]));
        }
        foreach ($membersSyncRocketGroups as $rocketGroupId) {
            Yii::$app->queue->push(new SendApiRequest([
                'method' => 'kickUserOutOfGroup',
                'arguments' => [$user->id, $rocketGroupId]
            ]));
        }
    }
}
