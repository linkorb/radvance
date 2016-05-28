ObjectStorage
=============

ObjectStorage is a very scalable and simple solution for storing data.

It can be used to store files, or any other blobs of text.

If you use ObjectStorage, you can automatically switch your backend storage from files to 
Amazon S3, GridFS, and many other scalable storage solutions.

ObjectStorage in Radvance is using the [linkorb/objectstorage](https://github.com/linkorb/objectstorage) library.

Please refer to it's [README](https://github.com/linkorb/objectstorage/blob/master/README.md) for further information.

## Configuration

If you don't configure objectstorage for your app, Radvance will by default use the FileAdapter,
and store any object data in `/app/storage`. You will need to add this location to your project's `.gitignore` file.

To configure alternative storage, you can update your `parameters.yml`.

For example:

```yml
---
parameters:
    objectstorage_adapter: s3
    objectstorage_adapter_s3_bucket: s3
    objectstorage_adapter_s3_key: hello
    objectstorage_adapter_s3_secret: abcdefg
```

Or a fixed file location:

```yml
---
parameters:
    objectstorage_adapter: file
    objectstorage_adapter_file_path: /nfs/storage
```

### Compression

You can transparently compress and decompress any data stored and retrieved from objectstorage by adding this:

```yml
---
parameters:
    objectstorage_bzip2_level: 9
```

Valid levels are 1-9

### Encryption

You can transparently encrypt and decrypt any data stored and retrieved from objectstorage by adding this:

```yml
---
parameters:
    objectstorage_encryption_key: 1234567890
    objectstorage_encryption_iv: abcdefg
```

Please refer to the ObjectStorage README for details on creating valid key+iv's
