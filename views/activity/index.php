<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

use humhub\modules\activity\widgets\ActivityBox;
use humhub\modules\rocket\assets\Assets;
use humhub\modules\space\models\Space;
use humhub\components\View;

/**
 * @var $this View
 * @var $space Space
 */

Assets::register($this);
?>

<base target="_blank">

<?= ActivityBox::widget([
    'contentContainer' => $space,
]) ?>
