<?php
/**
 * Web Syndication
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\webSyndication\controllers;

use humhub\modules\admin\permissions\ManageSpaces;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\webSyndication\models\ModuleSettings;
use Yii;


class ContainerConfigController extends ContentContainerController
{
    /**
     * @inheritdoc
     */
    public function getAccessRules()
    {
        return [
            ['permission' => ManageSpaces::class],
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

        return $this->render('index', [
            'model' => $model
        ]);
    }
}