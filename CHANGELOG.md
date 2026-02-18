# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] 2026-02-18
### Added
- Initial release.
- Public REST API endpoint `cz-article-api/v1` for post lookup by slug.
- JSON response with fields: `author`, `title`, `subtitle`, `content`, `volume`.
- `title` and `author` normalization (HTML entities decoded and plain text output).
- `subtitle` support via ACF field `sottotitolo` with post meta fallback.
- `volume` resolution from `cz_volume_items` using primary-volume-first logic.

[Unreleased]: https://github.com/erremauro/cz-article-api/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/erremauro/cz-article-api/releases/tag/v1.0.0
