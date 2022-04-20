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
use yii\helpers\Json;
use yii\queue\RetryableJobInterface;

class removeChannelsFromSpaceSettings extends ActiveJob implements RetryableJobInterface
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
        $api = new RocketApi();
        $api->initRocketChannelNames(true);
        $api->initRocketGroupNames(true);
        $api->logout();

        if (
            $api->rocketChannelNames === null || $api->rocketGroupNames === null
            || (count($api->rocketChannelNames) === 0 && count($api->rocketGroupNames) === 0)
        ) {
            return;
        }

        $this->removeChannels('webSyndicationRocketChannels', $api->rocketChannelNames);
        $this->removeChannels('webSyndicationRocketGroups', $api->rocketGroupNames);
        $this->removeChannels('membersSyncRocketChannels', $api->rocketChannelNames);
        $this->removeChannels('membersSyncRocketGroups', $api->rocketGroupNames);
    }

    protected function removeChannels($paramName, $existingChannels)
    {
        $existingChannelIds = array_keys($existingChannels);
        foreach (ContentContainerSetting::findAll(['module_id' => 'rocket', 'name' => $paramName]) as $setting) {
            $channelIds = (array)Json::decode($setting->value);
            $updateSetting = false;
            foreach ($channelIds as $key => $channelId) {
                if (!in_array($channelId, $existingChannelIds)) {
                    unset($channelIds[$key]);
                    $updateSetting = true;
                }
            }
            if ($updateSetting) {
                $setting->value = Json::encode($channelIds);
                $setting->save();
            }
        }
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
