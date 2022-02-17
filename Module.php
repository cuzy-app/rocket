<?php
/**
 * Web Syndication
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\webSyndication;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentContainerModule;
use humhub\modules\space\models\Space;
use Yii;

class Module extends ContentContainerModule
{

    /**
     * @var string defines the icon
     */
    public $icon = 'eye';

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

    /**
     * @inheritdoc
     */
    public function getContentContainerName(ContentContainerActiveRecord $container)
    {
        return $this->getName();
    }

    public function getName()
    {
        return Yii::t('WebSyndicationModule.config', 'Web Syndication');
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerDescription(ContentContainerActiveRecord $container)
    {
        return $this->getDescription();
    }

    public function getDescription()
    {
        return Yii::t('WebSyndicationModule.config', '');
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerConfigUrl(ContentContainerActiveRecord $container)
    {
        return $container->createUrl('/web-syndication/container-config');
    }
}
