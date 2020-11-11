## v3.0.0
* styte: apply CS fixer
* build(composer): update composer run scripts
* build(composer): Add dev-master alias on 3.0.x-dev
* feat(error): Add Exception classes
  > 404 errors now throws a `\Emri99\Gitlab\Exception\NotFoundException`

**BC changes:**
* `\RuntimeException` have been replaced with `\Emri99\Gitlab\Exception\GitlabApiClientException`

## v2.0.3
fix: detection of empty response
fix: remove PHP notice warning on missing Content-Type response header

## v2.0.2
fix: Allow usage of null parameter to pass optional url argument

Previously, if arguments pass as `$api->projects($idOrName)` were failing if `$idOrName` was null, expecting a scalar type, now ignore arguments if it's value is null.

This allow to use `$api->projects($idOrName)`:
* to request `/projects` when `$idOrName` is null
* to request `/projects/{idOrName}` otherwise

## v2.0.1
* fix: POST/PUT requests handling
  * use correct content-type `x-www-form-urlencoded`or `multipart/form-data` when files supplied
* refacto: GET/DELETE requests handling
* remove: PATCH request (no used on gitlab api currently)
* improvement: response error handling
* test: update test

## v2.0.0
* Throw Exception on response status code >= 400

## v1.0.0
* Initial version


