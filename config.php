<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

/** @noinspection MissedFieldInspection */

use humhub\modules\admin\widgets\SettingsMenu;
use humhub\modules\rocket\Events;
use humhub\modules\user\models\Group;
use humhub\modules\user\models\GroupUser;

return [
    'id' => 'rocket',
    'class' => humhub\modules\rocket\Module::class,
    'namespace' => 'humhub\modules\rocket',
    'events' => [
        [
            'class' => SettingsMenu::class,
            'event' => SettingsMenu::EVENT_INIT,
            'callback' => [Events::class, 'onSettingsMenuInit']
        ],
        [
            'class' => Group::class,
            'event' => Group::EVENT_AFTER_INSERT,
            'callback' => [
                Events::class,
                'onModelGroupAfterInsert'
            ]
        ],
        [
            'class' => Group::class,
            'event' => Group::EVENT_AFTER_DELETE,
            'callback' => [
                Events::class,
                'onModelGroupAfterDelete'
            ]
        ],
        [
            'class' => Group::class,
            'event' => Group::EVENT_AFTER_UPDATE,
            'callback' => [
                Events::class,
                'onModelGroupAfterUpdate'
            ]
        ],
        [
            'class' => GroupUser::class,
            'event' => GroupUser::EVENT_AFTER_INSERT,
            'callback' => [
                Events::class,
                'onModelGroupUserAfterInsert'
            ]
        ],
        [
            'class' => GroupUser::class,
            'event' => GroupUser::EVENT_AFTER_DELETE,
            'callback' => [
                Events::class,
                'onModelGroupUserAfterDelete'
            ]
        ],
    ],
];
?>