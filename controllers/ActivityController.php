<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\rocket\controllers;

use humhub\modules\content\components\ContentContainerController;


/**
 * IndexController
 */
class ActivityController extends ContentContainerController
{
    public function actionIndex()
    {
        return $this->renderAjax('index', [
            'space' => $this->space,
        ]);
    }
}
