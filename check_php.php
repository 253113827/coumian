<?php
echo "PHP版本: " . PHP_VERSION . "\n";
echo "已加载的扩展:\n";
print_r(get_loaded_extensions());
echo "\n\nPDO驱动:\n";
print_r(PDO::getAvailableDrivers());
?>
