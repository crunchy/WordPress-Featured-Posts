<?php
/*
Plugin Name: featured_posts
Plugin URI: https://github.com/crunchy/wp.sitePreview
Author: SalesCrunch, Inc.
Version: 0.0.1
Author URI: http://www.salescrunch.com
*/
error_reporting(E_ALL);
ini_set('display_errors', true);

class FeaturedPostPlugin
{
  public static $FEATURED_POST_CATEGORY = 'Featured';
  public static $DEFAULT_NUM_POSTS = 2;
  
  public function __construct()
  {
    add_action('init', array(&$this, "register_post_type"));
    add_shortcode('featured_post', array(&$this, "shortcode"));
    add_action('template_redirect', array(&$this, "setup_assets"));
    add_filter('post_thumbnail_html', array(&$this, "fix_ssl"));
  }

  function register_post_type()
  {
    register_post_type('slide',
                       array(
                            'label' => __('Slides'),
                            'singular_label' => __('Slide'),
                            'public' => true,
                            'hierarchical' => false,
                            'rewrite' => array("slug" => "carousel"),
                            'supports' => array('editor', 'title', 'thumbnail', 'page-attributes'),
                            'capability_type' => "page"
                       ));
  }

  function shortcode($atts, $content = null, $code = "")
  {
    global $post, $wp_query;

    $retval = "";

    $my_posts = get_posts(array(
    'numberposts'     => isset($atts['numposts']) ? $atts['numposts'] : FeaturedPostPlugin::$DEFAULT_NUM_POSTS,
    'category'        => isset($atts['category']) ? $atts['category'] : FeaturedPostPlugin::$FEATURED_POST_CATEGORY,
    'orderby'         => 'post_date',
    'order'           => 'DESC',
    'post_type'       => 'post',
    'post_status'     => 'publish')
    );
    
    if (empty($my_posts)) 
    {
      $retval = "<p>No featured posts.</p>";
    }
    else
    {
      foreach($my_posts as $my_post)
      {
        setup_postdata($my_post);
        $retval .= $this->render($my_post);
      }
    }
    
    wp_reset_query();

    return $retval;
  }

  function render($post)
  {
    ob_start();
    extract(array('post' => $post));
    require('views/post.php');
    $retval = ob_get_contents();
    ob_end_clean();

    return $retval;

  }

  public function get_attachments($post, $targetId)
  {
    $args = array(
      'post_type' => 'attachment',
      'numberposts' => 1,
      'post_status' => null,
      'post_parent' => $post->ID
    );

    $attachments = get_posts( $args );

    if($attachments) 
    {
      $imageInfo = wp_get_attachment_image_src($attachments[0]->ID, 'full');
      $attachmentImage = $imageInfo[0];
    } 
    else 
    {
      $attachmentImage = "https://www.google.com/intl/en_com/images/srpr/logo3w.png";
    }

    $attachments = array('postId' => $targetId, 'postImage' => $attachmentImage);

    return $attachments;
  }

  public function setup_assets() 
  {
    $path = get_option('siteurl').'/wp-content/plugins/'. basename(dirname(__FILE__));

    wp_enqueue_style('site_preview', $path . '/css/basic.css');
  }

  public function fix_ssl($html)
  {
    return is_ssl() ? str_replace('http://', 'https://', $html) : $html;
  }
}

new FeaturedPostPlugin();
?>
