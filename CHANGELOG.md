# Change Log

## 1.4.1 - Unreleased

### Fixed

- Removed columns method call when columns are already when overriding query.

## 1.4.0 - 2024-11-15

### Added

- Added more format options to AbstractMapper::formatData() method.
- Added QueryParams class.
- Added setQueryParams method to AbstractRequestMapperQuery class.
- Added AbstractAliasTree class and AliasTreeInterface interface.
- Added 'like' (lk / \~) and 'not like' (nl / !\~) operators to FiltersQueryParam.
- Added AbstractModel::hasSideModel() function.

## 1.3.0 - 2023-09-17

### Added

- Added support for arrays in filter query params. (ex. $filters=state eq 'pending', 'enabled')
- Added SideModelMap to better support mixed data types.
- Added default 'id' filter implementationt to RequestMapperQuery.
- Added getCleanQueryParamString function to query param classes.
- Added some PHPUnit tests.
- PHPStan static analysis.

### Changed

- AbstractRequestMapperQuery::isValidFilter() now handles 'id' by default.

## 1.2.3 - 2023-08-29

### Changed

- Changed it so side models are less strict about their type.
- Model setSiteModel function now properly supports MapperResult. (getSideModels-\>set() not yet supported.)

## 1.2.2 - 2023-07-29

### Fixed

- Fixed bad class reference in AbstractTree.

## 1.2.1 - 2023-07-15

### Fixed

- Fixed missing semicolons in KeyFormatter class.

## 1.2.0 - 2023-06-08

### Added

- Added ability to add arbitrary data to models.
- Added 'Add' functions to query params to append additional values to any existing ones.

### Changed

- Validator now only cleans data without errors.

## 1.1.1 - 2023-04-19

### Fixed

- Fixed wrong return type in FiltersQueryParam.

## 1.1.0 - 2023-04-11

### Changed

- Changed mapperQuery constructor to include ConnectionInterface.
- Removed ConnectionInterface parameter from overrideModel function in MapperQuery.

## 1.0.1 - 2023-03-05

### Changed

- Updated AbstractRelationMapper to use ConnectionTrait similar to
  AbstractMapper.

## 1.0.0 - 2022-12-28

Initial release.
