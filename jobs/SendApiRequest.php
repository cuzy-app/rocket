<?php
/**
 * Rocket
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\moduleModel\jobs;


use humhub\modules\queue\ActiveJob;
use humhub\modules\rocket\components\RocketApi;
use yii\queue\RetryableJobInterface;

class SendApiRequest extends ActiveJob implements RetryableJobInterface
{
    /**
     * @var string
     */
    public $method;

    /**
     * @var array
     */
    public $arguments = [];

    /**
     * @inhertidoc
     */
    private $maxExecutionTime = 3 * 60;

    /**
     * @inheritdoc
     */
    public function run()
    {
        $api = new RocketApi();
        call_user_func_array([$api, $this->method], $this->arguments);
        $api->logout();
    }

    /**
     * @inheritDoc
     */
    public function getTtr()
    {
        return $this->maxExecutionTime;
    }

    /**
     * @inheritDoc for RetryableJobInterface
     */
    public function canRetry($attempt, $error)
    {
        return true;
    }

}
