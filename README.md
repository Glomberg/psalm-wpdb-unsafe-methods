# Psalm Plugin to prevent direct using provided `$wpdb` methods

### Installation

```
composer require --dev glomberg/wpdb-unsafe-methods
vendor/bin/psalm --init
vendor/bin/psalm-plugin enable glomberg/wpdb-unsafe-methods
```

### Features

### Configuration

If you follow the installation instructions, the psalm-plugin command will add this plugin configuration to the `psalm.xml` configuration file.

```xml
<?xml version="1.0"?>
<psalm errorLevel="1">
    <!--  project configuration -->

    <plugins>
        <pluginClass class="Glomberg\WpdbUnsafeMethods\Plugin" />
    </plugins>
</psalm>
```

Do not forget to add `method` tags with the names of the methods you want to forbid.
```xml
<pluginClass class="Glomberg\WpdbUnsafeMethods\Plugin">
    <method>query</method>
    <method>get_results</method>
</pluginClass>
```