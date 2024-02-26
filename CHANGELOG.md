# CHANGELOG

## v1.0.8
 - More documentation and better styling of the documentation
 - Added new dependency `symfony/stimulus-bundle@^2.0` and allowed `symfony/webpack-encore-bundle@^2.0`
 - Refactored/Simplified profile in sidebar and dashboard
 - Improved styling of menus
 - Changed return type of `VoterAttributeTrait` from `?Page` to `null|string|Page`

## v1.0.6
 - Removed dependency to `coduo/php-to-string`
 - Added new bundle configuration `enable_turbo` (default `false`)
 - Added new possibility to configure Definitions. With the `OPT_ACTIONS_OVERFLOW` option you can define how many actions should be visible on the index. The rest will be hidden in an overflow menu.
 - More documentation and better styling of the documentation
 - Made access to `OPT_VISIBILITY` on Crud Actions more easy
 - Added new Twig Block in `views/dashboard.html.twig` to add custom content to the heading (Block name: `dashboard_heading`)
 - Improved styling generally

## v1.0.3
 - Add entity parameter to `getEntityTitle` / `getEntityTitlePlural` methods
 - Add entity parameter to `getLongTitle` / `getMetaTitle` methods

## v1.0.1
 - English translation
 - Vote on the default formatter if link to show should be rendered
 - Improved Titles
 - Make icon of submenu configurable
 - Moved batch actions to the `araise/table-bundle` package
 - Implemented next and previous entry action on the show page (needs to be enabled in the definition)
 - Fixed css classes merging in form types
 - Lots of styling improvements
