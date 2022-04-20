<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\rocket\controllers;

use humhub\components\Controller;
use humhub\modules\content\models\ContentContainerSetting;
use humhub\modules\rocket\Module;
use humhub\modules\space\models\Space;
use Yii;
use yii\helpers\BaseInflector;
use yii\web\Response;


class RedirectController extends Controller
{

    /**
     * @return \yii\console\Response|Response|string
     * @throws \yii\db\IntegrityException
     */
    public function actionIndex($rocketChannel = null)
    {
        if (!$rocketChannel) {
            return '';
        }

        /** @var Module $module */
        $module = Yii::$app->getModule('rocket');
        $settings = $module->settings;
        $rocketChannelNames = (array)$settings->getSerialized('rocketChannelNames');
        $rocketGroupNames = (array)$settings->getSerialized('rocketGroupNames');
        
        $rocketChannelOrGroupId =
            array_search(BaseInflector::slug($rocketChannel), $rocketChannelNames, true) ?:
                array_search(BaseInflector::slug($rocketChannel), $rocketGroupNames, true);

        if (!$rocketChannelOrGroupId) {
            return '';
        }

        $setting = ContentContainerSetting::find()
            ->andWhere(['module_id' => 'rocket'])
            ->andWhere(['or',
                ['name' => 'webSyndicationRocketChannels'],
                ['name' => 'webSyndicationRocketGroups'],
            ])
            ->andWhere(['like', 'value', '"' . $rocketChannelOrGroupId . '"'])
            ->one();

        if (
            $setting !== null
            && ($contentContainer = $setting->contentcontainer) !== null
            && ($space = $contentContainer->getPolymorphicRelation()) instanceof Space
            && ($moduleManager = $space->getModuleManager())
            && $moduleManager->isEnabled('rocket')
        ) {
            return $this->redirect($space->createUrl('/rocket/activity/index'));
        }
        return '';
    }
}
