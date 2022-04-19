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
use yii\web\Response;

/**
 * ConfigController handles the configuration requests.
 *
 * @author Marc FARRE (marc.fun)
 */
class ConfigController extends Controller
{
    /**
     * @return string|\yii\console\Response|Response
     */
    public function actionIndex()
    {
        $form = new ModuleSettings();

        if ($form->load(Yii::$app->request->post()) && $form->validate() && $form->save()) {
            return $this->redirect(['/rocket/config']);
        }

        return $this->render('index', [
            'model' => $form
        ]);
    }
}

?>
