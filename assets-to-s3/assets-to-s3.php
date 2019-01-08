<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Uri;
use Grav\Common\Asset;
use RocketTheme\Toolbox\Event\Event;
//use Aws\Common\Aws;
//use Aws\Sdk\Aws;
use Aws\S3\S3Client;

/**
 * Class AutoChildrenPlugin
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
        /** @var Uri $uri */
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
          //$aws = Aws::factory(
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
          $bucket = $config['bucket'];
          $header = (array)$page->value('header');
          //$header = $this->decompile($header, $aws->get('S3'), $bucket);
          $header = $this->decompile($header, $s3, $bucket, $config['bucket_profile']);
          $page->header($header);
        }
    }

    public function decompile($list, $s3, $bucket, $profile) {
      foreach ($list as $key => $value) {
        if ($key === 'image') {
            $list[$key] = $this->changeAsset((array)$list[$key], $s3, $bucket, $profile);
        } else if ($key === 'background') {
            $list[$key] = $this->changeAsset((array)$list[$key], $s3, $bucket, $profile);
        } else if ($key === 'video') {
            $list[$key] = $this->changeAsset((array)$list[$key], $s3, $bucket, $profile);
        } else if ($key === 'asset') {
            $list[$key] = $this->changeAsset((array)$list[$key], $s3, $bucket, $profile);
        } else if ($key === 'image360') {
            $list[$key] = $this->changeAsset((array)$list[$key], $s3, $bucket, $profile);
        } else if ($key === 'asset360') {
            $list[$key] = $this->changeAsset((array)$list[$key], $s3, $bucket, $profile);
        } else if ($key === 'images') {
            $list[$key] = $this->decompileList((array)$list[$key], $s3, $bucket, $profile);
        } else if ($key === 'assets') {
            $list[$key] = $this->decompileList((array)$list[$key], $s3, $bucket, $profile);
        } else if ($key === 'list') {
            $list[$key] = $this->decompileList((array)$list[$key], $s3, $bucket, $profile);
        } else if ($key === 'panels') {
            $list[$key] = $this->decompileList((array)$list[$key], $s3, $bucket, $profile);
        }
      }
      return $list;
    }

    public function decompileList($list, $s3, $bucket, $profile) {
      $newList = array();
      foreach($list as $item) {
        array_push($newList, $this->decompile((array)$item, $s3, $bucket, $profile));
      }
      return $newList;
    }

    public function changeAsset($asset, $s3, $bucket, $profile) {
      $staticUrl = 'assets/uploads/';
      $newAsset = array();
      foreach($asset as $key => $value) {
        if (strpos($key, $staticUrl) === 0){
          $newUrl = $this->uploadToS3($asset[$key]['path'], $s3, $bucket, $profile);
          $path = str_replace($staticUrl, $newUrl, $asset[$key]['path']);
          $asset[$key]['path'] = $path;
          $newAsset[$path] = $asset[$key];
          $newAsset[$path]['replaced'] = 'This asset has been replaced :D';
        } else {
          $newAsset = $asset;
        }
      }
      return $newAsset;
    }

    public function uploadToS3($path, $s3, $bucket, $profile) {
      // Put functionallity to upload asset (path contains path+filename) to S3.
      $pos = strrpos($path, '/');
      $key = $pos === false ? $path : substr($path, $pos + 1);
      $result = $s3->putObject(array(
          'Bucket' => $bucket,
          'Key' => $profile.'/'.$key,
          'SourceFile' => $path
      ));
      $s3->waitUntil('ObjectExists', array(
        'Bucket' => $bucket,
        'Key' => $profile.'/'.$key
      ));
      
      return $profile && $profile !== '' ? 'http://'.$bucket.'.s3.amazonaws.com/'.$profile.'/' : 'http://'.$bucket.'.s3.amazonaws.com/';
    }
}
