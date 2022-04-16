<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\rocket\models;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\rocket\jobs\AddMissingToRocket;
use humhub\modules\rocket\Module;
use humhub\modules\space\models\Space;
use Yii;
use yii\base\Model;


class ModuleSettings extends Model
{
    /**
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    /**
     * @var string
     */
    public $apiUrl;

    /**
     * @var string
     */
    public $apiUserLogin;

    /**
     * @var string
     */
    public $apiUserPassword;

    /**
     * @var string Rocket.chat channel name
     */
    public $rocketChannel;

    /**
     * @var bool
     */
    public $syncOnGroupAdd = false;

    /**
     * @var bool
     */
    public $syncOnGroupRename = false;

    /**
     * @var bool
     */
    public $syncOnGroupDelete = false;

    /**
     * @var bool
     */
    public $syncOnUserGroupAdd = false;

    /**
     * @var bool
     */
    public $syncOnUserGroupRemove = false;


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['apiUrl', 'apiUserLogin', 'apiUserPassword', 'rocketChannel'], 'string'],
            [['syncOnGroupAdd', 'syncOnGroupRename', 'syncOnGroupDelete', 'syncOnUserGroupAdd', 'syncOnUserGroupRemove'], 'boolean'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'rocketChannel' => Yii::t('RocketModule.config', 'Rocket.chat channel name'),
            'apiUrl' => Yii::t('RocketModule.config', 'Rocket.chat API URL'),
            'apiUserLogin' => Yii::t('RocketModule.config', 'Rocket.chat API admin username'),
            'apiUserPassword' => Yii::t('RocketModule.config', 'Rocket.chat API admin password'),
            'syncOnGroupAdd' => Yii::t('RocketModule.config', 'If a group is created on Humhub, create it on Rocket.chat'),
            'syncOnGroupRename' => Yii::t('RocketModule.config', 'If a group is renamed on Humhub, rename it on Rocket.chat'),
            'syncOnGroupDelete' => Yii::t('RocketModule.config', 'If a group is deleted on Humhub, delete it from Rocket.chat'),
            'syncOnUserGroupAdd' => Yii::t('RocketModule.config', 'If a user is added to a group on Humhub, add this user to the same group name on Rocket'),
            'syncOnUserGroupRemove' => Yii::t('RocketModule.config', 'If a user is removed from a group on Humhub, add this user from the same group name on Rocket'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'apiUserLogin' => Yii::t('RocketModule.config', 'This user must have the right to manage users (adding or removing to groups or channels'),
            'apiUserPassword' => Yii::t('RocketModule.config', 'This user must have the right to manage users (adding or removing to groups or channels'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('rocket');

        if (!$this->contentContainer instanceof Space) {
            $settings = $module->settings;
            $this->apiUrl = $settings->get('apiUrl');
            $this->apiUserLogin = $settings->get('apiUserLogin');
            $this->apiUserPassword = $settings->get('apiUserPassword');
            $this->syncOnGroupAdd = (bool)$settings->get('syncOnGroupAdd');
            $this->syncOnGroupRename = (bool)$settings->get('syncOnGroupRename');
            $this->syncOnGroupDelete = (bool)$settings->get('syncOnGroupDelete');
            $this->syncOnUserGroupAdd = (bool)$settings->get('syncOnUserGroupAdd');
            $this->syncOnUserGroupRemove = (bool)$settings->get('syncOnUserGroupRemove');
        } else {
            $settings = $module->settings->space($this->contentContainer);
            $this->rocketChannel = $settings->get('rocketChannel');
        }

        // Add groups sync to jobs
        Yii::$app->queue->push(new AddMissingToRocket(['firstSync' => true]));

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
        $module = Yii::$app->getModule('rocket');

        if (!$this->contentContainer instanceof Space) {
            $settings = $module->settings;
            $settings->set('apiUrl', rtrim(trim($this->apiUrl), "/"));
            $settings->set('apiUserLogin', trim($this->apiUserLogin));
            $settings->set('apiUserPassword', trim($this->apiUserPassword));
            $settings->set('syncOnGroupAdd', trim($this->syncOnGroupAdd));
            $settings->set('syncOnGroupRename', trim($this->syncOnGroupRename));
            $settings->set('syncOnGroupDelete', trim($this->syncOnGroupDelete));
            $settings->set('syncOnUserGroupAdd', trim($this->syncOnUserGroupAdd));
            $settings->set('syncOnUserGroupRemove', trim($this->syncOnUserGroupRemove));
        } else {
            $settings = $module->settings->space($this->contentContainer);
            $settings->set('rocketChannel', trim($this->rocketChannel));
        }

        return true;
    }
}
