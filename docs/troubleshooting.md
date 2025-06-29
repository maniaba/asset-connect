# Troubleshooting

This page provides solutions to common issues you might encounter when using CodeIgniter Asset Connect.

## Database Issues

### Migration Failed

**Issue**: The migration fails when running `php spark migrate --namespace=Maniaba\\FileConnect`.

**Solutions**:

1. Make sure your database connection is properly configured in your `.env` file or `app/Config/Database.php`.
2. Check if the `assets` table already exists. If it does, you can either drop it or run the migration with the `--force` option:
   ```bash
   php spark migrate --namespace=Maniaba\\FileConnect --force
   ```
3. Ensure you have the necessary permissions to create tables in your database.

### Entity Not Found

**Issue**: You get an error like "Entity not found" when trying to add assets to an entity.

**Solutions**:

1. Make sure the entity has been saved to the database before adding assets to it.
2. Check if the entity ID is properly set.
3. Verify that the entity class is using the `UseAssetConnectTrait`.

## File Handling Issues

### File Not Found

**Issue**: You get a "File not found" error when trying to add an asset.

**Solutions**:

1. Make sure the file path is correct and the file exists.
2. Check if the file path is absolute or relative, and adjust accordingly.
3. Verify that the PHP process has read permissions for the file.

### Unable to Save File

**Issue**: The asset is created in the database, but the file is not saved to the storage location.

**Solutions**:

1. Check if the storage directory exists and is writable by the PHP process.
2. Verify that the path generator is returning a valid path.
3. Make sure the disk configuration is correct if you're using a non-local disk.

## Asset Collection Issues

### Collection Not Found

**Issue**: You get a "Collection not found" error when trying to add an asset to a collection.

**Solutions**:

1. Make sure the collection name is correct and matches one of the collections defined in your entity's `setupAssetConnect` method.
2. Check if the collection class exists and implements the `AssetCollectionDefinitionInterface`.
3. Verify that the collection is properly registered in your configuration.

### Invalid Mime Type

**Issue**: You get an "Invalid mime type" error when trying to add an asset to a collection.

**Solutions**:

1. Check if the file's mime type is allowed by the collection's `allowedMimeTypes` method.
2. Verify that the file is not corrupted.
3. If you need to allow additional mime types, you can create a custom collection class and override the `allowedMimeTypes` method.

## Model Integration Issues

### Assets Not Loaded Automatically

**Issue**: Assets are not automatically loaded when retrieving entities from a model.

**Solutions**:

1. Make sure your model is using the `UseAssetConnectModelTrait`.
2. Check if the entity class is properly set in your model's `$returnType` property.
3. Verify that the entity is using the `UseAssetConnectTrait` and has implemented the `setupAssetConnect` method.

## Performance Issues

### Slow Asset Retrieval

**Issue**: Retrieving assets is slow, especially when dealing with many assets.

**Solutions**:

1. Consider using eager loading to load assets in a single query:
   ```php
   $model->with('assets')->find($id);
   ```
2. Use pagination when displaying assets to limit the number of assets loaded at once.
3. Optimize your database queries by adding indexes to frequently queried columns.

## Still Having Issues?

If you're still experiencing issues after trying the solutions above, you can:

1. Check the CodeIgniter logs in `writable/logs/` for more detailed error information.
2. Enable debug mode in your application to get more detailed error messages.
3. [Open an issue](https://github.com/maniaba/file-connect/issues) on the GitHub repository with a detailed description of your problem and steps to reproduce it.
