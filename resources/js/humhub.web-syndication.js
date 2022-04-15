humhub.module('rocket', function (module, require, $) {

    // Get varName
    // Caution : value is not set right away 
    var varName = module.config.varName;

    module.initOnPjaxLoad = true;

    /**
     * The init function will be called once all Humhub scripts are available.
     * Caution!!! if coming from an other page, init function is loaded BEFORE pjax has loaded document!
     *
     * @param isPjax
     */
    var init = function (isPjax) {
        // Do some global initialization work, which needs to run in any case
        if (isPjax) {
            /**
             * Runs only after a pjax page load, but doesn't wait for all elements to be loaded
             * Once this JS asset file is loaded, this init function runs even on others pages (even in others modules)
             */
            $(function () {
                // After all elements are loaded
            });
        } else {
            /**
             * Runs only after fresh page load, but doesn't wait for all elements to be loaded
             */
            $(function () {
                // After all elements are loaded
            });
        }
    };

    var myFunction = function () {
        console.log('ok');
    };

    /**
     * Outside of init some modules may not be available, so make sure to follow one of the other migration options
     * when using requiring a js module included in CoreAssetBundle.
     */

    module.export({
        init: init,
        myFunction: myFunction
    });
});