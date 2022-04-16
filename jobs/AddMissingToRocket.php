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
use humhub\modules\user\models\Group;
use humhub\modules\user\models\GroupUser;
use yii\queue\RetryableJobInterface;

class AddMissingToRocket extends ActiveJob implements RetryableJobInterface
{
    /**
     * @var bool
     */
    public $firstSync = false;

    /**
     * @inhertidoc
     */
    private $maxExecutionTime = 60 * 60;

    /**
     * @inheritdoc
     */
    public function run()
    {
        $api = new RocketApi();

        foreach (Group::find()->all() as $group) {
            $api->createGroup($group->name);
        }
        foreach (GroupUser::find()->all() as $groupUser) {
            $user = $groupUser->user;
            $group = $groupUser->group;
            $api->inviteUserToGroup($user, $group->name);
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
