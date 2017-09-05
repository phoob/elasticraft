# Elasticraft Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased

## 0.2.2 - 2017-09-05
### Added
- Translation files for nb (not complete)
- Document counts by type added to utility page
- dfo\elasticraft\controllers\DefaultController::recreateIndex()
- dfo\elasticraft\controllers\DefaultController::getDocumentCount()
- (private) dfo\elasticraft\controllers\DefaultController::_deleteIndex()
- (private) dfo\elasticraft\controllers\DefaultController::_indexAllElements()

### Fixed
- Saving entry with matrix fires `EVENT_AFTER_SAVE_ELEMENT` for each matrix block. To not add unnecessary jobs, the check for if the element is of a type that should be indexed must be done before adding job.
- The widget added in `0.2.1` had an extra `</div>`. Now removed.
- A little cleanup

### Removed
- dfo\elasticraft\controllers\DefaultController::getIndexStats()
- dfo\elasticraft\controllers\DefaultController::deleteIndex()
- dfo\elasticraft\controllers\DefaultController::indexAllElements()

## 0.2.1 - 2017-09-04
### Added
- Widget with the date and time of the last indexing when editing an entry

### Fixed
- Error in mapping example in `config.php`

## 0.2.0 - 2017-08-31
### Changed
- New handling of dates

## 0.1.0 - 2017-07-03
### Added
- Initial release
