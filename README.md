# Upload To S3 Plugin

The **Upload to S3** Plugin is designed for [Grav CMS](http://github.com/getgrav/grav) and automatically uploads assets to S3, change the variables values on markdown pages to point to S3 url and adds new javascript that change thumbnail and view pointers to S3 url.
This uploads a designed assets (defined by attribute names) and deletes the uploaded asset from local server.

## Description
On administration page there are the configurable values.

The simple values to make it works correctly are:
 * **key:** The key of the S3.
 * **secret:** The secret linked to key of the S3.
 * **bucket:** The bucket of the S3.
 * **region:** The region where whe have our S3.
 * **asset_attributes:** The attribute names that are assets to upload to S3 (comma separated).
 * **list_attributes:** The attributes that are lists that can contain attributes that are assets to upload to S3 (comma separated).

## How to install
Download the project and put all files inside /grav-cms-directory-path/user/plugins/assets-to-s3/* .

