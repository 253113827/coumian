<?php
echo "PHP Version: " . phpversion() . "\n\n";
echo "Installed Extensions:\n";
print_r(get_loaded_extensions());

echo "\n\nChecking Required Extensions:\n";
$required_extensions = ['openssl', 'pdo_mysql', 'mysqli', 'curl', 'fileinfo', 'zip'];
foreach ($required_extensions as $ext) {
    echo $ext . ": " . (extension_loaded($ext) ? "Installed" : "Not Installed") . "\n";
}
?>
