# GuardianKey for Magento 2

[veja em português (pt_BR)](README_pt_BR.md)

This is a Magento 2 extension to integrate with GuardianKey.

GuardianKey is a solution to protect systems against authentication attacks. It uses Artificial Intelligence to detect if access attempts are from legitimate users. Attackers can be blocked even using the right password. GuardianKey provides an attack risk in real-time based on the user’s behavior, threat intelligence, and psychometrics (or behavioral biometrics). You can notify your users, block or log the attempts.

Learn more how GuardianKey works:
- https://guardiankey.io/
- https://guardiankey.io/resource/folder-guardian-key.pdf
- https://guardiankey.io/products/guardiankey-auth-security-enterprise/
- https://guardiankey.io/documentation/documentation-for-users/
- https://youtu.be/SZLTaQUpIas
- https://youtu.be/R5QFcH4bXuA

GuardianKey has free and paid plans, check it at https://guardiankey.io/services/guardiankey-auth-security-lite/

# Extension Installation

In summary, you should:

1. Backup database and Magento files, and put it in maintenance mode.
2. Copy the module content to the Magento folder.
3. Execute some commands required by Magento to enable the extension.
4. Configure the extension at the Magento's administration page.
5. Test it!


```bash
# Download the extension in your server
wget https://github.com/guardiankey/guardiankey-magento2-authsecurity/archive/master.zip

# unzip it
unzip master.zip

# create the extension's directory in your Magento's directory
$MAGENTOROOT = "/var/www/html/magento" # SET YOUR DIR HERE
mkdir -p $MAGENTOROOT/app/code/GuardianKey/AuthSecurity/

# Move files 
mv guardiankey-magento2-authsecurity/*  $MAGENTOROOT/app/code/GuardianKey/AuthSecurity/

# Magento's commands now
cd $MAGENTOROOT
bin/magento maintenance:enable                        # maintenance mode
bin/magento module:status GuardianKey_AuthSecurity    # check the extension's status
bin/magento module:enable GuardianKey_AuthSecurity    # enable the extension
bin/magento setup:upgrade                             # read extension's information
bin/magento setup:di:compile                          # generate some files
bin/magento module:status GuardianKey_AuthSecurity    # confirm the extension's status
bin/magento cache:clean                               # clean cache
bin/magento maintenance:disable                       # disable the maintenance mode
```

The extension's configuration can be done at path below, in the Magento's administration webpage. Below each field there is a tip. Change the values and save!  You will need some information that can be found at https://panel.guardiankey.io. 

```
Stores -> Configuration -> GuardianKey -> Auth Security
```

In the GuardianKey's panel, under Settings -> Auth Groups, you should edit your authgroup and disable email sending. The Magento's extension already sends the messages.

# Customization

You may customize the messages in the files at path below.

```
app/code/GuardianKey/AuthSecurity/Controller/Index/templates/
```

# More help

Magento extension installation: https://devdocs.magento.com/extensions/install/
GuardianKey's administration panel: https://guardiankey.io/documentation/panel-documentation/

You can also contact-us by email: contact@guardiankey.io
