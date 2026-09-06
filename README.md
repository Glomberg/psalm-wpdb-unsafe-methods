# Psalm Plugin to prevent direct using provided `$wpdb` methods

### Installation

```
composer require --dev glomberg/wpdb-unsafe-methods
vendor/bin/psalm --init
vendor/bin/psalm-plugin enable glomberg/wpdb-unsafe-methods
```

### Features

- Flags configured `$wpdb` methods when the SQL argument is a raw string, concatenation, interpolated string, or `sprintf()`-like function call.
- Inspects the first argument only, so extra args such as `ARRAY_A` / `OBJECT` do not hide an unprepared query:
  `$wpdb->query($sql, ARRAY_A)` is treated the same as `$wpdb->query($sql)`.
- `$wpdb->prepare(...)` (and a variable assigned from `prepare()`) is allowed.
- `@psalm-suppress WpdbUnsafeMethodsIssue` on the line above the call still silences the issue.

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