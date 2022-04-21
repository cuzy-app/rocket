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
use Yii;
use yii\db\IntegrityException;
use yii\helpers\Json;
use yii\queue\RetryableJobInterface;

class AddMissingSpaceMembersToRocket extends ActiveJob implements RetryableJobInterface
{
    /**
     * @inhertidoc
     */
    private $maxExecutionTime = 60 * 60;

    /**
     * @var int
     */
    public $spaceContentContainerId;

    /**
     * @inheritdoc
     * @throws IntegrityException
     */
    public function run()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('rocket');

        $api = new RocketApi();

        // Add missing users to channels
        foreach ($this->getSettings('membersSyncRocketChannels') as $setting) {
            if (($space = $this->getSpace($setting)) === null) {
                continue;
            }
            foreach ($this->getRocketChannelOrGroupIds($setting, $space) as $rocketChannelId) {
                if (!$rocketChannelId) {
                    continue;
                }
                foreach ($space->getMembershipUser()->all() as $user) {
                    $api->inviteUserToChannel($user, $rocketChannelId); // TODO if the job tries another time to add the members, it shouldn't start again from the beginning (the current user ID should be stored in the cache to start to this one)
                }
            }
        }

        // Add missing users to groups
        foreach ($this->getSettings('membersSyncRocketGroups') as $setting) {
            if (($space = $this->getSpace($setting)) === null) {
                continue;
            }
            foreach ($this->getRocketChannelOrGroupIds($setting, $space) as $rocketGroupId) {
                if (!$rocketGroupId) {
                    continue;
                }
                foreach ($space->getMembershipUser()->all() as $user) {
                    $api->inviteUserToGroup($user, $rocketGroupId); // TODO if the job tries another time to add the members, it shouldn't start again from the beginning (the current user ID should be stored in the cache to start to this one)
                }
            }
        }

        $api->logout();
    }

    /**
     * @param $settingName
     * @return ContentContainerSetting[]
     */
    protected function getSettings($settingName)
    {
        $query = ContentContainerSetting::find()
            ->andWhere(['module_id' => 'rocket'])
            ->andWhere(['name' => $settingName]);
        if ($this->spaceContentContainerId) {
            $query->andWhere(['contentcontainer_id' => $this->spaceContentContainerId]);
        }
        return $query->all();
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
