<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

/** @noinspection MissedFieldInspection */

use humhub\commands\CronController;
use humhub\modules\rocket\Events;
use humhub\modules\space\models\Membership;
use humhub\modules\user\models\Group;
use humhub\modules\user\models\GroupUser;

return [
    'id' => 'rocket',
    'class' => humhub\modules\rocket\Module::class,
    'namespace' => 'humhub\modules\rocket',
    'events' => [
        [
            'class' => CronController::class,
            'event' => CronController::EVENT_ON_DAILY_RUN,
            'callback' => [Events::class, 'onCronDailyRun']
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
        [
            'class' => Membership::class,
            'event' => Membership::EVENT_MEMBER_ADDED,
            'callback' => [
                Events::class,
                'onModelSpaceMembershipMemberAdded'
            ]
        ],
        [
            'class' => Membership::class,
            'event' => Membership::EVENT_MEMBER_REMOVED,
            'callback' => [
                Events::class,
                'onModelSpaceMembershipMemberRemoved'
            ]
        ],
    ],
];
?>