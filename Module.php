<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\rocket;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentContainerModule;
use humhub\modules\space\models\Space;
use Yii;
use yii\helpers\Url;

class Module extends ContentContainerModule
{

    /**
     * @var string defines the icon
     */
    public $icon = 'commenting-o';

    /**
     * @var string defines path for resources, including the screenshots path for the marketplace
     */
    public $resourcesPath = 'resources';

    /**
     * @inheritdoc
     */
    public function getContentContainerTypes()
    {
        return [
            Space::class,
        ];
    }

    public function getName()
    {
        return 'Rocket.chat';
    }

    public function getDescription()
    {
        return Yii::t('RocketModule.config', '');
    }

    public function getConfigUrl()
    {
        return Url::to(['/rocket/config']);
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerConfigUrl(ContentContainerActiveRecord $container)
    {
        return $container->createUrl('/rocket/container-config');
    }
}
