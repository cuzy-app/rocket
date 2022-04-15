<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\rocket\controllers;

use humhub\modules\admin\permissions\ManageSpaces;
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