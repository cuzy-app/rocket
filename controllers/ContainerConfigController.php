<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\rocket\controllers;

use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\content\models\ContentContainerSetting;
use humhub\modules\rocket\components\RocketApi;
use humhub\modules\rocket\models\ModuleSettings;
use humhub\modules\space\modules\manage\components\Controller;
use Yii;
use yii\helpers\Json;


class ContainerConfigController extends Controller
{
    /**
     * @inheritdoc
     */
    public function getAccessRules()
    {
        return [
            ['permission' => ManageModules::class],
        ];
    }


    /**
     * Create an entry
     */
    public function actionIndex()
    {
        $api = new RocketApi();
        $api->initRocketChannelNames(true);
        $api->initRocketGroupNames(true);
        $api->logout();

        $apiIsValid = $api->rocketChannelNames !== null && $api->rocketGroupNames !== null;

        if ($apiIsValid) {
            $model = new ModuleSettings(['contentContainer' => $this->contentContainer]);
            if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->save()) {
                $this->view->saved();
            }

            $channelItemsForWebSyndication = $api->rocketChannelNames;
            $groupItemsForWebSyndication = $api->rocketGroupNames;
            $channelItemsForMembersSync = $api->rocketChannelNames;
            $groupItemsForMembersSync = $api->rocketGroupNames;

            // Remove Rocket channels or groups already in use in others spaces for web syndication
            $settings = ContentContainerSetting::find()
                ->andWhere(['module_id' => 'rocket'])
                ->andWhere(['or',
                    ['name' => 'webSyndicationRocketChannels'],
                    ['name' => 'webSyndicationRocketGroups'],
                ])
                ->andWhere(['not', ['contentcontainer_id' => $this->contentContainer->contentcontainer_id]]) // others spaces only
                ->all();
            foreach ($settings as $setting) {
                foreach ((array)Json::decode($setting->value) as $rocketChannelOrGroupId) {
                    if (array_key_exists($rocketChannelOrGroupId, $channelItemsForWebSyndication)) {
                        unset($channelItemsForWebSyndication[$rocketChannelOrGroupId]);
                    }
                    if (array_key_exists($rocketChannelOrGroupId, $groupItemsForWebSyndication)) {
                        unset($groupItemsForWebSyndication[$rocketChannelOrGroupId]);
                    }
                }
            }

            // Remove Rocket channels or groups already in use in others spaces for members sync
            $settings = ContentContainerSetting::find()
                ->andWhere(['module_id' => 'rocket'])
                ->andWhere(['or',
                    ['name' => 'membersSyncRocketChannels'],
                    ['name' => 'membersSyncRocketGroups'],
                ])
                ->andWhere(['not', ['contentcontainer_id' => $this->contentContainer->contentcontainer_id]]) // others spaces only
                ->all();
            foreach ($settings as $setting) {
                foreach ((array)Json::decode($setting->value) as $rocketChannelOrGroupId) {
                    if (array_key_exists($rocketChannelOrGroupId, $channelItemsForMembersSync)) {
                        unset($channelItemsForMembersSync[$rocketChannelOrGroupId]);
                    }
                    if (array_key_exists($rocketChannelOrGroupId, $groupItemsForMembersSync)) {
                        unset($groupItemsForMembersSync[$rocketChannelOrGroupId]);
                    }
                }
            }
        }

        return $this->render('index', [
            'model' => $model ?? null,
            'apiIsValid' => $apiIsValid,
            'channelItemsForWebSyndication' => $channelItemsForWebSyndication ?? null,
            'groupItemsForWebSyndication' => $groupItemsForWebSyndication ?? null,
            'channelItemsForMembersSync' => $channelItemsForMembersSync ?? null,
            'groupItemsForMembersSync' => $groupItemsForMembersSync ?? null,
        ]);
    }
}