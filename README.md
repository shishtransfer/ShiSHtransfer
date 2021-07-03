# ShiSHTransfer Source
[![author shishcat](https://img.shields.io/badge/author-ShiSHcat8214-red)](https://shishc.at)
![license wtfpl](https://img.shields.io/badge/LICENSE-WTFPL-green)
[![MadelineProto](https://img.shields.io/badge/framework--telegram-MadelineProto-yellow)](https://github.com/danog/MadelineProto)
[![AMP](https://img.shields.io/badge/framework--php-AMP-yellow)](https://github.com/amphp/)
### This project is dead. The code of it is bad, wasteful of resources and probably broken. I made this project only to learn.
### ⚠️ I don't assume any responsability. The software in this repository is provided without any warranty. If you decide to run this, you fully understand what it does and everything caused by it is completely your fault.
### A ban-resistant uploader on Telegram Network.
## Behavior
- Splits file in parts on-the-fly to bypass the 2 GB upload limit
- Forwards file to another Telegram account of the project to still access the file if an account gets banned
- Forwards file to an external account outside the account network to recover the file if both accounts get banned
## Requirements
- MariaDB/MysqL
- PHP 7.4 CLI 
- systemd (you can use whatever daemon you preefer but we officially support and give configs only for systemd)
- ATLEAST 2 high-trust Telegram accounts, preferably on the nearest DC the server location
- One high-trust Telegram account where all file get sent to recover them in case of a ban
- Redis on default port (you can change that in ShishcatUploader/index.php)
## Installation
- Create a MySQL database, and import the "shishtransfer_schema.sql" file in the root of this repository.
- run `composer install`
- Put the 2 high trust accounts in session folder and compile 
- Install sf_systemctl and sfa_systemctl as service
- Configure config.json
- Start the systemctl services (for sfa we advice to use php systemctl_list.php start | bash && php systemctl_list.php enable | bash)
### Contact me: @shishcat2 on Telegram or make an issue for support
I decided to opensourcify and kill my instance of this project because I didn't want to get any trouble with Telegram.

The files that you can find in the root are an HTTP server for accessing/uploading telegram files, ShishcatUploader contains the file splitting logic, the site and the api, it connects internally to the HTTP server to download/upload.
