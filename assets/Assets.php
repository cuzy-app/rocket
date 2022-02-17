<?php
/**
 * Web Syndication
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\webSyndication\assets;

use humhub\components\assets\AssetBundle;

// not yii\web\AssetBundle for deferred script loading - see https://docs.humhub.org/docs/develop/modules-migrate/#asset-management

class Assets extends AssetBundle
{
    public $sourcePath = '@web-syndication/resources';

    public $css = [
        'css/humhub.web-syndication.css',
    ];

    public $js = [
//        'js/humhub.web-syndication.js',
    ];
}