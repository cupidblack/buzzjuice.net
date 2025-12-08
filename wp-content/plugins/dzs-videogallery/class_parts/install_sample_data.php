<?php
$taxonomy = DZSVG_POST_NAME__CATEGORY;
$post_type = DZSVG_POST_NAME;

$arr_cats = array();

$args = array('cat_name' => 'Sample 1', 'category_description' => 'A sample collection', 'category_nicename' => 'sample-1', 'taxonomy' => $taxonomy);
$sample_cat_id = wp_insert_category($args);
array_push($arr_cats, $sample_cat_id);

$args = array('cat_name' => 'Sample 2', 'category_description' => 'A sample collection', 'category_nicename' => 'sample-2', 'taxonomy' => $taxonomy);
$sample_cat_id = wp_insert_category($args);
array_push($arr_cats, $sample_cat_id);


// -- start adding posts

$args = array(
  'post_title' => 'Sample Fashion 1',
  'post_content' => 'Sample post.',
  'post_status' => 'publish',
  'post_author' => 1,
  'post_type' => $post_type,
);

$sample_post_id = wp_insert_post($args);
wp_set_post_terms($sample_post_id, $arr_cats[0], $taxonomy);
update_post_meta($sample_post_id, 'dzsvp_featured_media', 'https://techslides.com/demos/sample-videos/small.mp4');
update_post_meta($sample_post_id, 'dzsvp_thumb', 'https://via.placeholder.com/400');

array_push($arr_posts, $sample_post_id);


$args = array(
  'post_title' => 'Sample Fashion 2',
  'post_content' => 'Sample post.',
  'post_status' => 'publish',
  'post_author' => 1,
  'post_type' => $post_type,
);

$sample_post_id = wp_insert_post($args);
wp_set_post_terms($sample_post_id, $arr_cats[0], $taxonomy);
update_post_meta($sample_post_id, 'dzsvp_featured_media', 'http://techslides.com/demos/sample-videos/small.mp4');
update_post_meta($sample_post_id, 'dzsvp_thumb', 'https://via.placeholder.com/400');
array_push($arr_posts, $sample_post_id);


$args = array(
  'post_title' => 'Sample Fashion 3',
  'post_content' => 'Sample post.',
  'post_status' => 'publish',
  'post_author' => 1,
  'post_type' => $post_type,
);

$sample_post_id = wp_insert_post($args);
wp_set_post_terms($sample_post_id, $arr_cats[1], $taxonomy);
update_post_meta($sample_post_id, 'dzsvp_featured_media', 'http://techslides.com/demos/sample-videos/small.mp4');
update_post_meta($sample_post_id, 'dzsvp_thumb', 'https://via.placeholder.com/400');

array_push($arr_posts, $sample_post_id);



echo $arr_cats[0] . ',' . $arr_cats[1];

$demo_data = array('cats' => $arr_cats, 'posts' => $arr_posts);

if (get_option('dzsvg_demo_data') == '') {
  update_option('dzsvg_demo_data', $demo_data);
}