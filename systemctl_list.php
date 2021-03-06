<?php
namespace ShiSHTransferServer\InstallingUtils;
include "./Config.php";
echo "sudo systemctl $argv[1] ";
(new \ShiSHTransferServer\ConfigParser\config(1))->generateSystemctlTemplate();
echo "\n";