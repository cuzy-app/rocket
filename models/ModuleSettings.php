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
     * What a stored secret (the API admin password) renders as once saved. Submitting this value
     * unchanged keeps the stored password; clearing the field and submitting empty deletes it.
     * Never echo the real stored password back into the form: any other admin could read or copy
     * it via the password field's reveal icon.
     */
    public const SECRET_PLACEHOLDER = '••••••••';

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
            'apiUserLogin' => Yii::t('RocketModule.config', 'This user must have the right to manage users (adding or removing groups or channels)'),
            'apiUserPassword' => Yii::t('RocketModule.config', 'This user must have the right to manage users (adding or removing groups or channels)'),
            'webSyndicationRocketChannels' => Yii::t('RocketModule.config', 'See instructions below'),
            'webSyndicationRocketGroups' => Yii::t('RocketModule.config', 'See instructions below'),
            'membersSyncRocketChannels' => Yii::t('RocketModule.config', 'Members synchronization is one way, from Humhub to Rocket.chat'),
            'membersSyncRocketGroups' => Yii::t('RocketModule.config', 'Members synchronization is one way, from Humhub to Rocket.chat'),
        ];
    }

    /**
     * Replaces the stored password with the placeholder for display. This class doubles as
     * {@see \humhub\modules\rocket\components\RocketApi}'s settings accessor (it builds its own
     * `new ModuleSettings()` for every API call), so masking cannot live in {@see init()} — that
     * would hand the literal placeholder to Rocket.chat as the admin login password. Only the
     * config controller may call this, and only right before rendering the form.
     */
    public function maskApiUserPasswordForDisplay(): void
    {
        if ((string)$this->apiUserPassword !== '') {
            $this->apiUserPassword = self::SECRET_PLACEHOLDER;
        }
    }

    /**
     * If the submitted password is still the placeholder, the admin left it untouched: restore
     * the real value so {@see save()} does not overwrite it with the placeholder bullets.
     */
    public function restoreApiUserPasswordIfUnchanged(?string $stored): void
    {
        if ($this->apiUserPassword === self::SECRET_PLACEHOLDER) {
            $this->apiUserPassword = $stored;
        }
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
     * @return bool success
     */
    public function save()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('rocket');

        if (!$this->contentContainer instanceof Space) {
            $settings = $module->settings;
            $settings->set('apiUrl', rtrim(trim((string)$this->apiUrl), '/'));
            $settings->set('apiUserLogin', trim((string)$this->apiUserLogin));
            $settings->set('apiUserPassword', trim((string)$this->apiUserPassword));
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
