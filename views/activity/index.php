<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

use humhub\modules\activity\widgets\ActivityStreamViewer;
use humhub\modules\rocket\assets\Assets;
use humhub\modules\space\models\Space;
use humhub\modules\ui\view\components\View;

/**
 * @var $this View
 * @var $space Space
 */

Assets::register($this);
?>

<base target="_blank">

<?= ActivityStreamViewer::widget([
    'contentContainer' => $space,
    'view' => '@rocket/widgets/views/activityStream',
]) ?>
