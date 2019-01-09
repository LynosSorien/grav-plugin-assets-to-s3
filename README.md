# grav-plugin-assets-to-s3
Plugin for Grav CMS to upload contents on assets/upload to Amazon S3, this allow to split heavy contents (such as images and videos) to S3 and normal contents on local server.

This puts automatically all assets to Amazon S3 (configurated via plugin administration config page). Also changes the assets variable names of the markdown page to S3 direction.

This have a javascript that will be executed on administration pages in order to change the url of the thumbnail images (Grav by default search on local server, now it will be changed to go to S3 remote direction).

## How to install
Download and put assets-to-s3 folder inside of user/plugins.

## Configuration
This plugin can be configurated using Grav plugin configuration page.
