# Rocket

Show Humhub spaces' activity in [Rocket.chat](https://rocket.chat/) and sync Humhub groups.


## Overview

- Embed Humhub space's activity in a Rocket.Chat channel:
  - If logged out Humhub: you see activity for guests
  - If logged in Humhub: you see others user's activity, including private contents
- Groups synchronization from Humhub to Rocket


## Embed a Rocket.chat channel on Humhub

You don't need this module, you can do it with the [Custom pages module](https://www.humhub.com/en/marketplace/custom_pages/), by adding an "iframe" page or a snippet containing this URL (replace uppercase characters): `https://ROCKET_DOMAIN_NAME.TDL/channel/CHANNEL_NAME?layout=embedded`


## Configuration

Go to "Administration" -> "Settings" -> "Rocket.chat"

### Embed Humhub space's activity in a Rocket.Chat channel

#### In Rocket.chat

Go to https://ROCKET_DOMAIN_NAME.TDL/admin/Layout -> "Custom Scripts". And in "Custom Script for Logged In Users" add (update `HUMHUB_DOMAIN_NAME.TDL` const):
```
const humhubUrl = 'https://HUMHUB_DOMAIN_NAME.TDL'; // Do not add a trailing /

$(function() {
  
  const addHumhubIntegration = function() {
    // Avoid embeding if has param in URL `layout=embedded`
    let searchParams = new URLSearchParams(window.location.search);
    if (searchParams.has('layout') && searchParams.get('layout') == 'embedded') {
      return;
    }
    
    $('#humhub').detach();
    let pathname = window.location.pathname.split('/');
    if ((pathname[1] === 'channel' || pathname[1] === 'group') && pathname[2]) {
      let src = humhubUrl + '/rocket/redirect?rocketChannel=' + pathname[2];
      $('#rocket-chat').append('<div id="humhub"><iframe src="' + src + '" height="100%"></iframe></div>');
    }
  };
  
  addHumhubIntegration();
  
  // Update after URL changes
  let lastUrl = location.href; 
  new MutationObserver(() => {
    const url = location.href;
    if (url !== lastUrl) {
      lastUrl = url;
      addHumhubIntegration();
    }
  }).observe(document, {subtree: true, childList: true});
  
  // Refresh every minute
  setInterval(addHumhubIntegration, 60*1000);
});
```

#### In Humhub

Activate the module in the space and go to the module configuration to setup the Rocket.chat channel. 