<?php
/*
Plugin Name: featured_posts
Plugin URI: https://github.com/crunchy/wp.sitePreview
Author: SalesCrunch, Inc.
Version: 0.0.1
Author URI: http://www.salescrunch.com
*/

class FeaturedPostPlugin
{
  public static $FEATURED_POST_CATEGORY      = 'Featured';
  public static $DEFAULT_NUM_POSTS           = 2;
  public static $DEFAULT_FEED_URL            = 'http://blog.salescrunch.com/featured/feed/';
  public static $CATEGORY_PERMALINK_TEMPLATE = 'http://blog.salescrunch.com/%s/';
  public static $HTTP_SUCCESS                = 200;
  public static $MAX_NUM_CATEGORY_LINKS      = 2;

  private static $_timeout    = 10;
  private static $_user_agent = "SalesCrunch Featured Posts Plugin Fetcher";
  
  private $_status = ''; 

  protected $num_posts, $url, $xml, $parsed;

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

  function fetch_xml()
  {
    $this->xml = "";

    $s = curl_init();
    curl_setopt($s,CURLOPT_URL,$this->url);
    curl_setopt($s,CURLOPT_TIMEOUT, FeaturedPostPlugin::$_timeout);
    curl_setopt($s,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($s,CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($s,CURLOPT_USERAGENT, FeaturedPostPlugin::$_user_agent);
    $this->xml = curl_exec($s);
    $this->_status = curl_getinfo($s, CURLINFO_HTTP_CODE);
    if ($this->_status != FeaturedPostPlugin::$HTTP_SUCCESS) 
    {
      $this->xml = "";
    } 
    return $this->xml;
  }

  function fetch() 
  {
    // will hold the structured data parsed out of the RSS feed
    $this->parsed = array();

    // fetch the feed
    $this->fetch_xml();
    if ($this->xml == '')
    {
      return $this->parsed;
    }

    // parse it
    $doc = new DOMDocument();
    $doc->loadXML($this->xml);
    $items = $doc->getElementsByTagName('item');
    if (empty($items)) 
    {
      return array();
    }
    $fetched = 0;
    $data = array();
    foreach($items as $item) 
    {
      $valid = $item->getElementsByTagName('title') && $item->getElementsByTagName('creator') && $item->getElementsByTagName('link') && $item->getElementsByTagName('pubDate');
      if (!$valid) 
      {
        continue;
      }
      $data = array(
        'title'      => $item->getElementsByTagName('title')->item(0)->nodeValue,
        'author'     => $item->getElementsByTagName('creator')->item(0)->nodeValue,
        'permalink'  => $item->getElementsByTagName('link')->item(0)->nodeValue,
        'date'       => date("M j, Y", strtotime($item->getElementsByTagName('pubDate')->item(0)->nodeValue)),
        'categories' => array()
      );
      if ($item->getElementsByTagName('category'))
      { 
        $cat_ct = 0;
        foreach($item->getElementsByTagName('category') as $node) 
        {
          $data['categories'][] = $node->nodeValue;
          if (++$cat_cat >= FeaturedPostPlugin::$MAX_NUM_CATEGORY_LINKS)
          {
            break;
          }
        }
      }
      $this->parsed[] = $data;
      
      if (++$fetched >= $this->num_posts) 
      {
        break;
      }
    }
    return $this->parsed;
  }

  function shortcode($atts, $content = null, $code = "")
  {
    $this->url = isset($atts['url']) ? $atts['url'] : FeaturedPostPlugin::$DEFAULT_FEED_URL;
    $this->num_posts = isset($atts['numposts']) ? $atts['numposts'] : FeaturedPostPlugin::$DEFAULT_NUM_POSTS;
    
    $my_posts = $this->fetch();

    $retval = "";
    $num_posts = count($my_posts);
    
    if ($num_posts == 0)
    {
      $retval = "<p>No featured posts.</p>";
    }
    else
    {
      $divider = $this->render_divider();
      
      $ct = 0;
      foreach($my_posts as $my_post)
      {
        $retval .= $this->render($my_post);
        if (++$ct < $num_posts) 
        {
          $retval .= $divider;
        }
      }
    }
    return $retval;
  }

  function render_divider()
  {
    ob_start();
    require('views/divider.php');
    $retval = ob_get_contents();
    ob_end_clean();

    return $retval;
  }

  function render_category_links($categories) 
  {
    $links = array();
    foreach($categories as $category)
    {
      $links[] = sprintf(
        '<a href="%s">%s</a>', 
        sprintf(FeaturedPostPlugin::$CATEGORY_PERMALINK_TEMPLATE, self::mangle_category_name($category)), 
        $category
      );
   }
   return implode(', ', $links);
  }

  private static function mangle_category_name($cat)
  {
    return str_replace(' ', '-', trim(strtolower($cat)));
  }

  function render($post)
  {
    ob_start();
    extract(array('post' => $post, 'category_links' => $this->render_category_links($post['categories'])));
    require('views/post.php');
    $retval = ob_get_contents();
    ob_end_clean();
    return $retval;
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
