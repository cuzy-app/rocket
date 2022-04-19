<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

use humhub\libs\Html;
use humhub\modules\survey\models\ModuleSettings;
use humhub\modules\ui\view\components\View;
use yii\bootstrap\ActiveForm;

/**
 * @var $this View
 * @var $model ModuleSettings
 */
?>

<div class="panel panel-default">

    <div class="panel-heading"><?= Yii::t('RocketModule.config', 'Rocket.chat module configuration') ?></div>

    <hr>

    <div class="panel-body">

        <?php $form = ActiveForm::begin(); ?>
        <?= $form->field($model, 'rocketChannels')->textInput() ?>
        <?= Html::saveButton() ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>