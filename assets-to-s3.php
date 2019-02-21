<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Uri;
use Grav\Common\Asset;
use RocketTheme\Toolbox\Event\Event;
use Aws\S3\S3Client;

/**
 * Class AssetsToS3Plugin
 * @package Grav\Plugin
 */
class AssetsToS3Plugin extends Plugin
{
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onAdminSave' => ['onAdminSave', 0],
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    public function onPluginsInitialized()
    {
        $uri = $this->grav['uri'];
        $route = $this->config->get('plugins.admin.route');

        if ($route && preg_match('#' . $route . '#', $uri->path())) {
            $this->enable([
                'onPageInitialized' => ['onPageInitialized', 0]
            ]);
        }
    }

    public function onPageInitialized()
    {
        $assets = $this->grav['assets'];
        $assets->addJs('user/plugins/assets-to-s3/assets-to-s3.js', 1);
    }

    public function onAdminSave(Event $event) {
        $page = $event['object'];
        if ($page instanceof \Grav\Common\Page\Page) {
          $config = $this->grav['config']->get('plugins.assets-to-s3');
          $testing = '';
          $s3 = new S3Client(
            array(
              'region' => $config['region'],
              'version' => $config['version'],
              'credentials' => array(
                'key' => $config['key'],
                'secret' => $config['secret']
              )
            )
          );
          $header = (array)$page->value('header');
          $uploadedAssets = array();
          $header = $this->decompile($header, $s3, $uploadedAssets);
          $page->header($header);
        }
    }

    public function decompile($list, $s3, & $uploadedAssets) {
      $config = $this->grav['config']->get('plugins.assets-to-s3');
      $assetsKeys = $config['asset_attributes'];
      $listKeys = $config['list_attributes'];
      $bucket = $config['bucket'];
      $profile = $config['bucket_profile'];
      foreach ($list as $key => $value) {
        if (in_array($key, $assetsKeys)) {
          $list[$key] = $this->changeAsset((array)$list[$key], $s3, $bucket, $profile, $uploadedAssets);
        } else if (in_array($key, $listKeys)) {
          $list[$key] = $this->decompileList((array)$list[$key], $s3, $uploadedAssets);
        }
      }
      return $list;
    }

    public function decompileList($list, $s3, & $uploadedAssets) {
      $newList = array();
      foreach($list as $item) {
        array_push($newList, $this->decompile((array)$item, $s3, $uploadedAssets));
      }
      return $newList;
    }

    public function changeAsset($asset, $s3, $bucket, $profile, & $uploadedAssets) {
      $staticUrl = 'assets/uploads/';
      $newAsset = array();
      foreach($asset as $key => $value) {
        if (strpos($key, $staticUrl) === 0){
          $uploadReturn = $this->uploadToS3($asset[$key]['path'], $s3, $bucket, $profile, $uploadedAssets);
          $newUrl = $uploadReturn['url'];
          //$path = str_replace($staticUrl, $newUrl, $asset[$key]['path']);
          $path = $newUrl.$uploadReturn['key'];
          $asset[$key]['path'] = $path;
          $newAsset[$path] = $asset[$key];
        } else {
          $newAsset = $asset;
        }
      }
      return $newAsset;
    }

    public function uploadToS3($path, $s3, $bucket, $profile, & $uploadedAssets) {
      // Put functionallity to upload asset (path contains path+filename) to S3.
      $config = $this->grav['config']->get('plugins.assets-to-s3');
      $autoGenerate = $config['auto_generate_names'];

      $pos = strrpos($path, '/');
      $key = $pos === false ? $path : substr($path, $pos + 1);

      if ($autoGenerate && $autoGenerate  == 1) {
        $key = microtime(true).'_'.$key;
      }
      if (!in_array($key, $uploadedAssets)) {
        try {
          $result = $s3->putObject(array(
              'Bucket' => $bucket,
              'Key' => $profile.'/'.$key,
              'SourceFile' => $path
          ));
          $s3->waitUntil('ObjectExists', array(
            'Bucket' => $bucket,
            'Key' => $profile.'/'.$key
          ));
          unlink($path);
        } catch (Exception $e) {
          echo 'Error while trying to upload file to S3 and delete the file from assets: ',  $e->getMessage(), "\n";
        }
        array_push($uploadedAssets, $key);
      }
      $returnValue = array(
        'url' => $profile && $profile !== '' ? 'https://'.$bucket.'.s3.amazonaws.com/'.$profile.'/' : 'https://'.$bucket.'.s3.amazonaws.com/',
        'key' => $key
      );
      return $returnValue;
    }
}
