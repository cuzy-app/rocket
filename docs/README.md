# Rocket

Show Humhub spaces' activity in [Rocket.chat](https://rocket.chat/) and sync Humhub groups.


## Overview

- Embed Humhub space's activity in a Rocket.Chat channel:
  - If logged out Humhub: you see the activity for guests
  - If logged in Humhub: you see others user's activity, including private contents
- Synchronization from Humhub groups and groups' members to Rocket.chat roles and roles' members (1)
- Synchronization from Humhub spaces' members to Rocket.chat channels' members (2)

Humhub and Rocket.chat relationship:
 - for groups to roles, with the name of Humhub group and Rocket.chat role (must be the same)
 - for spaces to channels, with a setting in each space allowing to select which channels to sync the members (only available to system administrators)
 - for users, with the email or, if not found, the username (possible conflict is a user on Humhub has the same username as another user on Rocket.chat)

(1) Use case:
- An admin creates the group "admin" on HH. The role "admin" is automatically created on RC.
- An admin deletes the group "admin" on HH. The role "admin" is automatically deleted on RC.
- User X becomes a member of HH group "admin". He automatically becomes a member of RC role "admin"
- Then, user X is removed from the members of HH group "admin". He automatically is removed form the members of RC role "admin".

(2) Use case:
- In HH Space A settings, you tick the RC channels B and C (today we have this page: https://dev.transition-space.org/s/test-space-for-marc/web-syndication/container-config, but I will change it with checkboxes, one per RC channel)
- Then, user X becomes a member of HH Space A. He automatically becomes a member of RC channels B and C
- Then, user X is removed from the members of HH Space A. He automatically is removed form the members of RC channels B and C


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
      let src = humhubUrl + '/rocket/redirect?rocketChannels=' + pathname[2];
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