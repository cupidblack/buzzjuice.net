<?php


add_action('widgets_init', array('dzsvg_widget', 'register_this_widget'));

if(!class_exists('dzsvg_widget')) {
	class dzsvg_widget extends WP_Widget
	{
		public $name = "Video Gallery";
		public $control_options = array();

		function __construct()
		{
			$wdesc = '';
			if (isset($this->widget_desc)) $wdesc = $this->widget_desc;
			$widget_options = array(
				'classname' => __CLASS__,
				'description' => $wdesc,
			);
			parent::__construct(__CLASS__, $this->name, $widget_options, $this->control_options);
		}

		// -- Static Functions
		static function register_this_widget(){
			register_widget(__CLASS__);
		}

		function form($instance)
		{

			$defaults = array('zs1id' => '1', 'title' => 'Video Gallery');
			$instance = wp_parse_args((array)$instance, $defaults);
			?>
      <p>
        <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
        <input type="text" name="<?php echo $this->get_field_name('title') ?>" id="<?php echo $this->get_field_id('title') ?> " value="<?php echo $instance['title'] ?>" size="20"> </p>
      <label for="<?php echo $this->get_field_id('zs1id'); ?>">Gallery Id:</label>
      <input type="text" name="<?php echo $this->get_field_name('zs1id') ?>" id="<?php echo $this->get_field_id('zs1id') ?> " value="<?php echo $instance['zs1id'] ?>" size="20"> </p>
      <p>
			<?php
		}

		function widget($args, $instance)
		{
			global $dzsvg;
			extract($args);

			$title = $instance['title'];


			echo $before_widget;
			echo $before_title;
			echo $title;
			echo $after_title;


			$arr = array("id" => $instance['zs1id']);
			echo dzsvg_shortcode_videogallery($arr);


			echo $after_widget;
		}

	}
}


add_action( 'widgets_init', function(){
	register_widget( 'DZSVG_Showcase_Widget' );
});




class DZSVG_Showcase_Widget extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$widget_ops = array(
			'classname' => 'dzsvg_showcase_widget',
			'description' => esc_html__('Video Gallery Showcase'),
		);
		parent::__construct( 'dzsvg_showcase_widget', esc_html__('Video Gallery Showcase'), $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		// outputs the content of the widget




		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}
		echo do_shortcode($instance['shortcode']);
		echo $args['after_widget'];
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {



		$margs = array(
			'title'=>'',
			'shortcode'=>'',
		);




		if(is_array($instance)){
			$margs = array_merge($margs, $instance);
		}



		?>
    <p>
    <h5 for="<?php echo $this->get_field_id('title'); ?>"><?php echo esc_html__("Title", DZSVG_ID); ?></h5>
    <input type="text" name="<?php echo $this->get_field_name('title') ?>" id="<?php echo $this->get_field_id('title') ?> " value="<?php echo $margs['title'] ?>" size="20"/> </p>

		<?php
		$lab = 'shortcode';
		?>
    <h5><?php echo esc_html__("Shortcode", DZSVG_ID); ?></h5><input class="shortcode-generator-target" type="text" name="<?php echo $this->get_field_name($lab) ?>" id="<?php echo $this->get_field_id($lab) ?> " value="<?php echo htmlspecialchars($margs[$lab]) ?>" size="20"/>
    <button class="button-secondary btn-shortcode-generator-dzsvg-showcase"><?php echo esc_html__("Edit Showcase", DZSVG_ID); ?></button><br>
		<?php
	}

}
