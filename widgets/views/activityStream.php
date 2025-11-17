<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.org/licences
 */

use humhub\modules\activity\assets\ActivityAsset;
use humhub\widgets\PanelMenu;
use yii\helpers\Html;

/* @var $this humhub\components\View */
/* @var $streamUrl string */
/* @var $options array */

ActivityAsset::register($this);
?>
<div class="panel panel-default panel-activities" id="panel-activities">
    <?= PanelMenu::widget(['id' => 'rocket-panel-activities']) ?>
    <div class="panel-heading">
        <?= Yii::t('RocketModule.base', 'What\'s new on Humhub\'s “{SpaceName}” space?', ['SpaceName' => Html::encode(Yii::$app->controller->contentContainer->name)]) ?>
    </div>
    <?= Html::beginTag('div', $options) ?>
    <div id="activityContents" class="hh-list activities" data-stream-content></div>
    <?= Html::endTag('div') ?>
</div>
