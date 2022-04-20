<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\rocket\jobs;


use humhub\modules\content\models\ContentContainerSetting;
use humhub\modules\queue\ActiveJob;
use humhub\modules\rocket\components\RocketApi;
use humhub\modules\rocket\Module;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\Group;
use humhub\modules\user\models\GroupUser;
use humhub\modules\user\models\User;
use Yii;
use yii\db\IntegrityException;
use yii\helpers\Json;
use yii\queue\RetryableJobInterface;

class AddMissingToRocket extends ActiveJob implements RetryableJobInterface
{
    /**
     * @inhertidoc
     */
    private $maxExecutionTime = 60 * 60;

    /**
     * @inheritdoc
     * @throws IntegrityException
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
                $api->addUserToRole($user, $group->name);
            }
        }

        // Add missing users to channels
        $settings = ContentContainerSetting::findAll(['module_id' => 'rocket', 'name' => 'membersSyncRocketChannels']);
        foreach ($settings as $setting) {
            if (($space = $this->getSpace($setting)) === null) {
                continue;
            }
            foreach ($this->getRocketChannelOrGroupIds($setting, $space) as $rocketChannelId) {
                if (!$rocketChannelId) {
                    continue;
                }
                foreach ($space->getMembershipUser()->all() as $user) {
                    $api->inviteUserToChannel($user, $rocketChannelId);
                }
            }
        }

        // Add missing users to groups
        $settings = ContentContainerSetting::findAll(['module_id' => 'rocket', 'name' => 'membersSyncRocketGroups']);
        foreach ($settings as $setting) {
            if (($space = $this->getSpace($setting)) === null) {
                continue;
            }
            foreach ($this->getRocketChannelOrGroupIds($setting, $space) as $rocketGroupId) {
                if (!$rocketGroupId) {
                    continue;
                }
                foreach ($space->getMembershipUser()->all() as $user) {
                    $api->inviteUserToGroup($user, $rocketGroupId);
                }
            }
        }

        $api->logout();
    }

    /**
     * @param ContentContainerSetting $setting
     * @return Space|null
     * @throws IntegrityException
     */
    protected function getSpace(ContentContainerSetting $setting)
    {
        if (
            ($contentContainer = $setting->contentcontainer) !== null
            && ($space = $contentContainer->getPolymorphicRelation()) instanceof Space
        ) {
            return $space;
        }
        return null;
    }

    /**
     * @param ContentContainerSetting $setting
     * @param Space $space
     * @return array
     */
    protected function getRocketChannelOrGroupIds(ContentContainerSetting $setting, Space $space)
    {
        if (
            ($moduleManager = $space->getModuleManager())
            && $moduleManager->isEnabled('rocket')
        ) {
            return (array)Json::decode($setting->value);
        }
        return [];
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
