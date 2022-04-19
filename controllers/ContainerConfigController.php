<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\rocket\controllers;

use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\rocket\components\RocketApi;
use humhub\modules\rocket\models\ModuleSettings;
use humhub\modules\space\modules\manage\components\Controller;
use Yii;


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
        $model = new ModuleSettings(['contentContainer' => $this->contentContainer]);
        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->save()) {
            $this->view->saved();
        }

        $api = new RocketApi();
        $api->initRocketChannelNames(true);
        $api->initRocketGroupNames(true);
        $api->logout();

        return $this->render('index', [
            'model' => $model,
            'channelItems' => $api->rocketChannelNames,
            'groupItems' => $api->rocketGroupNames,
        ]);
    }
}