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
use humhub\modules\space\models\Space;
use yii\web\Response;


class RedirectController extends Controller
{

    /**
     * @return \yii\console\Response|Response
     */
    public function actionIndex($rocketChannels = null)
    {
        if (
            $rocketChannels
            && ($setting = ContentContainerSetting::findOne(['value' => $rocketChannels])) !== null
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
