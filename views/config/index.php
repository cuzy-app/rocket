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
use humhub\modules\ui\view\components\View;

/**
 * @var $this View
 * @var $model ModuleSettings
 */
?>

<div class="container-fluid">
    <div class="panel panel-default">
        <div class="panel-heading">
            <?= Yii::t('RocketModule.config', '<strong>Rocket.chat</strong> module configuration') ?>
        </div>

        <div class="panel-body">
            <div class="alert alert-info">
                <?= Yii::t('RocketModule.config', 'To synchronize the members of a Humhub space with the members of one or more Rocket.chat channels, or to display the activity of a space in a channel, you must activate this module in the spaces concerned and configure it.') ?>
                <br><br>
                <?= Yii::t('RocketModule.config', 'Only system administrators can activate and configure this module in spaces.') ?>
            </div>
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
    </div>
</div>