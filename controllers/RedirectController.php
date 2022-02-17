<?php
/**
 * Web Syndication
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\webSyndication\controllers;

use humhub\components\Controller;
use humhub\modules\content\models\ContentContainerSetting;
use humhub\modules\space\models\Space;
use yii\web\Response;


class RedirectController extends Controller
{

    /**
     * @return \yii\console\Response|Response
     */
    public function actionIndex($rocketChannel = null)
    {
        if (
            $rocketChannel
            && ($setting = ContentContainerSetting::findOne(['value' => $rocketChannel])) !== null
            && ($contentContainer = $setting->contentcontainer) !== null
            && ($space = $contentContainer->getPolymorphicRelation()) instanceof Space
            && ($moduleManager = $space->getModuleManager())
            && $moduleManager->isEnabled('web-syndication')
        ) {
            return $this->redirect($space->createUrl('/web-syndication/activity/index'));
        }
        return '';
    }
}
