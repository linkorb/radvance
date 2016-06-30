# Repositories

Repositories are automatically loaded from your app's `src/Repository` directory.

By default, all classes matching Pdo*Repository are automatically registered.

## Automatic Soft deletes

If your model contains a `deleted_at` property, then `$repo->remove($object);` will automatically
perform a 'soft-delete' (setting that property to the current time, and persisting the object).

All standard `find*` methods on the `BaseRepository` will automatically filter out any rows
that have been soft-deleted. You don't need to pass any extra information to these methods.

## Automatic filtering by current space

If your repository's model is a child of a space object, the repository will automatically filter 
all `find*` requests by that foreign key. So you don't need to call `$repo->findByLibraryId($libraryId)`, you can just call `$repo->findAll()` or `$repo->findByXyz($name)` and get only the records
that are connected to the current space.
