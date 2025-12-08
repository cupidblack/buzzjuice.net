<?php
/**
 * Trait WPCode_Conditional_Meta
 *
 * A trait that provides methods for evaluating meta rules.
 *
 * @package WPCode
 */

/**
 * Trait WPCode_Conditional_Meta
 */
trait WPCode_Conditional_Meta {

	/**
	 * Evaluates a meta rule by comparing the stored meta value with a given value
	 * based on the specified relation operator.
	 *
	 * @param string $option The type of meta to evaluate. Expected values are 'post_meta' or 'user_meta'.
	 * @param string $meta_key The meta key for retrieving the meta value.
	 * @param string $relation The comparison operator. Common operators might include '=', '!=', 'contains', etc.
	 * @param mixed  $value The value to compare against the retrieved meta value.
	 *
	 * @return bool Returns true if the comparison between the retrieved meta value and the given value
	 *              matches the relation operator; false otherwise.
	 */
	protected function evaluate_meta_rule( $option, $meta_key, $relation, $value ) {
		if ( 'post_meta' === $option ) {
			$post_id    = get_the_ID();
			$meta_value = get_post_meta( $post_id, $meta_key, true );
		} elseif ( 'user_meta' === $option ) {
			$user_id    = get_current_user_id();
			$meta_value = get_user_meta( $user_id, $meta_key, true );
		} else {
			return false;
		}

		return $this->get_relation_comparison( $meta_value, $value, $relation );
	}
}
