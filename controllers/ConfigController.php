<?php

/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\rocket\controllers;

use humhub\modules\admin\components\Controller;
use humhub\modules\rocket\models\ModuleSettings;
use Yii;

/**
 * ConfigController handles the configuration requests.
 */
class ConfigController extends Controller
{
    /**
     * @return string
     */
    public function actionIndex()
    {
        $form = new ModuleSettings();
        $storedApiUserPassword = $form->apiUserPassword;

        if ($form->load(Yii::$app->request->post())) {
            $form->restoreApiUserPasswordIfUnchanged($storedApiUserPassword);
            if ($form->validate() && $form->save()) {
                $this->view->saved();
            }
        }

        // Never echo the real stored password back into the form: any other admin could read or
        // copy it via the password field's reveal icon.
        $form->maskApiUserPasswordForDisplay();

        return $this->render('index', [
            'model' => $form,
        ]);
    }
}
