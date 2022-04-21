<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\rocket\models;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\rocket\jobs\AddMissingRolesAndMembersToRocket;
use humhub\modules\rocket\jobs\AddMissingSpaceMembersToRocket;
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
     * @var string
     */
    public $webSyndicationRocketChannels;

    /**
     * @var string
     */
    public $webSyndicationRocketGroups;

    /**
     * @var string
     */
    public $membersSyncRocketChannels;

    /**
     * @var string
     */
    public $membersSyncRocketGroups;


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['apiUrl', 'apiUserLogin', 'apiUserPassword'], 'string'],
            [['syncOnGroupAdd', 'syncOnGroupRename', 'syncOnGroupDelete', 'syncOnUserGroupAdd', 'syncOnUserGroupRemove'], 'boolean'],
            [['webSyndicationRocketChannels', 'webSyndicationRocketGroups', 'membersSyncRocketChannels', 'membersSyncRocketGroups'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'apiUrl' => Yii::t('RocketModule.config', 'Rocket.chat API URL'),
            'apiUserLogin' => Yii::t('RocketModule.config', 'Rocket.chat API admin username'),
            'apiUserPassword' => Yii::t('RocketModule.config', 'Rocket.chat API admin password'),
            'syncOnGroupAdd' => Yii::t('RocketModule.config', 'If a group is created on Humhub, create it on Rocket.chat'),
            'syncOnGroupRename' => Yii::t('RocketModule.config', 'If a group is renamed on Humhub, rename it on Rocket.chat'),
            'syncOnGroupDelete' => Yii::t('RocketModule.config', 'If a group is deleted on Humhub, delete it from Rocket.chat'),
            'syncOnUserGroupAdd' => Yii::t('RocketModule.config', 'If a user is added to a group on Humhub, add this user to the same group name on Rocket'),
            'syncOnUserGroupRemove' => Yii::t('RocketModule.config', 'If a user is removed from a group on Humhub, add this user from the same group name on Rocket'),
            'webSyndicationRocketChannels' => Yii::t('RocketModule.config', 'Rocket.chat public channels that can show this space\'s activity'),
            'webSyndicationRocketGroups' => Yii::t('RocketModule.config', 'Rocket.chat private channels (groups) that can show this space\'s activity'),
            'membersSyncRocketChannels' => Yii::t('RocketModule.config', 'Rocket.chat public channels whose members should be synced with those in this space'),
            'membersSyncRocketGroups' => Yii::t('RocketModule.config', 'Rocket.chat private channels (groups) whose members should be synced with those in this space'),
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
            'webSyndicationRocketChannels' => Yii::t('RocketModule.config', 'See instructions below'),
            'webSyndicationRocketGroups' => Yii::t('RocketModule.config', 'See instructions below'),
            'membersSyncRocketChannels' => Yii::t('RocketModule.config', 'Members synchronization is one way, from Humhub to Rocket.chat'),
            'membersSyncRocketGroups' => Yii::t('RocketModule.config', 'Members synchronization is one way, from Humhub to Rocket.chat'),
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
            $this->webSyndicationRocketChannels = (array)$settings->getSerialized('webSyndicationRocketChannels');
            $this->webSyndicationRocketGroups = (array)$settings->getSerialized('webSyndicationRocketGroups');
            $this->membersSyncRocketChannels = (array)$settings->getSerialized('membersSyncRocketChannels');
            $this->membersSyncRocketGroups = (array)$settings->getSerialized('membersSyncRocketGroups');
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
        $module = Yii::$app->getModule('rocket');

        if (!$this->contentContainer instanceof Space) {
            $settings = $module->settings;
            $settings->set('apiUrl', rtrim(trim($this->apiUrl), "/"));
            $settings->set('apiUserLogin', trim($this->apiUserLogin));
            $settings->set('apiUserPassword', trim($this->apiUserPassword));
            $settings->set('syncOnGroupAdd', $this->syncOnGroupAdd);
            $settings->set('syncOnGroupRename', $this->syncOnGroupRename);
            $settings->set('syncOnGroupDelete', $this->syncOnGroupDelete);
            $settings->set('syncOnUserGroupAdd', $this->syncOnUserGroupAdd);
            $settings->set('syncOnUserGroupRemove', $this->syncOnUserGroupRemove);

            Yii::$app->queue->push(new AddMissingRolesAndMembersToRocket());
        } else {
            $settings = $module->settings->space($this->contentContainer);
            $settings->setSerialized('webSyndicationRocketChannels', $this->webSyndicationRocketChannels);
            $settings->setSerialized('webSyndicationRocketGroups', $this->webSyndicationRocketGroups);
            $settings->setSerialized('membersSyncRocketChannels', $this->membersSyncRocketChannels);
            $settings->setSerialized('membersSyncRocketGroups', $this->membersSyncRocketGroups);

            Yii::$app->queue->push(new AddMissingSpaceMembersToRocket(['spaceContentContainerId' => $this->contentContainer->contentcontainer_id]));
        }

        return true;
    }
}
