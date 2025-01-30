# OBJECTS

A helper class for use with WordPress site dependencies such as themes, plugins etc.

## Install Plugins

Sometimes you need to plugins installed in your test environment. This class has a few helper methods for downloading and installing plugins. 

### From Remote .zip

This method allows you to pass just the URL to a plugin, either WordPress plugin directory, git or any other publically accessible url.

```php 
// Set a constant for the path to the test install path
$wp_install_path = dirname( __FILE__, 2 ) . '/wordpress';
define( 'TEST_WP_ROOT', $wp_install_path );

// Inside your wpunit bootstrap file.
try {
    // Attempt to download the plugin. Throws exceptions if fails, so wrap in a try/catch.
    WP_Dependencies::install_remote_plugin_from_zip(
        'https://some.url/file.zip', 
        TEST_WP_ROOT
    );
} catch (\Throwable $th) {
    print "Failed to install plugin";
    print $th->getMessage();
    print "Cancelling setup";
    exit;
}


// Once installed, its just a case of activating and runing any setup needed.
WP_Dependencies::activate_plugin('/achme_plugin/plugin.php');
add_option('ache_setting', 1);
add_option('ache_setting_2', 'disabled');
```
### Activate Plugins

You can easily activate any installed plugins, this follows the same workflow as core WP, so all activation hooks should be called on set.

```php 
  WP_Dependencies::activate_plugin('/achme_plugin/plugin.php');
```
As with the above example (*From Remote .zip*), this is best done in the **tests_add_filter** callback.
