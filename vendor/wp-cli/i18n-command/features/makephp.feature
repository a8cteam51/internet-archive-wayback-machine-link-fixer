Feature: Generate PHP files from PO files

  Background:
    Given an empty directory

  Scenario: Bail for invalid source directories
    When I try `wp i18n make-php foo`
    Then STDERR should contain:
      """
      Error: Source file or directory does not exist.
      """
    And the return code should be 1

  Scenario: Uses source folder as destination by default
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin-de_DE.po file:
      """
      # Copyright (C) 2018 Foo Plugin
      # This file is distributed under the same license as the Foo Plugin package.
      msgid ""
      msgstr ""
      "Project-Id-Version: Foo Plugin\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/foo-plugin\n"
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      "Language: de_DE\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      "POT-Creation-Date: 2018-05-02T22:06:24+00:00\n"
      "PO-Revision-Date: 2018-05-02T22:06:24+00:00\n"
      "X-Domain: foo-plugin\n"
      "Plural-Forms: nplurals=2; plural=(n != 1);\n"

      #: foo-plugin.js:15
      msgid "Foo Plugin"
      msgstr "Foo Plugin"
      """

    When I run `wp i18n make-php foo-plugin`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the foo-plugin/foo-plugin-de_DE.l10n.php file should exist

  Scenario: Allows setting custom destination directory
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin-de_DE.po file:
      """
      # Copyright (C) 2018 Foo Plugin
      # This file is distributed under the same license as the Foo Plugin package.
      msgid ""
      msgstr ""
      "Project-Id-Version: Foo Plugin\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/foo-plugin\n"
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      "Language: de_DE\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      "POT-Creation-Date: 2018-05-02T22:06:24+00:00\n"
      "PO-Revision-Date: 2018-05-02T22:06:24+00:00\n"
      "X-Domain: foo-plugin\n"
      "Plural-Forms: nplurals=2; plural=(n != 1);\n"

      #: foo-plugin.js:15
      msgid "Foo Plugin"
      msgstr "Foo Plugin"
      """

    When I run `wp i18n make-php foo-plugin result`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the result/foo-plugin-de_DE.l10n.php file should exist

  Scenario: Does include headers
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin-de_DE.po file:
      """
      # Translation of 5.2.x in German
      # This file is distributed under the same license as the 5.2.x package.
      msgid ""
      msgstr ""
      "PO-Revision-Date: 2019-03-28 19:42+0300\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      "Plural-Forms: nplurals=2; plural=n != 1;\n"
      "X-Generator: Poedit 2.2.1\n"
      "Project-Id-Version: Development (5.2.x)\n"
      "Language: de_DE\n"
      "POT-Creation-Date: \n"
      "Last-Translator: \n"
      "Language-Team: \n"

      #. translators: Translate this to the correct language tag for your locale, see
      #. https://www.w3.org/International/articles/language-tags/ for reference. Do
      #. not translate into your own language.
      #: wp-includes/general-template.php:716
      msgid "html_lang_attribute"
      msgstr "de-DE"

      #. translators: 'rtl' or 'ltr'. This sets the text direction for WordPress.
      #: wp-includes/class-wp-locale.php:223
      msgctxt "text direction"
      msgid "ltr"
      msgstr "ltr"

      #. translators: $dec_point argument for https://secure.php.net/number_format,
      #. default is .
      #: wp-includes/class-wp-locale.php:215
      msgid "number_format_decimal_point"
      msgstr ","

      #. translators: $thousands_sep argument for
      #. https://secure.php.net/number_format, default is ,
      #: wp-includes/class-wp-locale.php:202
      msgid "number_format_thousands_sep"
      msgstr "."

      #. translators: %s: Plugin name and version
      #: wp-includes/script-loader.php:620
      msgid "Update %s now"
      msgstr "Jetzt %s aktualisieren"

      #. translators: Privacy data request subject. 1: Site name, 2: Name of the action
      #: wp-includes/user.php:3445
      msgid "[%1$s] Confirm Action: %2$s"
      msgstr "[%1$s] Aktion bestätigen: %2$s"

      #. translators: %s: Site name.
      #: wp-includes/user.php:3175
      msgid "[%s] Erasure Request Fulfilled"
      msgstr "[%s] Löschauftrag ausgeführt"

      #: wp-admin/includes/file.php:2415
      msgid "[%s] Personal Data Export"
      msgstr "[%s] Export personenbezogener Daten"
      """

    When I run `wp i18n make-php foo-plugin`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And STDERR should be empty
    And the foo-plugin/foo-plugin-de_DE.l10n.php file should contain:
      """
      <?php
      return ['domain'=>NULL,'plural-forms'=>'nplurals=2; plural=n != 1;','language'=>'de_DE','project-id-version'=>'Development (5.2.x)','pot-creation-date'=>'','po-revision-date'=>'2019-03-28 19:42+0300','x-generator'=>'Poedit 2.2.1','messages'=>['html_lang_attribute'=>'de-DE','text directionltr'=>'ltr','number_format_decimal_point'=>',','number_format_thousands_sep'=>'.','Update %s now'=>'Jetzt %s aktualisieren','[%1$s] Confirm Action: %2$s'=>'[%1$s] Aktion bestätigen: %2$s','[%s] Erasure Request Fulfilled'=>'[%s] Löschauftrag ausgeführt','[%s] Personal Data Export'=>'[%s] Export personenbezogener Daten']];
      """

  Scenario: Does include translations
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin-de_DE.po file:
      """
      # Copyright (C) 2018 Foo Plugin
      # This file is distributed under the same license as the Foo Plugin package.
      msgid ""
      msgstr ""
      "Project-Id-Version: Foo Plugin\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/foo-plugin\n"
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      "Language: de_DE\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      "POT-Creation-Date: 2018-05-02T22:06:24+00:00\n"
      "PO-Revision-Date: 2018-05-02T22:06:24+00:00\n"
      "X-Domain: foo-plugin\n"
      "Plural-Forms: nplurals=2; plural=(n != 1);\n"

      #: foo-plugin.js:15
      msgctxt "Plugin Name"
      msgid "Foo Plugin (EN)"
      msgstr "Foo Plugin (DE)"

      #: foo-plugin.js:15
      msgid "Foo Plugin"
      msgstr "Bar Plugin"

      #: foo-plugin.php:60
      msgid "You have %d new message"
      msgid_plural "You have %d new messages"
      msgstr[0] "Sie haben %d neue Nachricht"
      msgstr[1] "Sie haben %d neue Nachrichten"
      """

    When I run `wp i18n make-php foo-plugin`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the foo-plugin/foo-plugin-de_DE.l10n.php file should contain:
      """
      return ['domain'=>'foo-plugin','plural-forms'=>'nplurals=2; plural=(n != 1);','language'=>'de_DE','project-id-version'=>'Foo Plugin','pot-creation-date'=>'2018-05-02T22:06:24+00:00','po-revision-date'=>'2018-05-02T22:06:24+00:00','messages'=>['Plugin NameFoo Plugin (EN)'=>'Foo Plugin (DE)','Foo Plugin'=>'Bar Plugin','You have %d new message'=>'Sie haben %d neue Nachricht' . "\0" . 'Sie haben %d neue Nachrichten']];
      """

  Scenario: Excludes strings without translations
    Given an empty foo-plugin directory
    And a foo-plugin/foo-plugin-de_DE.po file:
      """
      # Copyright (C) 2018 Foo Plugin
      # This file is distributed under the same license as the Foo Plugin package.
      msgid ""
      msgstr ""
      "Project-Id-Version: Foo Plugin\n"
      "Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/foo-plugin\n"
      "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
      "Language-Team: LANGUAGE <LL@li.org>\n"
      "Language: de_DE\n"
      "MIME-Version: 1.0\n"
      "Content-Type: text/plain; charset=UTF-8\n"
      "Content-Transfer-Encoding: 8bit\n"
      "POT-Creation-Date: 2018-05-02T22:06:24+00:00\n"
      "PO-Revision-Date: 2018-05-02T22:06:24+00:00\n"
      "X-Domain: foo-plugin\n"
      "Plural-Forms: nplurals=2; plural=(n != 1);\n"

      #: foo-plugin.php:10
      msgid "I exist"
      msgstr "I exist (DE)"

      #: foo-plugin.php:20
      msgid "I am empty"
      msgstr ""

      #: foo-plugin.php:30
      msgid "You have %d new message"
      msgid_plural "You have %d new messages"
      msgstr[0] ""
      msgstr[1] ""
      """

    When I run `wp i18n make-php foo-plugin`
    Then STDOUT should contain:
      """
      Success: Created 1 file.
      """
    And the return code should be 0
    And the foo-plugin/foo-plugin-de_DE.l10n.php file should contain:
      """
      I exist
      """
    And the foo-plugin/foo-plugin-de_DE.l10n.php file should not contain:
      """
      I am empty
      """
    And the foo-plugin/foo-plugin-de_DE.l10n.php file should not contain:
      """
      new message
      """
