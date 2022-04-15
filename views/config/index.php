<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

use humhub\libs\Html;
use humhub\modules\mass_notification\models\ModuleSettings;
use humhub\modules\ui\form\widgets\ActiveForm;

/** @var $model ModuleSettings */
?>

<div class="panel-body">

    <h4><?= Yii::t('RocketModule.config', 'Rocket.chat module configuration') ?></h4>
    <br>

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'apiUrl')->textInput() ?>
    <?= $form->field($model, 'apiUserLogin')->textInput() ?>
    <?= $form->field($model, 'apiUserPassword')->textInput(['type' => 'password']) ?>
    <?= $form->field($model, 'syncOnGroupAdd')->checkbox() ?>
    <?= $form->field($model, 'syncOnGroupRename')->checkbox() ?>
    <?= $form->field($model, 'syncOnGroupDelete')->checkbox() ?>
    <?= $form->field($model, 'syncOnUserGroupAdd')->checkbox() ?>
    <?= $form->field($model, 'syncOnUserGroupRemove')->checkbox() ?>

    <?= Html::saveButton() ?>

    <?php ActiveForm::end(); ?>

</div>