<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

use humhub\libs\Html;
use humhub\modules\rocket\models\ModuleSettings;
use humhub\modules\ui\view\components\View;
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;

/**
 * @var $this View
 * @var $model ModuleSettings
 * @var $apiIsValid bool
 * @var $channelItemsForWebSyndication array
 * @var $groupItemsForWebSyndication array
 * @var $channelItemsForMembersSync array
 * @var $groupItemsForMembersSync array
 */
?>

<div class="panel panel-default">

    <div class="panel-heading">
        <?= Yii::t('RocketModule.config', 'Rocket.chat module configuration') ?>
    </div>

    <hr>

    <div class="panel-body">

        <?php if (!$apiIsValid): ?>
            <div class="alert alert-danger">
                <?= Yii::t('RocketModule.config', 'No Rocket channels found. Please check the API values in the module settings.') ?>
            </div>

        <?php else: ?>

            <div class="alert alert-info">
                <?= Yii::t('RocketModule.config', 'If channels are already in use in other spaces, they will not be displayed here.') ?>
            </div>

            <?php $form = ActiveForm::begin(); ?>
            <?= $form->field($model, 'webSyndicationRocketChannels')->checkboxList($channelItemsForWebSyndication) ?>
            <?= $form->field($model, 'webSyndicationRocketGroups')->checkboxList($groupItemsForWebSyndication) ?>
            <?= $form->field($model, 'membersSyncRocketChannels')->checkboxList($channelItemsForMembersSync) ?>
            <?= $form->field($model, 'membersSyncRocketGroups')->checkboxList($groupItemsForMembersSync) ?>
            <?= Html::saveButton() ?>
            <?php ActiveForm::end(); ?>

            <br><br>

            <div class="panel panel-info">
                <div class="panel-heading">
                    <?= Yii::t('RocketModule.config', 'Instructions to show this space\'s activity in the chosen Rocket.chat channels') ?>
                </div>
                <div class="panel-body">
                    <p><?= Yii::t('RocketModule.config', 'Allow Humhub to be embedded in Rocket.chat: in the {contentSecurityPolicy}, you should have:', ['contentSecurityPolicy' => '"Content Security Policy"']) ?></p>
                    <p><code>frame-ancestors 'self' to frame-ancestors 'self' <?= Url::base(true) ?></code></p>
                    <p><?= Yii::t('RocketModule.config', 'Go to {rocketUrl} -> "Custom Scripts". And in {buttonName} add:', ['rocketUrl' => 'https://MY_ROCKET_DOMAIN_NAME.TDL/admin/Layout', 'buttonName' => '"Custom Script for Logged In Users"']) ?></p>
                    <pre><code>
const humhubUrl = '<?= Url::base(true) ?>'; // Do not add a trailing /

$(function() {

  const addHumhubIntegration = function() {
    // Avoid embeding if has param in URL `layout=embedded`
    let searchParams = new URLSearchParams(window.location.search);
    if (searchParams.has('layout') && searchParams.get('layout') == 'embedded') {
      return;
    }

    $('#humhub').detach();
    let pathname = window.location.pathname.split('/');
    if ((pathname[1] === 'channel' || pathname[1] === 'group') && pathname[2]) {
      let src = humhubUrl + '/rocket/redirect?rocketChannel=' + pathname[2];
      $('#rocket-chat').append('<?= Html::encode('<div id="humhub"><iframe src="\' + src + \'" height="100%"></iframe></div>') ?>');
    }
  };

  addHumhubIntegration();

  // Update after URL changes
  let lastUrl = location.href;
  new MutationObserver(() => {
    const url = location.href;
    if (url !== lastUrl) {
      lastUrl = url;
      addHumhubIntegration();
    }
  }).observe(document, {subtree: true, childList: true});

  // Refresh every minute
  setInterval(addHumhubIntegration, 60*1000);
});
                        </code></pre>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>