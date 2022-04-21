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
 *
 * @author Marc FARRE (marc.fun)
 */
class ConfigController extends Controller
{
    /**
     * @return string
     */
    public function actionIndex()
    {
        $form = new ModuleSettings();

        if ($form->load(Yii::$app->request->post()) && $form->validate() && $form->save()) {
            $this->view->saved();
        }

        return $this->render('index', [
            'model' => $form
        ]);
    }
}

?>
