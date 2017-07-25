# aurora-module-mail-change-password-cpanel-plugin

Allows users to change passwords on their email accounts hosted by [cPanel](https://cpanel.com/).

How to install a module (taking WebMail Lite as an example of the product built on Aurora framework): [Adding modules in WebMail Lite](https://afterlogic.com/docs/webmail-lite-8/installation/adding-modules)

In `data/settings/modules/MailChangePasswordCpanelPlugin.config.json` file, you need to supply array of mail server names the feature is enabled for. If you put "*" item there, it means the feature is enabled for all accounts.

In the same file, you need to provide access credentials for cPanel user account that controls email accounts.