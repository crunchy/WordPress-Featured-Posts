<!-- BTN READ MORE -->
<div style=" float:right; height:50px; width:120px;">
  <a class="home-blog-btn" style="clear: both;" href="<?php echo $post['permalink']; ?>">Read More</a>
</div>
<div class="home-boxes-blog-content-featured-bloc">
  <!-- TITLE-->
  <div class="home-boxes-blog-content-featured-title"><a href="<?php echo $post['permalink'] ?>" class="home-boxes-blog-content-featured-title-link"><?php echo $post['title'] ?></a></div>
  <!-- DATE & CATEGORIES-->
  <div class="home-boxes-blog-content-featured-date"><?php echo $post['date']; ?> by <?php echo $post['author']; ?> | <?php echo $category_links; ?></div>
</div>
