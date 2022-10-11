<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\rocket\jobs;


use humhub\modules\queue\ActiveJob;
use humhub\modules\rocket\components\RocketApi;
use humhub\modules\rocket\Module;
use humhub\modules\user\models\Group;
use humhub\modules\user\models\GroupUser;
use humhub\modules\user\models\User;
use Yii;
use yii\queue\RetryableJobInterface;

class AddMissingRolesAndMembersToRocket extends ActiveJob implements RetryableJobInterface
{
    /**
     * @inhertidoc
     */
    private $maxExecutionTime = 60 * 60;

    /**
     * @inheritdoc
     */
    public function run()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('rocket');

        $api = new RocketApi();

        // Create missing roles
        if ($module->settings->get('syncOnGroupAdd')) {
            foreach (Group::find()->all() as $group) {
                $api->createRole($group->name);
            }
        }

        // Add missing users to roles
        $groupUsers = GroupUser::find()
            ->joinWith('user')
            ->andWhere(['user.status' => User::STATUS_ENABLED])
            ->all();
        if ($module->settings->get('syncOnUserGroupAdd')) {
            foreach ($groupUsers as $groupUser) {
                $user = $groupUser->user;
                $group = $groupUser->group;
                $api->addUserToRole($user, $group->name); // TODO if the job tries another time to add the members, it shouldn't start again from the beginning (the current user ID should be stored in the cache to start to this one)
            }
        }

        $api->logout();
    }

    /**
     * @inheritDoc
     */
    public function getTtr()
    {
        return $this->maxExecutionTime;
    }

    /**
     * @inheritDoc for RetryableJobInterface
     */
    public function canRetry($attempt, $error)
    {
        return true;
    }

}
