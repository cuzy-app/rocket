<?php
/**
 * Web Syndication
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\webSyndication\models;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\space\models\Space;
use humhub\modules\webSyndication\Module;
use Yii;
use yii\base\Model;


class ModuleSettings extends Model
{
    /**
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    /**
     * @var string Rocket.chat channel name
     */
    public $rocketChannel;


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['rocketChannel'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'rocketChannel' => Yii::t('WebSyndicationModule.config', 'Rocket.chat channel name'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
        ];
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('web-syndication');

        if ($this->contentContainer instanceof Space) {
            $settings = $module->settings->space($this->contentContainer);

            $this->rocketChannel = $settings->get('rocketChannel');
        }

        parent::init();
    }


    /**
     * Saves the current model values to the current user or globally.
     *
     * @return boolean success
     */
    public function save()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('web-syndication');

        if ($this->contentContainer instanceof Space) {
            $settings = $module->settings->space($this->contentContainer);
            
            $settings->set('rocketChannel', $this->rocketChannel);
        }

        return true;
    }
}
